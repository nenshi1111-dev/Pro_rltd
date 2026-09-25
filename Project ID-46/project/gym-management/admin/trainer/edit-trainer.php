<?php
// ==========================================================
// admin/trainer/edit-trainer.php — Edit an existing trainer
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Edit Trainer";
$active_menu = "trainers";
$error_msg = "";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM trainers WHERE trainer_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$trainer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trainer) {
    header("Location: trainers.php?err=" . urlencode("Trainer not found."));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim((isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $gender    = (isset($_POST['gender']) ? $_POST['gender'] : 'Male');
    $phone     = trim((isset($_POST['phone']) ? $_POST['phone'] : ''));
    $email     = trim((isset($_POST['email']) ? $_POST['email'] : ''));
    $spec      = trim((isset($_POST['specialization']) ? $_POST['specialization'] : ''));
    $status    = (isset($_POST['status']) ? $_POST['status'] : 'Active');

    if ($full_name=='' || $phone=='' || $email=='') {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        $chk = mysqli_prepare($conn, "SELECT 1 FROM trainers WHERE (email=? OR phone=?) AND trainer_id<>?");
        mysqli_stmt_bind_param($chk, "ssi", $email, $phone, $id);
        mysqli_stmt_execute($chk);
        if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
            $error_msg = "Email or phone already used by another trainer.";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE trainers SET full_name=?, gender=?, phone=?, email=?, specialization=?, status=? WHERE trainer_id=?");
            mysqli_stmt_bind_param($stmt, "ssssssi", $full_name, $gender, $phone, $email, $spec, $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: trainers.php?msg=" . urlencode("Trainer updated successfully."));
                exit;
            } else {
                $error_msg = "Update failed. Please try again.";
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
            <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($trainer['full_name']); ?>"></div>
            <div class="col-md-3"><label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option <?php echo $trainer['gender']==$g?'selected':''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option <?php echo $trainer['status']=='Active'?'selected':''; ?>>Active</option>
                    <option <?php echo $trainer['status']=='Inactive'?'selected':''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($trainer['phone']); ?>"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($trainer['email']); ?>"></div>
            <div class="col-md-12"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($trainer['specialization']); ?>"></div>
        </div>
        <button type="submit" class="btn btn-gym-primary mt-4">Save Changes</button>
        <a href="trainers.php" class="btn btn-outline-secondary mt-4">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
