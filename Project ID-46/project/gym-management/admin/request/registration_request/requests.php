<?php
// ==========================================================
// admin/request/registration_request/requests.php
// Pending / Approved / Rejected registration requests list
// ==========================================================
require_once "../../../includes/config.php";
require_once "../../includes/auth.php";

$page_title = "Registration Requests";
$active_menu = "reg_requests";

$filter = (isset($_GET['status']) ? $_GET['status'] : 'Pending');
$allowed = ['Pending','Approved','Rejected','All'];
if (!in_array($filter, $allowed)) { $filter = 'Pending'; }

$base_select = "SELECT rm.*, GROUP_CONCAT(b.batch_name ORDER BY b.start_time SEPARATOR ', ') AS batch_names
    FROM requested_members rm
    LEFT JOIN requested_member_batches rmb ON rmb.request_id = rm.request_id
    LEFT JOIN batches b ON b.batch_id = rmb.batch_id";

if ($filter === 'All') {
    $requests = mysqli_query($conn, "$base_select GROUP BY rm.request_id ORDER BY rm.request_date DESC");
} else {
    $stmt = mysqli_prepare($conn, "$base_select WHERE rm.status=? GROUP BY rm.request_id ORDER BY rm.request_date DESC");
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
            <thead><tr><th>Name</th><th>Phone</th><th>Requested Batches</th><th>Requested On</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while ($r = mysqli_fetch_assoc($requests)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['phone']); ?></td>
                    <td><?php echo htmlspecialchars((isset($r['batch_names']) ? $r['batch_names'] : '—')); ?></td>
                    <td><?php echo htmlspecialchars($r['request_date']); ?></td>
                    <td>
                        <?php $b = $r['status']=='Approved'?'badge-active':($r['status']=='Rejected'?'badge-rejected':'badge-pending'); ?>
                        <span class="badge <?php echo $b; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                    </td>
                    <td class="text-nowrap">
                        <a href="view-request.php?id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <?php if ($r['status']=='Pending'): ?>
                            <a href="approve-request.php?id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this registration?');"><i class="bi bi-check-lg"></i></a>
                            <a href="reject-request.php?id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this registration?');"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../../includes/footer.php"; ?>
