<?php
// ==========================================================
// admin/announcement/announcements.php — Announcements list
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Announcements";
$active_menu = "announcements";

$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY publish_date DESC");

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($_GET['err']); ?></div><?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <a href="add-announcement.php" class="btn btn-gym-primary"><i class="bi bi-plus-circle me-1"></i>New Announcement</a>
</div>

<div class="gym-card p-3">
    <?php while ($a = mysqli_fetch_assoc($announcements)): ?>
        <div class="border-bottom py-3 d-flex justify-content-between align-items-start">
            <div>
                <h6 class="mb-1"><?php echo htmlspecialchars($a['title']); ?></h6>
                <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars($a['description'])); ?></p>
                <small class="text-muted">Posted by <?php echo htmlspecialchars($a['posted_by_role']); ?> on <?php echo htmlspecialchars($a['publish_date']); ?></small>
            </div>
            <a href="delete-announcement.php?id=<?php echo $a['announcement_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this announcement?');"><i class="bi bi-trash"></i></a>
        </div>
    <?php endwhile; ?>
</div>

<?php include "../includes/footer.php"; ?>
