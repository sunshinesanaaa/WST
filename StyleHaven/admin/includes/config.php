<?php
/* =====================================================================
   Style Sync Admin — configuration (config.php)
   Style Haven Salon | IT 004 Web Systems and Technologies | Group 3
   ---------------------------------------------------------------------
   These are the default XAMPP settings (user "root" with no password).
   Change DB_USER / DB_PASS if your MySQL installation uses different
   credentials.
   ===================================================================== */

/* ------------------------------------------------------------ database */
define('DB_HOST', getenv('SH_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SH_DB_PORT') ?: '3306');
define('DB_NAME', getenv('SH_DB_NAME') ?: 'stylehaven_db');
define('DB_USER', getenv('SH_DB_USER') ?: 'root');
define('DB_PASS', getenv('SH_DB_PASS') !== false ? getenv('SH_DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/* ---------------------------------------------------------------- site */
define('SH_SALON', 'Style Haven Salon');
define('SH_SYSTEM', 'Style Sync');
define('SH_GROUP', 'IT 004 — Web Systems and Technologies · Project Part 4 · Group 3');
define('SH_TIMEZONE', 'Asia/Manila');

/* ------------------------------------------------------------- session */
define('SH_SESSION_NAME', 'STYLESYNC_SID');
/* Idle log-outs keep an unattended front-desk terminal safe. */
define('SH_SESSION_IDLE_LIMIT', 3600); // 1 hour

/* ----------------------------------------------------------- behaviour */
/* Set to true only while developing to display PHP errors in the browser. */
define('SH_DEBUG', false);

date_default_timezone_set(SH_TIMEZONE);
