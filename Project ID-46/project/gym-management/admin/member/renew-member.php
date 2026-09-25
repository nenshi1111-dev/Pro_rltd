<?php
// ==========================================================
// admin/member/renew-member.php — Admin-side manual renewal
// Renewal means: re-pick batches + duration, extends from today.
//
// Status goes to 'Pending Payment', not straight back to Active —
// same consistency rule as everywhere else: no path sets a
// member Active without a logged/verified payment. Admin still
// needs to log a payment (or the member pays online) to unlock
// the account after this.
// ==========================================================
require_once "../../includes/config.php";
require_once "../../includes/batch-functions.php";
require_once "../includes/auth.php";

$page_title = "Renew Member";
$active_menu = "members";
$error_msg = "";
$max_batches = get_max_batches_per_member($conn);
$grouped = get_batches_grouped($conn, true);

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$member) {
    header("Location: members.php?err=" . urlencode("Member not found."));
    exit;
}

$selected = [];
$sel_stmt = mysqli_prepare($conn, "SELECT batch_id FROM member_batches WHERE member_id=?");
mysqli_stmt_bind_param($sel_stmt, "i", $id);
mysqli_stmt_execute($sel_stmt);
$sel_res = mysqli_stmt_get_result($sel_stmt);
while ($row = mysqli_fetch_assoc($sel_res)) { $selected[] = (int)$row['batch_id']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration  = (int)((isset($_POST['duration_months']) ? $_POST['duration_months'] : 1));
    $batch_ids = array_map('intval', (isset($_POST['batch_ids']) ? $_POST['batch_ids'] : []));

    if (empty($batch_ids)) {
        $error_msg = "Please select at least one batch.";
    } elseif (count($batch_ids) > $max_batches) {
        $error_msg = "A member can select at most $max_batches batches.";
    } else {
        $conflict = find_batch_conflict($conn, $batch_ids);
        if ($conflict) {
            $error_msg = "\"{$conflict[0]}\" and \"{$conflict[1]}\" overlap in timing — choose batches with different schedules.";
        } else {
            foreach ($batch_ids as $bid) {
                if (in_array($bid, $selected)) continue; // already enrolled, no new seat needed
                $cap_q = mysqli_prepare($conn, "SELECT b.capacity, (SELECT COUNT(*) FROM member_batches WHERE batch_id=b.batch_id) AS filled, b.batch_name FROM batches b WHERE b.batch_id=?");
                mysqli_stmt_bind_param($cap_q, "i", $bid);
                mysqli_stmt_execute($cap_q);
                $cap = mysqli_fetch_assoc(mysqli_stmt_get_result($cap_q));
                if ($cap && $cap['filled'] >= $cap['capacity']) {
                    $error_msg = "\"{$cap['batch_name']}\" is full.";
                    break;
                }
            }

            if ($error_msg === "") {
                // Renewal always extends from today
                $new_start  = date('Y-m-d');
                $new_expiry = date('Y-m-d', strtotime("+$duration months"));

                $stmt = mysqli_prepare($conn, "UPDATE members SET duration_months=?, membership_start_date=?, membership_expiry_date=?, status='Pending Payment' WHERE member_id=?");
                mysqli_stmt_bind_param($stmt, "issi", $duration, $new_start, $new_expiry, $id);

                if (mysqli_stmt_execute($stmt)) {
                    $del = mysqli_prepare($conn, "DELETE FROM member_batches WHERE member_id=?");
                    mysqli_stmt_bind_param($del, "i", $id);
                    mysqli_stmt_execute($del);

                    foreach ($batch_ids as $bid) {
                        $link = mysqli_prepare($conn, "INSERT INTO member_batches (member_id, batch_id) VALUES (?,?)");
                        mysqli_stmt_bind_param($link, "ii", $id, $bid);
                        mysqli_stmt_execute($link);
                    }
                    header("Location: members.php?msg=" . urlencode("Membership renewed — awaiting payment before it activates."));
                    exit;
                } else {
                    $error_msg = "Renewal failed. Please try again.";
                }
            }
        }
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?>
    <div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="gym-card p-4" style="max-width:650px;">
    <h5>Renew: <?php echo htmlspecialchars($member['full_name']); ?></h5>
    <p class="text-muted small">Current expiry: <?php echo htmlspecialchars($member['membership_expiry_date']); ?></p>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Duration (months)</label>
            <select name="duration_months" class="form-select duration-select">
                <option value="1" <?php echo $member['duration_months']==1?'selected':''; ?>>1</option>
                <option value="3" <?php echo $member['duration_months']==3?'selected':''; ?>>3</option>
                <option value="6" <?php echo $member['duration_months']==6?'selected':''; ?>>6</option>
                <option value="12" <?php echo $member['duration_months']==12?'selected':''; ?>>12</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Select Batches (max <?php echo $max_batches; ?>) — non-overlapping only</label>
            <div data-batch-picker data-max-batches="<?php echo $max_batches; ?>" class="row">
                <?php foreach (['Morning','Evening'] as $slot): if (empty($grouped[$slot])) continue; ?>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase mt-2"><?php echo $slot; ?></h6>
                        <?php foreach ($grouped[$slot] as $b): ?>
                            <div class="form-check batch-check">
                                <input class="form-check-input batch-check-input" type="checkbox"
                                       name="batch_ids[]" value="<?php echo $b['batch_id']; ?>"
                                       id="renew_batch_<?php echo $b['batch_id']; ?>"
                                       data-start="<?php echo $b['start_time']; ?>"
                                       data-end="<?php echo $b['end_time']; ?>"
                                       data-fee="<?php echo $b['monthly_fee']; ?>"
                                       data-name="<?php echo htmlspecialchars($b['batch_name'], ENT_QUOTES); ?>"
                                       <?php echo in_array($b['batch_id'], $selected) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="renew_batch_<?php echo $b['batch_id']; ?>">
                                    <?php echo htmlspecialchars($b['batch_name']); ?>
                                    <span class="text-muted">(<?php echo substr($b['start_time'],0,5) . "-" . substr($b['end_time'],0,5); ?>)</span>
                                    <span class="d-block small text-danger fw-semibold">Rs. <?php echo number_format($b['monthly_fee'], 2); ?> / month</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-3">
            <div class="payment-breakdown" id="paymentBreakdown" style="display:none;">
                <h6>Estimated Payment</h6>
                <ul id="breakdownList"></ul>
                <div class="d-flex justify-content-between breakdown-total-row fw-bold">
                    <span>Total (<span id="breakdownDuration">1</span> month(s))</span>
                    <span>Rs. <span id="breakdownTotal">0.00</span></span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-gym-primary">Renew Membership</button>
        <a href="members.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
