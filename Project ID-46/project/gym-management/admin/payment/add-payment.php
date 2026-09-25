<?php
// ==========================================================
// admin/payment/add-payment.php — Log a payment collected
// in person (manual path — no notification needed, since Admin
// is doing this directly rather than verifying an online
// submission). Partial payment is not supported: Admin never
// types an amount here — it's always the member's current
// pending balance, calculated by the system. This mirrors how
// admin/payment/verify-submission.php also never lets Admin
// re-type an amount for an online submission.
// ==========================================================
require_once "../../includes/config.php";
require_once "../../includes/payment-functions.php";
require_once "../includes/auth.php";

$page_title = "Log Payment";
$active_menu = "payments";
$error_msg = "";

// Pre-select a member if we arrived from their profile/pending list
$preselect_member = (int)((isset($_GET['member_id']) ? $_GET['member_id'] : 0));

$members = mysqli_query($conn, "SELECT member_id, member_code, full_name FROM members ORDER BY full_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)((isset($_POST['member_id']) ? $_POST['member_id'] : 0));
    $date      = (isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d'));
    $method    = (isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Cash');
    $notes     = trim((isset($_POST['notes']) ? $_POST['notes'] : ''));

    if ($member_id <= 0) {
        $error_msg = "Please select a member.";
    } else {
        // The amount is NEVER taken from the form — always the
        // system's own live calculation, looked up fresh right here.
        $amount = get_member_pending_balance($conn, $member_id);

        if ($amount <= 0) {
            $error_msg = "This member has no pending balance to log.";
        } else {
            $receipt_number = get_next_receipt_number($conn);
            $stmt = mysqli_prepare($conn, "INSERT INTO payments (receipt_number, member_id, amount, payment_date, payment_method, notes, recorded_by) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "sidsssi", $receipt_number, $member_id, $amount, $date, $method, $notes, $_SESSION['admin_id']);

            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                $activated = sync_member_payment_status($conn, $member_id);
                $redirect_extra = $activated ? "&activated=1" : "";
                header("Location: receipt.php?id=$new_id&new=1$redirect_extra");
                exit;
            } else {
                $error_msg = "Could not save payment. Please try again.";
            }
        }
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

<div class="gym-card p-4" style="max-width:550px;">
    <p class="text-muted small">Use this for a payment collected in person (cash, UPI shown to you directly, card
       machine, etc). The amount is always the member's full pending balance — partial payments aren't supported,
       so there's nothing to type in for the amount.</p>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Member</label>
            <select name="member_id" class="form-select" required id="memberSelect" onchange="loadBalance()">
                <option value="">-- Select member --</option>
                <?php while ($m = mysqli_fetch_assoc($members)): ?>
                    <option value="<?php echo $m['member_id']; ?>" <?php echo $preselect_member==$m['member_id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($m['full_name']) . " (" . htmlspecialchars($m['member_code']) . ")"; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Amount</label>
            <input type="text" class="form-control" id="amountDisplay" value="Select a member to see the amount" disabled>
            <small class="text-muted">Calculated automatically from what this member owes — never typed manually.</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Date</label>
            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select">
                <option>Cash</option><option>UPI</option><option>Card</option><option>Bank Transfer</option><option>Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Notes (optional)</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Paid in person at reception">
        </div>
        <button type="submit" class="btn btn-gym-primary">Log Payment</button>
        <a href="payments.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<script>
// Show the member's current pending balance — this IS the amount that will be logged.
function loadBalance() {
    const id = document.getElementById('memberSelect').value;
    const display = document.getElementById('amountDisplay');
    if (!id) { display.value = 'Select a member to see the amount'; return; }
    fetch('member-balance.php?member_id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            display.value = 'Rs. ' + data.pending + ' (pending balance)';
        })
        .catch(() => { display.value = 'Could not load balance'; });
}
<?php if ($preselect_member): ?>
document.addEventListener('DOMContentLoaded', loadBalance);
<?php endif; ?>
</script>

<?php include "../includes/footer.php"; ?>
