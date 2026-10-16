<?php
/* =====================================================================
   Style Sync Admin — shared helpers (functions.php)
   ---------------------------------------------------------------------
   Output escaping, request helpers, CSRF protection, flash messages,
   number/date formatting and the activity-log writer.
   ===================================================================== */

require_once __DIR__ . '/db.php';

/* ------------------------------------------------------------ escaping */

/** Escape a value for safe use inside HTML output. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ------------------------------------------------------------- request */

/** Read a submitted field, trimmed, with a fallback. */
function post(string $key, $default = '')
{
    if (!isset($_POST[$key])) {
        return $default;
    }
    $value = $_POST[$key];
    return is_string($value) ? trim($value) : $value;
}

/** Read a query-string value, trimmed, with a fallback. */
function query(string $key, $default = '')
{
    if (!isset($_GET[$key])) {
        return $default;
    }
    $value = $_GET[$key];
    return is_string($value) ? trim($value) : $value;
}

/** Read an integer from the query string (0 when missing or invalid). */
function query_int(string $key, int $default = 0): int
{
    $raw = query($key, (string) $default);
    return is_numeric($raw) ? (int) $raw : $default;
}

/** Read an integer from a submitted field. */
function post_int(string $key, int $default = 0): int
{
    $raw = $_POST[$key] ?? $default;
    return is_numeric($raw) ? (int) $raw : $default;
}

/** True when the current request is a POST. */
function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Send a redirect and stop the script. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Value from a fixed list, or the fallback (guards ENUM columns). */
function pick($value, array $allowed, $default = null)
{
    return in_array($value, $allowed, true) ? $value : $default;
}

/* -------------------------------------------------------------- flashes */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Return and clear every queued flash message. */
function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ----------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input that every mutating form must contain. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Stop the request when the submitted token does not match the session. */
function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('<p style="font-family:sans-serif">This form has expired. '
            . 'Please go back, reload the page and submit it again.</p>');
    }
}

/* ------------------------------------------------------------- text size */
/*
 * The mbstring extension is enabled in a standard XAMPP installation, but
 * these two helpers keep the module working (and the character counts
 * correct for Filipino names) on hosts where it is switched off.
 */

/** Count characters, using the multi-byte counter when it is available. */
function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

/** Cut a string to a maximum number of characters. */
function text_cut(string $value, int $limit): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $limit) : substr($value, 0, $limit);
}

/* ----------------------------------------------------------- formatting */

/** Format a peso amount, e.g. 1500 -> ₱1,500.00 */
function money($amount): string
{
    return "\u{20B1}" . number_format((float) $amount, 2);
}

/** Format minutes as "2 hrs 30 mins". */
function duration_label(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $mins  = $minutes % 60;
    $parts = [];
    if ($hours > 0) {
        $parts[] = $hours . ' hr' . ($hours > 1 ? 's' : '');
    }
    if ($mins > 0) {
        $parts[] = $mins . ' min' . ($mins > 1 ? 's' : '');
    }
    return $parts ? implode(' ', $parts) : '0 mins';
}

/** Convert 24-hour "14:30" to "2:30 PM". */
function time_label(?string $time): string
{
    if (!$time) {
        return '—';
    }
    $stamp = strtotime('1970-01-01 ' . $time);
    return $stamp ? date('g:i A', $stamp) : (string) $time;
}

/** Convert "2026-10-20" to "Tue, 20 Oct 2026". */
function date_label(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $stamp = strtotime($date);
    return $stamp ? date('D, d M Y', $stamp) : (string) $date;
}

/** Turn a status word into the badge class used by css/style.css. */
function status_badge_class(string $status): string
{
    switch (strtolower($status)) {
        case 'confirmed':
        case 'available':
        case 'active':
            return 'badge-confirmed';
        case 'pending':
        case 'on leave':
            return 'badge-pending';
        case 'cancelled':
        case 'inactive':
        case 'unavailable':
            return 'badge-cancelled';
        case 'completed':
            return 'badge-done';
        default:
            return 'badge-pending';
    }
}

/* ------------------------------------------------------- audit trail */

/** Append one row to activity_log. Never interrupts the main request. */
function log_activity(string $action, string $table, ?int $recordId = null, string $details = ''): void
{
    try {
        $user = $_SESSION['user'] ?? null;
        db_insert('activity_log', [
            'user_id'    => $user['user_id'] ?? null,
            'username'   => $user['username'] ?? 'system',
            'action'     => $action,
            'table_name' => $table,
            'record_id'  => $recordId,
            'details'    => $details !== '' ? text_cut($details, 255) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        /* The audit trail must never break a save or delete. */
    }
}

/* ---------------------------------------------------------- validation */

/** True when a value looks like a valid e-mail address (blank passes). */
function is_email($value): bool
{
    return $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}
