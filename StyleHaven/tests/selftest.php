<?php
/* =====================================================================
   Style Sync Admin — developer self-check (tests/selftest.php)
   ---------------------------------------------------------------------
   Run from the project root:      php tests/selftest.php
   Run on Windows/XAMPP:           C:\xampp\php\php.exe tests\selftest.php
   ---------------------------------------------------------------------
   The script
     1. reads database/stylehaven.sql and converts every CREATE TABLE into
        an equivalent SQLite table (columns, NOT NULL, DEFAULT, CHECK for
        ENUM values and the real FOREIGN KEY rules);
     2. loads includes/functions.php and includes/models.php;
     3. runs the same insert / update / delete / conflict logic that the
        admin pages use, and reports pass or fail for each check.
   It is a developer aid only — it touches nothing outside memory, so the
   real MySQL database is never modified.
   ===================================================================== */

$root = dirname(__DIR__);
require_once $root . '/admin/includes/functions.php';
require_once $root . '/admin/includes/models.php';

/* --------------------------------------------------------------- reporter */
final class Check
{
    public static int $passed = 0;
    public static int $failed = 0;

    public static function ok(bool $condition, string $label): void
    {
        if ($condition) {
            self::$passed++;
            echo "  \033[32mPASS\033[0m  {$label}\n";
        } else {
            self::$failed++;
            echo "  \033[31mFAIL\033[0m  {$label}\n";
        }
    }

    public static function same($expected, $actual, string $label): void
    {
        $condition = $expected === $actual;
        self::ok($condition, $label . ($condition ? '' : ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')'));
    }
}

/* =====================================================================
   1. Read the MySQL schema that the project actually ships
   ===================================================================== */
function read_schema(string $file): array
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Cannot read {$file}\n");
        exit(1);
    }

    $tables = [];
    if (!preg_match_all('/CREATE TABLE `(\w+)` \((.*?)\n\) ENGINE=/s', $sql, $blocks, PREG_SET_ORDER)) {
        fwrite(STDERR, "No CREATE TABLE statements found in {$file}\n");
        exit(1);
    }

    foreach ($blocks as $block) {
        $name    = $block[1];
        $body    = $block[2];
        $columns = [];
        $foreign = [];

        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '--')) {
                continue;
            }
            if (preg_match('/^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT)/i', $line)) {
                continue;   /* keys are rebuilt below / not needed in memory */
            }
            if (!preg_match('/^`([^`]+)`\s+(.*)$/', $line, $matches)) {
                continue;
            }

            $column = $matches[1];
            $rules  = $matches[2];

            $definition = match (true) {
                str_contains($rules, 'AUTO_INCREMENT')            => "`{$column}` INTEGER PRIMARY KEY AUTOINCREMENT",
                (bool) preg_match('/^(INT|TINYINT|SMALLINT|BIGINT)/i', $rules) => "`{$column}` INTEGER",
                (bool) preg_match('/^(DECIMAL|FLOAT|DOUBLE)/i', $rules)        => "`{$column}` REAL",
                default                                           => "`{$column}` TEXT",
            };

            if (!str_contains($definition, 'PRIMARY KEY')) {
                if (stripos($rules, 'NOT NULL') !== false) {
                    $definition .= ' NOT NULL';
                }
                if (preg_match('/DEFAULT\s+(CURRENT_TIMESTAMP|\'[^\']*\'|[0-9.]+)/i', $rules, $default)) {
                    $definition .= ' DEFAULT ' . $default[1];
                }
            }

            /* ENUM values become a CHECK constraint in SQLite. */
            if (preg_match('/ENUM\(([^)]*)\)/i', $rules, $enum)) {
                $values = array_map(
                    fn($value) => "'" . trim($value, " '\"") . "'",
                    explode(',', $enum[1])
                );
                $definition .= ' CHECK (`' . $column . '` IN (' . implode(', ', $values) . '))';
            }

            $columns[$column] = $definition;
        }

        /* FOREIGN KEY ... REFERENCES ... (multi-line in the source file) */
        if (preg_match_all(
            '/CONSTRAINT `\w+` FOREIGN KEY \(([^)]+)\)\s*REFERENCES `(\w+)` \(([^)]+)\)\s*([^,;]*)/s',
            $body,
            $constraints,
            PREG_SET_ORDER
        )) {
            foreach ($constraints as $constraint) {
                $action  = '';
                if (preg_match('/ON DELETE (RESTRICT|CASCADE|SET NULL|NO ACTION)/i', $constraint[4], $onDelete)) {
                    $action = ' ON DELETE ' . strtoupper($onDelete[1]);
                }
                $local  = str_replace(['`', ' '], '', $constraint[1]);
                $target = $constraint[2];
                $ref    = str_replace(['`', ' '], '', $constraint[3]);
                $foreign[] = "FOREIGN KEY (`{$local}`) REFERENCES `{$target}` (`{$ref}`){$action}";
            }
        }

        $tables[$name] = ['columns' => $columns, 'foreign' => $foreign];
    }

    return [$tables, $sql];
}

