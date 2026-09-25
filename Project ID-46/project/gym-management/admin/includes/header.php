<?php
// ==========================================================
// admin/includes/header.php
// Opens the HTML document for every Admin panel page.
// $page_title should be set by the page BEFORE including this.
// ==========================================================
if (!isset($page_title)) { $page_title = "Admin Panel"; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Gym-Pro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>images/favicon.ico">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/css/admin.css">
</head>
<body>
<div class="d-flex">
