<?php
// ==========================================================
// admin/batch/edit-batch.php — Edit a batch + its trainers
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Edit Batch";
$active_menu = "batches";
$error_msg = "";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM batches WHERE batch_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$batch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$batch) {
    header("Location: batches.php?err=" . urlencode("Batch not found."));
    exit;
}

$trainers = mysqli_query($conn, "SELECT trainer_id, full_name FROM trainers WHERE status='Active'");

// Currently assigned trainer IDs
$assigned = [];
$stmt2 = mysqli_prepare($conn, "SELECT trainer_id FROM batch_trainers WHERE batch_id=?");
mysqli_stmt_bind_param($stmt2, "i", $id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($res2)) { $assigned[] = $row['trainer_id']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_name = trim((isset($_POST['batch_name']) ? $_POST['batch_name'] : ''));
    $start_time = (isset($_POST['start_time']) ? $_POST['start_time'] : '');
    $end_time   = (isset($_POST['end_time']) ? $_POST['end_time'] : '');
    $capacity   = (int)((isset($_POST['capacity']) ? $_POST['capacity'] : 20));
    $monthly_fee = (float)((isset($_POST['monthly_fee']) ? $_POST['monthly_fee'] : 0));
    $status     = (isset($_POST['status']) ? $_POST['status'] : 'Active');
    $trainer_ids = (isset($_POST['trainer_ids']) ? $_POST['trainer_ids'] : []);

    if ($batch_name=='' || $start_time=='' || $end_time=='') {
        $error_msg = "Please fill in all required fields.";
    } elseif ($start_time >= $end_time) {
        $error_msg = "End time must be after start time.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE batches SET batch_name=?, start_time=?, end_time=?, capacity=?, monthly_fee=?, status=? WHERE batch_id=?");
        mysqli_stmt_bind_param($stmt, "sssidsi", $batch_name, $start_time, $end_time, $capacity, $monthly_fee, $status, $id);

        if (mysqli_stmt_execute($stmt)) {
            // Reset and re-insert trainer assignments (simplest, safest way)
            $del = mysqli_prepare($conn, "DELETE FROM batch_trainers WHERE batch_id=?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);

            foreach ($trainer_ids as $tid) {
                $tid = (int)$tid;
                $link = mysqli_prepare($conn, "INSERT IGNORE INTO batch_trainers (batch_id, trainer_id) VALUES (?,?)");
                mysqli_stmt_bind_param($link, "ii", $id, $tid);
                mysqli_stmt_execute($link);
            }
            header("Location: batches.php?msg=" . urlencode("Batch updated successfully."));
            exit;
        } else {
            $error_msg = "Update failed. Please try again.";
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
            <div class="col-md-6"><label class="form-label">Batch Name</label><input type="text" name="batch_name" class="form-control" required value="<?php echo htmlspecialchars($batch['batch_name']); ?>"></div>
            <div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" required value="<?php echo substr($batch['start_time'],0,5); ?>"></div>
            <div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" required value="<?php echo substr($batch['end_time'],0,5); ?>"></div>
            <div class="col-md-4"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" min="1" required value="<?php echo (int)$batch['capacity']; ?>"></div>
            <div class="col-md-4"><label class="form-label">Monthly Fee (Rs.)</label><input type="number" name="monthly_fee" class="form-control" min="0" step="0.01" required value="<?php echo htmlspecialchars($batch['monthly_fee']); ?>"></div>
            <div class="col-md-4"><label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option <?php echo $batch['status']=='Active'?'selected':''; ?>>Active</option>
                    <option <?php echo $batch['status']=='Inactive'?'selected':''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Assign Trainers (hold Ctrl/Cmd to select multiple)</label>
                <select name="trainer_ids[]" class="form-select" multiple size="4">
                    <?php while ($t = mysqli_fetch_assoc($trainers)): ?>
                        <option value="<?php echo $t['trainer_id']; ?>" <?php echo in_array($t['trainer_id'],$assigned)?'selected':''; ?>><?php echo htmlspecialchars($t['full_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-gym-primary mt-4">Save Changes</button>
        <a href="batches.php" class="btn btn-outline-secondary mt-4">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
