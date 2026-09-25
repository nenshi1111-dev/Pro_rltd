<?php
// ==========================================================
// admin/member/export-members.php — CSV export of member list
// Respects the same search filter as members.php.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$search = trim((isset($_GET['q']) ? $_GET['q'] : ''));
$base_select = "SELECT m.member_code, m.full_name, m.phone, m.email, m.status,
    m.membership_start_date, m.membership_expiry_date,
    GROUP_CONCAT(b.batch_name ORDER BY b.start_time SEPARATOR ', ') AS batch_names
    FROM members m
    LEFT JOIN member_batches mb ON mb.member_id = m.member_id
    LEFT JOIN batches b ON b.batch_id = mb.batch_id";

if ($search !== '') {
    $like = "%$search%";
    $stmt = mysqli_prepare($conn, "$base_select WHERE m.full_name LIKE ? OR m.member_code LIKE ? GROUP BY m.member_id ORDER BY m.member_id DESC");
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, "$base_select GROUP BY m.member_id ORDER BY m.member_id DESC");
}

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=members_" . date('Y-m-d') . ".csv");

$out = fopen("php://output", "w");
fputcsv($out, array("Member Code", "Name", "Phone", "Email", "Status", "Start Date", "Expiry Date", "Batches"));
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, array(
        $row['member_code'], $row['full_name'], $row['phone'], $row['email'], $row['status'],
        $row['membership_start_date'], $row['membership_expiry_date'], $row['batch_names']
    ));
}
fclose($out);
exit;
?>
