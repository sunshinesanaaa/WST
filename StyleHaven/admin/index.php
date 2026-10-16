<?php
/* =====================================================================
   Style Sync Admin — DASHBOARD (index.php)
   ---------------------------------------------------------------------
   Overview of every file the module maintains, plus the newest
   appointments and the newest audit-trail entries.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

require_login();

$user  = current_user();
$stats = appointment_status_counts();

$counts = [
    'users'    => user_count(),
    'services' => count(services_list()),
    'products' => count(products_list()),
    'stylists' => count(stylists_list()),
];

$page_title  = 'Dashboard';
$page_kicker = 'Welcome, ' . $user['full_name'];

include __DIR__ . '/includes/header.php';
?>

<div class="row g-3">
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-calendar2-check"></i></div>
      <div class="stat-label">Appointments</div>
      <div class="stat-value"><?= (int) $stats['total'] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-hourglass-split"></i></div>
      <div class="stat-label">Pending</div>
      <div class="stat-value"><?= (int) $stats['Pending'] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-check2-circle"></i></div>
      <div class="stat-label">Confirmed</div>
      <div class="stat-value"><?= (int) $stats['Confirmed'] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-flag"></i></div>
      <div class="stat-label">Completed</div>
      <div class="stat-value"><?= (int) $stats['Completed'] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-x-octagon"></i></div>
      <div class="stat-label">Cancelled</div>
      <div class="stat-value"><?= (int) $stats['Cancelled'] ?></div>
    </div>
  </div>
  <div class="col-12 col-lg-4 col-xl-2">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-cash-coin"></i></div>
      <div class="stat-label">Booked value</div>
      <div class="stat-value" style="font-size:1.25rem;"><?= e(money(booked_value())) ?></div>
    </div>
  </div>
</div>

<!-- ======================= MASTER FILES AT A GLANCE ======================= -->
<div class="row g-3 mt-1">
  <div class="col-6 col-lg-3">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-person-badge"></i></div>
      <div class="stat-label">User accounts</div>
      <div class="stat-value"><?= (int) $counts['users'] ?></div>
      <a href="users.php" class="admin-hint text-rose-dark">User Maintenance <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-scissors"></i></div>
      <div class="stat-label">Services</div>
      <div class="stat-value"><?= (int) $counts['services'] ?></div>
      <a href="services.php" class="admin-hint text-rose-dark">Service Maintenance <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-bag-heart"></i></div>
      <div class="stat-label">Retail products</div>
      <div class="stat-value"><?= (int) $counts['products'] ?></div>
      <a href="products.php" class="admin-hint text-rose-dark">Product Maintenance <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-tile">
      <div class="stat-ico"><i class="bi bi-people"></i></div>
      <div class="stat-label">Stylists</div>
      <div class="stat-value"><?= (int) $counts['stylists'] ?></div>
      <a href="stylists.php" class="admin-hint text-rose-dark">Stylist Maintenance <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</div>

<!-- ===================== RECENT APPOINTMENTS + AUDIT ====================== -->
<div class="row g-4 mt-1">
  <div class="col-xl-7">
    <div class="admin-card">
      <h2 class="admin-card-title"><i class="bi bi-calendar2-week"></i> Newest appointment records</h2>
      <p class="admin-hint mb-3">The five most recently encoded bookings.</p>

      <div class="table-responsive">
        <table class="table table-sh align-middle mb-0">
          <thead>
            <tr>
              <th>Ref. no.</th>
              <th>Client</th>
              <th>Service</th>
              <th>Schedule</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php $recent = appointments_recent(5); ?>
            <?php if (!$recent): ?>
              <tr><td colspan="5" class="table-empty"><i class="bi bi-inbox"></i>No appointment records yet.</td></tr>
            <?php else: ?>
              <?php foreach ($recent as $row): ?>
                <tr>
                  <td><strong><?= e($row['ref_no']) ?></strong></td>
                  <td><?= e($row['customer_name']) ?><br><small class="text-muted-sh"><?= e($row['contact_no']) ?></small></td>
                  <td><?= e($row['service_name']) ?><br><small class="text-muted-sh"><?= e($row['stylist_name']) ?></small></td>
                  <td><?= e(date_label($row['appt_date'])) ?><br><small class="text-muted-sh"><?= e(time_label($row['appt_time'])) ?></small></td>
                  <td><span class="badge-soft <?= e(status_badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <a href="appointments.php" class="btn btn-outline-plum btn-sm mt-3">
        Open Appointment Maintenance <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="admin-card">
      <h2 class="admin-card-title"><i class="bi bi-clock-history"></i> Latest activity</h2>
      <p class="admin-hint mb-3">Every add, edit and delete is recorded in <code>activity_log</code>.</p>

      <div class="table-responsive">
        <table class="table table-sh align-middle mb-0">
          <thead>
            <tr><th>When</th><th>User</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php $log = activity_recent(6); ?>
            <?php if (!$log): ?>
              <tr><td colspan="3" class="table-empty"><i class="bi bi-clock"></i>No activity recorded yet.</td></tr>
            <?php else: ?>
              <?php foreach ($log as $entry): ?>
                <tr>
                  <td><small><?= e(date('d M · g:i A', strtotime((string) $entry['created_at']))) ?></small></td>
                  <td><small><?= e($entry['username']) ?></small></td>
                  <td>
                    <span class="badge-soft <?= $entry['action'] === 'DELETE' ? 'badge-cancelled' : ($entry['action'] === 'INSERT' ? 'badge-confirmed' : 'badge-done') ?>">
                      <?= e($entry['action']) ?>
                    </span>
                    <br><small class="text-muted-sh"><?= e($entry['table_name']) ?><?= $entry['record_id'] ? ' #' . (int) $entry['record_id'] : '' ?></small>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <a href="activity-log.php" class="btn btn-outline-plum btn-sm mt-3">
        View the full audit trail <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>
</div>

<!-- ============================ MAINTENANCE MAP =========================== -->
<div class="admin-card mt-4">
  <h2 class="admin-card-title"><i class="bi bi-diagram-3"></i> Maintenance map</h2>
  <div class="table-responsive">
    <table class="table table-sh align-middle mb-0">
      <thead>
        <tr><th>Requirement</th><th>Page</th><th>Table</th><th>Functions applied</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>Working log-in (User Maintenance)</td>
          <td><a href="login.php" class="text-rose-dark">login.php</a></td>
          <td><code>users</code></td>
          <td>Verify credentials, open session, guard every page</td>
        </tr>
        <tr>
          <td>User Maintenance</td>
          <td><a href="users.php" class="text-rose-dark">users.php</a></td>
          <td><code>users</code></td>
          <td><span class="badge-soft badge-confirmed">Add</span> <span class="badge-soft badge-pending">Edit</span> <span class="badge-soft badge-cancelled">Delete</span></td>
        </tr>
        <tr>
          <td>File Maintenance — services</td>
          <td><a href="services.php" class="text-rose-dark">services.php</a></td>
          <td><code>services</code></td>
          <td><span class="badge-soft badge-confirmed">Add</span> <span class="badge-soft badge-pending">Edit</span> <span class="badge-soft badge-cancelled">Delete</span></td>
        </tr>
        <tr>
          <td>File Maintenance — retail products</td>
          <td><a href="products.php" class="text-rose-dark">products.php</a></td>
          <td><code>products</code></td>
          <td><span class="badge-soft badge-confirmed">Add</span> <span class="badge-soft badge-pending">Edit</span> <span class="badge-soft badge-cancelled">Delete</span></td>
        </tr>
        <tr>
          <td>File Maintenance — stylists</td>
          <td><a href="stylists.php" class="text-rose-dark">stylists.php</a></td>
          <td><code>stylists</code></td>
          <td><span class="badge-soft badge-confirmed">Add</span> <span class="badge-soft badge-pending">Edit</span> <span class="badge-soft badge-cancelled">Delete</span></td>
        </tr>
        <tr>
          <td>Record maintenance — appointments</td>
          <td><a href="appointments.php" class="text-rose-dark">appointments.php</a></td>
          <td><code>appointments</code></td>
          <td><span class="badge-soft badge-confirmed">Add</span> <span class="badge-soft badge-pending">Edit</span> <span class="badge-soft badge-cancelled">Delete</span> + double-booking check</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
