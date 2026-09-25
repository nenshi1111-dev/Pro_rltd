<?php
// ==========================================================
// trainer/profile.php — Trainer's own profile (read-only)
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "My Profile";
$active_menu = "profile";
$trainer_id = $_SESSION['trainer_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM trainers WHERE trainer_id=?");
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$trainer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="gym-card p-4" style="max-width:600px;">
    <h4><?php echo htmlspecialchars($trainer['full_name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($trainer['trainer_code']); ?>)</small></h4>
    <table class="table table-borderless mt-3">
        <tr><th style="width:180px;">Gender</th><td><?php echo htmlspecialchars($trainer['gender']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($trainer['phone']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($trainer['email']); ?></td></tr>
        <tr><th>Specialization</th><td><?php echo htmlspecialchars($trainer['specialization']); ?></td></tr>
        <tr><th>Status</th><td><span class="badge <?php echo $trainer['status']=='Active'?'badge-active':'badge-inactive'; ?>"><?php echo htmlspecialchars($trainer['status']); ?></span></td></tr>
    </table>
    <p class="text-muted small">To change contact details, please ask Admin. You can change your password from the Settings menu.</p>
</div>

<?php include "includes/footer.php"; ?>
