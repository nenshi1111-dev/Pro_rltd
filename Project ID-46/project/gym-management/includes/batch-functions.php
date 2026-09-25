<?php
// ==========================================================
// includes/batch-functions.php
// Shared helpers for multi-batch selection. Included by every
// page that lets someone pick batches (register.php, admin
// add/edit member, member renewal) so the overlap rule and the
// Morning/Evening grouping logic live in exactly ONE place —
// never duplicated, never able to drift out of sync.
//
// Include this AFTER includes/config.php (needs $conn).
// ==========================================================

/**
 * Returns all batches (optionally filtered to Active only),
 * grouped into ['Morning' => [...], 'Evening' => [...]].
 * A batch is "Morning" if its start_time is before 12:00:00,
 * otherwise "Evening" — this is derived automatically from the
 * time the admin already entered, never a separate manual field.
 */
function get_batches_grouped($conn, $active_only = true) {
    $sql = "SELECT * FROM batches";
    if ($active_only) { $sql .= " WHERE status='Active'"; }
    $sql .= " ORDER BY start_time ASC";

    $result = mysqli_query($conn, $sql);
    $grouped = ['Morning' => [], 'Evening' => []];

    while ($row = mysqli_fetch_assoc($result)) {
        $bucket = ($row['start_time'] < '12:00:00') ? 'Morning' : 'Evening';
        $grouped[$bucket][] = $row;
    }
    return $grouped;
}

/**
 * True if two time ranges overlap at all (partial overlap counts,
 * not just an exact match). Standard interval-overlap check:
 * two ranges overlap if one starts before the other ends, in both
 * directions.
 */
function times_overlap($start1, $end1, $start2, $end2) {
    return ($start1 < $end2) && ($start2 < $end1);
}

/**
 * Given an array of batch_ids the user wants, checks every pair
 * for a time overlap. Returns an array with two batch names the
 * FIRST time a conflict is found, or an empty array if the whole
 * selection is conflict-free. Also flags Inactive/missing batch
 * ids so a stale form submission can't sneak one in.
 *
 * Usage:
 *   $conflict = find_batch_conflict($conn, [1, 2, 4]);
 *   if ($conflict) { $error = "{$conflict[0]} overlaps with {$conflict[1]}."; }
 */
function find_batch_conflict($conn, $batch_ids) {
    $batch_ids = array_values(array_unique(array_map('intval', $batch_ids)));
    if (count($batch_ids) < 2) { return []; }

    // Fetch the actual rows for just these ids
    $placeholders = implode(',', array_fill(0, count($batch_ids), '?'));
    $types = str_repeat('i', count($batch_ids));
    $stmt = mysqli_prepare($conn, "SELECT batch_id, batch_name, start_time, end_time FROM batches WHERE batch_id IN ($placeholders)");
    gp_bind_param_array($stmt, $types, $batch_ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }

    // Pairwise comparison — fine at this scale (a handful of batches)
    for ($i = 0; $i < count($rows); $i++) {
        for ($j = $i + 1; $j < count($rows); $j++) {
            if (times_overlap($rows[$i]['start_time'], $rows[$i]['end_time'], $rows[$j]['start_time'], $rows[$j]['end_time'])) {
                return [$rows[$i]['batch_name'], $rows[$j]['batch_name']];
            }
        }
    }
    return [];
}

/**
 * Reads the configurable max-batches-per-member limit from the
 * settings table (falls back to 3 if not set).
 */
function get_max_batches_per_member($conn) {
    $res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key='max_batches_per_member'");
    $row = mysqli_fetch_assoc($res);
    return $row ? (int)$row['setting_value'] : 3;
}
?>
