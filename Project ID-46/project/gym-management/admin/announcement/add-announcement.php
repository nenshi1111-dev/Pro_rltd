<?php
// ==========================================================
// admin/announcement/add-announcement.php — Post new announcement
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "New Announcement";
$active_menu = "announcements";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((isset($_POST['title']) ? $_POST['title'] : ''));
    $desc  = trim((isset($_POST['description']) ? $_POST['description'] : ''));

    if ($title === '' || $desc === '') {
        $error_msg = "Please fill in both title and description.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO announcements (title, description, posted_by_role, posted_by_id) VALUES (?,?,'Admin',?)");
        mysqli_stmt_bind_param($stmt, "ssi", $title, $desc, $_SESSION['admin_id']);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: announcements.php?msg=" . urlencode("Announcement posted successfully."));
            exit;
        } else {
            $error_msg = "Could not post announcement. Please try again.";
        }
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

<div class="gym-card p-4" style="max-width:600px;">
    <form method="POST">
        <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" rows="4" class="form-control" required></textarea></div>
        <button type="submit" class="btn btn-gym-primary">Post Announcement</button>
        <a href="announcements.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
