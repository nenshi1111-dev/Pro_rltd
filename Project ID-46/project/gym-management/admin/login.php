<?php
// ==========================================================
// admin/login.php — Admin Login
// ==========================================================
require_once "../includes/config.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((isset($_POST['username']) ? $_POST['username'] : ''));
    $password = trim((isset($_POST['password']) ? $_POST['password'] : ''));

    if ($username === '' || $password === '') {
        $error_msg = "Please enter both username and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT admin_id, admin_username, admin_password FROM admins WHERE admin_username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        if ($admin && password_verify($password, $admin['admin_password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['admin_username'];
            header("Location: index.php");
            exit;
        } else {
            $error_msg = "Invalid login credentials.";
        }
    }
}

$page_title = "Admin Login";
include "../includes/header.php"; // public header (plain HTML shell) is fine pre-login
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
            <h4 class="mt-2">Admin Login</h4>
        </div>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="adminPassword" class="form-control" required>
                    <i class="bi bi-eye toggle-password" data-target="adminPassword"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-gym-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3 small"><a href="<?php echo BASE_URL; ?>settings.php">Back to staff login selection</a></p>
    </div>
</div>
<?php include "../includes/footer.php"; ?>
