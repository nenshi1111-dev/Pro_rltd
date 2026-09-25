<?php
// ==========================================================
// admin/trainer/view-trainer.php — Read-only trainer profile
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "View Trainer";
$active_menu = "trainers";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM trainers WHERE trainer_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$trainer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trainer) {
    header("Location: trainers.php?err=" . urlencode("Trainer not found."));
    exit;
}

// Batches this trainer is assigned to
$stmt2 = mysqli_prepare($conn, "SELECT b.batch_id, b.batch_name, b.start_time, b.end_time FROM batch_trainers bt JOIN batches b ON bt.batch_id=b.batch_id WHERE bt.trainer_id=?");
mysqli_stmt_bind_param($stmt2, "i", $id);
mysqli_stmt_execute($stmt2);
$batches = mysqli_stmt_get_result($stmt2);

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="gym-card p-4">
    <h4><?php echo htmlspecialchars($trainer['full_name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($trainer['trainer_code']); ?>)</small></h4>
    <table class="table table-borderless mt-3">
        <tr><th style="width:220px;">Gender</th><td><?php echo htmlspecialchars($trainer['gender']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($trainer['phone']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($trainer['email']); ?></td></tr>
        <tr><th>Specialization</th><td><?php echo htmlspecialchars($trainer['specialization']); ?></td></tr>
        <tr><th>Status</th><td><span class="badge <?php echo $trainer['status']=='Active'?'badge-active':'badge-inactive'; ?>"><?php echo htmlspecialchars($trainer['status']); ?></span></td></tr>
    </table>

    <h6 class="mt-4">Assigned Batches</h6>
    <ul class="list-group mb-3">
        <?php $found = false; while ($b = mysqli_fetch_assoc($batches)): $found = true; ?>
            <li class="list-group-item"><?php echo htmlspecialchars($b['batch_name']) . " (" . substr($b['start_time'],0,5) . " - " . substr($b['end_time'],0,5) . ")"; ?></li>
        <?php endwhile; if (!$found): ?>
            <li class="list-group-item text-muted">No batches assigned yet.</li>
        <?php endif; ?>
    </ul>

    <a href="edit-trainer.php?id=<?php echo $trainer['trainer_id']; ?>" class="btn btn-gym-primary">Edit</a>
    <a href="trainers.php" class="btn btn-outline-secondary">Back to List</a>
</div>

<?php include "../includes/footer.php"; ?>
