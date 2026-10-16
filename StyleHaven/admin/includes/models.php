<?php
/* =====================================================================
   Style Sync Admin — data access layer (models.php)
   ---------------------------------------------------------------------
   Every SQL statement in the project lives here so that the page files
   only deal with validation, HTML and navigation. All statements are
   prepared and parameterised.
   ===================================================================== */

require_once __DIR__ . '/db.php';

/** Build a LIKE pattern for a case-insensitive search. */
function like(string $term): string
{
    return '%' . $term . '%';
}

/* =====================================================================
   USERS  —  User Maintenance
   ===================================================================== */

function users_list(string $search = '', string $role = '', string $status = ''): array
{
    $sql    = 'SELECT * FROM users WHERE 1 = 1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)';
        $needle = like($search);
        array_push($params, $needle, $needle, $needle);
    }
    if ($role !== '') {
        $sql .= ' AND role = ?';
        $params[] = $role;
    }
    if ($status !== '') {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY role ASC, full_name ASC';

    return db_all($sql, $params);
}

function user_find(int $id): ?array
{
    return db_one('SELECT * FROM users WHERE user_id = ?', [$id]);
}

function user_username_taken(string $username, int $excludeId = 0): bool
{
    return (bool) db_value(
        'SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1',
        [$username, $excludeId]
    );
}

function user_email_taken(string $email, int $excludeId = 0): bool
{
    return (bool) db_value(
        'SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1',
        [$email, $excludeId]
    );
}

/** Insert a user. $data must already contain a password_hash. */
function user_create(array $data): int
{
    return db_insert('users', $data);
}

function user_update(int $id, array $data): void
{
    db_update('users', $data, 'user_id', $id);
}

function user_delete(int $id): void
{
    db_delete('users', 'user_id', $id);
}

function user_count(): int
{
    return (int) db_value('SELECT COUNT(*) FROM users', [], 0);
}

/** How many *active* administrators are left (used to protect the last one). */
function active_admin_count(int $excludeId = 0): int
{
    return (int) db_value(
        "SELECT COUNT(*) FROM users WHERE role = 'Administrator' AND status = 'Active' AND user_id <> ?",
        [$excludeId],
        0
    );
}

/* =====================================================================
   SERVICES  —  File Maintenance
   ===================================================================== */

