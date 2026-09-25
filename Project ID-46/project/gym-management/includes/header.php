<?php
// ==========================================================
// includes/header.php
// Opens the HTML document for every public-facing page.
// $page_title should be set by the page BEFORE including this file.
// ==========================================================
if (!isset($page_title)) {
    $page_title = "Gym-Pro";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Gym-Pro</title>

    <!-- Bootstrap 5 (layout helpers only — theme comes from style.css) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (for eye-toggle, dumbbell logo, menu icons) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom site theme -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>images/favicon.ico">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/animations.css">
</head>
<body>
