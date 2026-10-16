<?php
/* =====================================================================
   Style Sync Admin — SERVICE MAINTENANCE (services.php)
   ---------------------------------------------------------------------
   Requirement: File Maintenance. Create, read, update and delete the
   salon's service menu (the `services` master file). A service that is
   still referenced by an appointment cannot be deleted, which protects
   the records that depend on it.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

require_login();

const SERVICE_CATEGORIES = ['Hair', 'Nails', 'Skin', 'Makeup', 'Packages'];
const SERVICE_STATUSES   = ['Available', 'Unavailable'];

$action = query('action', 'list');
$editId = query_int('id');
$errors = [];

$form = [
    'service_id'   => 0,
    'service_code' => '',
    'service_name' => '',
    'category'     => 'Hair',
    'price'        => '',
    'duration_min' => '60',
    'description'  => '',
    'status'       => 'Available',
];

/* =====================================================================
   1. DELETE
   ===================================================================== */
if (is_post() && post('do') === 'delete') {
    csrf_verify();

    $target = service_find(post_int('service_id'));
    if (!$target) {
        flash_set('danger', 'That service no longer exists.');
    } elseif (($inUse = service_usage((int) $target['service_id'])) > 0) {
        flash_set('danger', 'Service <strong>' . e($target['service_name']) . '</strong> is used by '
            . $inUse . ' appointment record(s) and cannot be deleted. Mark it <em>Unavailable</em> instead.');
    } else {
        service_delete((int) $target['service_id']);
        log_activity('DELETE', 'services', (int) $target['service_id'], 'Deleted service ' . $target['service_code']);
        flash_set('success', 'Service <strong>' . e($target['service_name']) . '</strong> was deleted.');
    }
    redirect('services.php');
}

/* =====================================================================
   2. SAVE (add or edit)
   ===================================================================== */
if (is_post() && post('do') === 'save') {
    csrf_verify();

    $form = [
        'service_id'   => post_int('service_id'),
        'service_code' => (string) post('service_code'),
        'service_name' => (string) post('service_name'),
        'category'     => (string) post('category'),
        'price'        => (string) post('price'),
        'duration_min' => (string) post('duration_min'),
        'description'  => (string) post('description'),
        'status'       => (string) post('status'),
    ];
    $isNew = $form['service_id'] === 0;

    /* ------------------------------------------------------ validation */
    if ($form['service_code'] === '') {
        $errors[] = 'Service code is required (for example SV-HC01).';
    } elseif (!preg_match('/^[A-Za-z0-9-]{3,20}$/', $form['service_code'])) {
        $errors[] = 'Service code may only contain letters, numbers and hyphens (3–20 characters).';
    } elseif (service_code_taken($form['service_code'], $form['service_id'])) {
        $errors[] = 'Service code <strong>' . e($form['service_code']) . '</strong> is already used by another service.';
    }

    if ($form['service_name'] === '') {
        $errors[] = 'Service name is required.';
    } elseif (text_length($form['service_name']) > 120) {
        $errors[] = 'Service name may not be longer than 120 characters.';
    }

    if (!in_array($form['category'], SERVICE_CATEGORIES, true)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!in_array($form['status'], SERVICE_STATUSES, true)) {
        $errors[] = 'Please choose a valid status.';
    }
    if (!is_numeric($form['price']) || (float) $form['price'] < 0) {
        $errors[] = 'Price must be a number of 0 or more.';
    }
    if (!ctype_digit($form['duration_min']) || (int) $form['duration_min'] < 5 || (int) $form['duration_min'] > 600) {
        $errors[] = 'Duration must be a whole number of minutes between 5 and 600.';
    }
    if (text_length($form['description']) > 255) {
        $errors[] = 'Description may not be longer than 255 characters.';
    }

    /* ----------------------------------------------------------- write */
    if (!$errors) {
        $data = [
            'service_code' => strtoupper($form['service_code']),
            'service_name' => $form['service_name'],
            'category'     => $form['category'],
            'price'        => number_format((float) $form['price'], 2, '.', ''),
            'duration_min' => (int) $form['duration_min'],
            'description'  => $form['description'] !== '' ? $form['description'] : null,
            'status'       => $form['status'],
        ];

        if ($isNew) {
            $newId = service_create($data);
            log_activity('INSERT', 'services', $newId, 'Added service ' . $data['service_code']);
            flash_set('success', 'Service <strong>' . e($data['service_name']) . '</strong> was added to the menu.');
        } else {
            service_update($form['service_id'], $data);
            log_activity('UPDATE', 'services', $form['service_id'], 'Updated service ' . $data['service_code']);
            flash_set('success', 'Service <strong>' . e($data['service_name']) . '</strong> was updated.');
        }
        redirect('services.php');
    }

    $action = $isNew ? 'add' : 'edit';
}

/* =====================================================================
   3. EDIT
   ===================================================================== */
if ($action === 'edit' && $editId > 0 && !is_post()) {
    $row = service_find($editId);
    if (!$row) {
        flash_set('danger', 'That service could not be found.');
        redirect('services.php');
    }
    $form = [
        'service_id'   => (int) $row['service_id'],
        'service_code' => $row['service_code'],
        'service_name' => $row['service_name'],
        'category'     => $row['category'],
        'price'        => $row['price'],
        'duration_min' => (string) $row['duration_min'],
        'description'  => (string) $row['description'],
        'status'       => $row['status'],
    ];
}

$showForm = in_array($action, ['add', 'edit'], true);

/* =====================================================================
   4. LIST
   ===================================================================== */
