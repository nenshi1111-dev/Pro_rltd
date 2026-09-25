<?php
// ==========================================================
// member/expired.php — Shown to members whose status != Active
// $allow_restricted must be true so auth.php doesn't redirect here
// again in an infinite loop.
// ==========================================================
$allow_restricted = true;
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Membership Expired";
$active_menu = "expired";
$member_id = $_SESSION['member_id'];

$stmt = mysqli_prepare($conn, "SELECT full_name, member_code, membership_expiry_date, status FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// If member is actually Active (e.g. admin just renewed them), send to dashboard
if ($member && $member['status'] === 'Active') {
    header("Location: index.php");
    exit;
}
// A Pending Payment member belongs on the payment page, not here
if ($member && $member['status'] === 'Pending Payment') {
    header("Location: pending-payment.php");
    exit;
}

$stmt2 = mysqli_prepare($conn, "SELECT status FROM renewal_requests WHERE member_id=? ORDER BY renewal_id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt2, "i", $member_id);
mysqli_stmt_execute($stmt2);
$latest_renewal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
$has_pending = $latest_renewal && $latest_renewal['status'] === 'Pending';

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="gym-card p-4 text-center" style="max-width:500px; margin:0 auto;">
    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:3rem;"></i>
    <h4 class="mt-3">Your Membership Has Expired</h4>
    <p class="text-muted">Hi <?php echo htmlspecialchars($member['full_name']); ?>, your membership expired on
       <strong><?php echo htmlspecialchars($member['membership_expiry_date']); ?></strong>.
       Please submit a renewal request to regain access to your dashboard, batch, and workout plans.</p>

    <?php if ($has_pending): ?>
        <div class="alert alert-info">Your renewal request is pending Admin approval.</div>
        <a href="renew-status.php" class="btn btn-outline-dark">View Renewal Status</a>
    <?php else: ?>
        <a href="renew-request.php" class="btn btn-gym-primary">Request Renewal</a>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
