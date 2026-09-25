<?php
// ==========================================================
// trainer/includes/auth.php
// Session guard for every protected Trainer page.
// ==========================================================
if (!isset($_SESSION['trainer_id'])) {
    header("Location: login.php");
    exit;
}
?>
