<?php
// ==========================================================
// member/index.php — Member Dashboard
// If status != Active, auth.php already redirected to expired.php
// before this file's own content ever renders.
// ==========================================================
require_once "../includes/config.php";
require_once "../includes/notification-functions.php";
require_once "includes/auth.php";

$page_title = "Dashboard";
$active_menu = "dashboard";
$member_id = $_SESSION['member_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// This member's batches (for the dashboard card + count)
$batch_stmt = mysqli_prepare($conn, "SELECT b.batch_name, b.start_time, b.end_time FROM member_batches mb JOIN batches b ON mb.batch_id=b.batch_id WHERE mb.member_id=? ORDER BY b.start_time");
mysqli_stmt_bind_param($batch_stmt, "i", $member_id);
mysqli_stmt_execute($batch_stmt);
$my_batches = mysqli_stmt_get_result($batch_stmt);
$batch_list = [];
while ($row = mysqli_fetch_assoc($my_batches)) { $batch_list[] = $row; }

// Days remaining until expiry (for a friendly reminder)
$days_left = (int)((strtotime($member['membership_expiry_date']) - strtotime(date('Y-m-d'))) / 86400);

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card bg-stat-1">
            <h3 data-count="<?php echo count($batch_list); ?>">0</h3>
            <p>My Batches</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card bg-stat-2">
            <h3 style="font-size:1.4rem;"><?php echo !empty($batch_list) ? htmlspecialchars(implode(', ', array_column($batch_list, 'batch_name'))) : 'No Batch'; ?></h3>
            <p>Enrolled In</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card <?php echo $days_left <= 7 ? 'bg-stat-4' : 'bg-stat-3'; ?>">
            <h3><?php echo $days_left; ?> days</h3>
            <p>Until Membership Expires</p>
        </div>
    </div>
</div>

<?php if ($days_left <= 7): ?>
    <div class="alert alert-warning">
        <strong><?php echo format_days_left($days_left); ?></strong> — your membership expires on
        <strong><?php echo htmlspecialchars($member['membership_expiry_date']); ?></strong>.
        Consider <a href="membership.php">renewing soon</a> to avoid interruption.
    </div>
<?php endif; ?>

<div class="gym-card p-4">
    <p class="mb-1">Welcome back, <strong><?php echo htmlspecialchars($member['full_name']); ?></strong> (<?php echo htmlspecialchars($member['member_code']); ?>)</p>
    <p class="text-muted small mb-0">Membership: <?php echo htmlspecialchars(format_membership_duration($member['duration_months'], $member['membership_start_date'], $member['membership_expiry_date'])); ?></p>
</div>

<?php include "includes/footer.php"; ?>
