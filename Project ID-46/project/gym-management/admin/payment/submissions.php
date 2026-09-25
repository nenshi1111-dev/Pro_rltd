<?php
// ==========================================================
// admin/payment/submissions.php — Online payment submissions
// Members submit a transaction ID after paying via the QR/UPI
// on their Pending Payment page; this queue is where Admin
// checks and verifies (or rejects) each one.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Online Payment Requests";
$active_menu = "submissions";

$filter = (isset($_GET['status']) ? $_GET['status'] : 'Pending Verification');
$allowed = array('Pending Verification', 'Verified', 'Rejected', 'All');
if (!in_array($filter, $allowed)) { $filter = 'Pending Verification'; }

$base_select = "SELECT ps.*, m.full_name, m.member_code FROM payment_submissions ps JOIN members m ON ps.member_id = m.member_id";

if ($filter === 'All') {
    $submissions = mysqli_query($conn, "$base_select ORDER BY ps.submitted_at DESC");
} else {
    $stmt = mysqli_prepare($conn, "$base_select WHERE ps.status=? ORDER BY ps.submitted_at DESC");
    mysqli_stmt_bind_param($stmt, "s", $filter);
    mysqli_stmt_execute($stmt);
    $submissions = mysqli_stmt_get_result($stmt);
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($_GET['err']); ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="btn-group">
        <?php foreach ($allowed as $s): ?>
            <a href="submissions.php?status=<?php echo urlencode($s); ?>" class="btn btn-sm <?php echo $filter==$s?'btn-gym-primary':'btn-outline-dark'; ?>"><?php echo htmlspecialchars($s); ?></a>
        <?php endforeach; ?>
    </div>
    <a href="payments.php" class="btn btn-outline-secondary">Back to Payment History</a>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Member</th><th>Amount</th><th>Transaction ID</th><th>Payment Date</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php $found=false; while ($s = mysqli_fetch_assoc($submissions)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['full_name']) . " (" . htmlspecialchars($s['member_code']) . ")"; ?></td>
                    <td>Rs. <?php echo number_format($s['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($s['transaction_id']); ?></td>
                    <td><?php echo htmlspecialchars($s['payment_date']); ?></td>
                    <td><?php echo htmlspecialchars($s['submitted_at']); ?></td>
                    <td>
                        <?php $b = $s['status']=='Verified'?'badge-active':($s['status']=='Rejected'?'badge-rejected':'badge-pending'); ?>
                        <span class="badge <?php echo $b; ?>"><?php echo htmlspecialchars($s['status']); ?></span>
                    </td>
                    <td><a href="verify-submission.php?id=<?php echo $s['submission_id']; ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endwhile; if (!$found): ?>
                    <tr><td colspan="7" class="text-muted">No submissions here.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
