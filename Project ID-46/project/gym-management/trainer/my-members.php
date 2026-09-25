<?php
// ==========================================================
// trainer/my-members.php — Members inside trainer's batches
// View only. Never shows another trainer's members.
// ==========================================================
require_once "../includes/config.php";
require_once "includes/auth.php";

$page_title = "My Members";
$active_menu = "members";
$trainer_id = $_SESSION['trainer_id'];

$stmt = mysqli_prepare($conn, "
    SELECT m.member_code, m.full_name, m.phone, m.status,
           GROUP_CONCAT(b.batch_name ORDER BY b.start_time SEPARATOR ', ') AS batch_names
    FROM members m
    JOIN member_batches mb ON mb.member_id = m.member_id
    JOIN batch_trainers bt ON mb.batch_id = bt.batch_id
    JOIN batches b ON mb.batch_id = b.batch_id
    WHERE bt.trainer_id = ?
    GROUP BY m.member_id
    ORDER BY m.full_name
");
mysqli_stmt_bind_param($stmt, "i", $trainer_id);
mysqli_stmt_execute($stmt);
$members = mysqli_stmt_get_result($stmt);

include "includes/header.php";
include "includes/sidebar.php";
include "includes/topbar.php";
?>

<div class="gym-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Batches</th><th>Status</th></tr></thead>
            <tbody>
                <?php $found=false; while ($m = mysqli_fetch_assoc($members)): $found=true; ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['member_code']); ?></td>
                    <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['phone']); ?></td>
                    <td><?php echo htmlspecialchars($m['batch_names']); ?></td>
                    <td><span class="badge <?php echo $m['status']=='Active'?'badge-active':($m['status']=='Expired'?'badge-expired':($m['status']=='Pending Payment'?'badge-pending':'badge-inactive')); ?>"><?php echo htmlspecialchars($m['status']); ?></span></td>
                </tr>
                <?php endwhile; if(!$found): ?>
                    <tr><td colspan="5" class="text-muted">No members in your batches yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
