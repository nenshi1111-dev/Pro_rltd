<?php
// ==========================================================
// trainer/workout-plan.php — Create/update workout plan per
// batch. Ownership enforced: trainer may only edit a plan for
// a batch that is actually assigned to them.
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Workout Plans";
$active_menu = "workout";
$trainer_id = $_SESSION['trainer_id'];
$success_msg = "";

// ---- Batch picker (trainer's own batches only) ----
$my_batches_stmt = mysqli_prepare($conn, "SELECT b.batch_id, b.batch_name FROM batch_trainers bt JOIN batches b ON bt.batch_id=b.batch_id WHERE bt.trainer_id=?");
mysqli_stmt_bind_param($my_batches_stmt, "i", $trainer_id);
mysqli_stmt_execute($my_batches_stmt);
$my_batches = mysqli_stmt_get_result($my_batches_stmt);

$batch_id = (int)((isset($_GET['batch_id']) ? $_GET['batch_id'] : (isset($_POST['batch_id']) ? $_POST['batch_id'] : 0)));
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

if ($batch_id) {
    // Ownership check
    $own = mysqli_prepare($conn, "SELECT batch_id FROM batch_trainers WHERE trainer_id=? AND batch_id=?");
    mysqli_stmt_bind_param($own, "ii", $trainer_id, $batch_id);
    mysqli_stmt_execute($own);
    if (mysqli_num_rows(mysqli_stmt_get_result($own)) === 0) {
        $batch_id = 0; // not your batch — ignore silently, fall back to picker
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $batch_id) {
    foreach ($days as $day) {
        $focus = trim((isset($_POST['focus_' . $day]) ? $_POST['focus_' . $day] : ''));
        $details = trim((isset($_POST['details_' . $day]) ? $_POST['details_' . $day] : ''));
        // Save a row only if the trainer entered something for that day
        if ($focus !== '' || $details !== '') {
            $stmt = mysqli_prepare($conn, "INSERT INTO workout_plans (batch_id, day_name, focus_area, details) VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE focus_area=VALUES(focus_area), details=VALUES(details)");
            mysqli_stmt_bind_param($stmt, "isss", $batch_id, $day, $focus, $details);
            mysqli_stmt_execute($stmt);
        } else {
            // Empty fields = remove that day's plan, if it exists
            $del = mysqli_prepare($conn, "DELETE FROM workout_plans WHERE batch_id=? AND day_name=?");
            mysqli_stmt_bind_param($del, "is", $batch_id, $day);
            mysqli_stmt_execute($del);
        }
    }
    $success_msg = "Workout plan saved successfully.";
}

// Load existing plan rows for the selected batch, keyed by day
$existing = [];
if ($batch_id) {
    $stmt2 = mysqli_prepare($conn, "SELECT day_name, focus_area, details FROM workout_plans WHERE batch_id=?");
    mysqli_stmt_bind_param($stmt2, "i", $batch_id);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($row = mysqli_fetch_assoc($res2)) { $existing[$row['day_name']] = $row; }
}

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<?php if ($success_msg): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>

<div class="gym-card p-3 mb-3">
    <form method="GET" class="d-flex gap-2 align-items-end">
        <div>
            <label class="form-label">Choose Batch</label>
            <select name="batch_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select a batch --</option>
                <?php mysqli_data_seek($my_batches, 0); while ($b = mysqli_fetch_assoc($my_batches)): ?>
                    <option value="<?php echo $b['batch_id']; ?>" <?php echo $batch_id==$b['batch_id']?'selected':''; ?>><?php echo htmlspecialchars($b['batch_name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($batch_id): ?>
<div class="gym-card p-4">
    <form method="POST">
        <input type="hidden" name="batch_id" value="<?php echo $batch_id; ?>">
        <?php foreach ($days as $day): $row = (isset($existing[$day]) ? $existing[$day] : ['focus_area'=>'','details'=>'']); ?>
            <div class="row g-2 mb-3 align-items-center border-bottom pb-3">
                <div class="col-md-2"><strong><?php echo $day; ?></strong></div>
                <div class="col-md-4">
                    <input type="text" name="focus_<?php echo $day; ?>" class="form-control" placeholder="Focus area (e.g. Chest & Triceps)" value="<?php echo htmlspecialchars($row['focus_area']); ?>">
                </div>
                <div class="col-md-6">
                    <input type="text" name="details_<?php echo $day; ?>" class="form-control" placeholder="Details (exercises, sets, reps)" value="<?php echo htmlspecialchars($row['details']); ?>">
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-gym-primary">Save Workout Plan</button>
    </form>
</div>
<?php else: ?>
    <p class="text-muted">Select one of your batches above to view or edit its weekly workout plan.</p>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
