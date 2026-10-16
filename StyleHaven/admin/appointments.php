<?php
/* =====================================================================
   Style Sync Admin — APPOINTMENT MAINTENANCE (appointments.php)
   ---------------------------------------------------------------------
   Content/record maintenance for the booking records. The page performs
   add, edit and delete on the `appointments` table and re-checks the
   double-booking rule on the server, so two clients can never be booked
   with the same stylist at the same date and time.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

require_login();

const APPOINTMENT_STATUSES = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
const OPEN_HOUR            = 9;   /* 9:00 AM  */
const CLOSE_HOUR           = 19;  /* 7:00 PM  */
const SLOT_STEP            = 30;  /* minutes  */

/** The 30-minute time slots offered by the salon. */
function booking_slots(): array
{
    $slots = [];
    for ($hour = OPEN_HOUR; $hour < CLOSE_HOUR; $hour++) {
        for ($minute = 0; $minute < 60; $minute += SLOT_STEP) {
            $slots[] = sprintf('%02d:%02d', $hour, $minute);
        }
    }
    return $slots;
}

$action = query('action', 'list');
$editId = query_int('id');
$errors = [];

$form = [
    'appointment_id' => 0,
    'customer_name'  => '',
    'contact_no'     => '',
    'email'          => '',
    'service_id'     => '',
    'stylist_id'     => '',
    'appt_date'      => date('Y-m-d'),
    'appt_time'      => '',
    'status'         => 'Pending',
    'notes'          => '',
];

/* =====================================================================
   1. DELETE
   ===================================================================== */
if (is_post() && post('do') === 'delete') {
    csrf_verify();

    $target = appointment_find(post_int('appointment_id'));
    if (!$target) {
        flash_set('danger', 'That appointment record no longer exists.');
    } else {
        appointment_delete((int) $target['appointment_id']);
        log_activity('DELETE', 'appointments', (int) $target['appointment_id'], 'Deleted appointment ' . $target['ref_no']);
        flash_set('success', 'Appointment <strong>' . e($target['ref_no']) . '</strong> for '
            . e($target['customer_name']) . ' was deleted.');
    }
    redirect('appointments.php');
}

/* =====================================================================
   2. STATUS CHANGE (quick action on the record list)
   ===================================================================== */
if (is_post() && post('do') === 'status') {
    csrf_verify();

    $target = appointment_find(post_int('appointment_id'));
    $status = (string) post('status');

    if (!$target || !in_array($status, APPOINTMENT_STATUSES, true)) {
        flash_set('danger', 'That status change could not be applied.');
    } elseif ($status !== 'Cancelled'
        && appointment_conflict((int) $target['stylist_id'], (string) $target['appt_date'], (string) $target['appt_time'], (int) $target['appointment_id'])) {
        flash_set('danger', 'That slot now clashes with another booking, so ' . e($target['ref_no']) . ' was left unchanged.');
    } else {
        appointment_update((int) $target['appointment_id'], ['status' => $status]);
        log_activity('UPDATE', 'appointments', (int) $target['appointment_id'], 'Status of ' . $target['ref_no'] . ' set to ' . $status);
        flash_set('success', 'Appointment <strong>' . e($target['ref_no']) . '</strong> is now marked <strong>' . e($status) . '</strong>.');
    }
    redirect('appointments.php');
}

/* =====================================================================
   3. SAVE (add or edit)
   ===================================================================== */
