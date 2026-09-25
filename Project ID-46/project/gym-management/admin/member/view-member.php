<?php
// ==========================================================
// admin/member/view-member.php — Read-only member profile view
// ==========================================================
require_once "../../includes/config.php";
require_once "../includes/auth.php";

$page_title = "View Member";
$active_menu = "members";

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$member) {
    header("Location: members.php?err=" . urlencode("Member not found."));
    exit;
}

$batch_stmt = mysqli_prepare($conn, "SELECT b.batch_name, b.start_time, b.end_time FROM member_batches mb JOIN batches b ON mb.batch_id=b.batch_id WHERE mb.member_id=? ORDER BY b.start_time");
mysqli_stmt_bind_param($batch_stmt, "i", $id);
mysqli_stmt_execute($batch_stmt);
$batches = mysqli_stmt_get_result($batch_stmt);

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<div class="gym-card p-4">
    <div class="row">
        <div class="col-md-8">
            <h4><?php echo htmlspecialchars($member['full_name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($member['member_code']); ?>)</small></h4>
            <table class="table table-borderless mt-3">
                <tr><th style="width:220px;">Gender</th><td><?php echo htmlspecialchars($member['gender']); ?></td></tr>
                <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($member['dob']); ?></td></tr>
                <tr><th>Phone</th><td><?php echo htmlspecialchars($member['phone']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($member['email']); ?></td></tr>
                <tr><th>Address</th><td><?php echo htmlspecialchars($member['address']); ?></td></tr>
                <tr><th>Emergency Contact</th><td><?php echo htmlspecialchars($member['emergency_contact']); ?></td></tr>
                <tr><th>Duration</th><td><?php echo htmlspecialchars(format_membership_duration($member['duration_months'], $member['membership_start_date'], $member['membership_expiry_date'])); ?></td></tr>
                <tr><th>Membership Start</th><td><?php echo htmlspecialchars($member['membership_start_date']); ?></td></tr>
                <tr><th>Membership Expiry</th><td><?php echo htmlspecialchars($member['membership_expiry_date']); ?></td></tr>
                <tr><th>Status</th><td><span class="badge <?php echo $member['status']=='Active'?'badge-active':($member['status']=='Expired'?'badge-expired':($member['status']=='Pending Payment'?'badge-pending':'badge-inactive')); ?>"><?php echo htmlspecialchars($member['status']); ?></span></td></tr>
                <tr><th>Created Via</th><td><?php echo htmlspecialchars($member['created_method']); ?> by <?php echo htmlspecialchars($member['created_by']); ?></td></tr>
            </table>

            <h6 class="mt-4">Enrolled Batches</h6>
            <ul class="list-group mb-3">
                <?php $found=false; while ($b = mysqli_fetch_assoc($batches)): $found=true; ?>
                    <li class="list-group-item"><?php echo htmlspecialchars($b['batch_name']) . " (" . substr($b['start_time'],0,5) . "-" . substr($b['end_time'],0,5) . ")"; ?></li>
                <?php endwhile; if (!$found): ?>
                    <li class="list-group-item text-muted">No batches assigned.</li>
                <?php endif; ?>
            </ul>

            <a href="edit-member.php?id=<?php echo $member['member_id']; ?>" class="btn btn-gym-primary">Edit</a>
            <a href="members.php" class="btn btn-outline-secondary">Back to List</a>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
