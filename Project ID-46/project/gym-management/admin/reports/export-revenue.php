<?php
// ==========================================================
// admin/reports/export-revenue.php — CSV export of revenue.php
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$res = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
    FROM payments
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
");

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=revenue_report_" . date('Y-m-d') . ".csv");

$out = fopen("php://output", "w");
fputcsv($out, array("Month", "Revenue (Rs.)"));
while ($row = mysqli_fetch_assoc($res)) {
    $label = date('M Y', strtotime($row['ym'] . '-01'));
    fputcsv($out, array($label, number_format($row['total'], 2, '.', '')));
}
fclose($out);
exit;
?>
