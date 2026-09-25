<?php
// ==========================================================
// contact.php — Public Contact page
// A simple contact form. It does not need its own DB table per
// spec, so on submit we just show a success message (extend
// later by adding a "contact_messages" table if needed).
// ==========================================================
require_once "includes/config.php";
$page_title = "Contact Us";
$active_page = "contact";

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((isset($_POST['name']) ? $_POST['name'] : ''));
    $email = trim((isset($_POST['email']) ? $_POST['email'] : ''));
    $message = trim((isset($_POST['message']) ? $_POST['message'] : ''));

    // Server-side validation (never trust HTML "required" alone)
    if ($name === '' || $email === '' || $message === '') {
        $error_msg = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        // In a full build, insert into a contact_messages table here.
        $success_msg = "Thank you, " . htmlspecialchars($name) . "! Your message has been received.";
    }
}

include "includes/header.php";
include "includes/navbar.php";

// Read settings for contact info display
$settings = [];
$res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
while ($row = mysqli_fetch_assoc($res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<section class="container py-5">
    <h2 class="section-title">Contact Us</h2>
    <div class="row g-5">
        <div class="col-md-5">
            <p><i class="bi bi-geo-alt-fill text-danger me-2"></i><?php echo htmlspecialchars((isset($settings['gym_address']) ? $settings['gym_address'] : '')); ?></p>
            <p><i class="bi bi-envelope-fill text-danger me-2"></i><?php echo htmlspecialchars((isset($settings['gym_email']) ? $settings['gym_email'] : '')); ?></p>
            <p><i class="bi bi-telephone-fill text-danger me-2"></i><?php echo htmlspecialchars((isset($settings['gym_phone']) ? $settings['gym_phone'] : '')); ?></p>
            <p class="text-muted mt-4">Visit our gym or contact us to become a member.</p>
        </div>
        <div class="col-md-7">
            <?php if ($success_msg): ?>
                <div class="alert alert-success auto-hide-alert"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form method="POST" action="contact.php">
                <div class="mb-3">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars((isset($_POST['name']) ? $_POST['name'] : '')); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars((isset($_POST['email']) ? $_POST['email'] : '')); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" rows="4" class="form-control" required><?php echo htmlspecialchars((isset($_POST['message']) ? $_POST['message'] : '')); ?></textarea>
                </div>
                <button type="submit" class="btn btn-gym-primary">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
