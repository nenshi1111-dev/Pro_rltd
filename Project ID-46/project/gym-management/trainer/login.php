<?php
// ==========================================================
// trainer/login.php — Trainer Login
// ==========================================================
require_once "../includes/config.php";

if (isset($_SESSION['trainer_id'])) {
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
        $stmt = mysqli_prepare($conn, "SELECT trainer_id, full_name, username, password, status FROM trainers WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $trainer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($trainer && password_verify($password, $trainer['password'])) {
            if ($trainer['status'] !== 'Active') {
                $error_msg = "Your trainer account is inactive. Please contact Admin.";
            } else {
                $_SESSION['trainer_id'] = $trainer['trainer_id'];
                $_SESSION['trainer_name'] = $trainer['full_name'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error_msg = "Invalid login credentials.";
        }
    }
}

$page_title = "Trainer Login";
include "../includes/header.php";
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
            <h4 class="mt-2">Trainer Login</h4>
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
                    <input type="password" name="password" id="trainerPassword" class="form-control" required>
                    <i class="bi bi-eye toggle-password" data-target="trainerPassword"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-gym-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3 small"><a href="<?php echo BASE_URL; ?>settings.php">Back to staff login selection</a></p>
    </div>
</div>
<?php include "../includes/footer.php"; ?>
