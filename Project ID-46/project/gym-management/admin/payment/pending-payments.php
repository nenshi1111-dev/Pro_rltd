<?php
// ==========================================================
// admin/payment/pending-payments.php — Members who still owe
// Calculates due/paid/pending live for every member, shows
// only those with pending > 0.
// ==========================================================
require_once "../../includes/config.php";
require_once "../../includes/payment-functions.php";
require_once "../includes/auth.php";

$page_title = "Pending Payments";
$active_menu = "payments";

$all_members = mysqli_query($conn, "SELECT member_id, member_code, full_name, phone, status FROM members ORDER BY full_name");

$pending_list = array();
while ($m = mysqli_fetch_assoc($all_members)) {
    $pending = get_member_pending_balance($conn, $m['member_id']);
    if ($pending > 0) {
        $m['pending'] = $pending;
        $pending_list[] = $m;
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?php echo count($pending_list); ?> member(s) with a pending balance</p>
    <a href="payments.php" class="btn btn-outline-secondary">Back to Payment History</a>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Status</th><th>Pending Amount</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($pending_list)): ?>
                    <tr><td colspan="6" class="text-muted">No pending payments — everyone is fully paid up.</td></tr>
                <?php else: foreach ($pending_list as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['member_code']); ?></td>
                    <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['phone']); ?></td>
                    <td><span class="badge <?php echo $m['status']=='Active'?'badge-active':($m['status']=='Expired'?'badge-expired':($m['status']=='Pending Payment'?'badge-pending':'badge-inactive')); ?>"><?php echo htmlspecialchars($m['status']); ?></span></td>
                    <td class="text-danger fw-bold">Rs. <?php echo number_format($m['pending'], 2); ?></td>
                    <td><a href="add-payment.php?member_id=<?php echo $m['member_id']; ?>" class="btn btn-sm btn-gym-primary">Log Payment</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
