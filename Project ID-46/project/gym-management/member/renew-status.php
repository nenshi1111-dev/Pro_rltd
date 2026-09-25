<?php
// ==========================================================
// member/renew-status.php — Renewal request history/status
// Reachable even while Expired.
// ==========================================================
$allow_restricted = true;
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "Renewal Status";
$active_menu = "renew_status";
$member_id = $_SESSION['member_id'];

$stmt = mysqli_prepare($conn, "
    SELECT rr.*, GROUP_CONCAT(b.batch_name ORDER BY b.start_time SEPARATOR ', ') AS batch_names
    FROM renewal_requests rr
    LEFT JOIN renewal_request_batches rrb ON rrb.renewal_id = rr.renewal_id
    LEFT JOIN batches b ON b.batch_id = rrb.batch_id
    WHERE rr.member_id=?
    GROUP BY rr.renewal_id
    ORDER BY rr.request_date DESC
");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Requested Batches</th><th>Duration</th><th>Requested On</th><th>Status</th></tr></thead>
            <tbody>
                <?php $found=false; while ($r = mysqli_fetch_assoc($requests)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars((isset($r['batch_names']) ? $r['batch_names'] : '—')); ?></td>
                    <td><?php echo (int)$r['requested_duration']; ?> mo</td>
                    <td><?php echo htmlspecialchars($r['request_date']); ?></td>
                    <td>
                        <?php $b = $r['status']=='Approved'?'badge-active':($r['status']=='Rejected'?'badge-rejected':'badge-pending'); ?>
                        <span class="badge <?php echo $b; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                    </td>
                </tr>
                <?php endwhile; if(!$found): ?>
                    <tr><td colspan="4" class="text-muted">You have not submitted any renewal requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
