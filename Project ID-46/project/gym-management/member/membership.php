<?php
// ==========================================================
// member/membership.php — Membership status + enrolled batches
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Membership";
$active_menu = "membership";
$member_id = $_SESSION['member_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$batch_stmt = mysqli_prepare($conn, "SELECT b.batch_name, b.start_time, b.end_time FROM member_batches mb JOIN batches b ON mb.batch_id=b.batch_id WHERE mb.member_id=? ORDER BY b.start_time");
mysqli_stmt_bind_param($batch_stmt, "i", $member_id);
mysqli_stmt_execute($batch_stmt);
$batches = mysqli_stmt_get_result($batch_stmt);

// Check if a renewal request is already pending, to avoid duplicates
$stmt2 = mysqli_prepare($conn, "SELECT status FROM renewal_requests WHERE member_id=? ORDER BY renewal_id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt2, "i", $member_id);
mysqli_stmt_execute($stmt2);
$latest_renewal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
$has_pending = $latest_renewal && $latest_renewal['status'] === 'Pending';

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="gym-card p-4" style="max-width:600px;">
    <h5>Your Batches</h5>
    <ul class="list-group mb-3">
        <?php $found=false; while ($b = mysqli_fetch_assoc($batches)): $found=true; ?>
            <li class="list-group-item"><?php echo htmlspecialchars($b['batch_name']) . " (" . substr($b['start_time'],0,5) . "-" . substr($b['end_time'],0,5) . ")"; ?></li>
        <?php endwhile; if (!$found): ?>
            <li class="list-group-item text-muted">No batches assigned.</li>
        <?php endif; ?>
    </ul>

    <table class="table table-borderless">
        <tr><th style="width:220px;">Duration</th><td><?php echo htmlspecialchars(format_membership_duration($member['duration_months'], $member['membership_start_date'], $member['membership_expiry_date'])); ?></td></tr>
        <tr><th>Start Date</th><td><?php echo htmlspecialchars($member['membership_start_date']); ?></td></tr>
        <tr><th>Expiry Date</th><td><?php echo htmlspecialchars($member['membership_expiry_date']); ?></td></tr>
        <tr><th>Status</th><td><span class="badge <?php echo $member['status']=='Active'?'badge-active':'badge-pending'; ?>"><?php echo htmlspecialchars($member['status']); ?></span></td></tr>
    </table>

    <?php if ($has_pending): ?>
        <div class="alert alert-info">You already have a pending renewal request. <a href="renew-status.php">View status</a></div>
    <?php else: ?>
        <a href="renew-request.php" class="btn btn-gym-primary">Request Renewal / Change Batches</a>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
