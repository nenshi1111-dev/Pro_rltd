<?php
// ==========================================================
// member/profile.php — Member's own profile (read-only)
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "My Profile";
$active_menu = "profile";
$member_id = $_SESSION['member_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="gym-card p-4" style="max-width:600px;">
    <h4><?php echo htmlspecialchars($member['full_name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($member['member_code']); ?>)</small></h4>
    <table class="table table-borderless mt-3">
        <tr><th style="width:180px;">Gender</th><td><?php echo htmlspecialchars($member['gender']); ?></td></tr>
        <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($member['dob']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($member['phone']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($member['email']); ?></td></tr>
        <tr><th>Address</th><td><?php echo htmlspecialchars($member['address']); ?></td></tr>
        <tr><th>Emergency Contact</th><td><?php echo htmlspecialchars($member['emergency_contact']); ?></td></tr>
    </table>
    <p class="text-muted small">To update your contact details, please speak with Admin. You can change your password from the Change Password menu.</p>
</div>

<?php include "includes/footer.php"; ?>
