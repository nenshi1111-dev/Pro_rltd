<?php
// ==========================================================
// gallery.php — Public Gallery page (static image showcase)
// ==========================================================
require_once "includes/config.php";
$page_title = "Gallery";
$active_page = "gallery";
include "includes/header.php";
include "includes/navbar.php";

// Simple static gallery list — replace with images/ folder photos later
$gallery_images = [
    "https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600",
    "https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600",
    "https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=600",
    "images/yoga.jpg",
    "https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600",
    "images/couple.jpg",
    "images/image1.jpg",
    "images/image2.jpg",
    "images/image3.jpg",
    "images/image4.jpg",
    "images/image5.jpg",
    "images/image6.jpg",
];
?>

<section class="container py-5">
    <h2 class="section-title">Gallery</h2>
    <div class="row g-3">
        <?php foreach ($gallery_images as $img): ?>
            <div class="col-md-4 col-6">
                <img src="<?php echo htmlspecialchars($img); ?>" class="img-fluid rounded shadow-sm" alt="Gym-Pro gallery photo">
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include "includes/footer.php"; ?>
