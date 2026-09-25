<?php
// ==========================================================
// admin/member/edit-member.php — Edit an existing member
// ==========================================================
require_once "../../includes/config.php";
require_once "../../includes/batch-functions.php";
require_once "../includes/auth.php";

$page_title = "Edit Member";
$active_menu = "members";
$error_msg = "";
$max_batches = get_max_batches_per_member($conn);
$grouped = get_batches_grouped($conn, true);

$id = (int)((isset($_GET['id']) ? $_GET['id'] : 0));
$stmt = mysqli_prepare($conn, "SELECT * FROM members WHERE member_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$member) {
    header("Location: members.php?err=" . urlencode("Member not found."));
    exit;
}

// Currently selected batch ids for this member
$selected = [];
$sel_stmt = mysqli_prepare($conn, "SELECT batch_id FROM member_batches WHERE member_id=?");
mysqli_stmt_bind_param($sel_stmt, "i", $id);
mysqli_stmt_execute($sel_stmt);
$sel_res = mysqli_stmt_get_result($sel_stmt);
while ($row = mysqli_fetch_assoc($sel_res)) { $selected[] = (int)$row['batch_id']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim((isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $gender    = (isset($_POST['gender']) ? $_POST['gender'] : 'Male');
    $dob       = (isset($_POST['dob']) ? $_POST['dob'] : null);
    $phone     = trim((isset($_POST['phone']) ? $_POST['phone'] : ''));
    $email     = trim((isset($_POST['email']) ? $_POST['email'] : ''));
    $address   = trim((isset($_POST['address']) ? $_POST['address'] : ''));
    $emergency = trim((isset($_POST['emergency_contact']) ? $_POST['emergency_contact'] : ''));
    $status    = (isset($_POST['status']) ? $_POST['status'] : 'Active');
    $batch_ids = array_map('intval', (isset($_POST['batch_ids']) ? $_POST['batch_ids'] : []));

    if ($full_name=='' || $phone=='' || $email=='') {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (empty($batch_ids)) {
        $error_msg = "Please select at least one batch.";
    } elseif (count($batch_ids) > $max_batches) {
        $error_msg = "A member can select at most $max_batches batches.";
    } else {
        $conflict = find_batch_conflict($conn, $batch_ids);
        if ($conflict) {
            $error_msg = "\"{$conflict[0]}\" and \"{$conflict[1]}\" overlap in timing — choose batches with different schedules.";
        } else {
            $chk = mysqli_prepare($conn, "SELECT 1 FROM members WHERE (email=? OR phone=?) AND member_id<>?");
            mysqli_stmt_bind_param($chk, "ssi", $email, $phone, $id);
            mysqli_stmt_execute($chk);
            if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
                $error_msg = "Email or phone already used by another member.";
            } else {
                // Capacity check per newly-added batch (skip ones they're already in)
                foreach ($batch_ids as $bid) {
                    if (in_array($bid, $selected)) continue;
                    $cap_q = mysqli_prepare($conn, "SELECT b.capacity, (SELECT COUNT(*) FROM member_batches WHERE batch_id=b.batch_id) AS filled, b.batch_name FROM batches b WHERE b.batch_id=?");
                    mysqli_stmt_bind_param($cap_q, "i", $bid);
                    mysqli_stmt_execute($cap_q);
                    $cap = mysqli_fetch_assoc(mysqli_stmt_get_result($cap_q));
                    if ($cap && $cap['filled'] >= $cap['capacity']) {
                        $error_msg = "\"{$cap['batch_name']}\" is full.";
                        break;
                    }
                }
            }

            if ($error_msg === "") {
                $stmt = mysqli_prepare($conn, "UPDATE members SET full_name=?, gender=?, dob=?, phone=?, email=?, address=?, emergency_contact=?, status=? WHERE member_id=?");
                mysqli_stmt_bind_param($stmt, "ssssssssi", $full_name, $gender, $dob, $phone, $email, $address, $emergency, $status, $id);

                if (mysqli_stmt_execute($stmt)) {
                    // Reset and re-insert batch selections (simplest, safest way)
                    $del = mysqli_prepare($conn, "DELETE FROM member_batches WHERE member_id=?");
                    mysqli_stmt_bind_param($del, "i", $id);
                    mysqli_stmt_execute($del);

                    foreach ($batch_ids as $bid) {
                        $link = mysqli_prepare($conn, "INSERT INTO member_batches (member_id, batch_id) VALUES (?,?)");
                        mysqli_stmt_bind_param($link, "ii", $id, $bid);
                        mysqli_stmt_execute($link);
                    }
                    header("Location: members.php?msg=" . urlencode("Member updated successfully."));
                    exit;
                } else {
                    $error_msg = "Update failed. Please try again.";
                }
            }
        }
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/topbar.php";
?>

<?php if ($error_msg): ?>
    <div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="gym-card p-4">
    <form method="POST">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars((isset($_POST['full_name']) ? $_POST['full_name'] : $member['full_name'])); ?>"></div>
            <div class="col-md-3"><label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option <?php echo $member['gender']==$g?'selected':''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($member['dob']); ?>"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($member['phone']); ?>"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($member['email']); ?>"></div>
            <div class="col-12"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($member['address']); ?>"></div>
            <div class="col-md-6"><label class="form-label">Emergency Contact</label><input type="text" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($member['emergency_contact']); ?>"></div>
            <div class="col-md-6"><label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['Active','Expired','Inactive'] as $s): ?>
                        <option <?php echo $member['status']==$s?'selected':''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Select Batches (max <?php echo $max_batches; ?>) — non-overlapping only</label>
                <div data-batch-picker data-max-batches="<?php echo $max_batches; ?>" class="row">
                    <?php foreach (['Morning','Evening'] as $slot): if (empty($grouped[$slot])) continue; ?>
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase mt-2"><?php echo $slot; ?></h6>
                            <?php foreach ($grouped[$slot] as $b): ?>
                                <div class="form-check batch-check">
                                    <input class="form-check-input batch-check-input" type="checkbox"
                                           name="batch_ids[]" value="<?php echo $b['batch_id']; ?>"
                                           id="edit_batch_<?php echo $b['batch_id']; ?>"
                                           data-start="<?php echo $b['start_time']; ?>"
                                           data-end="<?php echo $b['end_time']; ?>"
                                           data-fee="<?php echo $b['monthly_fee']; ?>"
                                           data-name="<?php echo htmlspecialchars($b['batch_name'], ENT_QUOTES); ?>"
                                           <?php echo in_array($b['batch_id'], $selected) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_batch_<?php echo $b['batch_id']; ?>">
                                        <?php echo htmlspecialchars($b['batch_name']); ?>
                                        <span class="text-muted">(<?php echo substr($b['start_time'],0,5) . "-" . substr($b['end_time'],0,5); ?>)</span>
                                        <span class="d-block small text-danger fw-semibold">Rs. <?php echo number_format($b['monthly_fee'], 2); ?> / month</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-gym-primary mt-4">Save Changes</button>
        <a href="members.php" class="btn btn-outline-secondary mt-4">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
