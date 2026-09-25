<?php
// ==========================================================
// member/attendance.php — Member's own attendance record
// Shows attendance % per batch (present / total marked days),
// plus a full history table.
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "My Attendance";
$active_menu = "attendance";
$member_id = $_SESSION['member_id'];

// Per-batch summary
$summary_stmt = mysqli_prepare($conn, "
    SELECT b.batch_name,
           COUNT(a.attendance_id) AS total_marked,
           SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) AS present_count
    FROM member_batches mb
    JOIN batches b ON mb.batch_id = b.batch_id
    LEFT JOIN attendance a ON a.batch_id = mb.batch_id AND a.member_id = mb.member_id
    WHERE mb.member_id = ?
    GROUP BY b.batch_id, b.batch_name
");
mysqli_stmt_bind_param($summary_stmt, "i", $member_id);
mysqli_stmt_execute($summary_stmt);
$summary = mysqli_stmt_get_result($summary_stmt);

// Full history, most recent first
$history_stmt = mysqli_prepare($conn, "
    SELECT a.date, a.status, b.batch_name
    FROM attendance a
    JOIN batches b ON a.batch_id = b.batch_id
    WHERE a.member_id = ?
    ORDER BY a.date DESC
    LIMIT 60
");
mysqli_stmt_bind_param($history_stmt, "i", $member_id);
mysqli_stmt_execute($history_stmt);
$history = mysqli_stmt_get_result($history_stmt);

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="row g-3 mb-4">
    <?php $found=false; while ($s = mysqli_fetch_assoc($summary)): $found=true;
        $total = (int)$s['total_marked'];
        $present = (int)$s['present_count'];
        $pct = $total > 0 ? round(($present / $total) * 100) : 0;
        $color_class = $pct >= 75 ? 'bg-stat-2' : ($pct >= 50 ? 'bg-stat-4' : 'bg-stat-1');
    ?>
        <div class="col-md-4">
            <div class="stat-card <?php echo $color_class; ?>">
                <h3><?php echo $pct; ?>%</h3>
                <p><?php echo htmlspecialchars($s['batch_name']); ?> (<?php echo $present; ?>/<?php echo $total; ?> days)</p>
            </div>
        </div>
    <?php endwhile; if (!$found): ?>
        <div class="col-12"><p class="text-muted">You're not enrolled in any batch yet.</p></div>
    <?php endif; ?>
</div>

<div class="gym-card p-3">
    <h6 class="mb-3">Recent Attendance</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Date</th><th>Batch</th><th>Status</th></tr></thead>
            <tbody>
                <?php $found=false; while ($h = mysqli_fetch_assoc($history)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars($h['date']); ?></td>
                    <td><?php echo htmlspecialchars($h['batch_name']); ?></td>
                    <td><span class="badge <?php echo $h['status']=='Present'?'badge-active':'badge-rejected'; ?>"><?php echo htmlspecialchars($h['status']); ?></span></td>
                </tr>
                <?php endwhile; if (!$found): ?>
                    <tr><td colspan="3" class="text-muted">No attendance records yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
