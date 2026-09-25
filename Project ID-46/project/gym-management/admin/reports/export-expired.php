<?php
// ==========================================================
// admin/reports/export-expired.php — CSV export of expired-memberships.php
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$res = mysqli_query($conn, "
    SELECT member_code, full_name, phone, email, membership_expiry_date,
           DATEDIFF(CURDATE(), membership_expiry_date) AS days_expired
    FROM members
    WHERE status = 'Expired'
    ORDER BY membership_expiry_date DESC
");

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=expired_memberships_" . date('Y-m-d') . ".csv");

$out = fopen("php://output", "w");
fputcsv($out, array("Member Code", "Name", "Phone", "Email", "Expired On", "Days Since Expired"));
while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($out, array(
        $row['member_code'], $row['full_name'], $row['phone'], $row['email'],
        $row['membership_expiry_date'], $row['days_expired']
    ));
}
fclose($out);
exit;
?>
