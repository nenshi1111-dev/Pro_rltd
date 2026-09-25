<?php
// ==========================================================
// admin/settings/settings.php — Admin change password
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Settings";
$active_menu = "settings";
$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = trim((isset($_POST['current_password']) ? $_POST['current_password'] : ''));
    $new_pw   = trim((isset($_POST['new_password']) ? $_POST['new_password'] : ''));
    $confirm  = trim((isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''));

    $stmt = mysqli_prepare($conn, "SELECT admin_password FROM admins WHERE admin_id=?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['admin_id']);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$admin || !password_verify($current, $admin['admin_password'])) {
        $error_msg = "Current password is incorrect.";
    } elseif (strlen($new_pw) < 6) {
        $error_msg = "New password must be at least 6 characters.";
    } elseif ($new_pw !== $confirm) {
        $error_msg = "New password and confirmation do not match.";
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE admins SET admin_password=? WHERE admin_id=?");
        mysqli_stmt_bind_param($upd, "si", $hashed, $_SESSION['admin_id']);
        if (mysqli_stmt_execute($upd)) {
            $success_msg = "Password changed successfully.";
        } else {
            $error_msg = "Could not update password. Please try again.";
        }
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
<?php if ($success_msg): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>

<div class="gym-card p-4" style="max-width:500px;">
    <h5 class="mb-3">Change Password</h5>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <div class="password-wrapper">
                <input type="password" name="current_password" id="cp1" class="form-control" required>
                <i class="bi bi-eye toggle-password" data-target="cp1"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="password-wrapper">
                <input type="password" name="new_password" id="cp2" class="form-control" required minlength="6">
                <i class="bi bi-eye toggle-password" data-target="cp2"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="cp3" class="form-control" required minlength="6">
                <i class="bi bi-eye toggle-password" data-target="cp3"></i>
            </div>
        </div>
        <button type="submit" class="btn btn-gym-primary">Update Password</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
