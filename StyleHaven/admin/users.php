<?php
/* =====================================================================
   Style Sync Admin — USER MAINTENANCE (users.php)
   ---------------------------------------------------------------------
   Requirement: User Maintenance / Customer Maintenance.
   Full create, read, update and delete of the accounts kept in the
   `users` table, including the credentials used by the log-in page.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

/* Only administrators may manage accounts. */
require_admin();

const USER_ROLES     = ['Administrator', 'Front Desk', 'Stylist', 'Customer'];
const USER_STATUSES  = ['Active', 'Inactive'];

$action = query('action', 'list');
$editId = query_int('id');
$errors = [];

/* The blank record used by the "Add user" form. */
$form = [
    'user_id'    => 0,
    'username'   => '',
    'full_name'  => '',
    'email'      => '',
    'contact_no' => '',
    'role'       => 'Front Desk',
    'status'     => 'Active',
];

/* =====================================================================
   1. DELETE
   ===================================================================== */
if (is_post() && post('do') === 'delete') {
    csrf_verify();

    $targetId = post_int('user_id');
    $target   = user_find($targetId);

    if (!$target) {
        flash_set('danger', 'That user account no longer exists.');
    } elseif ($targetId === (int) current_user()['user_id']) {
        flash_set('danger', 'You cannot delete the account you are signed in with.');
    } elseif ($target['role'] === 'Administrator' && active_admin_count($targetId) === 0) {
        flash_set('danger', 'At least one active Administrator must remain, so this account was not deleted.');
    } else {
        user_delete($targetId);
        log_activity('DELETE', 'users', $targetId, 'Deleted user ' . $target['username']);
        flash_set('success', 'User account <strong>' . e($target['full_name']) . '</strong> (' . e($target['username']) . ') was deleted.');
    }
    redirect('users.php');
}

/* =====================================================================
   2. SAVE (add or edit)
   ===================================================================== */
if (is_post() && post('do') === 'save') {
    csrf_verify();

    $form = [
        'user_id'    => post_int('user_id'),
        'username'   => (string) post('username'),
        'full_name'  => (string) post('full_name'),
        'email'      => (string) post('email'),
        'contact_no' => (string) post('contact_no'),
        'role'       => (string) post('role'),
        'status'     => (string) post('status'),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $isNew    = $form['user_id'] === 0;

    /* ------------------------------------------------------ validation */
    if ($form['full_name'] === '') {
        $errors[] = 'Full name is required.';
    } elseif (text_length($form['full_name']) > 120) {
        $errors[] = 'Full name may not be longer than 120 characters.';
    }

    if ($form['username'] === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $form['username'])) {
        $errors[] = 'Username must be 3–50 characters and may only contain letters, numbers, dots, hyphens and underscores.';
    } elseif (user_username_taken($form['username'], $form['user_id'])) {
        $errors[] = 'The username <strong>' . e($form['username']) . '</strong> is already taken.';
    }

    if ($form['email'] === '') {
        $errors[] = 'E-mail address is required.';
    } elseif (!is_email($form['email'])) {
        $errors[] = 'Please enter a valid e-mail address.';
    } elseif (user_email_taken($form['email'], $form['user_id'])) {
        $errors[] = 'That e-mail address is already registered.';
    }

    if (!in_array($form['role'], USER_ROLES, true)) {
        $errors[] = 'Please choose a valid role.';
    }
    if (!in_array($form['status'], USER_STATUSES, true)) {
        $errors[] = 'Please choose a valid status.';
    }

    if ($isNew && $password === '') {
        $errors[] = 'A password is required for a new account.';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'The password must be at least 8 characters long.';
    }

    /* Do not allow the last active administrator to be demoted or disabled. */
    if (!$isNew && !$errors) {
        $existing = user_find($form['user_id']);
        $wasAdmin = $existing && $existing['role'] === 'Administrator' && $existing['status'] === 'Active';
        $stillAdmin = $form['role'] === 'Administrator' && $form['status'] === 'Active';
        if ($wasAdmin && !$stillAdmin && active_admin_count($form['user_id']) === 0) {
            $errors[] = 'At least one active Administrator must remain, so this change was refused.';
        }
    }

    /* ----------------------------------------------------------- write */
    if (!$errors) {
        $data = [
            'username'   => $form['username'],
            'full_name'  => $form['full_name'],
            'email'      => $form['email'],
            'contact_no' => $form['contact_no'] !== '' ? $form['contact_no'] : null,
            'role'       => $form['role'],
            'status'     => $form['status'],
        ];

        if ($isNew) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $newId = user_create($data);
            log_activity('INSERT', 'users', $newId, 'Added user ' . $form['username']);
            flash_set('success', 'User account <strong>' . e($form['full_name']) . '</strong> was added. They can now log in with the password you set.');
        } else {
            if ($password !== '') {
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($form['username'] === current_user()['username']) {
                /* Keep the header greeting in step with the edited record. */
                $_SESSION['user']['full_name'] = $form['full_name'];
                $_SESSION['user']['email']     = $form['email'];
            }
            user_update($form['user_id'], $data);
            log_activity('UPDATE', 'users', $form['user_id'], 'Updated user ' . $form['username']);
            flash_set('success', 'User account <strong>' . e($form['full_name']) . '</strong> was updated.');
        }
        redirect('users.php');
    }

    $action = $isNew ? 'add' : 'edit';
}

