<?php
// ==========================================================
// admin/index.php — Admin Dashboard
// Summary cards pull live COUNT() queries, so they always
// reflect the current state after any CRUD operation.
// ==========================================================
require_once "../includes/config.php";
require_once "../includes/notification-functions.php";
require_once "includes/auth.php";

$page_title = "Dashboard";
$active_menu = "dashboard";

// Auto-expire sweep — keep stats accurate without a cron job
mysqli_query($conn, "UPDATE members SET status='Expired' WHERE status='Active' AND membership_expiry_date < CURDATE()");

// ---- Live counts for summary cards ----
function count_rows($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    return $res ? mysqli_fetch_assoc($res)['c'] : 0;
}
$total_members     = count_rows($conn, "SELECT COUNT(*) c FROM members");
$active_members     = count_rows($conn, "SELECT COUNT(*) c FROM members WHERE status='Active'");
$expired_members    = count_rows($conn, "SELECT COUNT(*) c FROM members WHERE status='Expired'");
$total_trainers     = count_rows($conn, "SELECT COUNT(*) c FROM trainers");
$total_batches      = count_rows($conn, "SELECT COUNT(*) c FROM batches");
$pending_reg        = count_rows($conn, "SELECT COUNT(*) c FROM requested_members WHERE status='Pending'");
$pending_renew      = count_rows($conn, "SELECT COUNT(*) c FROM renewal_requests WHERE status='Pending'");
$pending_payment_members = count_rows($conn, "SELECT COUNT(*) c FROM members WHERE status='Pending Payment'");

// ---- Data for a simple "members per batch" chart ----
$chart_labels = [];
$chart_values = [];
$res = mysqli_query($conn, "
    SELECT b.batch_name, COUNT(mb.member_id) AS total
    FROM batches b
    LEFT JOIN member_batches mb ON mb.batch_id = b.batch_id
    GROUP BY b.batch_id, b.batch_name
    ORDER BY b.start_time
");
while ($res_row = mysqli_fetch_assoc($res)) {
    $chart_labels[] = $res_row['batch_name'];
    $chart_values[] = (int)$res_row['total'];
}

// ---- Monthly registrations (last 6 months) ----
$reg_labels = array(); $reg_values = array();
$res = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM members
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym ASC
");
while ($row = mysqli_fetch_assoc($res)) {
    $reg_labels[] = date('M', strtotime($row['ym'] . '-01'));
    $reg_values[] = (int)$row['cnt'];
}

// ---- Revenue (last 6 months) ----
$rev_labels = array(); $rev_values = array();
$res = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
    FROM payments
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym ASC
");
while ($row = mysqli_fetch_assoc($res)) {
    $rev_labels[] = date('M', strtotime($row['ym'] . '-01'));
    $rev_values[] = (float)$row['total'];
}

// ---- Attendance overview (last 30 days, Present vs Absent) ----
$att_present = 0; $att_absent = 0;
$res = mysqli_query($conn, "
    SELECT status, COUNT(*) c FROM attendance
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY status
");
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['status'] === 'Present') { $att_present = (int)$row['c']; }
    else { $att_absent = (int)$row['c']; }
}

// ---- Active / Expired / Inactive / Pending Payment breakdown ----
$status_labels = array('Active', 'Expired', 'Inactive', 'Pending Payment');
$status_values = array(
    $active_members,
    $expired_members,
    count_rows($conn, "SELECT COUNT(*) c FROM members WHERE status='Inactive'"),
    $pending_payment_members
);

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-1">
            <h3 data-count="<?php echo $active_members; ?>">0</h3>
            <p>Active Members</p>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-2">
            <h3 data-count="<?php echo $total_trainers; ?>">0</h3>
            <p>Trainers</p>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-3">
            <h3 data-count="<?php echo $total_batches; ?>">0</h3>
            <p>Batches</p>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card bg-stat-4">
            <h3 data-count="<?php echo $expired_members; ?>">0</h3>
            <p>Expired Memberships</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="gym-card p-3">
            <h6 class="text-muted">Total Members</h6>
            <h4 data-count="<?php echo $total_members; ?>">0</h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="gym-card p-3">
            <h6 class="text-muted">Pending Registrations</h6>
            <h4><?php echo $pending_reg; ?> <a href="request/registration_request/requests.php" class="small">view</a></h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="gym-card p-3">
            <h6 class="text-muted">Pending Renewals</h6>
            <h4><?php echo $pending_renew; ?> <a href="request/renewable_request/requests.php" class="small">view</a></h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="gym-card p-3">
            <h6 class="text-muted">Pending Payments</h6>
            <h4><?php echo $pending_payment_members; ?> <a href="payment/pending-payments.php" class="small">view</a></h4>
        </div>
    </div>
</div>

<?php $admin_expiring = get_expiring_soon_members($conn, 7); ?>
<?php if (!empty($admin_expiring)): ?>
<div class="gym-card p-4 mb-4">
    <h6 class="mb-3"><i class="bi bi-clock-history text-warning me-1"></i>Members Expiring in the Next 7 Days</h6>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Expiry Date</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($admin_expiring as $ex): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ex['member_code']); ?></td>
                    <td><?php echo htmlspecialchars($ex['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($ex['membership_expiry_date']); ?></td>
                    <td><span class="badge badge-pending"><?php echo format_days_left($ex['days_left']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<h6 class="mb-3">Dashboard Analytics</h6>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="gym-card p-4">
            <h6 class="mb-3">Monthly New Registrations (6 mo)</h6>
            <canvas id="registrationsChart" height="150"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="gym-card p-4">
            <h6 class="mb-3">Revenue (6 mo)</h6>
            <canvas id="revenueChart" height="150"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="gym-card p-4">
            <h6 class="mb-3">Attendance, Last 30 Days</h6>
            <canvas id="attendanceChart" height="150"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="gym-card p-4">
            <h6 class="mb-3">Member Status Breakdown</h6>
            <canvas id="statusChart" height="150"></canvas>
        </div>
    </div>
</div>

<div class="gym-card p-4">
    <h6 class="mb-3">Members per Batch</h6>
    <canvas id="batchChart" height="90"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('batchChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Members',
                data: <?php echo json_encode($chart_values); ?>,
                backgroundColor: '#e63946'
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    new Chart(document.getElementById('registrationsChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($reg_labels); ?>,
            datasets: [{ label: 'New Members', data: <?php echo json_encode($reg_values); ?>, backgroundColor: '#457b9d' }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($rev_labels); ?>,
            datasets: [{ label: 'Revenue (Rs.)', data: <?php echo json_encode($rev_values); ?>, borderColor: '#e63946', backgroundColor: 'rgba(230,57,70,0.15)', fill: true, tension: 0.3 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent'],
            datasets: [{ data: [<?php echo (int)$att_present; ?>, <?php echo (int)$att_absent; ?>], backgroundColor: ['#2a9d8f', '#e63946'] }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{ data: <?php echo json_encode($status_values); ?>, backgroundColor: ['#2a9d8f', '#e63946', '#6c757d', '#e9873e'] }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>

<?php include "includes/footer.php"; ?>
