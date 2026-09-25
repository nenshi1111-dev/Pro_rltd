<?php
// ==========================================================
// admin/request/renewable_request/view-request.php
// ==========================================================
require_once "../../../includes/config.php";
require_once "../../includes/auth.php";

$page_title = "View Renewal Request";
$active_menu = "renew_requests";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT rr.*, m.full_name, m.member_code, m.membership_expiry_date FROM renewal_requests rr JOIN members m ON rr.member_id=m.member_id WHERE rr.renewal_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$r) {
    header("Location: requests.php?err=" . urlencode("Request not found."));
    exit;
}

$batch_stmt = mysqli_prepare($conn, "SELECT b.batch_name, b.start_time, b.end_time FROM renewal_request_batches rrb JOIN batches b ON rrb.batch_id=b.batch_id WHERE rrb.renewal_id=? ORDER BY b.start_time");
mysqli_stmt_bind_param($batch_stmt, "i", $id);
mysqli_stmt_execute($batch_stmt);
$batches = mysqli_stmt_get_result($batch_stmt);

include "../../includes/header.php";
include "../../includes/sidebar.php";
include "../../includes/topbar.php";
?>

<div class="gym-card p-4">
    <h4><?php echo htmlspecialchars($r['full_name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($r['member_code']); ?>)</small></h4>
    <table class="table table-borderless mt-3">
        <tr><th style="width:220px;">Current Expiry Date</th><td><?php echo htmlspecialchars($r['membership_expiry_date']); ?></td></tr>
        <tr><th>Requested Duration</th><td><?php echo (int)$r['requested_duration']; ?> month(s)</td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars($r['status']); ?></td></tr>
    </table>

    <h6>Requested Batches</h6>
    <ul class="list-group mb-3">
        <?php $found=false; while ($b = mysqli_fetch_assoc($batches)): $found=true; ?>
            <li class="list-group-item"><?php echo htmlspecialchars($b['batch_name']) . " (" . substr($b['start_time'],0,5) . "-" . substr($b['end_time'],0,5) . ")"; ?></li>
        <?php endwhile; if (!$found): ?>
            <li class="list-group-item text-muted">No batches selected.</li>
        <?php endif; ?>
    </ul>

    <?php if ($r['status']=='Pending'): ?>
        <a href="approve-request.php?id=<?php echo $r['renewal_id']; ?>" class="btn btn-success" onclick="return confirm('Approve this renewal?');">Approve</a>
        <a href="reject-request.php?id=<?php echo $r['renewal_id']; ?>" class="btn btn-danger" onclick="return confirm('Reject this renewal?');">Reject</a>
    <?php endif; ?>
    <a href="requests.php" class="btn btn-outline-secondary">Back to List</a>
</div>

<?php include "../../includes/footer.php"; ?>
