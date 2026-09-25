<?php
// ==========================================================
// admin/trainer/delete-trainer.php — Delete a trainer
// batch_trainers has ON DELETE CASCADE, so the trainer's batch
// assignments are cleaned up automatically. Batches themselves
// are NOT deleted — only the assignment link.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

$stmt = mysqli_prepare($conn, "DELETE FROM trainers WHERE trainer_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: trainers.php?msg=" . urlencode("Trainer deleted successfully."));
} else {
    header("Location: trainers.php?err=" . urlencode("Could not delete trainer."));
}
exit;
?>