/* =====================================================================
   3. EDIT — load the chosen record into the form
   ===================================================================== */
if ($action === 'edit' && $editId > 0 && !is_post()) {
    $row = user_find($editId);
    if (!$row) {
        flash_set('danger', 'That user account could not be found.');
        redirect('users.php');
    }
    $form = [
        'user_id'    => (int) $row['user_id'],
        'username'   => $row['username'],
        'full_name'  => $row['full_name'],
        'email'      => $row['email'],
        'contact_no' => (string) $row['contact_no'],
        'role'       => $row['role'],
        'status'     => $row['status'],
    ];
}

$showForm = in_array($action, ['add', 'edit'], true);

/* =====================================================================
   4. LIST — filters and rows
   ===================================================================== */
$fSearch = (string) query('search');
$fRole   = (string) query('role');
$fStatus = (string) query('status');
$rows    = users_list($fSearch, $fRole, $fStatus);

$page_title  = 'User Maintenance';
$page_kicker = 'User Maintenance — manage accounts and access';

include __DIR__ . '/includes/header.php';
?>

<!-- ================================ FORM ================================ -->
<?php if ($showForm): ?>
  <div class="admin-card mb-4">
    <h2 class="admin-card-title">
      <i class="bi bi-<?= $form['user_id'] ? 'pencil-square' : 'person-plus' ?>"></i>
      <?= $form['user_id'] ? 'Edit user account' : 'Add a new user account' ?>
    </h2>
    <p class="admin-hint">
      Fields marked <span class="required">*</span> are required.
      <?= $form['user_id'] ? 'Leave the password blank to keep the current one.' : 'The account can log in immediately after it is saved.' ?>
    </p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i><strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
          <?php foreach ($errors as $message): ?><li><?= $message ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="users.php" class="row g-3 mt-1">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="save">
      <input type="hidden" name="user_id" value="<?= (int) $form['user_id'] ?>">

      <div class="col-md-4">
        <label class="form-label" for="full_name">Full name <span class="required">*</span></label>
        <input type="text" class="form-control" id="full_name" name="full_name" maxlength="120"
               value="<?= e($form['full_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="username">Username <span class="required">*</span></label>
        <input type="text" class="form-control" id="username" name="username" maxlength="50"
               value="<?= e($form['username']) ?>" pattern="[A-Za-z0-9._-]{3,50}" required>
        <div class="admin-hint">Letters, numbers, dots, hyphens and underscores.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="email">E-mail address <span class="required">*</span></label>
        <input type="email" class="form-control" id="email" name="email" maxlength="120"
               value="<?= e($form['email']) ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="contact_no">Contact number</label>
        <input type="text" class="form-control" id="contact_no" name="contact_no" maxlength="30"
               value="<?= e($form['contact_no']) ?>" placeholder="0917 000 0000">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="role">Role <span class="required">*</span></label>
        <select class="form-select" id="role" name="role" required>
          <?php foreach (USER_ROLES as $role): ?>
            <option value="<?= e($role) ?>"<?= $form['role'] === $role ? ' selected' : '' ?>><?= e($role) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="status">Status <span class="required">*</span></label>
        <select class="form-select" id="status" name="status" required>
          <?php foreach (USER_STATUSES as $status): ?>
            <option value="<?= e($status) ?>"<?= $form['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="password">
          Password <?= $form['user_id'] ? '' : '<span class="required">*</span>' ?>
        </label>
        <input type="text" class="form-control" id="password" name="password" minlength="8"
               <?= $form['user_id'] ? '' : 'required' ?> autocomplete="new-password"
               placeholder="<?= $form['user_id'] ? 'Leave blank to keep current' : 'At least 8 characters' ?>">
        <div class="admin-hint">Stored as a bcrypt hash, never as plain text.</div>
      </div>

      <div class="col-12 d-flex flex-wrap gap-2 pt-2">
        <button type="submit" class="btn btn-rose">
          <i class="bi bi-save me-2"></i><?= $form['user_id'] ? 'Update account' : 'Add account' ?>
        </button>
        <a href="users.php" class="btn btn-outline-plum">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<!-- =============================== TOOLBAR ============================== -->
<div class="admin-toolbar">
  <form method="get" action="users.php" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="search">Search</label>
      <input type="search" class="form-control" id="search" name="search"
             value="<?= e($fSearch) ?>" placeholder="Username, full name or e-mail">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterRole">Role</label>
      <select class="form-select" id="filterRole" name="role">
        <option value="">All roles</option>
        <?php foreach (USER_ROLES as $role): ?>
          <option value="<?= e($role) ?>"<?= $fRole === $role ? ' selected' : '' ?>><?= e($role) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterStatus">Status</label>
      <select class="form-select" id="filterStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach (USER_STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= $fStatus === $status ? ' selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-outline-plum w-100"><i class="bi bi-funnel"></i></button>
      <a href="users.php" class="btn btn-outline-plum w-100" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div>

<!-- ================================= TABLE =============================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <span class="admin-pill"><i class="bi bi-people me-1"></i><?= count($rows) ?> account<?= count($rows) === 1 ? '' : 's' ?> listed</span>
  <a href="users.php?action=add" class="btn btn-rose btn-sm">
    <i class="bi bi-person-plus me-1"></i> Add new user
  </a>
</div>

<div class="admin-table-wrap table-responsive">
  <table class="table table-sh align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Full name</th>
        <th>E-mail</th>
        <th>Contact</th>
        <th>Role</th>
        <th>Status</th>
        <th>Last log-in</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="9" class="table-empty">
            <i class="bi bi-person-x"></i>
            No user accounts match your filters.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><small class="text-muted-sh">#<?= (int) $row['user_id'] ?></small></td>
            <td><code><?= e($row['username']) ?></code></td>
            <td>
              <?= e($row['full_name']) ?>
              <?php if ((int) $row['user_id'] === (int) current_user()['user_id']): ?>
                <br><span class="badge-soft badge-done">you</span>
              <?php endif; ?>
            </td>
            <td><small><?= e($row['email']) ?></small></td>
            <td><small><?= e($row['contact_no'] ?: '—') ?></small></td>
            <td><span class="admin-pill"><?= e($row['role']) ?></span></td>
            <td><span class="badge-soft <?= e(status_badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
            <td><small class="text-muted-sh"><?= $row['last_login'] ? e(date('d M Y · g:i A', strtotime((string) $row['last_login']))) : 'never' ?></small></td>
            <td class="text-end admin-actions">
              <a href="users.php?action=edit&amp;id=<?= (int) $row['user_id'] ?>" class="btn btn-outline-plum" title="Edit">
                <i class="bi bi-pencil"></i> Edit
              </a>
              <?php if ((int) $row['user_id'] !== (int) current_user()['user_id']): ?>
                <form method="post" action="users.php" class="admin-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="do" value="delete">
                  <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>">
                  <button type="submit" class="btn btn-outline-plum"
                          data-confirm="Delete the account of <?= e($row['full_name']) ?> (<?= e($row['username']) ?>)? This cannot be undone.">
                    <i class="bi bi-trash3"></i> Delete
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<p class="admin-hint mt-3 mb-0">
  <i class="bi bi-info-circle text-rose"></i>
  Deleting a user account does not touch appointment records; it is recorded in the
  <a href="activity-log.php" class="text-rose-dark">activity log</a> instead.
  An account that is still needed but should not log in can simply be set to <em>Inactive</em>.
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
