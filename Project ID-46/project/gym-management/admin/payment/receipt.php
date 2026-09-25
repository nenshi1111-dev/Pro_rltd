<?php
// ==========================================================
// admin/payment/receipt.php — Printable receipt for one payment
// A minimal, standalone print-friendly page — no sidebar/topbar
// clutter, just the receipt itself with a Print button.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "
    SELECT p.*, m.full_name, m.member_code, a.admin_username AS admin_name
    FROM payments p
    JOIN members m ON p.member_id = m.member_id
    JOIN admins a ON p.recorded_by = a.admin_id
    WHERE p.payment_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$payment) {
    die("Receipt not found.");
}

$settings = array();
$res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
while ($row = mysqli_fetch_assoc($res)) { $settings[$row['setting_key']] = $row['setting_value']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt <?php echo htmlspecialchars($payment['receipt_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 40px 15px; }
        .receipt-box { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 35px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        .receipt-header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 15px; margin-bottom: 15px; }
        .receipt-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dotted #eee; }
        .receipt-total { font-size: 1.4rem; font-weight: 800; text-align: right; margin-top: 15px; color: #e63946; }
        @media print { .no-print { display: none; } body { background: #fff; padding: 0; } .receipt-box { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="receipt-header">
            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars((isset($settings['gym_name']) ? $settings['gym_name'] : 'Gym-Pro')); ?></h4>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars((isset($settings['gym_address']) ? $settings['gym_address'] : '')); ?></p>
            <p class="text-muted small mb-0">Payment Receipt</p>
        </div>

        <div class="receipt-row"><span>Receipt No.</span><strong><?php echo htmlspecialchars($payment['receipt_number']); ?></strong></div>
        <div class="receipt-row"><span>Date</span><strong><?php echo htmlspecialchars($payment['payment_date']); ?></strong></div>
        <div class="receipt-row"><span>Member</span><strong><?php echo htmlspecialchars($payment['full_name']); ?></strong></div>
        <div class="receipt-row"><span>Member Code</span><strong><?php echo htmlspecialchars($payment['member_code']); ?></strong></div>
        <div class="receipt-row"><span>Payment Method</span><strong><?php echo htmlspecialchars($payment['payment_method']); ?></strong></div>
        <?php if ($payment['notes']): ?>
        <div class="receipt-row"><span>Notes</span><strong><?php echo htmlspecialchars($payment['notes']); ?></strong></div>
        <?php endif; ?>
        <div class="receipt-row"><span>Recorded By</span><strong><?php echo htmlspecialchars($payment['admin_name']); ?></strong></div>

        <div class="receipt-total">Rs. <?php echo number_format($payment['amount'], 2); ?></div>

        <?php if (isset($_GET['activated'])): ?>
        <div class="alert alert-success no-print text-center">
            <i class="bi bi-unlock-fill me-1"></i> This payment cleared the pending balance — the member's account is now Active.
        </div>
        <?php endif; ?>
        <div class="no-print text-center mt-4">
            <button onclick="window.print()" class="btn btn-gym-primary"><i class="bi bi-printer"></i> Print Receipt</button>
            <a href="payments.php" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</body>
</html>
