<?php
// ==========================================================
// trainer/export-attendance.php — CSV export of a batch's
// attendance history. Ownership enforced — trainer can only
// export a batch actually assigned to them.
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$trainer_id = $_SESSION['trainer_id'];
$batch_id = (int)((isset($_GET['batch_id']) ? $_GET['batch_id'] : 0));

$own = mysqli_prepare($conn, "SELECT b.batch_name FROM batch_trainers bt JOIN batches b ON bt.batch_id=b.batch_id WHERE bt.trainer_id=? AND bt.batch_id=?");
mysqli_stmt_bind_param($own, "ii", $trainer_id, $batch_id);
mysqli_stmt_execute($own);
$batch = mysqli_fetch_assoc(mysqli_stmt_get_result($own));

if (!$batch) {
    die("You do not have access to that batch's attendance.");
}

$stmt = mysqli_prepare($conn, "
    SELECT a.date, m.member_code, m.full_name, a.status
    FROM attendance a
    JOIN members m ON a.member_id = m.member_id
    WHERE a.batch_id = ?
    ORDER BY a.date DESC, m.full_name
");
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$filename = "attendance_" . preg_replace('/[^a-zA-Z0-9]+/', '_', $batch['batch_name']) . "_" . date('Y-m-d') . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");

$out = fopen("php://output", "w");
fputcsv($out, array("Date", "Member Code", "Member Name", "Status"));
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, array($row['date'], $row['member_code'], $row['full_name'], $row['status']));
}
fclose($out);
exit;
?>
