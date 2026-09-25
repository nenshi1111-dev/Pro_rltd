<?php
// ==========================================================
// admin/request/renewable_request/approve-request.php
// Approving: replaces the member's batch selection with what
// they requested, extends duration/expiry, and sets status to
// 'Pending Payment' (not straight back to Active) — the new
// cycle's batches and dates are reserved immediately, but the
// member is locked out of their dashboard until Admin logs a
// payment covering the new amount due. Re-validates overlap
// and capacity at approval time (schedules may have changed
// since the member submitted the request).
// ==========================================================
require_once "../../../includes/config.php";
require_once "../../../includes/batch-functions.php";
require_once "../../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

$stmt = mysqli_prepare($conn, "SELECT * FROM renewal_requests WHERE renewal_id=? AND status='Pending'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$r) {
    header("Location: requests.php?err=" . urlencode("Request not found or already processed."));
    exit;
}

// Requested batches for this renewal
$batch_ids = [];
$b_stmt = mysqli_prepare($conn, "SELECT batch_id FROM renewal_request_batches WHERE renewal_id=?");
mysqli_stmt_bind_param($b_stmt, "i", $id);
mysqli_stmt_execute($b_stmt);
$b_res = mysqli_stmt_get_result($b_stmt);
while ($row = mysqli_fetch_assoc($b_res)) { $batch_ids[] = (int)$row['batch_id']; }

if (empty($batch_ids)) {
    header("Location: requests.php?err=" . urlencode("This request has no batches selected — cannot approve."));
    exit;
}

// Re-check overlap
$conflict = find_batch_conflict($conn, $batch_ids);
if ($conflict) {
    header("Location: requests.php?err=" . urlencode("Cannot approve — \"{$conflict[0]}\" and \"{$conflict[1]}\" now overlap in timing."));
    exit;
}

// Member's current batches (so we don't wrongly count their own seat as "full")
$current = [];
$c_stmt = mysqli_prepare($conn, "SELECT batch_id FROM member_batches WHERE member_id=?");
mysqli_stmt_bind_param($c_stmt, "i", $r['member_id']);
mysqli_stmt_execute($c_stmt);
$c_res = mysqli_stmt_get_result($c_stmt);
while ($row = mysqli_fetch_assoc($c_res)) { $current[] = (int)$row['batch_id']; }

// Re-check capacity for any newly-requested batch
foreach ($batch_ids as $bid) {
    if (in_array($bid, $current)) continue; // already in it, not a new seat
    $cap_q = mysqli_prepare($conn, "SELECT b.capacity, b.batch_name, (SELECT COUNT(*) FROM member_batches WHERE batch_id=b.batch_id) AS filled FROM batches b WHERE b.batch_id=?");
    mysqli_stmt_bind_param($cap_q, "i", $bid);
    mysqli_stmt_execute($cap_q);
    $cap = mysqli_fetch_assoc(mysqli_stmt_get_result($cap_q));
    if ($cap && $cap['filled'] >= $cap['capacity']) {
        header("Location: requests.php?err=" . urlencode("Cannot approve — \"{$cap['batch_name']}\" is now full."));
        exit;
    }
}

$new_start  = date('Y-m-d');
$new_expiry = date('Y-m-d', strtotime("+{$r['requested_duration']} months"));

$upd = mysqli_prepare($conn, "UPDATE members SET duration_months=?, membership_start_date=?, membership_expiry_date=?, status='Pending Payment' WHERE member_id=?");
mysqli_stmt_bind_param($upd, "issi", $r['requested_duration'], $new_start, $new_expiry, $r['member_id']);

if (mysqli_stmt_execute($upd)) {
    // Replace the member's batch selection with what was requested
    $del = mysqli_prepare($conn, "DELETE FROM member_batches WHERE member_id=?");
    mysqli_stmt_bind_param($del, "i", $r['member_id']);
    mysqli_stmt_execute($del);

    foreach ($batch_ids as $bid) {
        $link = mysqli_prepare($conn, "INSERT INTO member_batches (member_id, batch_id) VALUES (?,?)");
        mysqli_stmt_bind_param($link, "ii", $r['member_id'], $bid);
        mysqli_stmt_execute($link);
    }

    $mark = mysqli_prepare($conn, "UPDATE renewal_requests SET status='Approved', approved_date=NOW(), approved_by=? WHERE renewal_id=?");
    mysqli_stmt_bind_param($mark, "ii", $_SESSION['admin_id'], $id);
    mysqli_stmt_execute($mark);

    header("Location: requests.php?msg=" . urlencode("Renewal approved — awaiting payment before it activates."));
} else {
    header("Location: requests.php?err=" . urlencode("Could not approve renewal. Please try again."));
}
exit;
?>
