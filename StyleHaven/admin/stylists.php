<?php
/* =====================================================================
   Style Sync Admin — STYLIST MAINTENANCE (stylists.php)
   ---------------------------------------------------------------------
   Requirement: File Maintenance (stylist / staff file). Create, read,
   update and delete the specialists that appointments are assigned to.
   Like services, a stylist who is still scheduled cannot be deleted.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

require_login();

const STYLIST_SPECIALIZATIONS = ['Hair', 'Nails', 'Skin', 'Makeup', 'Barber & Grooming', 'All-Around'];
const STYLIST_STATUSES        = ['Available', 'On Leave', 'Inactive'];

$action = query('action', 'list');
$editId = query_int('id');
$errors = [];

$form = [
    'stylist_id'       => 0,
    'stylist_code'     => '',
    'full_name'        => '',
    'specialization'   => 'Hair',
    'contact_no'       => '',
    'years_experience' => '0',
    'status'           => 'Available',
];

/* =====================================================================
   1. DELETE
   ===================================================================== */
if (is_post() && post('do') === 'delete') {
    csrf_verify();

    $target = stylist_find(post_int('stylist_id'));
    if (!$target) {
        flash_set('danger', 'That stylist no longer exists.');
    } elseif (($scheduled = stylist_usage((int) $target['stylist_id'])) > 0) {
        flash_set('danger', 'Stylist <strong>' . e($target['full_name']) . '</strong> still has '
            . $scheduled . ' appointment record(s) and cannot be deleted. Set the status to <em>Inactive</em> instead.');
    } else {
        stylist_delete((int) $target['stylist_id']);
        log_activity('DELETE', 'stylists', (int) $target['stylist_id'], 'Deleted stylist ' . $target['stylist_code']);
        flash_set('success', 'Stylist <strong>' . e($target['full_name']) . '</strong> was deleted.');
    }
    redirect('stylists.php');
}

/* =====================================================================
   2. SAVE (add or edit)
   ===================================================================== */
if (is_post() && post('do') === 'save') {
    csrf_verify();

    $form = [
        'stylist_id'       => post_int('stylist_id'),
        'stylist_code'     => (string) post('stylist_code'),
        'full_name'        => (string) post('full_name'),
        'specialization'   => (string) post('specialization'),
        'contact_no'       => (string) post('contact_no'),
        'years_experience' => (string) post('years_experience'),
        'status'           => (string) post('status'),
    ];
    $isNew = $form['stylist_id'] === 0;

    /* ------------------------------------------------------ validation */
    if ($form['stylist_code'] === '') {
        $errors[] = 'Stylist code is required (for example ST-006).';
    } elseif (!preg_match('/^[A-Za-z0-9-]{3,20}$/', $form['stylist_code'])) {
        $errors[] = 'Stylist code may only contain letters, numbers and hyphens (3–20 characters).';
    } elseif (stylist_code_taken($form['stylist_code'], $form['stylist_id'])) {
        $errors[] = 'Stylist code <strong>' . e($form['stylist_code']) . '</strong> is already used by another stylist.';
    }

    if ($form['full_name'] === '') {
        $errors[] = 'Full name is required.';
    } elseif (text_length($form['full_name']) > 120) {
        $errors[] = 'Full name may not be longer than 120 characters.';
    }
    if (!in_array($form['specialization'], STYLIST_SPECIALIZATIONS, true)) {
        $errors[] = 'Please choose a valid specialisation.';
    }
    if (!in_array($form['status'], STYLIST_STATUSES, true)) {
        $errors[] = 'Please choose a valid status.';
    }
    if (text_length($form['contact_no']) > 30) {
        $errors[] = 'Contact number may not be longer than 30 characters.';
    }
    if (!ctype_digit($form['years_experience']) || (int) $form['years_experience'] > 60) {
        $errors[] = 'Years of experience must be a whole number between 0 and 60.';
    }

    /* ----------------------------------------------------------- write */
    if (!$errors) {
        $data = [
            'stylist_code'     => strtoupper($form['stylist_code']),
            'full_name'        => $form['full_name'],
            'specialization'   => $form['specialization'],
            'contact_no'       => $form['contact_no'] !== '' ? $form['contact_no'] : null,
            'years_experience' => (int) $form['years_experience'],
            'status'           => $form['status'],
        ];

        if ($isNew) {
            $newId = stylist_create($data);
            log_activity('INSERT', 'stylists', $newId, 'Added stylist ' . $data['stylist_code']);
            flash_set('success', 'Stylist <strong>' . e($data['full_name']) . '</strong> was added to the staff file.');
        } else {
            stylist_update($form['stylist_id'], $data);
            log_activity('UPDATE', 'stylists', $form['stylist_id'], 'Updated stylist ' . $data['stylist_code']);
            flash_set('success', 'Stylist <strong>' . e($data['full_name']) . '</strong> was updated.');
        }
        redirect('stylists.php');
    }

    $action = $isNew ? 'add' : 'edit';
}

