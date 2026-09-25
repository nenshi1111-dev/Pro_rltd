<?php
// ==========================================================
// trainer/batch/batch-details.php — Batch detail view
// SECURITY: a trainer may only view a batch that is actually
// assigned to them — never another trainer's batch.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Batch Details";
$active_menu = "batches";
$trainer_id = $_SESSION['trainer_id'];
$batch_id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));

// Ownership check — must be an assigned batch for this trainer
$own = mysqli_prepare($conn, "SELECT b.* FROM batch_trainers bt JOIN batches b ON bt.batch_id=b.batch_id WHERE bt.trainer_id=? AND bt.batch_id=?");
mysqli_stmt_bind_param($own, "ii", $trainer_id, $batch_id);
mysqli_stmt_execute($own);
$batch = mysqli_fetch_assoc(mysqli_stmt_get_result($own));

if (!$batch) {
    header("Location: my-batches.php?err=" . urlencode("You do not have access to that batch."));
    exit;
}

$mem_stmt = mysqli_prepare($conn, "SELECT m.member_code, m.full_name, m.status FROM member_batches mb JOIN members m ON mb.member_id=m.member_id WHERE mb.batch_id=?");
mysqli_stmt_bind_param($mem_stmt, "i", $batch_id);
mysqli_stmt_execute($mem_stmt);
$members = mysqli_stmt_get_result($mem_stmt);

$wk_stmt = mysqli_prepare($conn, "SELECT * FROM workout_plans WHERE batch_id=? ORDER BY FIELD(day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
mysqli_stmt_bind_param($wk_stmt, "i", $batch_id);
mysqli_stmt_execute($wk_stmt);
$workouts = mysqli_stmt_get_result($wk_stmt);

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="gym-card p-4 mb-3">
    <h4><?php echo htmlspecialchars($batch['batch_name']); ?></h4>
    <p class="text-muted mb-0"><?php echo substr($batch['start_time'],0,5) . " - " . substr($batch['end_time'],0,5); ?></p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="gym-card p-3">
            <h6>Members</h6>
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
                <?php endwhile; if(!$found): ?><tr><td colspan="3" class="text-muted">No members yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="gym-card p-3">
            <h6>Weekly Workout Plan</h6>
            <?php $found=false; while ($w = mysqli_fetch_assoc($workouts)): $found=true; ?>
                <div class="border-bottom py-2">
                    <strong><?php echo htmlspecialchars($w['day_name']); ?></strong> — <?php echo htmlspecialchars($w['focus_area']); ?>
                    <div class="small text-muted"><?php echo htmlspecialchars($w['details']); ?></div>
                </div>
            <?php endwhile; if(!$found): ?><p class="text-muted">No workout plan set yet.</p><?php endif; ?>
            <a href="../workout-plan.php?batch_id=<?php echo $batch_id; ?>" class="btn btn-sm btn-gym-primary mt-2">Edit Workout Plan</a>
        </div>
    </div>
</div>

<a href="my-batches.php" class="btn btn-outline-secondary mt-3">Back to My Batches</a>

<?php include "../includes/footer.php"; ?>
