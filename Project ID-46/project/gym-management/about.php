<?php
// ==========================================================
// about.php — Public About page
// ==========================================================
require_once "includes/config.php";
$page_title = "About Us";
$active_page = "about";
include "includes/header.php";
include "includes/navbar.php";
?>

<section class="container py-5">
    <h2 class="section-title">About Gym-Pro</h2>
    <div class="row align-items-center g-5">
        <div class="col-md-6">
            <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=700"
                 class="img-fluid rounded shadow-sm" alt="Gym floor">
        </div>
        <div class="col-md-6">
            <p>Gym-Pro is a full-service fitness center offering strength training,
               group batches, and personal guidance from certified trainers. Our
               mission is simple: help every member build a stronger, healthier
               version of themselves in a clean, well-equipped, and supportive
               environment.</p>
            <p>Membership is managed through our online system — once you register
               or visit us in person, our admin team reviews and activates your
               membership so you get full access to your dashboard, batch
               schedule, and workout plans.</p>
            <ul class="list-unstyled">
                <li><i class="bi bi-check-circle-fill text-danger me-2"></i>Modern equipment, regularly maintained</li>
                <li><i class="bi bi-check-circle-fill text-danger me-2"></i>Morning &amp; evening batch options</li>
                <li><i class="bi bi-check-circle-fill text-danger me-2"></i>Trainer-designed weekly workout plans</li>
            </ul>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
