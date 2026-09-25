<?php
// ==========================================================
// admin/payment/verify-submission.php — Check and act on one
// member-submitted online payment claim.
//
// Verify: creates the real row in `payments` (using the exact
// amount snapshotted at submission time — admin never re-enters
// or edits it), marks the submission Verified, and runs
// sync_member_payment_status() so the member unlocks the moment
// their balance clears.
//
// Reject: marks the submission Rejected with an optional reason.
// The member's Pending Payment page will then let them submit
// again — this does NOT touch their status or balance at all,
// since nothing was ever recorded as a real payment.
// ==========================================================
require_once "../../includes/config.php";
require_once "../../includes/payment-functions.php";
require_once "../includes/auth.php";

$id = (int)(isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : 0));
$error_msg = "";

$stmt = mysqli_prepare($conn, "SELECT ps.*, m.full_name, m.member_code, m.phone FROM payment_submissions ps JOIN members m ON ps.member_id = m.member_id WHERE ps.submission_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$submission = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$submission) {
    header("Location: submissions.php?err=" . urlencode("Submission not found."));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $submission['status'] === 'Pending Verification') {
    $action = (isset($_POST['action']) ? $_POST['action'] : '');

    if ($action === 'verify') {
        // Create the real payment record — amount comes straight from
        // the submission, never re-typed by Admin.
        $receipt_number = get_next_receipt_number($conn);
        $notes = "Verified online payment — Transaction ID: " . $submission['transaction_id'];
        $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (receipt_number, member_id, amount, payment_date, payment_method, notes, recorded_by) VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($pay_stmt, "sidsssi", $receipt_number, $submission['member_id'], $submission['amount'], $submission['payment_date'], $submission['payment_method'], $notes, $_SESSION['admin_id']);

        if (mysqli_stmt_execute($pay_stmt)) {
            $upd = mysqli_prepare($conn, "UPDATE payment_submissions SET status='Verified', verified_by=?, verified_at=NOW() WHERE submission_id=?");
            mysqli_stmt_bind_param($upd, "ii", $_SESSION['admin_id'], $id);
            mysqli_stmt_execute($upd);

            sync_member_payment_status($conn, $submission['member_id']);

            header("Location: submissions.php?msg=" . urlencode("Payment verified and logged. Member unlocked if fully paid."));
            exit;
        } else {
            $error_msg = "Could not record the payment. Please try again.";
        }
    } elseif ($action === 'reject') {
        $reason = trim((isset($_POST['reject_reason']) ? $_POST['reject_reason'] : ''));
        $upd = mysqli_prepare($conn, "UPDATE payment_submissions SET status='Rejected', reject_reason=?, verified_by=?, verified_at=NOW() WHERE submission_id=?");
        mysqli_stmt_bind_param($upd, "sii", $reason, $_SESSION['admin_id'], $id);
        mysqli_stmt_execute($upd);

        header("Location: submissions.php?msg=" . urlencode("Submission rejected. The member can submit again."));
        exit;
    }
}

$page_title = "Verify Payment";
$active_menu = "submissions";

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

<div class="gym-card p-4" style="max-width:600px;">
    <h5>Payment Submission</h5>
    <table class="table table-borderless">
        <tr><th style="width:180px;">Member</th><td><?php echo htmlspecialchars($submission['full_name']) . " (" . htmlspecialchars($submission['member_code']) . ")"; ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($submission['phone']); ?></td></tr>
        <tr><th>Amount</th><td class="fw-bold text-danger">Rs. <?php echo number_format($submission['amount'], 2); ?></td></tr>
        <tr><th>Transaction ID / UTR</th><td><?php echo htmlspecialchars($submission['transaction_id']); ?></td></tr>
        <tr><th>Payment Date</th><td><?php echo htmlspecialchars($submission['payment_date']); ?></td></tr>
        <tr><th>Method</th><td><?php echo htmlspecialchars($submission['payment_method']); ?></td></tr>
        <tr><th>Submitted On</th><td><?php echo htmlspecialchars($submission['submitted_at']); ?></td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars($submission['status']); ?></td></tr>
        <?php if ($submission['status'] === 'Rejected' && $submission['reject_reason']): ?>
        <tr><th>Reject Reason</th><td><?php echo htmlspecialchars($submission['reject_reason']); ?></td></tr>
        <?php endif; ?>
    </table>

    <p class="text-muted small">Check this transaction ID / UTR against your gym's actual UPI account before verifying.
    Verifying will log this exact amount as a payment automatically — nothing to re-type.</p>

    <?php if ($submission['status'] === 'Pending Verification'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="action" value="verify">
            <button type="submit" class="btn btn-success" onclick="return confirm('Verify this payment and log it?');"><i class="bi bi-check-lg"></i> Verify &amp; Log Payment</button>
        </form>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#rejectBox">Reject</button>

        <div class="collapse mt-3" id="rejectBox">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="action" value="reject">
                <div class="mb-2">
                    <label class="form-label">Reason (optional, shown to the member)</label>
                    <input type="text" name="reject_reason" class="form-control" placeholder="e.g. Transaction ID not found">
                </div>
                <button type="submit" class="btn btn-danger btn-sm">Confirm Reject</button>
            </form>
        </div>
    <?php endif; ?>

    <a href="submissions.php" class="btn btn-outline-secondary mt-3">Back to List</a>
</div>

<?php include "../includes/footer.php"; ?>
