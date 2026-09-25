<?php
// ==========================================================
// trainer/today-schedule.php — Today's batches + workout focus
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Today's Schedule";
$active_menu = "schedule";
$trainer_id = $_SESSION['trainer_id'];
$today_name = date('l');

$stmt = mysqli_prepare($conn, "
    SELECT b.batch_id, b.batch_name, b.start_time, b.end_time, wp.focus_area, wp.details
    FROM batch_trainers bt
    JOIN batches b ON bt.batch_id = b.batch_id
    LEFT JOIN workout_plans wp ON wp.batch_id = b.batch_id AND wp.day_name = ?
    WHERE bt.trainer_id = ?
    ORDER BY b.start_time
");
mysqli_stmt_bind_param($stmt, "si", $today_name, $trainer_id);
mysqli_stmt_execute($stmt);
$schedule = mysqli_stmt_get_result($stmt);

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<h6 class="mb-3 text-muted">Today is <?php echo $today_name; ?></h6>

<div class="row g-3">
    <?php $found=false; while ($s = mysqli_fetch_assoc($schedule)): $found=true; ?>
    <div class="col-md-6">
        <div class="gym-card p-3">
            <h6><?php echo htmlspecialchars($s['batch_name']); ?></h6>
            <p class="text-muted mb-1"><?php echo substr($s['start_time'],0,5) . " - " . substr($s['end_time'],0,5); ?></p>
            <?php if ($s['focus_area']): ?>
                <p class="mb-0"><strong>Focus:</strong> <?php echo htmlspecialchars($s['focus_area']); ?></p>
                <p class="small text-muted mb-0"><?php echo htmlspecialchars($s['details']); ?></p>
            <?php else: ?>
                <p class="small text-muted mb-0">No workout plan set for today.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; if(!$found): ?>
        <div class="col-12"><p class="text-muted">You have no batches scheduled today.</p></div>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
