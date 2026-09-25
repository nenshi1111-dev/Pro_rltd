<?php
// ==========================================================
// admin/reports/revenue.php — Revenue by month (last 12 months)
// Based on actual logged payments — not "amount due", since
// that's a projection, this report is about money actually
// collected.
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Revenue Report";
$active_menu = "reports";

$res = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
    FROM payments
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
");

$labels = array();
$values = array();
$rows = array();
while ($row = mysqli_fetch_assoc($res)) {
    $label = date('M Y', strtotime($row['ym'] . '-01'));
    $labels[] = $label;
    $values[] = (float)$row['total'];
    $rows[] = array('label' => $label, 'total' => $row['total']);
}

$grand_total = array_sum($values);

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Rs. <?php echo number_format($grand_total, 2); ?> collected in the last 12 months</p>
    <div class="d-flex gap-2">
        <a href="export-revenue.php" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export CSV</a>
        <a href="reports.php" class="btn btn-outline-secondary">Back to Reports</a>
    </div>
</div>

<div class="gym-card p-4 mb-4">
    <canvas id="revenueChart" height="90"></canvas>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Month</th><th>Revenue</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="2" class="text-muted">No payments logged in this period.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr><td><?php echo htmlspecialchars($r['label']); ?></td><td>Rs. <?php echo number_format($r['total'], 2); ?></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{ label: 'Revenue (Rs.)', data: <?php echo json_encode($values); ?>, borderColor: '#e63946', backgroundColor: 'rgba(230,57,70,0.15)', fill: true, tension: 0.3 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>

<?php include "../includes/footer.php"; ?>
