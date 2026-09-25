<?php
// ==========================================================
// admin/includes/sidebar.php
// Shared sidebar for every Admin page. Highlights the active
// section using $active_menu, set by each page before include.
// ==========================================================
if (!isset($active_menu)) { $active_menu = ""; }
function amenu($key, $active) { return $key === $active ? 'active' : ''; }
?>
<div class="panel-sidebar p-3" id="panelSidebar">
    <a href="<?php echo BASE_URL; ?>admin/index.php" class="d-flex align-items-center gap-2 text-decoration-none mb-4">
        <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
        <span class="text-white fw-bold fs-5">GYM-PRO</span>
    </a>
    <div class="text-white-50 small mb-2 text-uppercase">Admin Panel</div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo amenu('dashboard',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a class="nav-link <?php echo amenu('members',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/member/members.php"><i class="bi bi-people-fill me-2"></i>Members</a>
        <a class="nav-link <?php echo amenu('trainers',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/trainer/trainers.php"><i class="bi bi-person-badge-fill me-2"></i>Trainers</a>
        <a class="nav-link <?php echo amenu('batches',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/batch/batches.php"><i class="bi bi-diagram-3-fill me-2"></i>Batches</a>
        <a class="nav-link <?php echo amenu('payments',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/payment/payments.php"><i class="bi bi-cash-coin me-2"></i>Payments</a>
        <a class="nav-link <?php echo amenu('submissions',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/payment/submissions.php"><i class="bi bi-qr-code me-2"></i>Online Payment Requests</a>
        <a class="nav-link <?php echo amenu('reports',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/reports/reports.php"><i class="bi bi-bar-chart-fill me-2"></i>Reports</a>
        <a class="nav-link <?php echo amenu('announcements',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/announcement/announcements.php"><i class="bi bi-megaphone-fill me-2"></i>Announcements</a>
        <a class="nav-link <?php echo amenu('reg_requests',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/request/registration_request/requests.php"><i class="bi bi-person-plus-fill me-2"></i>Registration Requests</a>
        <a class="nav-link <?php echo amenu('renew_requests',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/request/renewable_request/requests.php"><i class="bi bi-arrow-repeat me-2"></i>Renewal Requests</a>
        <a class="nav-link <?php echo amenu('settings',$active_menu); ?>" href="<?php echo BASE_URL; ?>admin/settings/settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a>
        <hr class="text-white-50">
        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>View Public Website</a>
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
</div>
<div class="panel-overlay" id="panelOverlay"></div>