if (is_post() && post('do') === 'save') {
    csrf_verify();

    $form = [
        'appointment_id' => post_int('appointment_id'),
        'customer_name'  => (string) post('customer_name'),
        'contact_no'     => (string) post('contact_no'),
        'email'          => (string) post('email'),
        'service_id'     => (string) post('service_id'),
        'stylist_id'     => (string) post('stylist_id'),
        'appt_date'      => (string) post('appt_date'),
        'appt_time'      => (string) post('appt_time'),
        'status'         => (string) post('status'),
        'notes'          => (string) post('notes'),
    ];
    $isNew = $form['appointment_id'] === 0;

    /* ------------------------------------------------------ validation */
    if ($form['customer_name'] === '') {
        $errors[] = 'Client name is required.';
    } elseif (text_length($form['customer_name']) > 120) {
        $errors[] = 'Client name may not be longer than 120 characters.';
    }
    if ($form['contact_no'] === '') {
        $errors[] = 'Contact number is required.';
    } elseif (text_length($form['contact_no']) > 30) {
        $errors[] = 'Contact number may not be longer than 30 characters.';
    }
    if (!is_email($form['email'])) {
        $errors[] = 'Please enter a valid e-mail address (or leave it blank).';
    }

    $service = ctype_digit($form['service_id']) ? service_find((int) $form['service_id']) : null;
    if (!$service) {
        $errors[] = 'Please choose a service from the file.';
    }
    $stylist = ctype_digit($form['stylist_id']) ? stylist_find((int) $form['stylist_id']) : null;
    if (!$stylist) {
        $errors[] = 'Please choose a stylist from the file.';
    }

    $dateCheck = DateTime::createFromFormat('Y-m-d', $form['appt_date']);
    if (!$dateCheck || $dateCheck->format('Y-m-d') !== $form['appt_date']) {
        $errors[] = 'Please enter a valid appointment date.';
    }
    if (!in_array($form['appt_time'], booking_slots(), true)) {
        $errors[] = 'Please choose a valid time slot (9:00 AM – 6:30 PM, 30-minute intervals).';
    }
    if (!in_array($form['status'], APPOINTMENT_STATUSES, true)) {
        $errors[] = 'Please choose a valid status.';
    }
    if (text_length($form['notes']) > 255) {
        $errors[] = 'Notes may not be longer than 255 characters.';
    }

    /* --------------------------------------- double-booking prevention */
    if (!$errors && $form['status'] !== 'Cancelled'
        && appointment_conflict((int) $form['stylist_id'], $form['appt_date'], $form['appt_time'] . ':00', $form['appointment_id'])) {
        $errors[] = 'Schedule conflict: <strong>' . e($stylist['full_name']) . '</strong> already has an appointment on '
            . e(date_label($form['appt_date'])) . ' at ' . e(time_label($form['appt_time']))
            . '. Please choose another slot or another stylist.';
    }

    /* ----------------------------------------------------------- write */
    if (!$errors) {
        $data = [
            'customer_name' => $form['customer_name'],
            'contact_no'    => $form['contact_no'],
            'email'         => $form['email'] !== '' ? $form['email'] : null,
            'service_id'    => (int) $form['service_id'],
            'stylist_id'    => (int) $form['stylist_id'],
            'appt_date'     => $form['appt_date'],
            'appt_time'     => $form['appt_time'] . ':00',
            'status'        => $form['status'],
            'notes'         => $form['notes'] !== '' ? $form['notes'] : null,
        ];

        if ($isNew) {
            $newId = appointment_create($data);
            $created = appointment_find($newId);
            log_activity('INSERT', 'appointments', $newId, 'Booked appointment ' . ($created['ref_no'] ?? ''));
            flash_set('success', 'Appointment <strong>' . e($created['ref_no'] ?? '') . '</strong> saved for '
                . e($data['customer_name']) . ' on ' . e(date_label($data['appt_date']))
                . ' at ' . e(time_label($data['appt_time'])) . '.');
        } else {
            appointment_update($form['appointment_id'], $data);
            log_activity('UPDATE', 'appointments', $form['appointment_id'], 'Updated appointment record');
            flash_set('success', 'Appointment record updated for <strong>' . e($data['customer_name']) . '</strong>.');
        }
        redirect('appointments.php');
    }

    $action = $isNew ? 'add' : 'edit';
}

/* =====================================================================
   4. EDIT
   ===================================================================== */
if ($action === 'edit' && $editId > 0 && !is_post()) {
    $row = appointment_find($editId);
    if (!$row) {
        flash_set('danger', 'That appointment record could not be found.');
        redirect('appointments.php');
    }
    $form = [
        'appointment_id' => (int) $row['appointment_id'],
        'customer_name'  => $row['customer_name'],
        'contact_no'     => (string) $row['contact_no'],
        'email'          => (string) $row['email'],
        'service_id'     => (string) $row['service_id'],
        'stylist_id'     => (string) $row['stylist_id'],
        'appt_date'      => (string) $row['appt_date'],
        'appt_time'      => substr((string) $row['appt_time'], 0, 5),
        'status'         => $row['status'],
        'notes'          => (string) $row['notes'],
    ];
}

$showForm = in_array($action, ['add', 'edit'], true);

/* =====================================================================
   5. LIST — filters, master-file options and rows
   ===================================================================== */
