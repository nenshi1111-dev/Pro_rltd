<?php
// ==========================================================
// login.php — Member login (public root)
// ==========================================================
require_once "includes/config.php";

// If already logged in as a member, go straight to dashboard
if (isset($_SESSION['member_id'])) {
    header("Location: member/index.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((isset($_POST['username']) ? $_POST['username'] : ''));
    $password = trim((isset($_POST['password']) ? $_POST['password'] : ''));

    if ($username === '' || $password === '') {
        $error_msg = "Please enter both username and password.";
    } else {
        // Prepared statement — safe from SQL injection
        $stmt = mysqli_prepare($conn, "SELECT member_id, full_name, username, password, status FROM members WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $member = mysqli_fetch_assoc($result);

        if ($member && password_verify($password, $member['password'])) {
            // Correct credentials — start session
            $_SESSION['member_id'] = $member['member_id'];
            $_SESSION['member_name'] = $member['full_name'];

            // Expired/Inactive members are redirected inside member/index.php,
            // not blocked here, so their session still exists to view the
            // Expired/Renewal pages.
            header("Location: member/index.php");
            exit;
        } else {
            $error_msg = "Invalid login credentials.";
        }
    }
}

$page_title = "Member Login";
$active_page = "";
include "includes/header.php";
include "includes/navbar.php";
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
            <h4 class="mt-2">Member Login</h4>
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
                    <input type="password" name="password" id="loginPassword" class="form-control" required>
                    <i class="bi bi-eye toggle-password" data-target="loginPassword"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-gym-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3 small">
            Not a member yet? <a href="register.php">Register here</a>
        </p>
        <p class="text-center small">
            <a href="settings.php">Admin / Trainer login</a>
        </p>
    </div>
</div>

<?php include "includes/footer.php"; ?>
