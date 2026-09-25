<?php
// ==========================================================
// admin/batch/delete-batch.php — Delete a batch
// Business rule: cannot delete a batch if members are assigned.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

// Check for assigned members first
$chk = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM member_batches WHERE batch_id=?");
mysqli_stmt_bind_param($chk, "i", $id);
mysqli_stmt_execute($chk);
$count = mysqli_fetch_assoc(mysqli_stmt_get_result($chk))['c'];

if ($count > 0) {
    header("Location: batches.php?err=" . urlencode("Cannot delete batch — members are still assigned to it."));
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM batches WHERE batch_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: batches.php?msg=" . urlencode("Batch deleted successfully."));
} else {
    header("Location: batches.php?err=" . urlencode("Could not delete batch."));
}
exit;
?>
