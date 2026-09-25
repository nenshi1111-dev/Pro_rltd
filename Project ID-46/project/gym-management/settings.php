<?php
// ==========================================================
// settings.php — Admin / Trainer login selection screen
// A simple screen so staff can choose which panel to log into,
// without exposing admin/trainer URLs directly on the public nav.
// ==========================================================
require_once "includes/config.php";
$page_title = "Staff Login";
include "includes/header.php";
include "includes/navbar.php";
?>

<div class="auth-wrapper">
    <div class="auth-card text-center">
        <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
        <h4 class="mt-3 mb-4">Staff Login</h4>
        <p class="text-muted small">Choose your panel to continue.</p>

        <div class="d-grid gap-3 mt-3">
            <a href="admin/login.php" class="btn btn-gym-primary btn-lg">
                <i class="bi bi-shield-lock-fill me-2"></i>Admin Login
            </a>
            <a href="trainer/login.php" class="btn btn-outline-dark btn-lg">
                <i class="bi bi-person-badge-fill me-2"></i>Trainer Login
            </a>
        </div>

        <p class="text-center mt-4 small">
            Are you a member? <a href="login.php">Member Login</a>
        </p>
    </div>
</div>

<?php include "includes/footer.php"; ?>
