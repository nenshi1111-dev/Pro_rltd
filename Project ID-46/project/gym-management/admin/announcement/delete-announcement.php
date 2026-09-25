<?php
// ==========================================================
// admin/announcement/delete-announcement.php — Delete an
// announcement. Admin can delete any announcement (Admin or
// Trainer posted); trainers can only delete their own (see
// trainer/announcements.php).
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

$stmt = mysqli_prepare($conn, "DELETE FROM announcements WHERE announcement_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: announcements.php?msg=" . urlencode("Announcement deleted successfully."));
} else {
    header("Location: announcements.php?err=" . urlencode("Could not delete announcement."));
}
exit;
?>
