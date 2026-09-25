<?php
// ==========================================================
// includes/config.php
// Database connection file (mysqli, procedural style)
// This file is included at the very top of every page that
// needs database access.
// ==========================================================

// Load PHP 5.4 compatibility polyfills FIRST — before anything
// else runs, so password_hash(), password_verify(), and
// array_column() are guaranteed to exist no matter which PHP
// version this runs on (harmless no-op on modern PHP).
require_once __DIR__ . "/compat.php";

// Start the session here so every page that includes this
// file automatically has session access (login checks, etc.)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Database settings ----
// UwAmp (college lab) default credentials are root / root.
// XAMPP (most personal PCs) default credentials are root / (blank).
// Change $db_pass below to match whichever server you're running on.
$db_host = "localhost";
$db_user = "root";
$db_pass = "root";    // UwAmp default. XAMPP users: change this to ""
$db_name = "gym_pro";

// ---- Create connection ----
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Stop everything if the connection failed
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Force UTF-8 so names/text save correctly
mysqli_set_charset($conn, "utf8mb4");

// Base URL of the project — used for links/redirects that need
// to work no matter which folder a page is opened from.
// Change 'gym-management' if you renamed the project folder.
define('BASE_URL', '/gym-management/');

// ---- Minimum registration age (used by register.php and
// admin/member/add-member.php) ----
define('MIN_REGISTRATION_AGE', 18);

/**
 * Returns the age in whole years for a given YYYY-MM-DD date of
 * birth, or null if $dob is empty, malformed, or in the future.
 * Used to enforce the 18+ registration rule server-side — the
 * <input type="date" max="..."> attribute on the form is only a
 * convenience for the calendar picker, this is the real check.
 */
function calculate_age($dob) {
    if (empty($dob)) { return null; }
    $dob_date = @DateTime::createFromFormat('Y-m-d', $dob);
    if (!$dob_date) { return null; }
    $today = new DateTime();
    if ($dob_date > $today) { return null; }
    $diff = $today->diff($dob_date);
    return (int)$diff->y;
}

/**
 * The latest date of birth someone can enter and still be at
 * least MIN_REGISTRATION_AGE today — used as the <input max="...">
 * value so the calendar itself won't offer a too-recent date.
 */
function max_dob_for_registration() {
    return date('Y-m-d', strtotime('-' . MIN_REGISTRATION_AGE . ' years'));
}
/**
 * Formats a membership cycle as "3 Months (Sep 2026 - Dec 2026)" —
 * used everywhere a member's duration is shown, so the wording
 * never drifts between the member dashboard, membership page,
 * and admin's member detail view.
 */
function format_membership_duration($duration_months, $start_date, $expiry_date) {
    $duration_months = (int)$duration_months;
    $start_label = date('M Y', strtotime($start_date));
    $end_label = date('M Y', strtotime($expiry_date));
    $month_word = ($duration_months === 1) ? "Month" : "Months";
    return "$duration_months $month_word ($start_label - $end_label)";
}
?>
