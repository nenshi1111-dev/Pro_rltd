<?php
// ==========================================================
// admin/trainer/add-trainer.php — Add a new trainer
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Add Trainer";
$active_menu = "trainers";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim((isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $gender    = (isset($_POST['gender']) ? $_POST['gender'] : 'Male');
    $phone     = trim((isset($_POST['phone']) ? $_POST['phone'] : ''));
    $email     = trim((isset($_POST['email']) ? $_POST['email'] : ''));
    $spec      = trim((isset($_POST['specialization']) ? $_POST['specialization'] : ''));
    $username  = trim((isset($_POST['username']) ? $_POST['username'] : ''));
    $password  = trim((isset($_POST['password']) ? $_POST['password'] : ''));

    if ($full_name=='' || $phone=='' || $email=='' || $username=='' || $password=='') {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        $chk = mysqli_prepare($conn, "SELECT 1 FROM trainers WHERE username=? OR email=? OR phone=?");
        mysqli_stmt_bind_param($chk, "sss", $username, $email, $phone);
        mysqli_stmt_execute($chk);
        if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
            $error_msg = "Username already exists, or email/phone already in use.";
        } else {
            $res = mysqli_query($conn, "SELECT trainer_id FROM trainers ORDER BY trainer_id DESC LIMIT 1");
            $last = mysqli_fetch_assoc($res);
            $next_id = $last ? $last['trainer_id'] + 1 : 1;
            $trainer_code = "TR" . str_pad($next_id, 4, "0", STR_PAD_LEFT);
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO trainers (trainer_code, full_name, gender, phone, email, specialization, username, password, status) VALUES (?,?,?,?,?,?,?,?,'Active')");
            mysqli_stmt_bind_param($stmt, "ssssssss", $trainer_code, $full_name, $gender, $phone, $email, $spec, $username, $hashed);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: trainers.php?msg=" . urlencode("Trainer added successfully."));
                exit;
            } else {
                $error_msg = "Could not add trainer. Please try again.";
            }
        }
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

<div class="gym-card p-4">
    <form method="POST">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Gender</label>
                <select name="gender" class="form-select"><option>Male</option><option>Female</option><option>Other</option></select>
            </div>
            <div class="col-md-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="pw2" class="form-control" required minlength="6">
                    <i class="bi bi-eye toggle-password" data-target="pw2"></i>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-gym-primary mt-4">Add Trainer</button>
        <a href="trainers.php" class="btn btn-outline-secondary mt-4">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
