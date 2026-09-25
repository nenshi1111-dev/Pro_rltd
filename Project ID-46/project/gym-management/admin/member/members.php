<?php
// ==========================================================
// admin/member/members.php — Member list
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "Members";
$active_menu = "members";

// Auto-expire sweep: flip any Active member whose expiry date has
// already passed to 'Expired', so this list is always accurate —
// even for members who haven't logged in since expiring.
mysqli_query($conn, "UPDATE members SET status='Expired' WHERE status='Active' AND membership_expiry_date < CURDATE()");

// Simple search by name/code (prepared statement even for LIKE)
$search = trim((isset($_GET['q']) ? $_GET['q'] : ''));
$base_select = "SELECT m.*, GROUP_CONCAT(b.batch_name ORDER BY b.start_time SEPARATOR ', ') AS batch_names
    FROM members m
    LEFT JOIN member_batches mb ON mb.member_id = m.member_id
    LEFT JOIN batches b ON b.batch_id = mb.batch_id";

if ($search !== '') {
    $like = "%$search%";
    $stmt = mysqli_prepare($conn, "$base_select WHERE m.full_name LIKE ? OR m.member_code LIKE ? GROUP BY m.member_id ORDER BY m.member_id DESC");
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $members = mysqli_stmt_get_result($stmt);
} else {
    $members = mysqli_query($conn, "$base_select GROUP BY m.member_id ORDER BY m.member_id DESC");
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success auto-hide-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search by name or code" value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn btn-outline-dark"><i class="bi bi-search"></i></button>
    </form>
    <div class="d-flex gap-2">
        <a href="export-members.php<?php echo $search !== '' ? '?q=' . urlencode($search) : ''; ?>" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export CSV</a>
        <a href="add-member.php" class="btn btn-gym-primary"><i class="bi bi-plus-circle me-1"></i>Add Member</a>
    </div>
</div>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Code</th><th>Name</th><th>Phone</th><th>Batches</th>
                    <th>Expiry Date</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = mysqli_fetch_assoc($members)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['member_code']); ?></td>
                    <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['phone']); ?></td>
                    <td><?php echo htmlspecialchars((isset($m['batch_names']) ? $m['batch_names'] : '—')); ?></td>
                    <td><?php echo htmlspecialchars($m['membership_expiry_date']); ?></td>
                    <td>
                        <?php
                        $badge = $m['status']=='Active' ? 'badge-active' : ($m['status']=='Expired' ? 'badge-expired' : ($m['status']=='Pending Payment' ? 'badge-pending' : 'badge-inactive'));
                        echo "<span class='badge $badge'>" . htmlspecialchars($m['status']) . "</span>";
                        ?>
                    </td>
                    <td class="text-nowrap">
                        <a href="view-member.php?id=<?php echo $m['member_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <a href="edit-member.php?id=<?php echo $m['member_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="renew-member.php?id=<?php echo $m['member_id']; ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-repeat"></i></a>
                        <a href="delete-member.php?id=<?php echo $m['member_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this member permanently?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
