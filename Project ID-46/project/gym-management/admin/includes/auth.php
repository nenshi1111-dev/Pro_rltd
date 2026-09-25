<?php
// ==========================================================
// admin/includes/auth.php
// Session guard for every protected Admin page.
// Include this AFTER config.php, before any HTML output.
// ==========================================================
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
