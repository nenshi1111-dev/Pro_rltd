<?php
// ==========================================================
// member/change-password.php — Member changes own password
// ==========================================================
$allow_restricted = true; // an expired member should still be able to secure their account
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Change Password";
$active_menu = "settings";
$member_id = $_SESSION['member_id'];
$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = trim((isset($_POST['current_password']) ? $_POST['current_password'] : ''));
    $new_pw   = trim((isset($_POST['new_password']) ? $_POST['new_password'] : ''));
    $confirm  = trim((isset($_POST['confirm_password']) ? $_POST['confirm_password'] : ''));

    $stmt = mysqli_prepare($conn, "SELECT password FROM members WHERE member_id=?");
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    $member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$member || !password_verify($current, $member['password'])) {
        $error_msg = "Current password is incorrect.";
    } elseif (strlen($new_pw) < 6) {
        $error_msg = "New password must be at least 6 characters.";
    } elseif ($new_pw !== $confirm) {
        $error_msg = "New password and confirmation do not match.";
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE members SET password=? WHERE member_id=?");
        mysqli_stmt_bind_param($upd, "si", $hashed, $member_id);
        if (mysqli_stmt_execute($upd)) {
            $success_msg = "Password changed successfully.";
        } else {
            $error_msg = "Could not update password. Please try again.";
        }
    }
}

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
<?php if ($success_msg): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>

<div class="gym-card p-4" style="max-width:500px;">
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <div class="password-wrapper">
                <input type="password" name="current_password" id="mp1" class="form-control" required>
                <i class="bi bi-eye toggle-password" data-target="mp1"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="password-wrapper">
                <input type="password" name="new_password" id="mp2" class="form-control" required minlength="6">
                <i class="bi bi-eye toggle-password" data-target="mp2"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="mp3" class="form-control" required minlength="6">
                <i class="bi bi-eye toggle-password" data-target="mp3"></i>
            </div>
        </div>
        <button type="submit" class="btn btn-gym-primary">Update Password</button>
    </form>
</div>

<?php include "includes/footer.php"; ?>
