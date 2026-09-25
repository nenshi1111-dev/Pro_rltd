<?php
// ==========================================================
// batches.php — Public Batches page (replaces plans.php)
// Shows what batches exist, their timing/availability, and
// their monthly fee — so a visitor knows the cost before they
// register and pick batches.
// ==========================================================
require_once "includes/config.php";
require_once "includes/batch-functions.php";

$page_title = "Batches";
$active_page = "batches";

$grouped = get_batches_grouped($conn, true);

include "includes/header.php";
include "includes/navbar.php";
?>

<section class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Available Batches</h2>
        <p class="text-muted">See what's on offer, then register and pick the batches that fit your schedule</p>
    </div>

    <?php foreach (['Morning', 'Evening'] as $slot): if (empty($grouped[$slot])) continue; ?>
        <h5 class="section-title"><?php echo $slot; ?> Batches</h5>
        <div class="row g-4 mb-5">
            <?php foreach ($grouped[$slot] as $b):
                // Live seat count for this batch
                $cnt_stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM member_batches WHERE batch_id=?");
                mysqli_stmt_bind_param($cnt_stmt, "i", $b['batch_id']);
                mysqli_stmt_execute($cnt_stmt);
                $filled = mysqli_fetch_assoc(mysqli_stmt_get_result($cnt_stmt))['c'];
                $spots_left = max(0, $b['capacity'] - $filled);
            ?>
            <div class="col-md-4">
                <div class="gym-card p-4 h-100">
                    <h5><?php echo htmlspecialchars($b['batch_name']); ?></h5>
                    <p class="text-muted mb-2">
                        <i class="bi bi-clock me-1"></i>
                        <?php echo substr($b['start_time'],0,5) . " - " . substr($b['end_time'],0,5); ?>
                    </p>
                    <p class="fw-bold text-danger mb-2">Rs. <?php echo number_format($b['monthly_fee'], 2); ?> / month</p>
                    <p class="mb-0 small <?php echo $spots_left > 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $spots_left > 0 ? "$spots_left spot(s) left" : "Batch full"; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-3">
        <p class="text-muted">You can join more than one batch at registration, as long as their timings don't overlap.</p>
        <a href="register.php" class="btn btn-gym-primary btn-lg">Register Now</a>
    </div>
</section>

<?php include "includes/footer.php"; ?>
