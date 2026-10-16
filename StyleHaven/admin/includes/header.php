<?php
/* =====================================================================
   Style Sync Admin — shared page header (header.php)
   ---------------------------------------------------------------------
   Set $page_title (and optionally $page_kicker) before including this
   file. The sidebar marks the current page automatically.
   ===================================================================== */

require_once __DIR__ . '/auth.php';

$page_title  = $page_title  ?? 'Dashboard';
$page_kicker = $page_kicker ?? 'Style Sync Admin';

/* Which navigation item is open right now? */
$current      = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$signed_in    = current_user();
$nav_items    = [
    ['Dashboard',        'index.php',          'speedometer2'],
    ['Appointments',     'appointments.php',   'calendar2-week'],
    ['Services',         'services.php',       'scissors'],
    ['Products',         'products.php',       'bag-heart'],
    ['Stylists',         'stylists.php',       'people'],
];
if (is_admin()) {
    $nav_items[] = ['Users', 'users.php', 'person-badge'];
}
$nav_items[] = ['Activity Log', 'activity-log.php', 'clock-history'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Style Sync admin module — content and record maintenance for Style Haven Salon.">
  <meta name="author" content="Group 3 — IT 004 Web Systems and Technologies">
  <title><?= e($page_title) ?> | Style Sync Admin — Style Haven Salon</title>
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

  <!-- ============================== TOP BAR ============================== -->
  <div class="topbar d-none d-lg-block">
    <div class="container-fluid px-lg-4 d-flex justify-content-between align-items-center">
      <div><i class="bi bi-gear-wide-connected text-gold"></i> <?= e(SH_SYSTEM) ?> control panel <span class="sep">|</span> content and record maintenance</div>
      <div>
        <i class="bi bi-person-circle text-gold"></i>
        <?= e($signed_in['full_name'] ?? '') ?>
        <span class="sep">|</span><?= e($signed_in['role'] ?? '') ?>
      </div>
    </div>
  </div>

  <!-- =============================== NAVBAR ============================== -->
  <nav class="navbar navbar-expand-lg navbar-sh sticky-top">
    <div class="container-fluid px-lg-4">
      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <img src="../assets/logo.svg" alt="Style Haven Salon logo" class="brand-logo">
      </a>
      <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
      </button>
      <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="../index.html" target="_blank" rel="noopener"><i class="bi bi-globe2 me-1"></i> View website</a></li>
          <li class="nav-item"><a class="nav-link" href="change-password.php"><i class="bi bi-key me-1"></i> Change password</a></li>
          <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
            <a class="btn btn-rose btn-sm px-3" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Log out</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="admin-shell">

    <!-- ============================== SIDEBAR ============================ -->
    <aside class="admin-side" id="adminSide">
      <div class="admin-side-title"><i class="bi bi-sliders2"></i> Maintenance menu</div>
      <nav class="admin-side-nav">
        <?php foreach ($nav_items as $item): ?>
          <a href="<?= e($item[1]) ?>" class="admin-side-link<?= $current === $item[1] ? ' active' : '' ?>">
            <i class="bi bi-<?= e($item[2]) ?>"></i><span><?= e($item[0]) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="admin-side-foot">
        <p class="mb-1"><strong><?= e(SH_SALON) ?></strong></p>
        <p class="mb-0" style="font-size:0.74rem;">IT 004 · Project Part 4<br>Group 3</p>
      </div>
    </aside>

    <!-- =============================== MAIN ============================== -->
    <main class="admin-main">
      <div class="admin-page-head">
        <div>
          <span class="section-kicker mb-0"><?= e($page_kicker) ?></span>
          <h1 class="admin-page-title"><?= e($page_title) ?></h1>
        </div>
        <div class="admin-page-head-side">
          <span class="admin-clock"><i class="bi bi-clock"></i> <?= e(date('D, d M Y · g:i A')) ?></span>
        </div>
      </div>

      <?php foreach (flash_get() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
          <?= $flash['message'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endforeach; ?>
