<?php
// ==========================================================
// admin/member/add-member.php — Add a new member directly
// Multi-batch checkbox picker (overlap + capacity + max
// checked server-side) instead of a plan/single-batch dropdown.
//
// Status is always 'Pending Payment', never 'Active' — no path
// anywhere in the system sets a member Active without a logged/
// verified payment clearing their balance first, so this stays
// consistent with the self-registration approval flow. Admin
// still needs to log a payment (or the member pays online) to
// unlock the account, exactly like any other Pending Payment
// member.
// ==========================================================
require_once "../../includes/config.php";
require_once "../../includes/batch-functions.php";
require_once "../includes/auth.php";

$page_title = "Add Member";
$active_menu = "members";
$error_msg = "";
$max_batches = get_max_batches_per_member($conn);
$grouped = get_batches_grouped($conn, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim((isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $gender    = (isset($_POST['gender']) ? $_POST['gender'] : 'Male');
    $dob       = (isset($_POST['dob']) ? $_POST['dob'] : null);
    $phone     = trim((isset($_POST['phone']) ? $_POST['phone'] : ''));
    $email     = trim((isset($_POST['email']) ? $_POST['email'] : ''));
    $address   = trim((isset($_POST['address']) ? $_POST['address'] : ''));
    $emergency = trim((isset($_POST['emergency_contact']) ? $_POST['emergency_contact'] : ''));
    $duration  = (int)((isset($_POST['duration_months']) ? $_POST['duration_months'] : 1));
    $username  = trim((isset($_POST['username']) ? $_POST['username'] : ''));
    $password  = trim((isset($_POST['password']) ? $_POST['password'] : ''));
    $batch_ids = array_map('intval', (isset($_POST['batch_ids']) ? $_POST['batch_ids'] : []));

    if ($full_name=='' || $phone=='' || $email=='' || $username=='' || $password=='' || $dob=='') {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (calculate_age($dob) === null || calculate_age($dob) < MIN_REGISTRATION_AGE) {
        $error_msg = "Member must be at least " . MIN_REGISTRATION_AGE . " years old.";
    } elseif (empty($batch_ids)) {
        $error_msg = "Please select at least one batch.";
    } elseif (count($batch_ids) > $max_batches) {
        $error_msg = "A member can select at most $max_batches batches.";
    } else {
        $conflict = find_batch_conflict($conn, $batch_ids);
        if ($conflict) {
            $error_msg = "\"{$conflict[0]}\" and \"{$conflict[1]}\" overlap in timing — choose batches with different schedules.";
        } else {
            // Uniqueness: username, email, phone must all be unique
            $chk = mysqli_prepare($conn, "SELECT 1 FROM members WHERE username=? OR email=? OR phone=?");
            mysqli_stmt_bind_param($chk, "sss", $username, $email, $phone);
            mysqli_stmt_execute($chk);
            if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
                $error_msg = "Username already exists, or email/phone already in use.";
            } else {
                // Capacity check per selected batch
                foreach ($batch_ids as $bid) {
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
                $res = mysqli_query($conn, "SELECT member_id FROM members ORDER BY member_id DESC LIMIT 1");
                $last = mysqli_fetch_assoc($res);
                $next_id = $last ? $last['member_id'] + 1 : 1;
                $member_code = "GM" . str_pad($next_id, 4, "0", STR_PAD_LEFT);

                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $start_date = date('Y-m-d');
                $expiry_date = date('Y-m-d', strtotime("+$duration months"));

                $stmt = mysqli_prepare($conn, "INSERT INTO members
                    (member_code, full_name, gender, dob, phone, email, address, emergency_contact,
                     duration_months, membership_start_date, membership_expiry_date,
                     username, password, status, created_by, created_method)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending Payment',?,'Admin')");
                mysqli_stmt_bind_param($stmt, "ssssssssisssss",
                    $member_code, $full_name, $gender, $dob, $phone, $email, $address, $emergency,
                    $duration, $start_date, $expiry_date,
                    $username, $hashed, $_SESSION['admin_name']);

                if (mysqli_stmt_execute($stmt)) {
                    $new_member_id = mysqli_insert_id($conn);
                    foreach ($batch_ids as $bid) {
                        $link = mysqli_prepare($conn, "INSERT INTO member_batches (member_id, batch_id) VALUES (?,?)");
                        mysqli_stmt_bind_param($link, "ii", $new_member_id, $bid);
                        mysqli_stmt_execute($link);
                    }
                    header("Location: members.php?msg=" . urlencode("Member added — awaiting payment before it activates."));
                    exit;
                } else {
                    $error_msg = "Could not add member. Please try again.";
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
            <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars((isset($_POST['full_name']) ? $_POST['full_name'] : '')); ?>"></div>
            <div class="col-md-3"><label class="form-label">Gender</label>
                <select name="gender" class="form-select"><option>Male</option><option>Female</option><option>Other</option></select>
            </div>
            <div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control" required max="<?php echo max_dob_for_registration(); ?>"><small class="text-muted">18+ only</small></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Emergency Contact</label><input type="text" name="emergency_contact" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Duration (months)</label>
                <select name="duration_months" class="form-select duration-select">
                    <option value="1">1</option><option value="3">3</option><option value="6">6</option><option value="12">12</option>
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
                                           id="add_batch_<?php echo $b['batch_id']; ?>"
                                           data-start="<?php echo $b['start_time']; ?>"
                                           data-end="<?php echo $b['end_time']; ?>"
                                           data-fee="<?php echo $b['monthly_fee']; ?>"
                                           data-name="<?php echo htmlspecialchars($b['batch_name'], ENT_QUOTES); ?>">
                                    <label class="form-check-label" for="add_batch_<?php echo $b['batch_id']; ?>">
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

            <div class="col-12">
                <div class="payment-breakdown" id="paymentBreakdown" style="display:none;">
                    <h6>Estimated Payment</h6>
                    <ul id="breakdownList"></ul>
                    <div class="d-flex justify-content-between breakdown-total-row fw-bold">
                        <span>Total (<span id="breakdownDuration">1</span> month(s))</span>
                        <span>Rs. <span id="breakdownTotal">0.00</span></span>
                    </div>
                </div>
            </div>

            <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="pw1" class="form-control" required minlength="6">
                    <i class="bi bi-eye toggle-password" data-target="pw1"></i>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-gym-primary mt-4">Add Member</button>
        <a href="members.php" class="btn btn-outline-secondary mt-4">Cancel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
