<?php
// ==========================================================
// member/announcement.php — View all announcements (read-only)
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Announcements";
$active_menu = "announcements";

$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY publish_date DESC");

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="gym-card p-3">
    <?php while ($a = mysqli_fetch_assoc($announcements)): ?>
        <div class="border-bottom py-3">
            <h6 class="mb-1"><?php echo htmlspecialchars($a['title']); ?></h6>
            <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars($a['description'])); ?></p>
            <small class="text-muted">Posted by <?php echo htmlspecialchars($a['posted_by_role']); ?> on <?php echo htmlspecialchars($a['publish_date']); ?></small>
        </div>
    <?php endwhile; ?>
</div>

<?php include "includes/footer.php"; ?>
