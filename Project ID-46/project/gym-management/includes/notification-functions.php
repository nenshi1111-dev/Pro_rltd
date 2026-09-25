<?php
// ==========================================================
// includes/notification-functions.php
// Shared helpers for expiry-related notifications. Included by
// admin topbar (every page), trainer dashboard, and member
// dashboard. One source of truth for "who's expiring soon".
//
// Include this AFTER includes/config.php (needs $conn).
// ==========================================================

/**
 * Members whose status is Active and whose expiry date falls
 * within the next $days days (inclusive of today). Optionally
 * scoped to only members inside batches a specific trainer is
 * assigned to.
 * Returns an array of rows: member_id, member_code, full_name,
 * membership_expiry_date, days_left.
 */
function get_expiring_soon_members($conn, $days = 7, $trainer_id = null) {
    if ($trainer_id) {
        $sql = "
            SELECT DISTINCT m.member_id, m.member_code, m.full_name, m.membership_expiry_date,
                   DATEDIFF(m.membership_expiry_date, CURDATE()) AS days_left
            FROM members m
            JOIN member_batches mb ON mb.member_id = m.member_id
            JOIN batch_trainers bt ON bt.batch_id = mb.batch_id
            WHERE m.status = 'Active'
              AND m.membership_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
              AND bt.trainer_id = ?
            ORDER BY m.membership_expiry_date ASC
        ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $days, $trainer_id);
    } else {
        $sql = "
            SELECT m.member_id, m.member_code, m.full_name, m.membership_expiry_date,
                   DATEDIFF(m.membership_expiry_date, CURDATE()) AS days_left
            FROM members m
            WHERE m.status = 'Active'
              AND m.membership_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY m.membership_expiry_date ASC
        ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $days);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

/**
 * Friendly text for a days_left value: "Expires today",
 * "Expires in 1 day", "Expires in 5 days".
 */
function format_days_left($days_left) {
    $days_left = (int)$days_left;
    if ($days_left <= 0) { return "Expires today"; }
    if ($days_left === 1) { return "Expires in 1 day"; }
    return "Expires in $days_left days";
}
?>
