<?php
// ==========================================================
// admin/member/delete-member.php — Delete a member
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

$stmt = mysqli_prepare($conn, "DELETE FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: members.php?msg=" . urlencode("Member deleted successfully."));
} else {
    header("Location: members.php?err=" . urlencode("Could not delete member."));
}
exit;
?>
