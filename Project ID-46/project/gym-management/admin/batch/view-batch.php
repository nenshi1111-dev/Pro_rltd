<?php
// ==========================================================
// admin/batch/view-batch.php — Batch details: assigned
// members + trainers
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "View Batch";
$active_menu = "batches";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM batches WHERE batch_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$batch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$batch) {
    header("Location: batches.php?err=" . urlencode("Batch not found."));
    exit;
}

$stmt2 = mysqli_prepare($conn, "SELECT t.full_name FROM batch_trainers bt JOIN trainers t ON bt.trainer_id=t.trainer_id WHERE bt.batch_id=?");
mysqli_stmt_bind_param($stmt2, "i", $id);
mysqli_stmt_execute($stmt2);
$trainers = mysqli_stmt_get_result($stmt2);

$stmt3 = mysqli_prepare($conn, "SELECT m.member_code, m.full_name, m.status FROM member_batches mb JOIN members m ON mb.member_id=m.member_id WHERE mb.batch_id=?");
mysqli_stmt_bind_param($stmt3, "i", $id);
mysqli_stmt_execute($stmt3);
$members = mysqli_stmt_get_result($stmt3);

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="gym-card p-4 mb-3">
    <h4><?php echo htmlspecialchars($batch['batch_name']); ?></h4>
    <p class="text-muted mb-0"><?php echo substr($batch['start_time'],0,5) . " - " . substr($batch['end_time'],0,5); ?> | Capacity: <?php echo (int)$batch['capacity']; ?></p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="gym-card p-3">
            <h6>Assigned Trainers</h6>
            <ul class="list-group list-group-flush">
                <?php $found=false; while ($t = mysqli_fetch_assoc($trainers)): $found=true; ?>
                    <li class="list-group-item"><?php echo htmlspecialchars($t['full_name']); ?></li>
                <?php endwhile; if(!$found): ?><li class="list-group-item text-muted">None assigned.</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-8">
        <div class="gym-card p-3">
            <h6>Members in this Batch</h6>
            <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Code</th><th>Name</th><th>Status</th></tr></thead>
                <tbody>
                    <?php $found=false; while ($m = mysqli_fetch_assoc($members)): $found=true; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['member_code']); ?></td>
                        <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                        <td><span class="badge <?php echo $m['status']=='Active'?'badge-active':($m['status']=='Expired'?'badge-expired':($m['status']=='Pending Payment'?'badge-pending':'badge-inactive')); ?>"><?php echo htmlspecialchars($m['status']); ?></span></td>
                    </tr>
                    <?php endwhile; if(!$found): ?><tr><td colspan="3" class="text-muted">No members in this batch yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<a href="batches.php" class="btn btn-outline-secondary mt-3">Back to List</a>

<?php include "../includes/footer.php"; ?>
