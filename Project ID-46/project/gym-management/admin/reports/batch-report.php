<?php
// ==========================================================
// admin/reports/batch-report.php — Per-batch / trainer report
// Combines: capacity/fill rate, assigned trainers, attendance %
// (based on logged attendance records), and projected monthly
// revenue (monthly_fee x filled seats — a projection, not actual
// collected payments, since not every member pays every batch's
// full fee on time; see the Revenue report for actual collections).
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Batch / Trainer Report";
$active_menu = "reports";

// Base batch info + trainers + filled count
$res = mysqli_query($conn, "
    SELECT b.batch_id, b.batch_name, b.start_time, b.end_time, b.capacity, b.monthly_fee, b.status,
           (SELECT COUNT(*) FROM member_batches mb WHERE mb.batch_id = b.batch_id) AS filled,
           GROUP_CONCAT(DISTINCT t.full_name ORDER BY t.full_name SEPARATOR ', ') AS trainers
    FROM batches b
    LEFT JOIN batch_trainers bt ON bt.batch_id = b.batch_id
    LEFT JOIN trainers t ON bt.trainer_id = t.trainer_id
    GROUP BY b.batch_id
    ORDER BY b.start_time
");

// Attendance % per batch, computed separately (simpler than one giant join)
$att_by_batch = array();
$att_res = mysqli_query($conn, "
    SELECT batch_id, COUNT(*) AS total, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count
    FROM attendance
    GROUP BY batch_id
");
while ($row = mysqli_fetch_assoc($att_res)) {
    $att_by_batch[$row['batch_id']] = $row;
}

$batches = array();
while ($row = mysqli_fetch_assoc($res)) { $batches[] = $row; }

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="d-flex justify-content-end mb-3">
    <a href="reports.php" class="btn btn-outline-secondary">Back to Reports</a>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Batch</th><th>Time</th><th>Trainer(s)</th><th>Fill Rate</th>
                    <th>Attendance %</th><th>Projected Monthly Revenue</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $b):
                    $filled = (int)$b['filled'];
                    $capacity = (int)$b['capacity'];
                    $fill_pct = $capacity > 0 ? round(($filled / $capacity) * 100) : 0;

                    $att = isset($att_by_batch[$b['batch_id']]) ? $att_by_batch[$b['batch_id']] : null;
                    $att_pct = ($att && $att['total'] > 0) ? round(($att['present_count'] / $att['total']) * 100) : null;

                    $projected_revenue = $filled * (float)$b['monthly_fee'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['batch_name']); ?></td>
                    <td><?php echo substr($b['start_time'],0,5) . "-" . substr($b['end_time'],0,5); ?></td>
                    <td><?php echo htmlspecialchars((isset($b['trainers']) && $b['trainers']) ? $b['trainers'] : '—'); ?></td>
                    <td>
                        <?php echo $filled . " / " . $capacity; ?>
                        <div class="progress mt-1" style="height:6px;">
                            <div class="progress-bar bg-danger" style="width:<?php echo $fill_pct; ?>%"></div>
                        </div>
                    </td>
                    <td><?php echo $att_pct !== null ? $att_pct . '%' : '—'; ?></td>
                    <td>Rs. <?php echo number_format($projected_revenue, 2); ?></td>
                    <td><span class="badge <?php echo $b['status']=='Active'?'badge-active':'badge-inactive'; ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($batches)): ?>
                    <tr><td colspan="7" class="text-muted">No batches found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small mt-2 mb-0">Projected revenue = monthly fee &times; filled seats. This is a projection based on current enrollment, not a record of payments actually collected — see the Revenue report for that.</p>
</div>

<?php include "../includes/footer.php"; ?>
