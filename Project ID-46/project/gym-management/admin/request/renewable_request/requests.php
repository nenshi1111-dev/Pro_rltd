<?php
// ==========================================================
// admin/request/renewable_request/requests.php
// Pending / Approved / Rejected renewal requests list
// ==========================================================
require_once "../../../includes/config.php";
require_once "../../includes/auth.php";

$page_title = "Renewal Requests";
$active_menu = "renew_requests";

$filter = (isset($_GET['status']) ? $_GET['status'] : 'Pending');
$allowed = ['Pending','Approved','Rejected','All'];
if (!in_array($filter, $allowed)) { $filter = 'Pending'; }

$base_sql = "SELECT rr.*, m.full_name, m.member_code,
    GROUP_CONCAT(b.batch_name ORDER BY b.start_time SEPARATOR ', ') AS batch_names
    FROM renewal_requests rr
    JOIN members m ON rr.member_id = m.member_id
    LEFT JOIN renewal_request_batches rrb ON rrb.renewal_id = rr.renewal_id
    LEFT JOIN batches b ON b.batch_id = rrb.batch_id";

if ($filter === 'All') {
    $requests = mysqli_query($conn, "$base_sql GROUP BY rr.renewal_id ORDER BY rr.request_date DESC");
} else {
    $stmt = mysqli_prepare($conn, "$base_sql WHERE rr.status=? GROUP BY rr.renewal_id ORDER BY rr.request_date DESC");
    mysqli_stmt_bind_param($stmt, "s", $filter);
    mysqli_stmt_execute($stmt);
    $requests = mysqli_stmt_get_result($stmt);
}

include "../../includes/header.php";
include "../../includes/sidebar.php";
include "../../includes/topbar.php";
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($_GET['err']); ?></div><?php endif; ?>

<div class="btn-group mb-3">
    <?php foreach ($allowed as $s): ?>
        <a href="requests.php?status=<?php echo $s; ?>" class="btn btn-sm <?php echo $filter==$s?'btn-gym-primary':'btn-outline-dark'; ?>"><?php echo $s; ?></a>
    <?php endforeach; ?>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Member</th><th>Requested Batches</th><th>Duration</th><th>Requested On</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while ($r = mysqli_fetch_assoc($requests)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['full_name']) . " (" . htmlspecialchars($r['member_code']) . ")"; ?></td>
                    <td><?php echo htmlspecialchars((isset($r['batch_names']) ? $r['batch_names'] : '—')); ?></td>
                    <td><?php echo (int)$r['requested_duration']; ?> mo</td>
                    <td><?php echo htmlspecialchars($r['request_date']); ?></td>
                    <td>
                        <?php $b = $r['status']=='Approved'?'badge-active':($r['status']=='Rejected'?'badge-rejected':'badge-pending'); ?>
                        <span class="badge <?php echo $b; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                    </td>
                    <td class="text-nowrap">
                        <a href="view-request.php?id=<?php echo $r['renewal_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <?php if ($r['status']=='Pending'): ?>
                            <a href="approve-request.php?id=<?php echo $r['renewal_id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this renewal?');"><i class="bi bi-check-lg"></i></a>
                            <a href="reject-request.php?id=<?php echo $r['renewal_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this renewal?');"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../../includes/footer.php"; ?>
