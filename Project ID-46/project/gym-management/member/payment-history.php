<?php
// ==========================================================
// member/payment-history.php — Member's own payment record
// Read-only. Shows what they've paid, what they owe based on
// their current batches, and the difference.
// ==========================================================
require_once "../includes/config.php";
require_once "../includes/payment-functions.php";
require_once "includes/auth.php";

$page_title = "Payment History";
$active_menu = "payments";
$member_id = $_SESSION['member_id'];

$due = get_member_amount_due($conn, $member_id);
$paid = get_member_amount_paid($conn, $member_id);
$pending = get_member_pending_balance($conn, $member_id);

$stmt = mysqli_prepare($conn, "SELECT * FROM payments WHERE member_id=? ORDER BY payment_date DESC, payment_id DESC");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$payments = mysqli_stmt_get_result($stmt);

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card bg-stat-3">
            <h3>Rs. <?php echo number_format($due, 2); ?></h3>
            <p>Total Due (current batches)</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card bg-stat-2">
            <h3>Rs. <?php echo number_format($paid, 2); ?></h3>
            <p>Total Paid</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card <?php echo $pending > 0 ? 'bg-stat-4' : 'bg-stat-2'; ?>">
            <h3>Rs. <?php echo number_format($pending, 2); ?></h3>
            <p>Pending Balance</p>
        </div>
    </div>
</div>

<?php if ($pending > 0): ?>
    <div class="alert alert-warning">You have a pending balance of <strong>Rs. <?php echo number_format($pending, 2); ?></strong>. Please contact Admin to make a payment.</div>
<?php endif; ?>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Receipt #</th><th>Amount</th><th>Date</th><th>Method</th><th>Notes</th></tr></thead>
            <tbody>
                <?php $found=false; while ($p = mysqli_fetch_assoc($payments)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['receipt_number']); ?></td>
                    <td>Rs. <?php echo number_format($p['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($p['notes']); ?></td>
                </tr>
                <?php endwhile; if (!$found): ?>
                    <tr><td colspan="5" class="text-muted">No payments recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
