<?php
// ==========================================================
// admin/request/registration_request/reject-request.php
// ==========================================================
require_once "../../../includes/config.php";
require_once "../../includes/auth.php";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

$stmt = mysqli_prepare($conn, "UPDATE requested_members SET status='Rejected', approved_date=NOW(), approved_by=? WHERE request_id=? AND status='Pending'");
mysqli_stmt_bind_param($stmt, "ii", $_SESSION['admin_id'], $id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    header("Location: requests.php?msg=" . urlencode("Registration request rejected."));
} else {
    header("Location: requests.php?err=" . urlencode("Could not reject request (already processed?)."));
}
exit;
?>