$fSearch   = (string) query('search');
$fCategory = (string) query('category');
$fStatus   = (string) query('status');
$rows      = services_list($fSearch, $fCategory, $fStatus);

$page_title  = 'Service Maintenance';
$page_kicker = 'File Maintenance — the salon service menu';

include __DIR__ . '/includes/header.php';
?>

<?php if ($showForm): ?>
  <div class="admin-card mb-4">
    <h2 class="admin-card-title">
      <i class="bi bi-<?= $form['service_id'] ? 'pencil-square' : 'plus-circle' ?>"></i>
      <?= $form['service_id'] ? 'Edit service' : 'Add a new service' ?>
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

    <form method="post" action="services.php" class="row g-3 mt-1">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="save">
      <input type="hidden" name="service_id" value="<?= (int) $form['service_id'] ?>">

      <div class="col-md-3">
        <label class="form-label" for="service_code">Service code <span class="required">*</span></label>
        <input type="text" class="form-control text-uppercase" id="service_code" name="service_code" maxlength="20"
               value="<?= e($form['service_code']) ?>" placeholder="SV-HC01" required>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="service_name">Service name <span class="required">*</span></label>
        <input type="text" class="form-control" id="service_name" name="service_name" maxlength="120"
               value="<?= e($form['service_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="category">Category <span class="required">*</span></label>
        <select class="form-select" id="category" name="category" required>
          <?php foreach (SERVICE_CATEGORIES as $category): ?>
            <option value="<?= e($category) ?>"<?= $form['category'] === $category ? ' selected' : '' ?>><?= e($category) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="price">Price (₱) <span class="required">*</span></label>
        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01"
               value="<?= e($form['price']) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="duration_min">Duration (minutes) <span class="required">*</span></label>
        <input type="number" class="form-control" id="duration_min" name="duration_min" min="5" max="600" step="5"
               value="<?= e($form['duration_min']) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="status">Status <span class="required">*</span></label>
        <select class="form-select" id="status" name="status" required>
          <?php foreach (SERVICE_STATUSES as $status): ?>
            <option value="<?= e($status) ?>"<?= $form['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="description">Short description</label>
        <input type="text" class="form-control" id="description" name="description" maxlength="255"
               value="<?= e($form['description']) ?>">
        <div class="admin-hint" data-counter-for="description"></div>
      </div>

      <div class="col-12 d-flex flex-wrap gap-2 pt-2">
        <button type="submit" class="btn btn-rose">
          <i class="bi bi-save me-2"></i><?= $form['service_id'] ? 'Update service' : 'Add service' ?>
        </button>
        <a href="services.php" class="btn btn-outline-plum">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="admin-toolbar">
  <form method="get" action="services.php" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="search">Search</label>
      <input type="search" class="form-control" id="search" name="search"
             value="<?= e($fSearch) ?>" placeholder="Code, name or description">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterCategory">Category</label>
      <select class="form-select" id="filterCategory" name="category">
        <option value="">All categories</option>
        <?php foreach (SERVICE_CATEGORIES as $category): ?>
          <option value="<?= e($category) ?>"<?= $fCategory === $category ? ' selected' : '' ?>><?= e($category) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterStatus">Status</label>
      <select class="form-select" id="filterStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach (SERVICE_STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= $fStatus === $status ? ' selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-outline-plum w-100"><i class="bi bi-funnel"></i></button>
      <a href="services.php" class="btn btn-outline-plum w-100" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <span class="admin-pill"><i class="bi bi-scissors me-1"></i><?= count($rows) ?> service<?= count($rows) === 1 ? '' : 's' ?> listed</span>
  <a href="services.php?action=add" class="btn btn-rose btn-sm">
    <i class="bi bi-plus-lg me-1"></i> Add new service
  </a>
</div>

<div class="admin-table-wrap table-responsive">
  <table class="table table-sh align-middle">
    <thead>
      <tr>
        <th>Code</th>
        <th>Service</th>
        <th>Category</th>
        <th>Price</th>
        <th>Duration</th>
        <th>Used by</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="8" class="table-empty">
            <i class="bi bi-inbox"></i>
            No services match your filters.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <?php $usage = service_usage((int) $row['service_id']); ?>
          <tr>
            <td><code><?= e($row['service_code']) ?></code></td>
            <td>
              <strong><?= e($row['service_name']) ?></strong>
              <?php if ($row['description']): ?>
                <br><small class="text-muted-sh"><?= e($row['description']) ?></small>
              <?php endif; ?>
            </td>
            <td><span class="admin-pill"><?= e($row['category']) ?></span></td>
            <td><?= e(money($row['price'])) ?></td>
            <td><small><?= e(duration_label((int) $row['duration_min'])) ?></small></td>
            <td><small><?= $usage ?> booking<?= $usage === 1 ? '' : 's' ?></small></td>
            <td><span class="badge-soft <?= e(status_badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
            <td class="text-end admin-actions">
              <a href="services.php?action=edit&amp;id=<?= (int) $row['service_id'] ?>" class="btn btn-outline-plum" title="Edit">
                <i class="bi bi-pencil"></i> Edit
              </a>
              <form method="post" action="services.php" class="admin-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="do" value="delete">
                <input type="hidden" name="service_id" value="<?= (int) $row['service_id'] ?>">
                <button type="submit" class="btn btn-outline-plum" title="Delete"
                        data-confirm="Delete the service &quot;<?= e($row['service_name']) ?>&quot;? This cannot be undone.">
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
  A service that is used by at least one appointment is protected by the
  <code>fk_appt_service</code> foreign key, so it can only be set to <em>Unavailable</em> —
  never deleted while records still depend on it.
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
