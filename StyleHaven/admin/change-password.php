<?php
/* =====================================================================
   Style Sync Admin — change own password (change-password.php)
   ---------------------------------------------------------------------
   Part of User Maintenance: any signed-in account can rotate its own
   password. The old password must be supplied and the new one is stored
   as a fresh bcrypt hash.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

require_login();

$page_title  = 'Change Password';
$page_kicker = 'User Maintenance';
$errors      = [];

if (is_post()) {
    csrf_verify();

    $currentId = (int) current_user()['user_id'];
    $row       = user_find($currentId);

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword     = (string) ($_POST['new_password'] ?? '');
    $repeatPassword  = (string) ($_POST['repeat_password'] ?? '');

    if (!$row || !password_verify($currentPassword, (string) $row['password_hash'])) {
        $errors[] = 'Your current password is not correct.';
    }
    if (strlen($newPassword) < 8) {
        $errors[] = 'The new password must be at least 8 characters long.';
    }
    if ($newPassword !== $repeatPassword) {
        $errors[] = 'The new password and its confirmation do not match.';
    }
    if ($newPassword === $currentPassword && $newPassword !== '') {
        $errors[] = 'The new password must be different from the current one.';
    }

    if (!$errors) {
        user_update($currentId, ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);
        log_activity('UPDATE', 'users', $currentId, 'Changed own password');
        flash_set('success', 'Your password was updated successfully. Use it the next time you log in.');
        redirect('change-password.php');
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="admin-card">
      <h2 class="admin-card-title"><i class="bi bi-key"></i> Change your password</h2>
      <p class="admin-hint">
        Stored as a bcrypt hash in <code>users.password_hash</code>.
        Minimum length: 8 characters.
      </p>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <?php foreach ($errors as $message): ?>
            <?= e($message) ?><br>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="change-password.php" class="mt-3">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="current_password">Current password <span class="required">*</span></label>
          <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="mb-3">
          <label class="form-label" for="new_password">New password <span class="required">*</span></label>
          <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required autocomplete="new-password">
          <div class="admin-hint mt-1">At least 8 characters.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="repeat_password">Repeat new password <span class="required">*</span></label>
          <input type="password" class="form-control" id="repeat_password" name="repeat_password" minlength="8" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-rose">
          <i class="bi bi-check2-circle me-2"></i>Update password
        </button>
        <a href="index.php" class="btn btn-outline-plum ms-2">Cancel</a>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="admin-card">
      <h2 class="admin-card-title"><i class="bi bi-shield-check"></i> Signed-in account</h2>
      <table class="table table-sm mb-0">
        <tbody>
          <tr><th style="width:38%">Username</th><td><code><?= e(current_user()['username']) ?></code></td></tr>
          <tr><th>Full name</th><td><?= e(current_user()['full_name']) ?></td></tr>
          <tr><th>E-mail</th><td><?= e(current_user()['email']) ?></td></tr>
          <tr><th>Role</th><td><span class="admin-pill"><?= e(current_user()['role']) ?></span></td></tr>
          <tr><th>Session started</th><td><?= e(date('D, d M Y · g:i A')) ?></td></tr>
        </tbody>
      </table>
      <p class="admin-hint mt-3 mb-0">
        Administrators can reset any other account (and set its role or status) from
        <a href="users.php" class="text-rose-dark">User Maintenance</a>.
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
