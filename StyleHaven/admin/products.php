<?php
/* =====================================================================
   Style Sync Admin — PRODUCT MAINTENANCE (products.php)
   ---------------------------------------------------------------------
   Requirement: File Maintenance (second master file). Create, read,
   update and delete the retail products sold at the front desk — the
   `products` table behind the "Retail Products" section of
   services.html.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

require_login();

const PRODUCT_CATEGORIES = ['Hair Care', 'Nail Care', 'Skin Care', 'Makeup', 'Tools'];
const PRODUCT_STATUSES   = ['Active', 'Inactive'];

$action = query('action', 'list');
$editId = query_int('id');
$errors = [];

$form = [
    'product_id'   => 0,
    'product_code' => '',
    'product_name' => '',
    'brand'        => '',
    'category'     => 'Hair Care',
    'price'        => '',
    'stock_qty'    => '0',
    'description'  => '',
    'status'       => 'Active',
];

/* =====================================================================
   1. DELETE
   ===================================================================== */
if (is_post() && post('do') === 'delete') {
    csrf_verify();

    $target = product_find(post_int('product_id'));
    if (!$target) {
        flash_set('danger', 'That product no longer exists.');
    } else {
        product_delete((int) $target['product_id']);
        log_activity('DELETE', 'products', (int) $target['product_id'], 'Deleted product ' . $target['product_code']);
        flash_set('success', 'Product <strong>' . e($target['product_name']) . '</strong> was deleted.');
    }
    redirect('products.php');
}

/* =====================================================================
   2. SAVE (add or edit)
   ===================================================================== */
if (is_post() && post('do') === 'save') {
    csrf_verify();

    $form = [
        'product_id'   => post_int('product_id'),
        'product_code' => (string) post('product_code'),
        'product_name' => (string) post('product_name'),
        'brand'        => (string) post('brand'),
        'category'     => (string) post('category'),
        'price'        => (string) post('price'),
        'stock_qty'    => (string) post('stock_qty'),
        'description'  => (string) post('description'),
        'status'       => (string) post('status'),
    ];
    $isNew = $form['product_id'] === 0;

    /* ------------------------------------------------------ validation */
    if ($form['product_code'] === '') {
        $errors[] = 'Product code is required (for example PR-011).';
    } elseif (!preg_match('/^[A-Za-z0-9-]{3,20}$/', $form['product_code'])) {
        $errors[] = 'Product code may only contain letters, numbers and hyphens (3–20 characters).';
    } elseif (product_code_taken($form['product_code'], $form['product_id'])) {
        $errors[] = 'Product code <strong>' . e($form['product_code']) . '</strong> is already used by another product.';
    }

    if ($form['product_name'] === '') {
        $errors[] = 'Product name is required.';
    } elseif (text_length($form['product_name']) > 120) {
        $errors[] = 'Product name may not be longer than 120 characters.';
    }
    if (text_length($form['brand']) > 80) {
        $errors[] = 'Brand may not be longer than 80 characters.';
    }
    if (!in_array($form['category'], PRODUCT_CATEGORIES, true)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!in_array($form['status'], PRODUCT_STATUSES, true)) {
        $errors[] = 'Please choose a valid status.';
    }
    if (!is_numeric($form['price']) || (float) $form['price'] < 0) {
        $errors[] = 'Price must be a number of 0 or more.';
    }
    if (!ctype_digit($form['stock_qty']) || (int) $form['stock_qty'] < 0) {
        $errors[] = 'Stock quantity must be a whole number of 0 or more.';
    }
    if (text_length($form['description']) > 255) {
        $errors[] = 'Description may not be longer than 255 characters.';
    }

    /* ----------------------------------------------------------- write */
    if (!$errors) {
        $data = [
            'product_code' => strtoupper($form['product_code']),
            'product_name' => $form['product_name'],
            'brand'        => $form['brand'] !== '' ? $form['brand'] : null,
            'category'     => $form['category'],
            'price'        => number_format((float) $form['price'], 2, '.', ''),
            'stock_qty'    => (int) $form['stock_qty'],
            'description'  => $form['description'] !== '' ? $form['description'] : null,
            'status'       => $form['status'],
        ];

        if ($isNew) {
            $newId = product_create($data);
            log_activity('INSERT', 'products', $newId, 'Added product ' . $data['product_code']);
            flash_set('success', 'Product <strong>' . e($data['product_name']) . '</strong> was added.');
        } else {
            product_update($form['product_id'], $data);
            log_activity('UPDATE', 'products', $form['product_id'], 'Updated product ' . $data['product_code']);
            flash_set('success', 'Product <strong>' . e($data['product_name']) . '</strong> was updated.');
        }
        redirect('products.php');
    }

    $action = $isNew ? 'add' : 'edit';
}

/* =====================================================================
   3. EDIT
   ===================================================================== */