echo "\nStyle Sync — developer self-check\n";
echo "================================\n\n";

[$schema, $schemaSql] = read_schema($root . '/database/stylehaven.sql');

echo "Schema read from database/stylehaven.sql\n";
foreach ($schema as $table => $definition) {
    echo '  - ' . str_pad($table, 14) . count($definition['columns']) . " columns\n";
}
echo "\n";

/* =====================================================================
   2. Mirror the schema into an in-memory SQLite database
   ===================================================================== */
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

foreach ($schema as $table => $definition) {
    $parts = array_merge(array_values($definition['columns']), $definition['foreign']);
    $pdo->exec("CREATE TABLE `{$table}` (\n  " . implode(",\n  ", $parts) . "\n)");
}
db($pdo);

echo "In-memory database built (foreign keys enforced)\n\n";

/* The schema of the MySQL file must cover every column the code uses. */
$expectedTables = ['users', 'services', 'products', 'stylists', 'appointments', 'activity_log'];
foreach ($expectedTables as $table) {
    Check::ok(isset($schema[$table]), "schema contains table `{$table}`");
}
echo "\n";

/* =====================================================================
   3. Seed data — the INSERT column lists must match the real schema
   ===================================================================== */
if (preg_match_all('/INSERT INTO `(\w+)`\s*\((.*?)\)\s*VALUES/s', $schemaSql, $inserts, PREG_SET_ORDER)) {
    foreach ($inserts as $insert) {
        $table   = $insert[1];
        $columns = array_map(fn($c) => trim($c, " `\n\r\t"), explode(',', $insert[2]));
        $unknown = array_diff($columns, array_keys($schema[$table]['columns'] ?? []));
        Check::ok($unknown === [], "seed data for `{$table}` uses only real columns"
            . ($unknown ? ' (unknown: ' . implode(', ', $unknown) . ')' : ''));
    }
} else {
    Check::ok(false, 'seed INSERT statements found in the SQL file');
}
echo "\n";

/* =====================================================================
   4. USER MAINTENANCE — add, edit, delete
   ===================================================================== */
echo "User Maintenance\n----------------\n";

$id = user_create([
    'username'      => 'test.admin',
    'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
    'full_name'     => 'Test Administrator',
    'email'         => 'test.admin@stylehavensalon.ph',
    'contact_no'    => '0917 000 1111',
    'role'          => 'Administrator',
    'status'        => 'Active',
]);
Check::ok($id > 0, 'ADD: a new user account is inserted and gets an id');

$row = user_find($id);
Check::same('test.admin', $row['username'] ?? null, 'READ: the saved account can be read back');
Check::ok(password_verify('secret123', (string) $row['password_hash']), 'the password is stored as a verifiable bcrypt hash');
Check::ok(user_username_taken('test.admin'), 'the duplicate username is detected');
Check::ok(!user_username_taken('test.admin', $id), 'the same username is allowed for the record being edited');

user_update($id, ['full_name' => 'Test Administrator II', 'status' => 'Inactive']);
$row = user_find($id);
Check::same('Test Administrator II', $row['full_name'] ?? null, 'EDIT: the record is updated');
Check::same('Inactive', $row['status'] ?? null, 'EDIT: the status is updated');

Check::same(0, active_admin_count($id), 'an inactive account stops counting as an active administrator');
Check::same(0, active_admin_count(0), 'the guard sees no other active administrator, so this one cannot be removed');

user_delete($id);
Check::ok(user_find($id) === null, 'DELETE: the account is removed');

/* =====================================================================
   5. SERVICE MAINTENANCE + the delete guard
   ===================================================================== */
echo "\nService Maintenance\n-------------------\n";

