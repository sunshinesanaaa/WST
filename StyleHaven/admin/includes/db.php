<?php
/* =====================================================================
   Style Sync Admin — database connection (db.php)
   ---------------------------------------------------------------------
   One shared PDO connection. Every query in the project is prepared and
   executed with bound parameters, so user input never reaches the SQL
   string directly (protection against SQL injection).
   ===================================================================== */

require_once __DIR__ . '/config.php';

/**
 * Return the shared PDO connection, opening it on first use.
 * Passing a connection replaces the shared one (used by the self-test).
 */
function db(?PDO $connection = null): PDO
{
    static $pdo = null;

    if ($connection instanceof PDO) {
        $pdo = $connection;
        return $pdo;
    }
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
         . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $help = SH_DEBUG
            ? '<br><small>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small>'
            : '';
        exit('<p style="font-family:sans-serif">'
            . 'Style Sync could not connect to the database <strong>'
            . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</strong>. '
            . 'Start MySQL in the XAMPP control panel and import '
            . '<code>database/stylehaven.sql</code> in phpMyAdmin.' . $help . '</p>');
    }

    return $pdo;
}

/** Run a prepared statement and return the statement object. */
function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** Fetch every row of a prepared query. */
function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

/** Fetch the first row of a prepared query, or null. */
function db_one(string $sql, array $params = []): ?array
{
    $row = db_run($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** Fetch a single scalar value. */
function db_value(string $sql, array $params = [], $default = null)
{
    $value = db_run($sql, $params)->fetchColumn();
    return $value === false ? $default : $value;
}

/** Insert a row from an associative array; returns the new id. */
function db_insert(string $table, array $data): int
{
    $columns = array_keys($data);
    $fields  = implode(', ', array_map(fn($c) => '`' . $c . '`', $columns));
    $marks   = implode(', ', array_fill(0, count($columns), '?'));

    db_run("INSERT INTO `{$table}` ({$fields}) VALUES ({$marks})", array_values($data));

    return (int) db()->lastInsertId();
}

/** Update one row by primary key; returns the number of affected rows. */
function db_update(string $table, array $data, string $keyColumn, $keyValue): int
{
    $sets = implode(', ', array_map(fn($c) => '`' . $c . '` = ?', array_keys($data)));
    $args = array_values($data);
    $args[] = $keyValue;

    return db_run("UPDATE `{$table}` SET {$sets} WHERE `{$keyColumn}` = ?", $args)->rowCount();
}

/** Delete one row by primary key; returns the number of affected rows. */
function db_delete(string $table, string $keyColumn, $keyValue): int
{
    return db_run("DELETE FROM `{$table}` WHERE `{$keyColumn}` = ?", [$keyValue])->rowCount();
}
