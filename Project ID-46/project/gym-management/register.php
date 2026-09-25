<?php
// ==========================================================
// register.php — Online membership request form (public)
// Saves into requested_members + requested_member_batches,
// pending admin approval. No plan field anywhere — the person
// picks one or more batches directly (overlap-checked, capped
// at the configurable max).
// ==========================================================
require_once "includes/config.php";
require_once "includes/batch-functions.php";

$error_msg = "";
$success_msg = "";
$max_batches = get_max_batches_per_member($conn);
$grouped = get_batches_grouped($conn, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim((isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $gender     = (isset($_POST['gender']) ? $_POST['gender'] : 'Male');
    $dob        = (isset($_POST['dob']) ? $_POST['dob'] : '');
    $phone      = trim((isset($_POST['phone']) ? $_POST['phone'] : ''));
    $email      = trim((isset($_POST['email']) ? $_POST['email'] : ''));
    $address    = trim((isset($_POST['address']) ? $_POST['address'] : ''));
    $emergency  = trim((isset($_POST['emergency_contact']) ? $_POST['emergency_contact'] : ''));
    $duration   = (int)((isset($_POST['duration']) ? $_POST['duration'] : 1));
    $username   = trim((isset($_POST['username']) ? $_POST['username'] : ''));
    $password   = trim((isset($_POST['password']) ? $_POST['password'] : ''));
    $batch_ids  = array_map('intval', (isset($_POST['batch_ids']) ? $_POST['batch_ids'] : []));

    // ---- Server-side validation ----
    if ($full_name==''||$phone==''||$email==''||$username==''||$password==''||$dob=='') {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters.";
    } elseif (calculate_age($dob) === null || calculate_age($dob) < MIN_REGISTRATION_AGE) {
        $error_msg = "You must be at least " . MIN_REGISTRATION_AGE . " years old to register.";
    } elseif (empty($batch_ids)) {
        $error_msg = "Please select at least one batch.";
    } elseif (count($batch_ids) > $max_batches) {
        $error_msg = "You can select at most $max_batches batches.";
    } else {
        // ---- Overlap check (the real, authoritative one) ----
        $conflict = find_batch_conflict($conn, $batch_ids);
        if ($conflict) {
            $error_msg = "\"{$conflict[0]}\" and \"{$conflict[1]}\" overlap in timing — please choose batches with different schedules.";
        } else {
            // ---- Uniqueness checks across members + requested_members ----
            $dup_check = mysqli_prepare($conn, "
                SELECT 1 FROM members WHERE username=? OR email=? OR phone=?
                UNION
                SELECT 1 FROM requested_members WHERE (username=? OR email=? OR phone=?) AND status='Pending'
            ");
            mysqli_stmt_bind_param($dup_check, "ssssss", $username, $email, $phone, $username, $email, $phone);
            mysqli_stmt_execute($dup_check);
            $dup_result = mysqli_stmt_get_result($dup_check);

            if (mysqli_num_rows($dup_result) > 0) {
                $error_msg = "Username already exists, or email/phone is already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "INSERT INTO requested_members
                    (full_name, gender, dob, phone, email, address, emergency_contact, duration, username, password, status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,'Pending')");
                mysqli_stmt_bind_param($stmt, "sssssssiss",
                    $full_name, $gender, $dob, $phone, $email, $address, $emergency,
                    $duration, $username, $hashed_password);

                if (mysqli_stmt_execute($stmt)) {
                    $request_id = mysqli_insert_id($conn);
                    foreach ($batch_ids as $bid) {
                        $link = mysqli_prepare($conn, "INSERT INTO requested_member_batches (request_id, batch_id) VALUES (?,?)");
                        mysqli_stmt_bind_param($link, "ii", $request_id, $bid);
                        mysqli_stmt_execute($link);
                    }
                    $success_msg = "Registration request submitted successfully! Please wait for Admin approval before logging in.";
                } else {
                    $error_msg = "Something went wrong. Please try again.";
                }
            }
        }
    }
}

$page_title = "Register";
$active_page = "register";
include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="auth-card mx-auto">
                <h4 class="text-center mb-1">Membership Registration</h4>
                <p class="text-center text-muted small mb-4">Submit your details — an Admin will review and approve your membership.</p>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger auto-hide-alert"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>

                <?php if (!$success_msg): ?>
                <form method="POST" action="register.php">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars((isset($_POST['full_name']) ? $_POST['full_name'] : '')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option>Male</option><option>Female</option><option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" required max="<?php echo max_dob_for_registration(); ?>">
                            <small class="text-muted">You must be at least <?php echo MIN_REGISTRATION_AGE; ?> years old to register.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duration (months)</label>
                            <select name="duration" class="form-select duration-select">
                                <option value="1">1 Month</option>
                                <option value="3">3 Months</option>
                                <option value="6">6 Months</option>
                                <option value="12">12 Months</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Select Batches (max <?php echo $max_batches; ?>) — choose times that don't overlap</label>
                            <div data-batch-picker data-max-batches="<?php echo $max_batches; ?>" class="row">
                                <?php foreach (['Morning','Evening'] as $slot): if (empty($grouped[$slot])) continue; ?>
                                    <div class="col-md-6">
                                        <h6 class="text-muted small text-uppercase mt-2"><?php echo $slot; ?></h6>
                                        <?php foreach ($grouped[$slot] as $b): ?>
                                            <div class="form-check batch-check">
                                                <input class="form-check-input batch-check-input" type="checkbox"
                                                       name="batch_ids[]" value="<?php echo $b['batch_id']; ?>"
                                                       id="reg_batch_<?php echo $b['batch_id']; ?>"
                                                       data-start="<?php echo $b['start_time']; ?>"
                                                       data-end="<?php echo $b['end_time']; ?>"
                                                       data-fee="<?php echo $b['monthly_fee']; ?>"
                                                       data-name="<?php echo htmlspecialchars($b['batch_name'], ENT_QUOTES); ?>">
                                                <label class="form-check-label" for="reg_batch_<?php echo $b['batch_id']; ?>">
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
                                <p class="text-muted small mb-0 mt-2">This is an estimate. Admin will confirm your exact payment schedule after approval.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Choose Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Choose Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="password" id="regPassword" class="form-control" required minlength="6">
                                <i class="bi bi-eye toggle-password" data-target="regPassword"></i>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-gym-primary w-100 mt-4">Submit Registration Request</button>
                </form>
                <?php endif; ?>

                <p class="text-center mt-3 small">Already a member? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
