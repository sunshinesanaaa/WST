<?php
/* =====================================================================
   Style Sync Admin — log out (logout.php)
   Destroys the session, then starts a fresh one only so that a
   confirmation message can be shown on the log-in page.
   ===================================================================== */

require_once __DIR__ . '/includes/auth.php';

logout_user();
sh_session_start();

flash_set('success', 'You have been signed out of ' . e(SH_SYSTEM) . '. Your session has ended.');
redirect('login.php');