function services_list(string $search = '', string $category = '', string $status = ''): array
{
    $sql    = 'SELECT * FROM services WHERE 1 = 1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (service_code LIKE ? OR service_name LIKE ? OR description LIKE ?)';
        $needle = like($search);
        array_push($params, $needle, $needle, $needle);
    }
    if ($category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    if ($status !== '') {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY category ASC, service_name ASC';

    return db_all($sql, $params);
}

function service_find(int $id): ?array
{
    return db_one('SELECT * FROM services WHERE service_id = ?', [$id]);
}

function service_code_taken(string $code, int $excludeId = 0): bool
{
    return (bool) db_value(
        'SELECT service_id FROM services WHERE service_code = ? AND service_id <> ? LIMIT 1',
        [$code, $excludeId]
    );
}

function service_create(array $data): int
{
    return db_insert('services', $data);
}

function service_update(int $id, array $data): void
{
    db_update('services', $data, 'service_id', $id);
}

function service_delete(int $id): void
{
    db_delete('services', 'service_id', $id);
}

/** Number of appointments that still point at a service (blocks delete). */
function service_usage(int $id): int
{
    return (int) db_value('SELECT COUNT(*) FROM appointments WHERE service_id = ?', [$id], 0);
}

/* =====================================================================
   PRODUCTS  —  File Maintenance
   ===================================================================== */

function products_list(string $search = '', string $category = '', string $status = ''): array
{
    $sql    = 'SELECT * FROM products WHERE 1 = 1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (product_code LIKE ? OR product_name LIKE ? OR brand LIKE ?)';
        $needle = like($search);
        array_push($params, $needle, $needle, $needle);
    }
    if ($category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    if ($status !== '') {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY category ASC, product_name ASC';

    return db_all($sql, $params);
}

function product_find(int $id): ?array
{
    return db_one('SELECT * FROM products WHERE product_id = ?', [$id]);
}

function product_code_taken(string $code, int $excludeId = 0): bool
{
    return (bool) db_value(
        'SELECT product_id FROM products WHERE product_code = ? AND product_id <> ? LIMIT 1',
        [$code, $excludeId]
    );
}

function product_create(array $data): int
{
    return db_insert('products', $data);
}

function product_update(int $id, array $data): void
{
    db_update('products', $data, 'product_id', $id);
}

function product_delete(int $id): void
{
    db_delete('products', 'product_id', $id);
}

/* =====================================================================
   STYLISTS  —  File Maintenance
   ===================================================================== */

function stylists_list(string $search = '', string $specialization = '', string $status = ''): array
{
    $sql    = 'SELECT * FROM stylists WHERE 1 = 1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (stylist_code LIKE ? OR full_name LIKE ? OR contact_no LIKE ?)';
        $needle = like($search);
        array_push($params, $needle, $needle, $needle);
    }
    if ($specialization !== '') {
        $sql .= ' AND specialization = ?';
        $params[] = $specialization;
    }
    if ($status !== '') {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY full_name ASC';

    return db_all($sql, $params);
}

function stylist_find(int $id): ?array
{
    return db_one('SELECT * FROM stylists WHERE stylist_id = ?', [$id]);
}

function stylist_code_taken(string $code, int $excludeId = 0): bool
{
    return (bool) db_value(
        'SELECT stylist_id FROM stylists WHERE stylist_code = ? AND stylist_id <> ? LIMIT 1',
        [$code, $excludeId]
    );
}

function stylist_create(array $data): int
{
    return db_insert('stylists', $data);
}

function stylist_update(int $id, array $data): void
{
    db_update('stylists', $data, 'stylist_id', $id);
}

function stylist_delete(int $id): void
{
    db_delete('stylists', 'stylist_id', $id);
}

function stylist_usage(int $id): int
{
    return (int) db_value('SELECT COUNT(*) FROM appointments WHERE stylist_id = ?', [$id], 0);
}

/* =====================================================================
   APPOINTMENTS  —  records managed by the front desk
   ===================================================================== */

/**
 * Appointment rows joined to their service and stylist.
 * Filters: status, date, stylist_id, search (client, contact or ref no.).
 */
function appointments_list(string $status = '', string $date = '', int $stylistId = 0, string $search = ''): array
{
    $sql = 'SELECT a.*, s.service_name, s.price, s.service_code, t.full_name AS stylist_name
              FROM appointments a
              JOIN services s ON s.service_id = a.service_id
              JOIN stylists t ON t.stylist_id = a.stylist_id
             WHERE 1 = 1';
    $params = [];

    if ($status !== '') {
        $sql .= ' AND a.status = ?';
        $params[] = $status;
    }
    if ($date !== '') {
        $sql .= ' AND a.appt_date = ?';
        $params[] = $date;
    }
    if ($stylistId > 0) {
        $sql .= ' AND a.stylist_id = ?';
        $params[] = $stylistId;
    }
    if ($search !== '') {
        $sql .= ' AND (a.ref_no LIKE ? OR a.customer_name LIKE ? OR a.contact_no LIKE ?)';
        $needle = like($search);
        array_push($params, $needle, $needle, $needle);
    }
    $sql .= ' ORDER BY a.appt_date DESC, a.appt_time DESC';

    return db_all($sql, $params);
}

function appointment_find(int $id): ?array
{
    return db_one('SELECT * FROM appointments WHERE appointment_id = ?', [$id]);
}

/** True when the stylist already holds an appointment in that slot. */
function appointment_conflict(int $stylistId, string $date, string $time, int $excludeId = 0): bool
{
    return (bool) db_value(
        "SELECT appointment_id FROM appointments
          WHERE stylist_id = ? AND appt_date = ? AND appt_time = ?
            AND status <> 'Cancelled' AND appointment_id <> ? LIMIT 1",
        [$stylistId, $date, $time, $excludeId]
    );
}

/** Insert an appointment, then stamp its reference number (SH-0001). */
function appointment_create(array $data): int
{
    /* A temporary unique value satisfies the NOT NULL / UNIQUE column. */
    $data['ref_no'] = 'TMP' . bin2hex(random_bytes(4));
    $id = db_insert('appointments', $data);

    db_update('appointments', ['ref_no' => 'SH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)], 'appointment_id', $id);

    return $id;
}

function appointment_update(int $id, array $data): void
{
    db_update('appointments', $data, 'appointment_id', $id);
}

function appointment_delete(int $id): void
{
    db_delete('appointments', 'appointment_id', $id);
}

/** Counts for the dashboard tiles. */
function appointment_status_counts(): array
{
    $counts = ['Pending' => 0, 'Confirmed' => 0, 'Completed' => 0, 'Cancelled' => 0, 'total' => 0];
    foreach (db_all('SELECT status, COUNT(*) AS total FROM appointments GROUP BY status') as $row) {
        $counts[$row['status']] = (int) $row['total'];
        $counts['total']        += (int) $row['total'];
    }
    return $counts;
}

/** Booked value: the price of every appointment that is not cancelled. */
function booked_value(): float
{
    $value = db_value(
        "SELECT SUM(s.price) FROM appointments a
           JOIN services s ON s.service_id = a.service_id
          WHERE a.status <> 'Cancelled'",
        [],
        0
    );
    return (float) $value;
}

/** Most recent appointments for the dashboard. */
function appointments_recent(int $limit = 5): array
{
    $limit = max(1, (int) $limit);
    return db_all(
        "SELECT a.*, s.service_name, t.full_name AS stylist_name
           FROM appointments a
           JOIN services s ON s.service_id = a.service_id
           JOIN stylists t ON t.stylist_id = a.stylist_id
          ORDER BY a.created_at DESC, a.appointment_id DESC
          LIMIT {$limit}"
    );
}

/* =====================================================================
   ACTIVITY LOG  —  audit trail
   ===================================================================== */

function activity_recent(int $limit = 20): array
{
    $limit = max(1, (int) $limit);
    return db_all("SELECT * FROM activity_log ORDER BY log_id DESC LIMIT {$limit}");
}
