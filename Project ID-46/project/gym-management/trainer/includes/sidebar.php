<?php
// ==========================================================
// trainer/includes/sidebar.php
// ==========================================================
if (!isset($active_menu)) { $active_menu = ""; }
function tmenu($key, $active) { return $key === $active ? 'active' : ''; }
?>
<div class="panel-sidebar p-3" id="panelSidebar">
    <a href="<?php echo BASE_URL; ?>trainer/index.php" class="d-flex align-items-center gap-2 text-decoration-none mb-4">
        <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
        <span class="text-white fw-bold fs-5">GYM-PRO</span>
    </a>
    <div class="text-white-50 small mb-2 text-uppercase">Trainer Panel</div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo tmenu('dashboard',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a class="nav-link <?php echo tmenu('batches',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/batch/my-batches.php"><i class="bi bi-diagram-3-fill me-2"></i>My Batches</a>
        <a class="nav-link <?php echo tmenu('members',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/my-members.php"><i class="bi bi-people-fill me-2"></i>My Members</a>
        <a class="nav-link <?php echo tmenu('schedule',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/today-schedule.php"><i class="bi bi-calendar-day me-2"></i>Today's Schedule</a>
        <a class="nav-link <?php echo tmenu('workout',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/workout-plan.php"><i class="bi bi-clipboard2-pulse me-2"></i>Workout Plans</a>
        <a class="nav-link <?php echo tmenu('attendance',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/attendance.php"><i class="bi bi-calendar-check-fill me-2"></i>Mark Attendance</a>
        <a class="nav-link <?php echo tmenu('announcements',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/announcements.php"><i class="bi bi-megaphone-fill me-2"></i>Announcements</a>
        <a class="nav-link <?php echo tmenu('profile',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/profile.php"><i class="bi bi-person-badge-fill me-2"></i>My Profile</a>
        <a class="nav-link <?php echo tmenu('settings',$active_menu); ?>" href="<?php echo BASE_URL; ?>trainer/settings/change-password.php"><i class="bi bi-gear-fill me-2"></i>Change Password</a>
        <hr class="text-white-50">
        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>View Public Website</a>
        <a class="nav-link" href="<?php echo BASE_URL; ?>trainer/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
</div>
<div class="panel-overlay" id="panelOverlay"></div>
