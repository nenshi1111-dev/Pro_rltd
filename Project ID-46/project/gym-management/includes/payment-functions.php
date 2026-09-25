<?php
// ==========================================================
// includes/payment-functions.php
// Shared helpers for fee calculation. Included by every page
// that needs to know what a member owes or has paid — admin
// payment module, member payment history, reports, dashboard.
// One source of truth, so the math can never drift between
// pages.
//
// Fees are per-batch (batches.monthly_fee). A member's total
// due = SUM(monthly_fee of their batches) * duration_months.
// This is recalculated fresh every time from their CURRENT
// batch selection and duration — it is not stored anywhere,
// so it always reflects reality even after they change batches.
//
// Include this AFTER includes/config.php (needs $conn).
// ==========================================================

/**
 * Total amount due for a member's CURRENT batch selection and
 * duration. Recalculated live, not stored.
 */
function get_member_amount_due($conn, $member_id) {
    $stmt = mysqli_prepare($conn, "
        SELECT COALESCE(SUM(b.monthly_fee), 0) AS fee_per_month, m.duration_months
        FROM members m
        LEFT JOIN member_batches mb ON mb.member_id = m.member_id
        LEFT JOIN batches b ON b.batch_id = mb.batch_id
        WHERE m.member_id = ?
        GROUP BY m.member_id, m.duration_months
    ");
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) { return 0.0; }
    return (float)$row['fee_per_month'] * (int)$row['duration_months'];
}

/**
 * Total amount a member has actually paid, across every
 * payment record logged for them (all-time, not just this cycle —
 * matches the simple "admin logs each payment" model where
 * renewal resets the due amount but payment history stays
 * intact for record-keeping).
 */
function get_member_amount_paid($conn, $member_id) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE member_id=?");
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return (float)$row['total'];
}

/**
 * Pending balance = due - paid, floored at 0 (never show a
 * negative "pending" amount — a member who overpaid or paid
 * ahead just shows Rs. 0 pending, not a negative number).
 */
function get_member_pending_balance($conn, $member_id) {
    $due = get_member_amount_due($conn, $member_id);
    $paid = get_member_amount_paid($conn, $member_id);
    $pending = $due - $paid;
    return $pending > 0 ? $pending : 0.0;
}

/**
 * Generates the next receipt number in sequence (RC0001, RC0002, ...),
 * matching the same style as member_code/trainer_code generation
 * used elsewhere in the project.
 */
function get_next_receipt_number($conn) {
    $res = mysqli_query($conn, "SELECT payment_id FROM payments ORDER BY payment_id DESC LIMIT 1");
    $last = mysqli_fetch_assoc($res);
    $next_id = $last ? $last['payment_id'] + 1 : 1;
    return "RC" . str_pad($next_id, 4, "0", STR_PAD_LEFT);
}

/**
 * If this member is sitting in 'Pending Payment' status and their
 * pending balance has now been cleared (paid >= due), flips them
 * to 'Active' automatically. Returns true if it just activated
 * them, false otherwise.
 *
 * Called immediately after Admin logs a payment (so access unlocks
 * right away), and again defensively on the member's own login/
 * dashboard load — mirroring the same lazy-check pattern already
 * used for auto-expiring memberships.
 */
function sync_member_payment_status($conn, $member_id) {
    $stmt = mysqli_prepare($conn, "SELECT status FROM members WHERE member_id=?");
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row || $row['status'] !== 'Pending Payment') {
        return false;
    }

    $pending = get_member_pending_balance($conn, $member_id);
    if ($pending <= 0) {
        $upd = mysqli_prepare($conn, "UPDATE members SET status='Active' WHERE member_id=?");
        mysqli_stmt_bind_param($upd, "i", $member_id);
        mysqli_stmt_execute($upd);
        return true;
    }
    return false;
}

/**
 * The member's most recent payment_submissions row, or null if
 * they've never submitted one. Used by pending-payment.php to
 * decide whether to show the submission form, a "waiting on
 * Admin" status card, or a rejection notice.
 */
function get_latest_submission($conn, $member_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM payment_submissions WHERE member_id=? ORDER BY submission_id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/**
 * Count of submissions still awaiting Admin verification — powers
 * the notification bell badge and the dashboard list.
 */
function count_pending_submissions($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM payment_submissions WHERE status='Pending Verification'");
    $row = mysqli_fetch_assoc($res);
    return $row ? (int)$row['c'] : 0;
}

/**
 * Full list of submissions awaiting verification, with the
 * member's name attached — used by the admin notification bell.
 */
function get_pending_payment_submissions($conn) {
    $res = mysqli_query($conn, "
        SELECT ps.submission_id, ps.amount, ps.transaction_id, m.full_name, m.member_code
        FROM payment_submissions ps
        JOIN members m ON ps.member_id = m.member_id
        WHERE ps.status = 'Pending Verification'
        ORDER BY ps.submitted_at ASC
    ");
    $rows = array();
    while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
    return $rows;
}
?>
