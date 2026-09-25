<?php
// ==========================================================
// logout.php — Member logout (public root)
// Fully destroys the session and returns to the login page.
// ==========================================================
require_once "includes/config.php";

session_unset();
session_destroy();

header("Location: login.php");
exit;
?>
