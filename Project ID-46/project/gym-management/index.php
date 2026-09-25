<?php
// ==========================================================
// index.php — Public Homepage
// ==========================================================
require_once "includes/config.php";

$page_title = "Home";
$active_page = "home";
include "includes/header.php";
include "includes/navbar.php";

// Pull a few live numbers for the homepage (safe, read-only queries)
$member_count = 0;
$trainer_count = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM members WHERE status='Active'");
if ($result) { $member_count = mysqli_fetch_assoc($result)['c']; }
$result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM trainers WHERE status='Active'");
if ($result) { $trainer_count = mysqli_fetch_assoc($result)['c']; }
?>

<!-- Hero -->
<section class="gym-hero">
    <div class="container">
        <h1>Build Strength. Build Discipline.</h1>
        <p class="lead">Gym-Pro is a modern fitness center with expert trainers, structured batches, and a clean online membership system.</p>
        <a href="about.php" class="btn btn-gym-primary btn-lg mt-3">Learn More</a>
    </div>
</section>

<!-- Quick stats -->
<section class="container py-5">
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="gym-card p-4">
                <h2 class="fw-bold text-danger"><?php echo (int)$member_count; ?>+</h2>
                <p>Active Members</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="gym-card p-4">
                <h2 class="fw-bold text-danger"><?php echo (int)$trainer_count; ?>+</h2>
                <p>Certified Trainers</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="gym-card p-4">
                <h2 class="fw-bold text-danger">7</h2>
                <p>Days a Week Open</p>
            </div>
        </div>
    </div>
</section>

<!-- Why choose us -->
<section class="container py-4">
    <h2 class="section-title">Why Choose Gym-Pro</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="gym-card p-4 h-100">
                <i class="bi bi-people-fill fs-2 text-danger"></i>
                <h5 class="mt-3">Expert Trainers</h5>
                <p class="text-muted">Certified trainers who design batch-wise workout plans for every fitness level.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="gym-card p-4 h-100">
                <i class="bi bi-calendar-check fs-2 text-danger"></i>
                <h5 class="mt-3">Structured Batches</h5>
                <p class="text-muted">Morning and evening batches so training fits around your schedule.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="gym-card p-4 h-100">
                <i class="bi bi-graph-up-arrow fs-2 text-danger"></i>
                <h5 class="mt-3">Track Progress</h5>
                <p class="text-muted">Your own member dashboard for membership status, batch info, and announcements.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to action (no "Join Now" per spec) -->
<section class="container py-5 text-center">
    <h3>Interested in becoming a member?</h3>
    <p class="text-muted">Visit our gym or contact us to become a member.</p>
    <a href="register.php" class="btn btn-gym-primary btn-lg">Register</a>
</section>

<?php include "includes/footer.php"; ?>