$fStatus  = (string) query('status');
$fDate    = (string) query('date');
$fStylist = query_int('stylist_id');
$fSearch  = (string) query('search');

$rows           = appointments_list($fStatus, $fDate, $fStylist, $fSearch);
$serviceOptions = services_list();
$stylistOptions = stylists_list();

$page_title  = 'Appointment Maintenance';
$page_kicker = 'Records — bookings managed by the front desk';

include __DIR__ . '/includes/header.php';
?>

<?php if ($showForm): ?>
  <div class="admin-card mb-4">
    <h2 class="admin-card-title">
      <i class="bi bi-<?= $form['appointment_id'] ? 'pencil-square' : 'calendar2-plus' ?>"></i>
      <?= $form['appointment_id'] ? 'Edit appointment record' : 'Add a new appointment record' ?>
    </h2>
    <p class="admin-hint">
      Fields marked <span class="required">*</span> are required. Style Sync re-checks the
      double-booking rule on the server before the record is saved.
    </p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i><strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
          <?php foreach ($errors as $message): ?><li><?= $message ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="appointments.php" class="row g-3 mt-1">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="save">
      <input type="hidden" name="appointment_id" value="<?= (int) $form['appointment_id'] ?>">

      <div class="col-md-4">
        <label class="form-label" for="customer_name">Client name <span class="required">*</span></label>
        <input type="text" class="form-control" id="customer_name" name="customer_name" maxlength="120"
               value="<?= e($form['customer_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="contact_no">Contact number <span class="required">*</span></label>
        <input type="text" class="form-control" id="contact_no" name="contact_no" maxlength="30"
               value="<?= e($form['contact_no']) ?>" placeholder="09XX XXX XXXX" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="email">E-mail address</label>
        <input type="email" class="form-control" id="email" name="email" maxlength="120"
               value="<?= e($form['email']) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="service_id">Service (from the service file) <span class="required">*</span></label>
        <select class="form-select" id="service_id" name="service_id" required>
          <option value="">Select a service</option>
          <?php foreach ($serviceOptions as $service): ?>
            <option value="<?= (int) $service['service_id'] ?>"
              <?= (string) $form['service_id'] === (string) $service['service_id'] ? ' selected' : '' ?>>
              <?= e($service['service_code'] . ' · ' . $service['service_name'] . ' — ' . money($service['price'])) ?>
              <?= $service['status'] === 'Unavailable' ? ' (unavailable)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="stylist_id">Stylist (from the stylist file) <span class="required">*</span></label>
        <select class="form-select" id="stylist_id" name="stylist_id" required>
          <option value="">Select a stylist</option>
          <?php foreach ($stylistOptions as $stylist): ?>
            <option value="<?= (int) $stylist['stylist_id'] ?>"
              <?= (string) $form['stylist_id'] === (string) $stylist['stylist_id'] ? ' selected' : '' ?>>
              <?= e($stylist['stylist_code'] . ' · ' . $stylist['full_name'] . ' — ' . $stylist['specialization']) ?>
              <?= $stylist['status'] !== 'Available' ? ' (' . e($stylist['status']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="appt_date">Date <span class="required">*</span></label>
        <input type="date" class="form-control" id="appt_date" name="appt_date"
               value="<?= e($form['appt_date']) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="appt_time">Time <span class="required">*</span></label>
        <select class="form-select" id="appt_time" name="appt_time" required>
          <option value="">Select a time</option>
          <?php foreach (booking_slots() as $slot): ?>
            <option value="<?= e($slot) ?>"<?= $form['appt_time'] === $slot ? ' selected' : '' ?>><?= e(time_label($slot)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="status">Status <span class="required">*</span></label>
        <select class="form-select" id="status" name="status" required>
          <?php foreach (APPOINTMENT_STATUSES as $status): ?>
            <option value="<?= e($status) ?>"<?= $form['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="notes">Notes for the stylist</label>
        <input type="text" class="form-control" id="notes" name="notes" maxlength="255"
               value="<?= e($form['notes']) ?>">
      </div>

      <div class="col-12 d-flex flex-wrap gap-2 pt-2">
        <button type="submit" class="btn btn-rose">
          <i class="bi bi-save me-2"></i><?= $form['appointment_id'] ? 'Update record' : 'Save record' ?>
        </button>
        <a href="appointments.php" class="btn btn-outline-plum">Cancel</a>
        <?php if ($form['appointment_id']): ?>
          <span class="admin-pill align-self-center">
            Reference no. <strong><?= e((appointment_find((int) $form['appointment_id'])['ref_no'] ?? '')) ?></strong>
          </span>
        <?php endif; ?>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="admin-toolbar">
  <form method="get" action="appointments.php" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="search">Search</label>
      <input type="search" class="form-control" id="search" name="search"
             value="<?= e($fSearch) ?>" placeholder="Reference no., client or contact">
    </div>
    <div class="col-md-2">
      <label class="form-label" for="filterStatus">Status</label>
      <select class="form-select" id="filterStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach (APPOINTMENT_STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= $fStatus === $status ? ' selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label" for="filterDate">Date</label>
      <input type="date" class="form-control" id="filterDate" name="date" value="<?= e($fDate) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label" for="filterStylist">Stylist</label>
      <select class="form-select" id="filterStylist" name="stylist_id">
        <option value="">All stylists</option>
        <?php foreach ($stylistOptions as $stylist): ?>
          <option value="<?= (int) $stylist['stylist_id'] ?>"<?= $fStylist === (int) $stylist['stylist_id'] ? ' selected' : '' ?>>
            <?= e($stylist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-outline-plum w-100"><i class="bi bi-funnel"></i></button>
      <a href="appointments.php" class="btn btn-outline-plum w-100" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <span class="admin-pill"><i class="bi bi-calendar2-week me-1"></i><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?> listed</span>
  <a href="appointments.php?action=add" class="btn btn-rose btn-sm">
    <i class="bi bi-calendar2-plus me-1"></i> Add new appointment
  </a>
</div>

<div class="admin-table-wrap table-responsive">
  <table class="table table-sh align-middle">
    <thead>
      <tr>
        <th>Ref. no.</th>
        <th>Client</th>
        <th>Service</th>
        <th>Stylist</th>
        <th>Schedule</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="7" class="table-empty">
            <i class="bi bi-inbox"></i>
            No appointment records match your filters.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><strong><?= e($row['ref_no']) ?></strong></td>
            <td>
              <?= e($row['customer_name']) ?>
              <br><small class="text-muted-sh"><?= e($row['contact_no'] ?: '—') ?></small>
            </td>
            <td>
              <?= e($row['service_name']) ?>
              <br><small class="text-muted-sh"><?= e(money($row['price'])) ?></small>
            </td>
            <td><small><?= e($row['stylist_name']) ?></small></td>
            <td>
              <?= e(date_label($row['appt_date'])) ?>
              <br><small class="text-muted-sh"><?= e(time_label($row['appt_time'])) ?></small>
            </td>
            <td>
              <span class="badge-soft <?= e(status_badge_class($row['status'])) ?>"><?= e($row['status']) ?></span>
              <form method="post" action="appointments.php" class="mt-1 d-flex gap-1">
                <?= csrf_field() ?>
                <input type="hidden" name="do" value="status">
                <input type="hidden" name="appointment_id" value="<?= (int) $row['appointment_id'] ?>">
                <select class="form-select form-select-sm" name="status" style="min-width:7.5rem;"
                        aria-label="Change status of <?= e($row['ref_no']) ?>">
                  <?php foreach (APPOINTMENT_STATUSES as $status): ?>
                    <option value="<?= e($status) ?>"<?= $row['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline-plum btn-sm" title="Apply status">
                  <i class="bi bi-check2"></i>
                </button>
              </form>
            </td>
            <td class="text-end admin-actions">
              <a href="appointments.php?action=edit&amp;id=<?= (int) $row['appointment_id'] ?>" class="btn btn-outline-plum" title="Edit">
                <i class="bi bi-pencil"></i> Edit
              </a>
              <form method="post" action="appointments.php" class="admin-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="do" value="delete">
                <input type="hidden" name="appointment_id" value="<?= (int) $row['appointment_id'] ?>">
                <button type="submit" class="btn btn-outline-plum" title="Delete"
                        data-confirm="Delete appointment <?= e($row['ref_no']) ?> for <?= e($row['customer_name']) ?>? This cannot be undone.">
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
  <i class="bi bi-shield-check text-rose"></i>
  A <em>Cancelled</em> record releases its slot immediately; every other status keeps the
  stylist busy for that date and time. The same rule is enforced here and on the public
  booking page, so the two views never disagree.
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
