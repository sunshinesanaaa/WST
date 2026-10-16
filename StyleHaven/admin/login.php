<?php
/* =====================================================================
   Style Sync Admin — WORKING LOG-IN PAGE (login.php)
   ---------------------------------------------------------------------
   User Maintenance requirement: credentials are verified on the server
   against the users table with password_verify(); a PHP session is then
   opened and every admin page checks it before rendering.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';

/* An already signed-in user goes straight to the dashboard. */
if (is_logged_in()) {
    redirect('index.php');
}

$errors   = [];
$username = '';

if (is_post()) {
    csrf_verify();

    $username = (string) post('username');
    $password = (string) ($_POST['password'] ?? '');

    $failure = login_attempt($username, $password);
    if ($failure === null) {
        flash_set('success', 'Welcome back, <strong>' . e(current_user()['full_name']) . '</strong>. You are now signed in to ' . e(SH_SYSTEM) . '.');
        redirect('index.php');
    }

    $errors[] = $failure;
    $password = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Log in to Style Haven Salon — the server-side sign-in for Style Sync, the salon's appointment and record management system.">
  <meta name="author" content="Group 3 — IT 004 Web Systems and Technologies">
  <title>Log In | <?= e(SH_SYSTEM) ?> — <?= e(SH_SALON) ?></title>
  <link rel="icon" type="image/png" href="../assets/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
  <link href="assets/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

  <div class="admin-login-wrap">
    <div class="container">
      <div class="row justify-content-center g-5 align-items-center">

        <!-- ====================== LOG-IN FORM (USER MAINTENANCE) ====================== -->
        <div class="col-lg-5">
          <div class="booking-card admin-login-card">
            <div class="text-center mb-4">
              <img src="../assets/logo-mark.svg" alt="Style Haven emblem" style="height:74px;">
              <h1 class="login-title mt-3 mb-1">Log In</h1>
              <p class="text-muted-sh mb-0" style="font-size:0.9rem;">
                Sign in with your Style Haven account
              </p>
            </div>

            <?php foreach (flash_get() as $flash): ?>
              <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= $flash['message'] ?></div>
            <?php endforeach; ?>

            <?php if ($errors): ?>
              <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php foreach ($errors as $message): ?>
                  <?= e($message) ?><br>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
              <?= csrf_field() ?>

              <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                  <input type="text" class="form-control border-start-0" id="username" name="username"
                         value="<?= e($username) ?>" placeholder="Enter your username"
                         autocomplete="username" required autofocus>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                  <input type="password" class="form-control border-start-0" id="password" name="password"
                         placeholder="Enter your password" autocomplete="current-password" required>
                  <button class="btn btn-outline-plum" type="button" id="togglePass" aria-label="Show or hide password">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <button type="submit" class="btn btn-rose w-100 btn-lg-sh">
                <i class="bi bi-box-arrow-in-right me-2"></i>Log in
              </button>
            </form>

            <div class="mt-4 pt-3 border-top">
              <p class="admin-hint mb-2"><strong>Demo accounts (from the seed data):</strong></p>
              <table class="table table-sm mb-0" style="font-size:0.8rem;">
                <tbody>
                  <tr><td><code>admin</code></td><td><code>stylehaven2026</code></td><td>Administrator</td></tr>
                  <tr><td><code>frontdesk</code></td><td><code>salon123</code></td><td>Front Desk</td></tr>
                  <tr><td><code>ana.stylist</code></td><td><code>stylist123</code></td><td>Stylist</td></tr>
                  <tr><td><code>mariel</code></td><td><code>customer123</code></td><td>Customer</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================ SIDE PANEL ============================ -->
        <div class="col-lg-6">
          <span class="section-kicker">Part 4 — server-side scripting</span>
          <h2 class="section-title">One secure door to the maintenance desk</h2>
          <div class="divider-rose start"></div>
          <p class="lead-sh mt-3">
            This is the working log-in of <strong>Style Sync</strong>. Staff and clients use the same form —
            the account's role decides what opens next. The password is verified on the server and the visitor
            is given a PHP session before any record can be opened.
          </p>
          <ul class="check-list mt-3">
            <li><i class="bi bi-check-circle-fill"></i> Credentials checked against the <code>users</code> table with <code>password_verify()</code></li>
            <li><i class="bi bi-check-circle-fill"></i> Passwords stored as bcrypt hashes — never as plain text</li>
            <li><i class="bi bi-check-circle-fill"></i> Inactive accounts are refused even with the right password</li>
            <li><i class="bi bi-check-circle-fill"></i> Every protected page calls <code>require_login()</code> before it renders</li>
            <li><i class="bi bi-check-circle-fill"></i> Cross-Site Request Forgery token on every form</li>
          </ul>
          <?php if (isset($dbError)): ?>
            <div class="alert alert-danger mt-3"><?= e($dbError) ?></div>
          <?php endif; ?>
          <a href="../index.html" class="btn btn-outline-plum mt-3">
            <i class="bi bi-arrow-left me-1"></i> Back to the website
          </a>
        </div>

      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    /* Show / hide the password so you can check what you typed. */
    (function () {
      var field  = document.getElementById("password");
      var toggle = document.getElementById("togglePass");
      if (!field || !toggle) { return; }
      toggle.addEventListener("click", function () {
        var hidden = field.type === "password";
        field.type = hidden ? "text" : "password";
        toggle.innerHTML = hidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      });
    })();
  </script>
</body>
</html>