$haircut = service_create([
    'service_code' => 'SV-TEST',
    'service_name' => 'Test Service',
    'category'     => 'Hair',
    'price'        => '100.00',
    'duration_min' => 30,
    'description'  => 'Added by the self-check.',
    'status'       => 'Available',
]);
Check::ok($haircut > 0, 'ADD: a service is inserted');
Check::same('Test Service', service_find($haircut)['service_name'] ?? null, 'READ: the service can be read back');

service_update($haircut, ['price' => '150.00', 'category' => 'Nails']);
/* Compared numerically: MySQL returns DECIMAL as "150.00", SQLite as a float. */
Check::same(150.0, (float) service_find($haircut)['price'], 'EDIT: the price is updated');
Check::same('Nails', service_find($haircut)['category'] ?? null, 'EDIT: the category is updated');
Check::ok(service_code_taken('SV-TEST'), 'the duplicate service code is detected');

/* A bad ENUM value must be refused — this is what the page validation prevents. */
$enumRefused = false;
try {
    service_create(['service_code' => 'SV-BAD', 'service_name' => 'Bad', 'category' => 'NotACategory',
        'price' => '1.00', 'duration_min' => 30, 'description' => null, 'status' => 'Available']);
} catch (PDOException $e) {
    $enumRefused = true;
}
Check::ok($enumRefused, 'the database refuses a category outside the ENUM list');

/* =====================================================================
   6. STYLIST + PRODUCT MAINTENANCE
   ===================================================================== */
echo "\nProduct and Stylist Maintenance\n-------------------------------\n";

$productId = product_create([
    'product_code' => 'PR-TEST',
    'product_name' => 'Test Product',
    'brand'        => 'Self Check',
    'category'     => 'Tools',
    'price'        => '99.50',
    'stock_qty'    => 5,
    'description'  => null,
    'status'       => 'Active',
]);
Check::ok($productId > 0, 'ADD: a product is inserted');
product_update($productId, ['stock_qty' => 12, 'status' => 'Inactive']);
Check::same(12, (int) product_find($productId)['stock_qty'], 'EDIT: the stock quantity is updated');
Check::ok(product_code_taken('PR-TEST'), 'the duplicate product code is detected');
product_delete($productId);
Check::ok(product_find($productId) === null, 'DELETE: the product is removed');

$stylistId = stylist_create([
    'stylist_code'     => 'ST-TEST',
    'full_name'        => 'Test Stylist',
    'specialization'   => 'Hair',
    'contact_no'       => '0917 000 2222',
    'years_experience' => 3,
    'status'           => 'Available',
]);
Check::ok($stylistId > 0, 'ADD: a stylist is inserted');
stylist_update($stylistId, ['years_experience' => 4, 'status' => 'On Leave']);
Check::same(4, (int) stylist_find($stylistId)['years_experience'], 'EDIT: the experience is updated');
Check::same('On Leave', stylist_find($stylistId)['status'] ?? null, 'EDIT: the status is updated');

/* =====================================================================
   7. APPOINTMENT RECORDS + double-booking rule
   ===================================================================== */
echo "\nAppointment Maintenance\n-----------------------\n";

$appointmentId = appointment_create([
    'customer_name' => 'Self Check Client',
    'contact_no'    => '0917 000 3333',
    'email'         => 'client@example.com',
    'service_id'    => $haircut,
    'stylist_id'    => $stylistId,
    'appt_date'     => '2026-11-02',
    'appt_time'     => '10:00:00',
    'status'        => 'Pending',
    'notes'         => 'Created by the self-check.',
]);
Check::ok($appointmentId > 0, 'ADD: an appointment is inserted');

$appointment = appointment_find($appointmentId);
Check::same('SH-' . str_pad((string) $appointmentId, 4, '0', STR_PAD_LEFT), $appointment['ref_no'] ?? null,
    'the reference number is generated from the record id');

Check::ok(appointment_conflict($stylistId, '2026-11-02', '10:00:00'),
    'DOUBLE BOOKING: the same stylist, date and time is flagged as a conflict');
Check::ok(!appointment_conflict($stylistId, '2026-11-02', '10:00:00', $appointmentId),
    'editing the same record does not conflict with itself');
Check::ok(!appointment_conflict($stylistId, '2026-11-03', '10:00:00'),
    'a different date is free');

appointment_update($appointmentId, ['status' => 'Cancelled']);
Check::ok(!appointment_conflict($stylistId, '2026-11-02', '10:00:00'),
    'a cancelled record releases its slot');

