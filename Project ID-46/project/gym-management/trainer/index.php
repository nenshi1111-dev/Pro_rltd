<?php
// ==========================================================
// trainer/index.php — Trainer Dashboard
// Only ever shows data belonging to THIS trainer's batches.
// ==========================================================
require_once "../includes/config.php";
require_once "../includes/notification-functions.php";
require_once "includes/auth.php";

$page_title = "Dashboard";
$active_menu = "dashboard";
$trainer_id = $_SESSION['trainer_id'];

// Batches assigned to this trainer
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM batch_trainers WHERE trainer_id=?");
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$my_batches = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

// Members across this trainer's batches only
$stmt2 = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT m.member_id) c FROM members m
    JOIN member_batches mb ON mb.member_id = m.member_id
    JOIN batch_trainers bt ON mb.batch_id = bt.batch_id
    WHERE bt.trainer_id=?
");
mysqli_stmt_bind_param($stmt2, "i", $trainer_id);
mysqli_stmt_execute($stmt2);
$my_members = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2))['c'];

// Workout plans written for this trainer's batches
$stmt3 = mysqli_prepare($conn, "
    SELECT COUNT(*) c FROM workout_plans wp
    JOIN batch_trainers bt ON wp.batch_id = bt.batch_id
    WHERE bt.trainer_id=?
");
mysqli_stmt_bind_param($stmt3, "i", $trainer_id);
mysqli_stmt_execute($stmt3);
$my_workouts = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt3))['c'];

// Today's weekday name, to preview today's schedule count
$today_name = date('l'); // e.g. "Monday"
$stmt4 = mysqli_prepare($conn, "
    SELECT COUNT(*) c FROM workout_plans wp
    JOIN batch_trainers bt ON wp.batch_id = bt.batch_id
    WHERE bt.trainer_id=? AND wp.day_name=?
");
mysqli_stmt_bind_param($stmt4, "is", $trainer_id, $today_name);
mysqli_stmt_execute($stmt4);
$today_sessions = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt4))['c'];

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-1"><h3 data-count="<?php echo $my_batches; ?>">0</h3><p>My Batches</p></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-2"><h3 data-count="<?php echo $my_members; ?>">0</h3><p>My Members</p></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-3"><h3 data-count="<?php echo $my_workouts; ?>">0</h3><p>Workout Plans Set</p></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-4"><h3 data-count="<?php echo $today_sessions; ?>">0</h3><p>Sessions Today (<?php echo $today_name; ?>)</p></div>
    </div>
</div>

<div class="gym-card p-4">
    <p class="mb-2">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['trainer_name']); ?></strong>.</p>
    <a href="batch/my-batches.php" class="btn btn-gym-primary btn-sm">View My Batches</a>
    <a href="today-schedule.php" class="btn btn-outline-dark btn-sm">Today's Schedule</a>
    <a href="attendance.php" class="btn btn-outline-dark btn-sm">Mark Attendance</a>
</div>

<?php $expiring = get_expiring_soon_members($conn, 7, $trainer_id); ?>
<?php if (!empty($expiring)): ?>
<div class="gym-card p-4 mt-3">
    <h6 class="mb-3"><i class="bi bi-clock-history text-warning me-1"></i>Members Expiring Soon (in your batches)</h6>
    <ul class="list-group list-group-flush">
        <?php foreach ($expiring as $ex): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars($ex['full_name']); ?> (<?php echo htmlspecialchars($ex['member_code']); ?>)</span>
                <span class="badge badge-pending"><?php echo format_days_left($ex['days_left']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
