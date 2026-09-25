<?php
// ==========================================================
// trainer/attendance.php — Mark attendance for a batch/date
// Ownership enforced: a trainer can only mark attendance for
// a batch actually assigned to them. Can't mark future dates.
// Re-marking the same batch/date overwrites the previous
// record (INSERT ... ON DUPLICATE KEY UPDATE), so a trainer
// can correct a mistake without creating duplicate rows.
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Mark Attendance";
$active_menu = "attendance";
$trainer_id = $_SESSION['trainer_id'];
$success_msg = "";
$error_msg = "";
$today = date('Y-m-d');

// ---- Batch picker (trainer's own batches only) ----
$my_batches_stmt = mysqli_prepare($conn, "SELECT b.batch_id, b.batch_name FROM batch_trainers bt JOIN batches b ON bt.batch_id=b.batch_id WHERE bt.trainer_id=?");
mysqli_stmt_bind_param($my_batches_stmt, "i", $trainer_id);
mysqli_stmt_execute($my_batches_stmt);
$my_batches = mysqli_stmt_get_result($my_batches_stmt);

$batch_id = (int)((isset($_GET['batch_id']) ? $_GET['batch_id'] : (isset($_POST['batch_id']) ? $_POST['batch_id'] : 0)));
$att_date = (isset($_GET['att_date']) ? $_GET['att_date'] : (isset($_POST['att_date']) ? $_POST['att_date'] : $today));

// Never allow a future date
if ($att_date > $today) { $att_date = $today; }

if ($batch_id) {
    $own = mysqli_prepare($conn, "SELECT batch_id FROM batch_trainers WHERE trainer_id=? AND batch_id=?");
    mysqli_stmt_bind_param($own, "ii", $trainer_id, $batch_id);
    mysqli_stmt_execute($own);
    if (mysqli_num_rows(mysqli_stmt_get_result($own)) === 0) {
        $batch_id = 0; // not your batch — ignore silently
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $batch_id) {
    if ($att_date > $today) {
        $error_msg = "Cannot mark attendance for a future date.";
    } else {
        $statuses = (isset($_POST['status']) ? $_POST['status'] : array());
        foreach ($statuses as $member_id => $status) {
            $member_id = (int)$member_id;
            $status = ($status === 'Absent') ? 'Absent' : 'Present';
            $stmt = mysqli_prepare($conn, "INSERT INTO attendance (member_id, batch_id, date, status) VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE status=VALUES(status)");
            mysqli_stmt_bind_param($stmt, "iiss", $member_id, $batch_id, $att_date, $status);
            mysqli_stmt_execute($stmt);
        }
        $success_msg = "Attendance saved for " . htmlspecialchars($att_date) . ".";
    }
}

// Members in the selected batch, with any existing attendance for this date
$members = array();
if ($batch_id) {
    $mem_stmt = mysqli_prepare($conn, "
        SELECT m.member_id, m.member_code, m.full_name, a.status
        FROM member_batches mb
        JOIN members m ON mb.member_id = m.member_id
        LEFT JOIN attendance a ON a.member_id = m.member_id AND a.batch_id = mb.batch_id AND a.date = ?
        WHERE mb.batch_id = ?
        ORDER BY m.full_name
    ");
    mysqli_stmt_bind_param($mem_stmt, "si", $att_date, $batch_id);
    mysqli_stmt_execute($mem_stmt);
    $res = mysqli_stmt_get_result($mem_stmt);
    while ($row = mysqli_fetch_assoc($res)) { $members[] = $row; }
}

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<?php if ($success_msg): ?><div class="alert alert-success auto-hide-alert"><?php echo $success_msg; ?></div><?php endif; ?>
<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

<div class="gym-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Batch</label>
            <select name="batch_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select a batch --</option>
                <?php mysqli_data_seek($my_batches, 0); while ($b = mysqli_fetch_assoc($my_batches)): ?>
                    <option value="<?php echo $b['batch_id']; ?>" <?php echo $batch_id==$b['batch_id']?'selected':''; ?>><?php echo htmlspecialchars($b['batch_name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="att_date" class="form-control" value="<?php echo htmlspecialchars($att_date); ?>" max="<?php echo $today; ?>" onchange="this.form.submit()">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-outline-dark w-100">Load</button>
        </div>
    </form>
</div>

<?php if ($batch_id && !empty($members)): ?>
<div class="d-flex justify-content-end mb-2">
    <a href="export-attendance.php?batch_id=<?php echo $batch_id; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Export Attendance Sheet (CSV)</a>
</div>
<div class="gym-card p-4">
    <form method="POST">
        <input type="hidden" name="batch_id" value="<?php echo $batch_id; ?>">
        <input type="hidden" name="att_date" value="<?php echo htmlspecialchars($att_date); ?>">
        <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Code</th><th>Name</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($members as $m): $current = (isset($m['status']) && $m['status']) ? $m['status'] : 'Present'; ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['member_code']); ?></td>
                    <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="status[<?php echo $m['member_id']; ?>]" id="p<?php echo $m['member_id']; ?>" value="Present" <?php echo $current=='Present'?'checked':''; ?>>
                            <label class="btn btn-outline-success btn-sm" for="p<?php echo $m['member_id']; ?>">Present</label>

                            <input type="radio" class="btn-check" name="status[<?php echo $m['member_id']; ?>]" id="a<?php echo $m['member_id']; ?>" value="Absent" <?php echo $current=='Absent'?'checked':''; ?>>
                            <label class="btn btn-outline-danger btn-sm" for="a<?php echo $m['member_id']; ?>">Absent</label>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn btn-gym-primary">Save Attendance</button>
    </form>
</div>
<?php elseif ($batch_id): ?>
    <p class="text-muted">No members are enrolled in this batch yet.</p>
<?php else: ?>
    <p class="text-muted">Select one of your batches above to mark attendance.</p>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
