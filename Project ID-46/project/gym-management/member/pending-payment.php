<?php
// ==========================================================
// member/pending-payment.php — Payment required to activate
// Shown while a member's status is 'Pending Payment'. Online
// payment ONLY (QR code / UPI ID) — no Cash/Card/Net Banking
// options here, since those require an admin standing in front
// of the member; those in-person payments are instead logged
// directly by Admin via admin/payment/add-payment.php.
//
// The member NEVER enters an amount, here or anywhere else in
// this flow — the amount is always the system's own calculation
// of their pending balance, snapshotted at submission time. They
// only supply the transaction ID/UTR and the date they paid.
// Submitting does NOT activate the account — it queues a
// payment_submissions row for Admin to verify.
// ==========================================================
$allow_restricted = true;
require_once "../includes/config.php";
require_once "../includes/payment-functions.php";
require_once "includes/auth.php";

$page_title = "Complete Your Payment";
$active_menu = "pending_payment";
$member_id = $_SESSION['member_id'];
$error_msg = "";
$success_msg = "";

$stmt = mysqli_prepare($conn, "SELECT full_name, member_code, status FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($member && $member['status'] === 'Active') {
    header("Location: index.php");
    exit;
}
if ($member && $member['status'] !== 'Pending Payment') {
    header("Location: expired.php");
    exit;
}

$latest = get_latest_submission($conn, $member_id);
$has_open_submission = $latest && $latest['status'] === 'Pending Verification';

// ---- Handle a new submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_open_submission) {
    $transaction_id = trim((isset($_POST['transaction_id']) ? $_POST['transaction_id'] : ''));
    $payment_date = date('Y-m-d'); // always today — not a form field, so it can't be backdated or future-dated

    if ($transaction_id === '') {
        $error_msg = "Please enter the Transaction ID / UTR from your payment.";
    } else {
        // Amount is ALWAYS the system's own number — never taken from the form.
        $amount = get_member_pending_balance($conn, $member_id);
        if ($amount <= 0) {
            // Nothing owed (shouldn't normally happen here, but be safe)
            sync_member_payment_status($conn, $member_id);
            header("Location: index.php");
            exit;
        }
        $stmt2 = mysqli_prepare($conn, "INSERT INTO payment_submissions (member_id, amount, transaction_id, payment_date, payment_method, status) VALUES (?,?,?,?,'UPI','Pending Verification')");
        mysqli_stmt_bind_param($stmt2, "idss", $member_id, $amount, $transaction_id, $payment_date);
        if (mysqli_stmt_execute($stmt2)) {
            $success_msg = "Payment submitted! Admin will verify it shortly and your account will activate automatically.";
            $latest = get_latest_submission($conn, $member_id);
            $has_open_submission = true;
        } else {
            $error_msg = "Could not submit your payment. Please try again.";
        }
    }
}

$due = get_member_amount_due($conn, $member_id);
$paid = get_member_amount_paid($conn, $member_id);
$pending = get_member_pending_balance($conn, $member_id);

$batch_stmt = mysqli_prepare($conn, "SELECT b.batch_name, b.monthly_fee FROM member_batches mb JOIN batches b ON mb.batch_id=b.batch_id WHERE mb.member_id=? ORDER BY b.start_time");
mysqli_stmt_bind_param($batch_stmt, "i", $member_id);
mysqli_stmt_execute($batch_stmt);
$batch_res = mysqli_stmt_get_result($batch_stmt);
$batch_rows = array();
while ($row = mysqli_fetch_assoc($batch_res)) { $batch_rows[] = $row; }

$settings = array();
$res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
while ($row = mysqli_fetch_assoc($res)) { $settings[$row['setting_key']] = $row['setting_value']; }
$upi_id = (isset($settings['gym_upi_id']) ? $settings['gym_upi_id'] : 'gympro@upi');
$gym_name = (isset($settings['gym_name']) ? $settings['gym_name'] : 'Gym-Pro');

