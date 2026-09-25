<?php
// ==========================================================
// admin/payment/member-balance.php — JSON endpoint
// Returns { due, paid, pending } for a given member_id.
// Used only by the small JS hint on add-payment.php — not a
// page a person navigates to directly.
// ==========================================================
require_once "../../includes/config.php";
require_once "../../includes/payment-functions.php";
require_once "../includes/auth.php";

header("Content-Type: application/json");

$member_id = (int)((isset($_GET['member_id']) ? $_GET['member_id'] : 0));

if ($member_id <= 0) {
    echo json_encode(array("due" => "0.00", "paid" => "0.00", "pending" => "0.00"));
    exit;
}

$due = get_member_amount_due($conn, $member_id);
$paid = get_member_amount_paid($conn, $member_id);
$pending = get_member_pending_balance($conn, $member_id);

echo json_encode(array(
    "due" => number_format($due, 2, '.', ''),
    "paid" => number_format($paid, 2, '.', ''),
    "pending" => number_format($pending, 2, '.', '')
));
?>
