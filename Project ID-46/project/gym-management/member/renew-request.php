<?php
// ==========================================================
// member/renew-request.php — Submit a renewal request
// Now means: re-pick batches (overlap + max enforced) and a
// duration. Reachable by BOTH Active members (early renewal /
// change batches) and Expired members (mandatory renewal).
// ==========================================================
$allow_restricted = true;
require_once "../includes/config.php";
require_once "../includes/batch-functions.php";
require_once "includes/auth.php";

$page_title = "Renewal Request";
$active_menu = "membership";
$member_id = $_SESSION['member_id'];
$error_msg = "";
$success_msg = "";
$max_batches = get_max_batches_per_member($conn);
$grouped = get_batches_grouped($conn, true);

// Block duplicate pending requests
$stmt2 = mysqli_prepare($conn, "SELECT status FROM renewal_requests WHERE member_id=? ORDER BY renewal_id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt2, "i", $member_id);
mysqli_stmt_execute($stmt2);
$latest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
$has_pending = $latest && $latest['status'] === 'Pending';

// Member's current batches, to pre-check the boxes
$current = [];
$c_stmt = mysqli_prepare($conn, "SELECT batch_id FROM member_batches WHERE member_id=?");
mysqli_stmt_bind_param($c_stmt, "i", $member_id);
mysqli_stmt_execute($c_stmt);
$c_res = mysqli_stmt_get_result($c_stmt);
while ($row = mysqli_fetch_assoc($c_res)) { $current[] = (int)$row['batch_id']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_pending) {
    $duration = (int)((isset($_POST['duration']) ? $_POST['duration'] : 1));
    $batch_ids = array_map('intval', (isset($_POST['batch_ids']) ? $_POST['batch_ids'] : []));

    if (empty($batch_ids)) {
        $error_msg = "Please select at least one batch.";
    } elseif (count($batch_ids) > $max_batches) {
        $error_msg = "You can select at most $max_batches batches.";
    } else {
        $conflict = find_batch_conflict($conn, $batch_ids);
        if ($conflict) {
            $error_msg = "\"{$conflict[0]}\" and \"{$conflict[1]}\" overlap in timing — please choose batches with different schedules.";
        } else {
            $stmt3 = mysqli_prepare($conn, "INSERT INTO renewal_requests (member_id, requested_duration, status) VALUES (?,?,'Pending')");
            mysqli_stmt_bind_param($stmt3, "ii", $member_id, $duration);
            if (mysqli_stmt_execute($stmt3)) {
                $renewal_id = mysqli_insert_id($conn);
                foreach ($batch_ids as $bid) {
                    $link = mysqli_prepare($conn, "INSERT INTO renewal_request_batches (renewal_id, batch_id) VALUES (?,?)");
                    mysqli_stmt_bind_param($link, "ii", $renewal_id, $bid);
                    mysqli_stmt_execute($link);
                }
                $success_msg = "Renewal request submitted! Please wait for Admin approval.";
                $has_pending = true;
            } else {
                $error_msg = "Could not submit request. Please try again.";
            }
        }
    }
}

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
<?php if ($success_msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>

<div class="gym-card p-4" style="max-width:650px;">
    <?php if ($has_pending): ?>
        <p>You already have a pending renewal request.</p>
        <a href="renew-status.php" class="btn btn-outline-dark">View Status</a>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Duration (months)</label>
                <select name="duration" class="form-select duration-select">
                    <option value="1">1</option><option value="3">3</option><option value="6">6</option><option value="12">12</option>
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
                                           id="mrenew_batch_<?php echo $b['batch_id']; ?>"
                                           data-start="<?php echo $b['start_time']; ?>"
                                           data-end="<?php echo $b['end_time']; ?>"
                                           data-fee="<?php echo $b['monthly_fee']; ?>"
                                           data-name="<?php echo htmlspecialchars($b['batch_name'], ENT_QUOTES); ?>"
                                           <?php echo in_array($b['batch_id'], $current) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mrenew_batch_<?php echo $b['batch_id']; ?>">
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

            <button type="submit" class="btn btn-gym-primary">Submit Request</button>
        </form>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
