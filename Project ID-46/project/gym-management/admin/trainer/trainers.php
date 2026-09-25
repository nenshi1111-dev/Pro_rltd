<?php
// ==========================================================
// admin/trainer/trainers.php — Trainer list
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Trainers";
$active_menu = "trainers";

$trainers = mysqli_query($conn, "SELECT * FROM trainers ORDER BY trainer_id DESC");

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($_GET['err']); ?></div><?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <a href="add-trainer.php" class="btn btn-gym-primary"><i class="bi bi-plus-circle me-1"></i>Add Trainer</a>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Specialization</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while ($t = mysqli_fetch_assoc($trainers)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['trainer_code']); ?></td>
                    <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($t['phone']); ?></td>
                    <td><?php echo htmlspecialchars($t['specialization']); ?></td>
                    <td><span class="badge <?php echo $t['status']=='Active'?'badge-active':'badge-inactive'; ?>"><?php echo htmlspecialchars($t['status']); ?></span></td>
                    <td class="text-nowrap">
                        <a href="view-trainer.php?id=<?php echo $t['trainer_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <a href="edit-trainer.php?id=<?php echo $t['trainer_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="delete-trainer.php?id=<?php echo $t['trainer_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this trainer?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