// upi:// deep link — a real UPI app can scan this and pre-fill the amount
$upi_link = "upi://pay?pa=" . urlencode($upi_id) . "&pn=" . urlencode($gym_name) . "&am=" . urlencode(number_format($pending, 2, '.', '')) . "&cu=INR";

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="checkout-card">
            <div class="checkout-header text-center">
                <i class="bi bi-lock-fill fs-2"></i>
                <h4 class="mt-2 mb-0">Complete Your Payment to Activate</h4>
                <p class="mb-0 opacity-75">Hi <?php echo htmlspecialchars($member['full_name']); ?> — one step left</p>
            </div>

            <div class="checkout-body">

                <?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
                <?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>

                <h6 class="text-muted text-uppercase small mb-3">Order Summary</h6>
                <table class="table table-sm mb-3">
                    <tbody>
                        <?php foreach ($batch_rows as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['batch_name']); ?></td>
                            <td class="text-end">Rs. <?php echo number_format($b['monthly_fee'], 2); ?> / month</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($batch_rows)): ?>
                        <tr><td colspan="2" class="text-muted">No batches on file.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="checkout-totals">
                    <div class="d-flex justify-content-between"><span>Total Amount</span><span>Rs. <?php echo number_format($due, 2); ?></span></div>
                    <div class="d-flex justify-content-between"><span>Paid Amount</span><span>Rs. <?php echo number_format($paid, 2); ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between checkout-grand-total">
                        <span>Remaining Amount</span><span>Rs. <?php echo number_format($pending, 2); ?></span>
                    </div>
                </div>

                <?php if ($has_open_submission): ?>
                    <!-- ============ AWAITING ADMIN VERIFICATION ============ -->
                    <div class="verification-pending mt-4">
                        <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                        <h5 class="mt-2">Payment Verification Pending</h5>
                        <p class="text-muted">Your payment has been submitted and is awaiting Admin's review. Your account
                           will activate automatically as soon as it's verified — no further action needed from you.</p>
                        <table class="table table-sm w-auto mx-auto text-start">
                            <tr><th>Amount Submitted</th><td>Rs. <?php echo number_format($latest['amount'], 2); ?></td></tr>
                            <tr><th>Transaction ID</th><td><?php echo htmlspecialchars($latest['transaction_id']); ?></td></tr>
                            <tr><th>Payment Date</th><td><?php echo htmlspecialchars($latest['payment_date']); ?></td></tr>
                            <tr><th>Submitted On</th><td><?php echo htmlspecialchars($latest['submitted_at']); ?></td></tr>
                        </table>
                        <a href="pending-payment.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refresh Status</a>
                    </div>

                <?php else: ?>
                    <!-- ============ PAY + SUBMIT FORM ============ -->
                    <?php if ($latest && $latest['status'] === 'Rejected'): ?>
                        <div class="alert alert-warning mt-3">
                            <strong>Your last submission was rejected.</strong>
                            <?php if ($latest['reject_reason']): ?>Reason: <?php echo htmlspecialchars($latest['reject_reason']); ?><?php endif; ?>
                            Please double-check your payment and submit again below.
                        </div>
                    <?php elseif ($latest && $latest['status'] === 'Verified' && $pending > 0): ?>
                        <div class="alert alert-info mt-3">
                            Your previous payment was verified — there's a remaining balance below to clear (e.g. from a
                            recent renewal). Please pay the remaining amount and submit.
                        </div>
                    <?php endif; ?>

                    <h6 class="text-muted text-uppercase small mt-4 mb-3">Pay Online</h6>
                    <div class="text-center mb-3">
                        <div id="upiQrCode" class="d-inline-block p-2 bg-white border rounded"></div>
                        <p class="mt-2 mb-0">Scan with any UPI app, or pay to:</p>
                        <p class="fw-bold fs-5 mb-0"><?php echo htmlspecialchars($upi_id); ?></p>
                        <p class="text-muted small">Amount to pay: <strong>Rs. <?php echo number_format($pending, 2); ?></strong></p>
                    </div>

                    <hr>

                    <h6 class="text-muted text-uppercase small mb-3">Submit Payment</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <input type="text" class="form-control" value="UPI / QR Payment" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount Paid</label>
                            <input type="text" class="form-control" value="Rs. <?php echo number_format($pending, 2); ?>" disabled>
                            <small class="text-muted">Calculated automatically — this is exactly what you owe.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction ID / UTR</label>
                            <input type="text" name="transaction_id" class="form-control" required placeholder="e.g. 123456789012">
                        </div>
                        <button type="submit" class="btn btn-gym-primary w-100">Submit Payment</button>
                    </form>
                <?php endif; ?>

                <div class="alert alert-info mt-4 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This system does not process the payment itself — pay via the QR/UPI above, then submit your
                    transaction ID. Admin verifies it against the gym's account and your account unlocks automatically.
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    var qrBox = document.getElementById('upiQrCode');
    if (qrBox) {
        new QRCode(qrBox, {
            text: <?php echo json_encode($upi_link); ?>,
            width: 180,
            height: 180
        });
    }
</script>

<?php include "includes/footer.php"; ?>
