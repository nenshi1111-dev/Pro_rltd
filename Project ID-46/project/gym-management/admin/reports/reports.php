<?php
// ==========================================================
// admin/reports/reports.php — Reports hub
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Reports";
$active_menu = "reports";

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="row g-4">
    <div class="col-md-6 col-lg-3">
        <a href="monthly-members.php" class="text-decoration-none">
            <div class="gym-card p-4 text-center h-100">
                <i class="bi bi-person-plus-fill fs-1 text-danger"></i>
                <h6 class="mt-3 text-dark">New Members</h6>
                <p class="text-muted small mb-0">Monthly registration trend</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="revenue.php" class="text-decoration-none">
            <div class="gym-card p-4 text-center h-100">
                <i class="bi bi-graph-up-arrow fs-1 text-danger"></i>
                <h6 class="mt-3 text-dark">Revenue</h6>
                <p class="text-muted small mb-0">Monthly payments collected</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="expired-memberships.php" class="text-decoration-none">
            <div class="gym-card p-4 text-center h-100">
                <i class="bi bi-exclamation-octagon-fill fs-1 text-danger"></i>
                <h6 class="mt-3 text-dark">Expired Memberships</h6>
                <p class="text-muted small mb-0">Who's lapsed, and for how long</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="batch-report.php" class="text-decoration-none">
            <div class="gym-card p-4 text-center h-100">
                <i class="bi bi-diagram-3-fill fs-1 text-danger"></i>
                <h6 class="mt-3 text-dark">Batch / Trainer Report</h6>
                <p class="text-muted small mb-0">Fill rate, attendance, revenue per batch</p>
            </div>
        </a>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
