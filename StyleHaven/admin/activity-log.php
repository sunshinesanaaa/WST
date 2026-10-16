<?php
/* =====================================================================
   Style Sync Admin — ACTIVITY LOG (activity-log.php)
   ---------------------------------------------------------------------
   Read-only audit trail. Every add, edit, delete and log-in performed in
   the admin module writes a row to `activity_log`, so the group can show
   exactly who changed which record and when.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

require_login();

const LOG_ACTIONS = ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'];

/* Only an administrator may empty the audit trail. */
if (is_post() && post('do') === 'clear') {
    csrf_verify();
    require_admin();

    $removed = (int) db_value('SELECT COUNT(*) FROM activity_log', [], 0);
    db_run('DELETE FROM activity_log');
    log_activity('DELETE', 'activity_log', null, 'Cleared ' . $removed . ' audit entries');
    flash_set('success', 'The audit trail was cleared (' . $removed . ' entr' . ($removed === 1 ? 'y' : 'ies') . ' removed).');
    redirect('activity-log.php');
}

$fAction = (string) query('action_filter');
$fTable  = (string) query('table_name');
$fSearch = (string) query('search');

/* ------------------------------------------------------------- query */
$sql    = 'SELECT * FROM activity_log WHERE 1 = 1';
$params = [];

if (in_array($fAction, LOG_ACTIONS, true)) {
    $sql .= ' AND action = ?';
    $params[] = $fAction;
}
if ($fTable !== '') {
    $sql .= ' AND table_name = ?';
    $params[] = $fTable;
}
if ($fSearch !== '') {
    $sql .= ' AND (username LIKE ? OR details LIKE ?)';
    $needle = like($fSearch);
    array_push($params, $needle, $needle);
}
$sql .= ' ORDER BY log_id DESC LIMIT 200';

$rows          = db_all($sql, $params);
$total         = (int) db_value('SELECT COUNT(*) FROM activity_log', [], 0);
$tablesTracked = db_all('SELECT DISTINCT table_name FROM activity_log ORDER BY table_name ASC');

$page_title  = 'Activity Log';
$page_kicker = 'Audit trail — every maintenance action is recorded';

include __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
  <form method="get" action="activity-log.php" class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="search">Search</label>
      <input type="search" class="form-control" id="search" name="search"
             value="<?= e($fSearch) ?>" placeholder="Username or description">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="action_filter">Action</label>
      <select class="form-select" id="action_filter" name="action_filter">
        <option value="">All actions</option>
        <?php foreach (LOG_ACTIONS as $action): ?>
          <option value="<?= e($action) ?>"<?= $fAction === $action ? ' selected' : '' ?>><?= e($action) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label" for="table_name">Table affected</label>
      <select class="form-select" id="table_name" name="table_name">
        <option value="">All tables</option>
        <?php foreach ($tablesTracked as $table): ?>
          <option value="<?= e($table['table_name']) ?>"<?= $fTable === $table['table_name'] ? ' selected' : '' ?>>
            <?= e($table['table_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-outline-plum w-100"><i class="bi bi-funnel"></i></button>
      <a href="activity-log.php" class="btn btn-outline-plum w-100" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <span class="admin-pill">
    <i class="bi bi-clock-history me-1"></i>
    Showing <?= count($rows) ?> of <?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?> (newest first, 200 maximum)
  </span>
  <?php if (is_admin() && $total > 0): ?>
    <form method="post" action="activity-log.php" class="admin-inline">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="clear">
      <button type="submit" class="btn btn-outline-plum btn-sm"
              data-confirm="Clear the entire audit trail? This removes all <?= $total ?> entries and cannot be undone.">
        <i class="bi bi-trash3 me-1"></i> Clear audit trail
      </button>
    </form>
  <?php endif; ?>
</div>

<div class="admin-table-wrap table-responsive">
  <table class="table table-sh align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Date &amp; time</th>
        <th>User</th>
        <th>Action</th>
        <th>Table</th>
        <th>Record</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="7" class="table-empty">
            <i class="bi bi-clock"></i>
            No audit entries match your filters yet.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><small class="text-muted-sh"><?= (int) $row['log_id'] ?></small></td>
            <td><small><?= e(date('d M Y · g:i A', strtotime((string) $row['created_at']))) ?></small></td>
            <td><code><?= e($row['username'] ?: 'system') ?></code></td>
            <td>
              <span class="badge-soft <?= e(status_badge_class($row['action'] === 'DELETE' ? 'cancelled' : ($row['action'] === 'INSERT' ? 'confirmed' : 'completed'))) ?>">
                <?= e($row['action']) ?>
              </span>
            </td>
            <td><code><?= e($row['table_name']) ?></code></td>
            <td><small><?= $row['record_id'] ? '#' . (int) $row['record_id'] : '—' ?></small></td>
            <td><small><?= e($row['details'] ?: '—') ?></small></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<p class="admin-hint mt-3 mb-0">
  <i class="bi bi-info-circle text-rose"></i>
  The audit trail is written by <code>log_activity()</code> in
  <code>includes/functions.php</code> and stores only the account username, the action,
  the table and a short description — never a password or a full record.
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
