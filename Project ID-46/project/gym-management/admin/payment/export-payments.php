<?php
// ==========================================================
// admin/payment/export-payments.php — CSV export
// Streams a CSV file directly to the browser using PHP's
// built-in fputcsv() — no library needed, works on any PHP
// version. Respects the same search filter as payments.php.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$search = trim((isset($_GET['q']) ? $_GET['q'] : ''));
$base_select = "SELECT p.receipt_number, m.member_code, m.full_name, p.amount, p.payment_date, p.payment_method, p.notes
    FROM payments p JOIN members m ON p.member_id = m.member_id";

if ($search !== '') {
    $like = "%$search%";
    $stmt = mysqli_prepare($conn, "$base_select WHERE m.full_name LIKE ? OR m.member_code LIKE ? OR p.receipt_number LIKE ? ORDER BY p.payment_date DESC");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, "$base_select ORDER BY p.payment_date DESC");
}

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=payments_" . date('Y-m-d') . ".csv");

$out = fopen("php://output", "w");
fputcsv($out, array("Receipt No.", "Member Code", "Member Name", "Amount", "Payment Date", "Method", "Notes"));

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, array(
        $row['receipt_number'],
        $row['member_code'],
        $row['full_name'],
        $row['amount'],
        $row['payment_date'],
        $row['payment_method'],
        $row['notes']
    ));
}
fclose($out);
exit;
?>
