<?php
// ==========================================================
// includes/navbar.php
// Shared public navbar. Highlights the active page using
// $active_page, which each page sets before including this file.
// ==========================================================
if (!isset($active_page)) {
    $active_page = "";
}
?>
<nav class="navbar navbar-expand-lg navbar-dark gym-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>index.php">
            <span class="gym-logo-badge"><img src="<?php echo BASE_URL; ?>images/logo.png" alt="Gym-Pro logo" width="24" height="24"></span>
            <span class="fw-bold">GYM-PRO</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link <?php echo $active_page=='home'?'active':''; ?>" href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active_page=='about'?'active':''; ?>" href="<?php echo BASE_URL; ?>about.php">About</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active_page=='batches'?'active':''; ?>" href="<?php echo BASE_URL; ?>batches.php">Batches</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active_page=='gallery'?'active':''; ?>" href="<?php echo BASE_URL; ?>gallery.php">Gallery</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active_page=='contact'?'active':''; ?>" href="<?php echo BASE_URL; ?>contact.php">Contact</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active_page=='register'?'active':''; ?>" href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                <li class="nav-item">
                    <a class="btn btn-gym-primary btn-sm ms-lg-2" href="<?php echo BASE_URL; ?>login.php">Member Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
