<?php
// ==========================================================
// admin/payment/payments.php — Payment history list
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Payments";
$active_menu = "payments";

$search = trim((isset($_GET['q']) ? $_GET['q'] : ''));
$base_select = "SELECT p.*, m.full_name, m.member_code FROM payments p JOIN members m ON p.member_id = m.member_id";

if ($search !== '') {
    $like = "%$search%";
    $stmt = mysqli_prepare($conn, "$base_select WHERE m.full_name LIKE ? OR m.member_code LIKE ? OR p.receipt_number LIKE ? ORDER BY p.payment_date DESC, p.payment_id DESC");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $payments = mysqli_stmt_get_result($stmt);
} else {
    $payments = mysqli_query($conn, "$base_select ORDER BY p.payment_date DESC, p.payment_id DESC");
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search by member name, code, or receipt #" value="<?php echo htmlspecialchars($search); ?>" style="min-width:320px;">
        <button class="btn btn-outline-dark"><i class="bi bi-search"></i></button>
    </form>
    <div class="d-flex gap-2">
        <a href="pending-payments.php" class="btn btn-outline-danger"><i class="bi bi-exclamation-circle me-1"></i>Pending Payments</a>
        <a href="export-payments.php<?php echo $search !== '' ? '?q=' . urlencode($search) : ''; ?>" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export CSV</a>
        <a href="add-payment.php" class="btn btn-gym-primary"><i class="bi bi-plus-circle me-1"></i>Log Payment</a>
    </div>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Receipt #</th><th>Member</th><th>Amount</th><th>Date</th><th>Method</th><th>Notes</th><th>Actions</th></tr></thead>
            <tbody>
                <?php $found=false; while ($p = mysqli_fetch_assoc($payments)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['receipt_number']); ?></td>
                    <td><?php echo htmlspecialchars($p['full_name']) . " (" . htmlspecialchars($p['member_code']) . ")"; ?></td>
                    <td>Rs. <?php echo number_format($p['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($p['notes']); ?></td>
                    <td><a href="receipt.php?id=<?php echo $p['payment_id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bi bi-receipt"></i> Receipt</a></td>
                </tr>
                <?php endwhile; if (!$found): ?>
                    <tr><td colspan="7" class="text-muted">No payments logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