appointment_update($appointmentId, ['status' => 'Confirmed', 'notes' => 'Edited by the self-check.']);
$appointment = appointment_find($appointmentId);
Check::same('Confirmed', $appointment['status'] ?? null, 'EDIT: the status is updated');
Check::same('Edited by the self-check.', $appointment['notes'] ?? null, 'EDIT: the notes are updated');

$joined = appointments_list('', '', $stylistId, 'Self Check');
Check::same(1, count($joined), 'the joined record list finds the appointment by search text');
Check::same('Test Service', $joined[0]['service_name'] ?? null, 'the join brings in the service name');
Check::same('Test Stylist', $joined[0]['stylist_name'] ?? null, 'the join brings in the stylist name');

Check::same(1, service_usage($haircut), 'the service shows one dependent booking');
Check::same(1, stylist_usage($stylistId), 'the stylist shows one dependent booking');

/* The real MySQL foreign keys must block a delete that is still referenced. */
$blocked = false;
try {
    service_delete($haircut);
} catch (PDOException $e) {
    $blocked = true;
}
Check::ok($blocked, 'a service still used by a record cannot be deleted (FOREIGN KEY RESTRICT)');
Check::ok(service_find($haircut) !== null, 'the protected service is still in the file');

/* Dashboard figures */
$counts = appointment_status_counts();
Check::same(1, $counts['Confirmed'], 'the dashboard counts the confirmed appointment');
Check::same(1, $counts['total'], 'the dashboard counts one record in total');
Check::same(150.0, booked_value(), 'the booked value is the service price');

/* =====================================================================
   8. AUDIT TRAIL
   ===================================================================== */
echo "\nAudit trail\n-----------\n";

log_activity('INSERT', 'services', $haircut, 'Self-check entry');
$log = activity_recent(5);
Check::same(1, count($log), 'the action is written to activity_log');
Check::same('services', $log[0]['table_name'] ?? null, 'the log stores the affected table');


/* =====================================================================
   9. DELETE APPOINTMENT, THEN THE FILES IT USED
   ===================================================================== */
echo "\nDeleting with the foreign keys satisfied\n----------------------------------------\n";

appointment_delete($appointmentId);
Check::ok(appointment_find($appointmentId) === null, 'DELETE: the appointment record is removed');
Check::same(0, service_usage($haircut), 'the service no longer has dependent bookings');

$deleted = true;
try {
    service_delete($haircut);
    stylist_delete($stylistId);
} catch (PDOException $e) {
    $deleted = false;
}
Check::ok($deleted && service_find($haircut) === null && stylist_find($stylistId) === null,
    'once no record depends on them, the service and the stylist can be deleted');

/* =====================================================================
   10. HELPERS USED BY THE PAGES
   ===================================================================== */
echo "\nFormatting and input helpers\n----------------------------\n";

Check::same('&#039;&lt;b&gt;Hi&lt;/b&gt;&quot;', e("'<b>Hi</b>\""), 'e() escapes HTML special characters');
Check::same('₱1,500.00', money(1500), 'money() formats a peso amount');
Check::same('2 hrs 30 mins', duration_label(150), 'duration_label() formats minutes');
Check::same('2:30 PM', time_label('14:30:00'), 'time_label() converts to a 12-hour clock');
Check::same('Tue, 03 Nov 2026', date_label('2026-11-03'), 'date_label() formats a date');
Check::same('badge-cancelled', status_badge_class('Inactive'), 'status_badge_class() maps a status to a badge');
Check::same('Front Desk', pick('Front Desk', ['Administrator', 'Front Desk'], 'X'), 'pick() accepts an allowed value');
Check::same('X', pick('Hacker', ['Administrator', 'Front Desk'], 'X'), 'pick() rejects a value outside the list');
Check::ok(is_email('client@example.com') && !is_email('not-an-email'), 'is_email() validates an address');
Check::same(42, query_int('missing', 42), 'query_int() falls back to the default');

/* =====================================================================
   Summary
   ===================================================================== */
$total = Check::$passed + Check::$failed;
echo "\n================================\n";
echo 'Finished: ' . Check::$passed . ' of ' . $total . " checks passed.\n";
if (Check::$failed > 0) {
    echo "\033[31m" . Check::$failed . " check(s) FAILED\033[0m\n\n";
    exit(1);
}
echo "\033[32mAll checks passed.\033[0m\n\n";
exit(0);
