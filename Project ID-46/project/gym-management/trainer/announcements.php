<?php
// ==========================================================
// trainer/announcements.php — View all announcements, and
// post new ones as a Trainer. A trainer can only delete
// announcements THEY posted (never Admin's or another trainer's).
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Announcements";
$active_menu = "announcements";
$trainer_id = $_SESSION['trainer_id'];
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((isset($_POST['title']) ? $_POST['title'] : ''));
    $desc  = trim((isset($_POST['description']) ? $_POST['description'] : ''));

    if ($title === '' || $desc === '') {
        $error_msg = "Please fill in both title and description.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO announcements (title, description, posted_by_role, posted_by_id) VALUES (?,?,'Trainer',?)");
        mysqli_stmt_bind_param($stmt, "ssi", $title, $desc, $trainer_id);
        mysqli_stmt_execute($stmt);
    }
}

// Handle delete (own announcements only)
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM announcements WHERE announcement_id=? AND posted_by_role='Trainer' AND posted_by_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $del_id, $trainer_id);
    mysqli_stmt_execute($stmt);
    header("Location: announcements.php");
    exit;
}

$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY publish_date DESC");

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

<div class="gym-card p-4 mb-3">
    <h6>Post New Announcement</h6>
    <form method="POST">
        <div class="mb-2"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
        <div class="mb-2"><textarea name="description" class="form-control" rows="2" placeholder="Description" required></textarea></div>
        <button type="submit" class="btn btn-gym-primary btn-sm">Post</button>
    </form>
</div>

<div class="gym-card p-3">
    <?php while ($a = mysqli_fetch_assoc($announcements)): ?>
        <div class="border-bottom py-3 d-flex justify-content-between align-items-start">
            <div>
                <h6 class="mb-1"><?php echo htmlspecialchars($a['title']); ?></h6>
                <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars($a['description'])); ?></p>
                <small class="text-muted">Posted by <?php echo htmlspecialchars($a['posted_by_role']); ?> on <?php echo htmlspecialchars($a['publish_date']); ?></small>
            </div>
            <?php if ($a['posted_by_role']=='Trainer' && $a['posted_by_id']==$trainer_id): ?>
                <a href="announcements.php?delete=<?php echo $a['announcement_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete your announcement?');"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>

<?php include "includes/footer.php"; ?>