if ($action === 'edit' && $editId > 0 && !is_post()) {
    $row = product_find($editId);
    if (!$row) {
        flash_set('danger', 'That product could not be found.');
        redirect('products.php');
    }
    $form = [
        'product_id'   => (int) $row['product_id'],
        'product_code' => $row['product_code'],
        'product_name' => $row['product_name'],
        'brand'        => (string) $row['brand'],
        'category'     => $row['category'],
        'price'        => $row['price'],
        'stock_qty'    => (string) $row['stock_qty'],
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
$rows      = products_list($fSearch, $fCategory, $fStatus);

$page_title  = 'Product Maintenance';
$page_kicker = 'File Maintenance — retail product line';

include __DIR__ . '/includes/header.php';
?>

<?php if ($showForm): ?>
  <div class="admin-card mb-4">
    <h2 class="admin-card-title">
      <i class="bi bi-<?= $form['product_id'] ? 'pencil-square' : 'plus-circle' ?>"></i>
      <?= $form['product_id'] ? 'Edit product' : 'Add a new product' ?>
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

    <form method="post" action="products.php" class="row g-3 mt-1">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="save">
      <input type="hidden" name="product_id" value="<?= (int) $form['product_id'] ?>">

      <div class="col-md-3">
        <label class="form-label" for="product_code">Product code <span class="required">*</span></label>
        <input type="text" class="form-control text-uppercase" id="product_code" name="product_code" maxlength="20"
               value="<?= e($form['product_code']) ?>" placeholder="PR-011" required>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="product_name">Product name <span class="required">*</span></label>
        <input type="text" class="form-control" id="product_name" name="product_name" maxlength="120"
               value="<?= e($form['product_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="brand">Brand</label>
        <input type="text" class="form-control" id="brand" name="brand" maxlength="80"
               value="<?= e($form['brand']) ?>" placeholder="Style Haven Pro">
      </div>

      <div class="col-md-3">
        <label class="form-label" for="category">Category <span class="required">*</span></label>
        <select class="form-select" id="category" name="category" required>
          <?php foreach (PRODUCT_CATEGORIES as $category): ?>
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
        <label class="form-label" for="stock_qty">Stock on hand <span class="required">*</span></label>
        <input type="number" class="form-control" id="stock_qty" name="stock_qty" min="0" step="1"
               value="<?= e($form['stock_qty']) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="status">Status <span class="required">*</span></label>
        <select class="form-select" id="status" name="status" required>
          <?php foreach (PRODUCT_STATUSES as $status): ?>
            <option value="<?= e($status) ?>"<?= $form['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label" for="description">Short description</label>
        <input type="text" class="form-control" id="description" name="description" maxlength="255"
               value="<?= e($form['description']) ?>">
        <div class="admin-hint" data-counter-for="description"></div>
      </div>

      <div class="col-12 d-flex flex-wrap gap-2 pt-2">
        <button type="submit" class="btn btn-rose">
          <i class="bi bi-save me-2"></i><?= $form['product_id'] ? 'Update product' : 'Add product' ?>
        </button>
        <a href="products.php" class="btn btn-outline-plum">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="admin-toolbar">
  <form method="get" action="products.php" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="search">Search</label>
      <input type="search" class="form-control" id="search" name="search"
             value="<?= e($fSearch) ?>" placeholder="Code, product name or brand">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterCategory">Category</label>
      <select class="form-select" id="filterCategory" name="category">
        <option value="">All categories</option>
        <?php foreach (PRODUCT_CATEGORIES as $category): ?>
          <option value="<?= e($category) ?>"<?= $fCategory === $category ? ' selected' : '' ?>><?= e($category) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filterStatus">Status</label>
      <select class="form-select" id="filterStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach (PRODUCT_STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= $fStatus === $status ? ' selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-outline-plum w-100"><i class="bi bi-funnel"></i></button>
      <a href="products.php" class="btn btn-outline-plum w-100" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <span class="admin-pill"><i class="bi bi-bag-heart me-1"></i><?= count($rows) ?> product<?= count($rows) === 1 ? '' : 's' ?> listed</span>
  <a href="products.php?action=add" class="btn btn-rose btn-sm">
    <i class="bi bi-plus-lg me-1"></i> Add new product
  </a>
</div>

<div class="admin-table-wrap table-responsive">
  <table class="table table-sh align-middle">
    <thead>
      <tr>
        <th>Code</th>
        <th>Product</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="7" class="table-empty">
            <i class="bi bi-inbox"></i>
            No products match your filters.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><code><?= e($row['product_code']) ?></code></td>
            <td>
              <strong><?= e($row['product_name']) ?></strong>
              <?php if ($row['brand']): ?><br><small class="text-muted-sh"><?= e($row['brand']) ?></small><?php endif; ?>
            </td>
            <td><span class="admin-pill"><?= e($row['category']) ?></span></td>
            <td><?= e(money($row['price'])) ?></td>
            <td>
              <?php if ((int) $row['stock_qty'] === 0): ?>
                <span class="badge-soft badge-cancelled">out of stock</span>
              <?php elseif ((int) $row['stock_qty'] <= 10): ?>
                <span class="badge-soft badge-pending"><?= (int) $row['stock_qty'] ?> left</span>
              <?php else: ?>
                <span class="badge-soft badge-confirmed"><?= (int) $row['stock_qty'] ?> pcs</span>
              <?php endif; ?>
            </td>
            <td><span class="badge-soft <?= e(status_badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
            <td class="text-end admin-actions">
              <a href="products.php?action=edit&amp;id=<?= (int) $row['product_id'] ?>" class="btn btn-outline-plum" title="Edit">
                <i class="bi bi-pencil"></i> Edit
              </a>
              <form method="post" action="products.php" class="admin-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="do" value="delete">
                <input type="hidden" name="product_id" value="<?= (int) $row['product_id'] ?>">
                <button type="submit" class="btn btn-outline-plum" title="Delete"
                        data-confirm="Delete the product &quot;<?= e($row['product_name']) ?>&quot;? This cannot be undone.">
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
  Stock levels are maintained here; a product at zero is shown as <em>out of stock</em>
  but stays in the file until it is deleted or set to <em>Inactive</em>.
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
