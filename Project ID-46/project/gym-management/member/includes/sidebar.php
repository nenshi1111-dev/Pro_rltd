<?php
// ==========================================================
// member/includes/sidebar.php
// If the member's status is not Active, only a minimal menu
// is shown (Expired, Renewal Status, Logout) — this matches
// the restriction enforced in member/includes/auth.php.
// ==========================================================
if (!isset($active_menu)) { $active_menu = ""; }
function mmenu($key, $active) { return $key === $active ? 'active' : ''; }

$stmt = mysqli_prepare($conn, "SELECT status FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['member_id']);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

$my_status = isset($row['status']) ? $row['status'] : 'Active';
?>
<div class="panel-sidebar p-3" id="panelSidebar">
    <a href="<?php echo BASE_URL; ?>member/index.php" class="d-flex align-items-center gap-2 text-decoration-none mb-4">
        <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
        <span class="text-white fw-bold fs-5">GYM-PRO</span>
    </a>
    <div class="text-white-50 small mb-2 text-uppercase">Member Panel</div>
    <nav class="nav flex-column">
        <?php if ($my_status === 'Active'): ?>
            <a class="nav-link <?php echo mmenu('dashboard',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a class="nav-link <?php echo mmenu('profile',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/profile.php"><i class="bi bi-person-fill me-2"></i>My Profile</a>
            <a class="nav-link <?php echo mmenu('membership',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/membership.php"><i class="bi bi-card-checklist me-2"></i>Membership</a>
            <a class="nav-link <?php echo mmenu('payments',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/payment-history.php"><i class="bi bi-cash-coin me-2"></i>Payment History</a>
            <a class="nav-link <?php echo mmenu('attendance',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/attendance.php"><i class="bi bi-calendar-check-fill me-2"></i>My Attendance</a>
            <a class="nav-link <?php echo mmenu('batch',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/my-batch.php"><i class="bi bi-diagram-3-fill me-2"></i>My Batch</a>
            <a class="nav-link <?php echo mmenu('announcements',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/announcement.php"><i class="bi bi-megaphone-fill me-2"></i>Announcements</a>
            <a class="nav-link <?php echo mmenu('settings',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/change-password.php"><i class="bi bi-gear-fill me-2"></i>Change Password</a>
        <?php elseif ($my_status === 'Pending Payment'): ?>
            <a class="nav-link <?php echo mmenu('pending_payment',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/pending-payment.php"><i class="bi bi-lock-fill me-2"></i>Complete Payment</a>
            <a class="nav-link <?php echo mmenu('settings',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/change-password.php"><i class="bi bi-gear-fill me-2"></i>Change Password</a>
        <?php else: ?>
            <a class="nav-link <?php echo mmenu('expired',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/expired.php"><i class="bi bi-exclamation-triangle-fill me-2"></i>Membership Expired</a>
            <a class="nav-link <?php echo mmenu('renew_status',$active_menu); ?>" href="<?php echo BASE_URL; ?>member/renew-status.php"><i class="bi bi-hourglass-split me-2"></i>Renewal Status</a>
        <?php endif; ?>
        <hr class="text-white-50">
        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>View Public Website</a>
        <a class="nav-link" href="<?php echo BASE_URL; ?>member/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
</div>
<div class="panel-overlay" id="panelOverlay"></div>
