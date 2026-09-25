<?php
// ==========================================================
// admin/batch/add-batch.php — Add a new batch
// Also lets Admin assign one or more trainers at creation time
// (writes to the batch_trainers link table).
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Add Batch";
$active_menu = "batches";
$error_msg = "";

$trainers = mysqli_query($conn, "SELECT trainer_id, full_name FROM trainers WHERE status='Active'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_name = trim((isset($_POST['batch_name']) ? $_POST['batch_name'] : ''));
    $start_time = (isset($_POST['start_time']) ? $_POST['start_time'] : '');
    $end_time   = (isset($_POST['end_time']) ? $_POST['end_time'] : '');
    $capacity   = (int)((isset($_POST['capacity']) ? $_POST['capacity'] : 20));
    $monthly_fee = (float)((isset($_POST['monthly_fee']) ? $_POST['monthly_fee'] : 0));
    $trainer_ids = (isset($_POST['trainer_ids']) ? $_POST['trainer_ids'] : []);

    if ($batch_name=='' || $start_time=='' || $end_time=='') {
        $error_msg = "Please fill in all required fields.";
    } elseif ($start_time >= $end_time) {
        $error_msg = "End time must be after start time.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO batches (batch_name, start_time, end_time, capacity, monthly_fee, status) VALUES (?,?,?,?,?,'Active')");
        mysqli_stmt_bind_param($stmt, "sssid", $batch_name, $start_time, $end_time, $capacity, $monthly_fee);

        if (mysqli_stmt_execute($stmt)) {
            $new_batch_id = mysqli_insert_id($conn);
            // Link selected trainers
            foreach ($trainer_ids as $tid) {
                $tid = (int)$tid;
                $link = mysqli_prepare($conn, "INSERT IGNORE INTO batch_trainers (batch_id, trainer_id) VALUES (?,?)");
                mysqli_stmt_bind_param($link, "ii", $new_batch_id, $tid);
                mysqli_stmt_execute($link);
            }
            header("Location: batches.php?msg=" . urlencode("Batch added successfully."));
            exit;
        } else {
            $error_msg = "Could not add batch. Please try again.";
        }
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

<div class="gym-card p-4">
    <form method="POST">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Batch Name</label><input type="text" name="batch_name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="20" min="1" required></div>
            <div class="col-md-4"><label class="form-label">Monthly Fee (Rs.)</label><input type="number" name="monthly_fee" class="form-control" value="0" min="0" step="0.01" required></div>
            <div class="col-md-8">
                <label class="form-label">Assign Trainers (hold Ctrl/Cmd to select multiple)</label>
                <select name="trainer_ids[]" class="form-select" multiple size="4">
                    <?php while ($t = mysqli_fetch_assoc($trainers)): ?>
                        <option value="<?php echo $t['trainer_id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-gym-primary mt-4">Add Batch</button>
        <a href="batches.php" class="btn btn-outline-secondary mt-4">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
