<?php
/* =====================================================================
   Style Sync Admin — session & authentication (auth.php)
   ---------------------------------------------------------------------
   Starts the session, checks a submitted username/password against the
   users table with password_verify(), and guards every protected page.
   ===================================================================== */

require_once __DIR__ . '/functions.php';

/* ----------------------------------------------------------- session */

function sh_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_name(SH_SESSION_NAME);
    session_start();
}

sh_session_start();

/* ------------------------------------------------------------ log-in */

/**
 * Verify credentials and open a session.
 * Returns null on success, or a short message describing the failure.
 */
function login_attempt(string $username, string $password): ?string
{
    if ($username === '' || $password === '') {
        return 'Please enter both your username and your password.';
    }

    $user = db_one('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);

    /* password_verify() keeps the comparison constant-time. */
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        return 'Invalid username or password. Please try again.';
    }
    if ($user['status'] !== 'Active') {
        return 'This account is inactive. Please contact the administrator.';
    }

    /* Stop a session that was opened before this log-in. */
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'user_id'   => (int) $user['user_id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];
    $_SESSION['last_seen'] = time();

    db_update('users', ['last_login' => date('Y-m-d H:i:s')], 'user_id', (int) $user['user_id']);
    log_activity('LOGIN', 'users', (int) $user['user_id'], 'Signed in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    return null;
}

function logout_user(): void
{
    $user = current_user();
    if ($user) {
        log_activity('LOGOUT', 'users', (int) $user['user_id'], 'Signed out');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

/* --------------------------------------------------------- accessors */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function current_role(): string
{
    return (string) (current_user()['role'] ?? '');
}

function is_admin(): bool
{
    return current_role() === 'Administrator';
}

/* ------------------------------------------------------------ guards */

/** Block the page unless an account is signed in. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('warning', 'Please sign in to open the Style Sync admin module.');
        redirect('login.php');
    }
    /* Idle log-out on a shared front-desk terminal. */
    $last = (int) ($_SESSION['last_seen'] ?? time());
    if (time() - $last > SH_SESSION_IDLE_LIMIT) {
        logout_user();
        sh_session_start();
        flash_set('warning', 'You were signed out after a period of inactivity.');
        redirect('login.php');
    }
    $_SESSION['last_seen'] = time();
}

/** Send a non-administrator back to the dashboard with a notice. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash_set('danger', 'User Maintenance is restricted to Administrator accounts.');
        redirect('index.php');
    }
}
