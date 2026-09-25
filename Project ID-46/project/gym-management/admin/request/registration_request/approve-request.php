<?php
// ==========================================================
// admin/request/registration_request/approve-request.php
// Approving a request: creates the real "members" row, links
// every batch they requested via member_batches, then marks
// the request as Approved. This is the ONLY way a visitor
// becomes a member via Method 1 (self-registration).
// Everything is re-validated here (uniqueness, overlap,
// capacity) in case anything changed since the request was
// first submitted.
// ==========================================================
require_once "../../../includes/config.php";
require_once "../../../includes/batch-functions.php";
require_once "../../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

$stmt = mysqli_prepare($conn, "SELECT * FROM requested_members WHERE request_id=? AND status='Pending'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$r) {
    header("Location: requests.php?err=" . urlencode("Request not found or already processed."));
    exit;
}

// Requested batches for this request
$batch_ids = [];
$b_stmt = mysqli_prepare($conn, "SELECT batch_id FROM requested_member_batches WHERE request_id=?");
mysqli_stmt_bind_param($b_stmt, "i", $id);
mysqli_stmt_execute($b_stmt);
$b_res = mysqli_stmt_get_result($b_stmt);
while ($row = mysqli_fetch_assoc($b_res)) { $batch_ids[] = (int)$row['batch_id']; }

if (empty($batch_ids)) {
    header("Location: requests.php?err=" . urlencode("This request has no batches selected — cannot approve."));
    exit;
}

// Re-check uniqueness at approval time
$chk = mysqli_prepare($conn, "SELECT 1 FROM members WHERE username=? OR email=? OR phone=?");
mysqli_stmt_bind_param($chk, "sss", $r['username'], $r['email'], $r['phone']);
mysqli_stmt_execute($chk);
if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
    header("Location: requests.php?err=" . urlencode("Cannot approve — username/email/phone now conflicts with an existing member."));
    exit;
}

// Re-check overlap (times may have changed since the request was submitted)
$conflict = find_batch_conflict($conn, $batch_ids);
if ($conflict) {
    header("Location: requests.php?err=" . urlencode("Cannot approve — \"{$conflict[0]}\" and \"{$conflict[1]}\" now overlap in timing."));
    exit;
}

// Re-check capacity for every requested batch
foreach ($batch_ids as $bid) {
    $cap_q = mysqli_prepare($conn, "SELECT b.capacity, b.batch_name, (SELECT COUNT(*) FROM member_batches WHERE batch_id=b.batch_id) AS filled FROM batches b WHERE b.batch_id=?");
    mysqli_stmt_bind_param($cap_q, "i", $bid);
    mysqli_stmt_execute($cap_q);
    $cap = mysqli_fetch_assoc(mysqli_stmt_get_result($cap_q));
    if ($cap && $cap['filled'] >= $cap['capacity']) {
        header("Location: requests.php?err=" . urlencode("Cannot approve — \"{$cap['batch_name']}\" is now full."));
        exit;
    }
}

// Generate next member_code
$res = mysqli_query($conn, "SELECT member_id FROM members ORDER BY member_id DESC LIMIT 1");
$last = mysqli_fetch_assoc($res);
$next_id = $last ? $last['member_id'] + 1 : 1;
$member_code = "GM" . str_pad($next_id, 4, "0", STR_PAD_LEFT);

$start_date = date('Y-m-d');
$expiry_date = date('Y-m-d', strtotime("+{$r['duration']} months"));

$stmt2 = mysqli_prepare($conn, "INSERT INTO members
    (member_code, full_name, gender, dob, phone, email, address, emergency_contact,
     duration_months, membership_start_date, membership_expiry_date,
     username, password, status, created_by, created_method)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending Payment',?,'Request')");
mysqli_stmt_bind_param($stmt2, "ssssssssisssss",
    $member_code, $r['full_name'], $r['gender'], $r['dob'], $r['phone'], $r['email'], $r['address'], $r['emergency_contact'],
    $r['duration'], $start_date, $expiry_date,
    $r['username'], $r['password'], $_SESSION['admin_name']);

if (mysqli_stmt_execute($stmt2)) {
    $new_member_id = mysqli_insert_id($conn);
    foreach ($batch_ids as $bid) {
        $link = mysqli_prepare($conn, "INSERT INTO member_batches (member_id, batch_id) VALUES (?,?)");
        mysqli_stmt_bind_param($link, "ii", $new_member_id, $bid);
        mysqli_stmt_execute($link);
    }

    // Mark the request Approved
    $upd = mysqli_prepare($conn, "UPDATE requested_members SET status='Approved', approved_date=NOW(), approved_by=? WHERE request_id=?");
    mysqli_stmt_bind_param($upd, "ii", $_SESSION['admin_id'], $id);
    mysqli_stmt_execute($upd);

    header("Location: requests.php?msg=" . urlencode("Registration approved. Member account created ($member_code) — awaiting payment before it activates."));
} else {
    header("Location: requests.php?err=" . urlencode("Could not approve request. Please try again."));
}
exit;
?>
