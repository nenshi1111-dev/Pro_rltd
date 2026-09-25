<?php
// ==========================================================
// member/my-batch.php — ALL of the member's enrolled batches,
// each with its own trainers + weekly workout plan.
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "My Batches";
$active_menu = "batch";
$member_id = $_SESSION['member_id'];

$stmt = mysqli_prepare($conn, "SELECT b.* FROM member_batches mb JOIN batches b ON mb.batch_id=b.batch_id WHERE mb.member_id=? ORDER BY b.start_time");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$my_batches = mysqli_stmt_get_result($stmt);
$batch_rows = [];
while ($row = mysqli_fetch_assoc($my_batches)) { $batch_rows[] = $row; }

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<?php if (empty($batch_rows)): ?>
    <div class="gym-card p-4">
        <p class="text-muted mb-0">You are not currently assigned to any batch. Please contact Admin.</p>
    </div>
<?php else: ?>
    <?php foreach ($batch_rows as $batch):
        $stmt2 = mysqli_prepare($conn, "SELECT t.full_name, t.specialization FROM batch_trainers bt JOIN trainers t ON bt.trainer_id=t.trainer_id WHERE bt.batch_id=?");
        mysqli_stmt_bind_param($stmt2, "i", $batch['batch_id']);
        mysqli_stmt_execute($stmt2);
        $trainers = mysqli_stmt_get_result($stmt2);

        $stmt3 = mysqli_prepare($conn, "SELECT * FROM workout_plans WHERE batch_id=? ORDER BY FIELD(day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
        mysqli_stmt_bind_param($stmt3, "i", $batch['batch_id']);
        mysqli_stmt_execute($stmt3);
        $workouts = mysqli_stmt_get_result($stmt3);
    ?>
    <div class="gym-card p-4 mb-3">
        <h4><?php echo htmlspecialchars($batch['batch_name']); ?></h4>
        <p class="text-muted mb-3"><?php echo substr($batch['start_time'],0,5) . " - " . substr($batch['end_time'],0,5); ?></p>

        <div class="row g-3">
            <div class="col-md-4">
                <h6>Trainers</h6>
                <ul class="list-group list-group-flush">
                    <?php $found=false; while ($t = mysqli_fetch_assoc($trainers)): $found=true; ?>
                        <li class="list-group-item"><?php echo htmlspecialchars($t['full_name']); ?> <span class="text-muted small">(<?php echo htmlspecialchars($t['specialization']); ?>)</span></li>
                    <?php endwhile; if(!$found): ?><li class="list-group-item text-muted">No trainer assigned yet.</li><?php endif; ?>
                </ul>
            </div>
            <div class="col-md-8">
                <h6>Weekly Workout Plan</h6>
                <?php $found=false; while ($w = mysqli_fetch_assoc($workouts)): $found=true; ?>
                    <div class="border-bottom py-2">
                        <strong><?php echo htmlspecialchars($w['day_name']); ?></strong> — <?php echo htmlspecialchars($w['focus_area']); ?>
                        <div class="small text-muted"><?php echo htmlspecialchars($w['details']); ?></div>
                    </div>
                <?php endwhile; if(!$found): ?><p class="text-muted">No workout plan set yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
