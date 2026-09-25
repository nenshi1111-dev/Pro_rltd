<?php
// ==========================================================
// trainer/includes/topbar.php
// ==========================================================
?>
<div class="flex-grow-1">
    <div class="panel-topbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <button class="btn panel-hamburger d-lg-none" id="sidebarToggle" aria-label="Toggle menu"><i class="bi bi-list fs-4"></i></button>
            <h5 class="mb-0"><?php echo htmlspecialchars((isset($page_title) ? $page_title : 'Trainer')); ?></h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle fs-4 text-secondary"></i>
            <span class="fw-semibold d-none d-sm-inline"><?php echo htmlspecialchars((isset($_SESSION['trainer_name']) ? $_SESSION['trainer_name'] : 'Trainer')); ?></span>
        </div>
    </div>
    <div class="panel-content">
