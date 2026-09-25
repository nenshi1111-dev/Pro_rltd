<?php
// ==========================================================
// admin/request/registration_request/view-request.php
// ==========================================================
require_once "../../../includes/config.php";
require_once "../../includes/auth.php";

$page_title = "View Registration Request";
$active_menu = "reg_requests";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM requested_members WHERE request_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$r) {
    header("Location: requests.php?err=" . urlencode("Request not found."));
    exit;
}

$batch_stmt = mysqli_prepare($conn, "SELECT b.batch_name, b.start_time, b.end_time FROM requested_member_batches rmb JOIN batches b ON rmb.batch_id=b.batch_id WHERE rmb.request_id=? ORDER BY b.start_time");
mysqli_stmt_bind_param($batch_stmt, "i", $id);
mysqli_stmt_execute($batch_stmt);
$batches = mysqli_stmt_get_result($batch_stmt);

include "../../includes/header.php";
include "../../includes/sidebar.php";
include "../../includes/topbar.php";
?>

<div class="gym-card p-4">
    <h4><?php echo htmlspecialchars($r['full_name']); ?></h4>
    <table class="table table-borderless mt-3">
        <tr><th style="width:220px;">Gender</th><td><?php echo htmlspecialchars($r['gender']); ?></td></tr>
        <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($r['dob']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($r['phone']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($r['email']); ?></td></tr>
        <tr><th>Address</th><td><?php echo htmlspecialchars($r['address']); ?></td></tr>
        <tr><th>Emergency Contact</th><td><?php echo htmlspecialchars($r['emergency_contact']); ?></td></tr>
        <tr><th>Duration</th><td><?php echo (int)$r['duration']; ?> month(s)</td></tr>
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
        <a href="approve-request.php?id=<?php echo $r['request_id']; ?>" class="btn btn-success" onclick="return confirm('Approve this registration?');">Approve</a>
        <a href="reject-request.php?id=<?php echo $r['request_id']; ?>" class="btn btn-danger" onclick="return confirm('Reject this registration?');">Reject</a>
    <?php endif; ?>
    <a href="requests.php" class="btn btn-outline-secondary">Back to List</a>
</div>

<?php include "../../includes/footer.php"; ?>
