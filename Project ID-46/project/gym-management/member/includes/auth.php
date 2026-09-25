<?php
// ==========================================================
// member/includes/auth.php
// Session guard for every protected Member page.
// A member whose status isn't 'Active' is redirected to one of
// two dedicated pages depending on WHY:
//   - 'Pending Payment' -> pending-payment.php (awaiting Admin
//     to log a payment that covers the amount due)
//   - 'Expired' / 'Inactive' -> expired.php (membership lapsed,
//     needs renewal)
// $allow_restricted can be set to true by a page BEFORE including
// this file if that page should be reachable regardless of which
// restricted state the member is in (e.g. change-password.php,
// renew-status.php, logout.php).
// ==========================================================
require_once __DIR__ . "/../../includes/payment-functions.php";

if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($allow_restricted)) {
    $allow_restricted = false;
}

// If they were awaiting payment and it's now been covered
// (Admin may have logged it just now, or earlier in another
// tab), flip them back to Active before doing anything else.
sync_member_payment_status($conn, $_SESSION['member_id']);

// ---- Auto-expire check ----
// The stored 'status' column does NOT change by itself when the
// clock passes membership_expiry_date. So on every page load we
// compare the expiry date to today and, if it has passed while
// status is still 'Active', we flip it to 'Expired' right here.
// This means: to TEST expiry, you don't need to wait — just edit
// membership_expiry_date to a past date (e.g. yesterday) in
// phpMyAdmin, then reload any member page. No cron job needed.
$stmt = mysqli_prepare($conn, "SELECT status, membership_expiry_date FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['member_id']);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($row) {
    $expiry = strtotime($row['membership_expiry_date']);
    $today  = strtotime(date('Y-m-d'));

    if ($row['status'] === 'Active' && $expiry < $today) {
        $upd = mysqli_prepare($conn, "UPDATE members SET status='Expired' WHERE member_id=?");
        mysqli_stmt_bind_param($upd, "i", $_SESSION['member_id']);
        mysqli_stmt_execute($upd);
        $row['status'] = 'Expired'; // reflect the change for this request too
    }

    if (!$allow_restricted && $row['status'] !== 'Active') {
        if ($row['status'] === 'Pending Payment') {
            header("Location: pending-payment.php");
        } else {
            header("Location: expired.php");
        }
        exit;
    }
}
?>
