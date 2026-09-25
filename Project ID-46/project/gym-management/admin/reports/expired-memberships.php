<?php
// ==========================================================
// admin/reports/expired-memberships.php — Expired members list
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Expired Memberships";
$active_menu = "reports";

// Auto-expire sweep first, same as the admin member list, so this
// report is always accurate without needing anyone to log in first
mysqli_query($conn, "UPDATE members SET status='Expired' WHERE status='Active' AND membership_expiry_date < CURDATE()");

$res = mysqli_query($conn, "
    SELECT member_code, full_name, phone, email, membership_expiry_date,
           DATEDIFF(CURDATE(), membership_expiry_date) AS days_expired
    FROM members
    WHERE status = 'Expired'
    ORDER BY membership_expiry_date DESC
");

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?php echo mysqli_num_rows($res); ?> expired membership(s)</p>
    <div class="d-flex gap-2">
        <a href="export-expired.php" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export CSV</a>
        <a href="reports.php" class="btn btn-outline-secondary">Back to Reports</a>
    </div>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Expired On</th><th>Days Since Expired</th><th>Actions</th></tr></thead>
            <tbody>
                <?php $found=false; while ($m = mysqli_fetch_assoc($res)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['member_code']); ?></td>
                    <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['phone']); ?></td>
                    <td><?php echo htmlspecialchars($m['membership_expiry_date']); ?></td>
                    <td><span class="badge badge-expired"><?php echo (int)$m['days_expired']; ?> day(s)</span></td>
                    <td><a href="../member/members.php?q=<?php echo urlencode($m['member_code']); ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endwhile; if (!$found): ?>
                    <tr><td colspan="6" class="text-muted">No expired memberships right now.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