/* =====================================================================
   3. EDIT
   ===================================================================== */
if ($action === 'edit' && $editId > 0 && !is_post()) {
    $row = stylist_find($editId);
    if (!$row) {
        flash_set('danger', 'That stylist could not be found.');
        redirect('stylists.php');
    }
    $form = [
        'stylist_id'       => (int) $row['stylist_id'],
        'stylist_code'     => $row['stylist_code'],
        'full_name'        => $row['full_name'],
        'specialization'   => $row['specialization'],
        'contact_no'       => (string) $row['contact_no'],
        'years_experience' => (string) $row['years_experience'],
        'status'           => $row['status'],
    ];
}

$showForm = in_array($action, ['add', 'edit'], true);

/* =====================================================================
   4. LIST
   ===================================================================== */
$fSearch         = (string) query('search');
$fSpecialization = (string) query('specialization');
$fStatus         = (string) query('status');
$rows            = stylists_list($fSearch, $fSpecialization, $fStatus);

$page_title  = 'Stylist Maintenance';
$page_kicker = 'File Maintenance — stylist and staff file';

include __DIR__ . '/includes/header.php';
?>

<?php if ($showForm): ?>
  <div class="admin-card mb-4">
    <h2 class="admin-card-title">
      <i class="bi bi-<?= $form['stylist_id'] ? 'pencil-square' : 'person-plus' ?>"></i>
      <?= $form['stylist_id'] ? 'Edit stylist' : 'Add a new stylist' ?>
    </h2>
    <p class="admin-hint">Fields marked <span class="required">*</span> are required.</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i><strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
          <?php foreach ($errors as $message): ?><li><?= $message ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="stylists.php" class="row g-3 mt-1">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="save">
      <input type="hidden" name="stylist_id" value="<?= (int) $form['stylist_id'] ?>">

      <div class="col-md-3">
        <label class="form-label" for="stylist_code">Stylist code <span class="required">*</span></label>
        <input type="text" class="form-control text-uppercase" id="stylist_code" name="stylist_code" maxlength="20"
               value="<?= e($form['stylist_code']) ?>" placeholder="ST-006" required>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="full_name">Full name <span class="required">*</span></label>
        <input type="text" class="form-control" id="full_name" name="full_name" maxlength="120"
               value="<?= e($form['full_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="specialization">Specialisation <span class="required">*</span></label>
        <select class="form-select" id="specialization" name="specialization" required>
          <?php foreach (STYLIST_SPECIALIZATIONS as $item): ?>
            <option value="<?= e($item) ?>"<?= $form['specialization'] === $item ? ' selected' : '' ?>><?= e($item) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="contact_no">Contact number</label>
        <input type="text" class="form-control" id="contact_no" name="contact_no" maxlength="30"
               value="<?= e($form['contact_no']) ?>" placeholder="0917 000 0000">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="years_experience">Years of experience <span class="required">*</span></label>
        <input type="number" class="form-control" id="years_experience" name="years_experience" min="0" max="60"
               value="<?= e($form['years_experience']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="status">Status <span class="required">*</span></label>
        <select class="form-select" id="status" name="status" required>
          <?php foreach (STYLIST_STATUSES as $status): ?>
            <option value="<?= e($status) ?>"<?= $form['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 d-flex flex-wrap gap-2 pt-2">
        <button type="submit" class="btn btn-rose">
          <i class="bi bi-save me-2"></i><?= $form['stylist_id'] ? 'Update stylist' : 'Add stylist' ?>
        </button>
        <a href="stylists.php" class="btn btn-outline-plum">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="admin-toolbar">
  <form method="get" action="stylists.php" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="search">Search</label>
      <input type="search" class="form-control" id="search" name="search"
             value="<?= e($fSearch) ?>" placeholder="Code, name or contact number">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterSpec">Specialisation</label>
      <select class="form-select" id="filterSpec" name="specialization">
        <option value="">All specialisations</option>
        <?php foreach (STYLIST_SPECIALIZATIONS as $item): ?>
          <option value="<?= e($item) ?>"<?= $fSpecialization === $item ? ' selected' : '' ?>><?= e($item) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterStatus">Status</label>
      <select class="form-select" id="filterStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach (STYLIST_STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= $fStatus === $status ? ' selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-outline-plum w-100"><i class="bi bi-funnel"></i></button>
      <a href="stylists.php" class="btn btn-outline-plum w-100" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <span class="admin-pill"><i class="bi bi-people me-1"></i><?= count($rows) ?> stylist<?= count($rows) === 1 ? '' : 's' ?> listed</span>
  <a href="stylists.php?action=add" class="btn btn-rose btn-sm">
    <i class="bi bi-person-plus me-1"></i> Add new stylist
  </a>
</div>

<div class="admin-table-wrap table-responsive">
  <table class="table table-sh align-middle">
    <thead>
      <tr>
        <th>Code</th>
        <th>Stylist</th>
        <th>Specialisation</th>
        <th>Contact</th>
        <th>Experience</th>
        <th>Scheduled</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="8" class="table-empty">
            <i class="bi bi-inbox"></i>
            No stylists match your filters.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <?php $scheduled = stylist_usage((int) $row['stylist_id']); ?>
          <tr>
            <td><code><?= e($row['stylist_code']) ?></code></td>
            <td><strong><?= e($row['full_name']) ?></strong></td>
            <td><span class="admin-pill"><?= e($row['specialization']) ?></span></td>
            <td><small><?= e($row['contact_no'] ?: '—') ?></small></td>
            <td><small><?= (int) $row['years_experience'] ?> yr<?= (int) $row['years_experience'] === 1 ? '' : 's' ?></small></td>
            <td><small><?= $scheduled ?> booking<?= $scheduled === 1 ? '' : 's' ?></small></td>
            <td><span class="badge-soft <?= e(status_badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
            <td class="text-end admin-actions">
              <a href="stylists.php?action=edit&amp;id=<?= (int) $row['stylist_id'] ?>" class="btn btn-outline-plum" title="Edit">
                <i class="bi bi-pencil"></i> Edit
              </a>
              <form method="post" action="stylists.php" class="admin-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="do" value="delete">
                <input type="hidden" name="stylist_id" value="<?= (int) $row['stylist_id'] ?>">
                <button type="submit" class="btn btn-outline-plum" title="Delete"
                        data-confirm="Delete the stylist &quot;<?= e($row['full_name']) ?>&quot;? This cannot be undone.">
                  <i class="bi bi-trash3"></i> Delete
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<p class="admin-hint mt-3 mb-0">
  <i class="bi bi-info-circle text-rose"></i>
  Deleting a stylist is blocked while appointments still point at them
  (<code>fk_appt_stylist</code>). Use <em>On Leave</em> or <em>Inactive</em> to keep the history intact.
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
