<?php
// ==========================================================
// admin/batch/batches.php — Batch list
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Batches";
$active_menu = "batches";

$batches = mysqli_query($conn, "
    SELECT b.*, COUNT(m.member_id) AS filled
    FROM batches b
    LEFT JOIN member_batches mb ON mb.batch_id = b.batch_id
    LEFT JOIN members m ON m.member_id = mb.member_id
    GROUP BY b.batch_id
    ORDER BY b.batch_id DESC
");

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($_GET['err']); ?></div><?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <a href="add-batch.php" class="btn btn-gym-primary"><i class="bi bi-plus-circle me-1"></i>Add Batch</a>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Batch Name</th><th>Time</th><th>Capacity</th><th>Filled</th><th>Fee/mo</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while ($b = mysqli_fetch_assoc($batches)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['batch_name']); ?></td>
                    <td><?php echo substr($b['start_time'],0,5) . " - " . substr($b['end_time'],0,5); ?></td>
                    <td><?php echo (int)$b['capacity']; ?></td>
                    <td>Rs. <?php echo number_format($b['monthly_fee'], 2); ?></td>
                    <td><?php echo (int)$b['filled']; ?></td>
                    <td><span class="badge <?php echo $b['status']=='Active'?'badge-active':'badge-inactive'; ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                    <td class="text-nowrap">
                        <a href="view-batch.php?id=<?php echo $b['batch_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <a href="edit-batch.php?id=<?php echo $b['batch_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="delete-batch.php?id=<?php echo $b['batch_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this batch?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
