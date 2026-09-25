<?php
// ==========================================================
// trainer/batch/my-batches.php — Only THIS trainer's batches
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "My Batches";
$active_menu = "batches";
$trainer_id = $_SESSION['trainer_id'];

$stmt = mysqli_prepare($conn, "
    SELECT b.*, COUNT(m.member_id) AS filled
    FROM batch_trainers bt
    JOIN batches b ON bt.batch_id = b.batch_id
    LEFT JOIN member_batches mb ON mb.batch_id = b.batch_id
    LEFT JOIN members m ON m.member_id = mb.member_id
    WHERE bt.trainer_id = ?
    GROUP BY b.batch_id
");
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$batches = mysqli_stmt_get_result($stmt);

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Batch Name</th><th>Time</th><th>Capacity</th><th>Filled</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php $found=false; while ($b = mysqli_fetch_assoc($batches)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['batch_name']); ?></td>
                    <td><?php echo substr($b['start_time'],0,5) . " - " . substr($b['end_time'],0,5); ?></td>
                    <td><?php echo (int)$b['capacity']; ?></td>
                    <td><?php echo (int)$b['filled']; ?></td>
                    <td><span class="badge <?php echo $b['status']=='Active'?'badge-active':'badge-inactive'; ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                    <td><a href="batch-details.php?id=<?php echo $b['batch_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> View</a></td>
                </tr>
                <?php endwhile; if(!$found): ?>
                    <tr><td colspan="6" class="text-muted">You are not assigned to any batch yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
