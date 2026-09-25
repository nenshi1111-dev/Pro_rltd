<?php
// ==========================================================
// admin/reports/monthly-members.php — New members by month
// Last 12 months, based on members.created_at.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "New Members Report";
$active_menu = "reports";

$res = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM members
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
");

$labels = array();
$values = array();
$rows = array();
while ($row = mysqli_fetch_assoc($res)) {
    $label = date('M Y', strtotime($row['ym'] . '-01'));
    $labels[] = $label;
    $values[] = (int)$row['cnt'];
    $rows[] = array('label' => $label, 'count' => $row['cnt']);
}

$total_last_12 = array_sum($values);

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?php echo $total_last_12; ?> new members in the last 12 months</p>
    <a href="reports.php" class="btn btn-outline-secondary">Back to Reports</a>
</div>

<div class="gym-card p-4 mb-4">
    <canvas id="monthlyMembersChart" height="90"></canvas>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Month</th><th>New Members</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="2" class="text-muted">No registrations in this period.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr><td><?php echo htmlspecialchars($r['label']); ?></td><td><?php echo (int)$r['count']; ?></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('monthlyMembersChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{ label: 'New Members', data: <?php echo json_encode($values); ?>, backgroundColor: '#e63946' }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
</script>

<?php include "../includes/footer.php"; ?>
