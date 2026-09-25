-- ======================================================
-- GYM-PRO DATABASE (v3 — payment gate + renamed junction PKs)
-- Database Name: gym_pro
-- Import this file in phpMyAdmin (XAMPP/WAMP/UwAmp) to create
-- the full database with default + dummy data.
--
-- CHANGES FROM v2:
--   - members.status gains a new value: 'Pending Payment'.
--     A member lands in this state right after their
--     registration OR renewal is approved, and stays there
--     until Admin logs a payment that covers the amount due.
--     While in this state, they can log in but every page
--     redirects them to member/pending-payment.php instead of
--     their dashboard.
--   - Junction table primary keys renamed for clarity:
--       batch_trainers.id              -> batch_trainer_id
--       member_batches.id              -> member_batch_id
--       requested_member_batches.id    -> req_mem_btc_id
--       renewal_request_batches.id     -> renwl_req_btc_id
--     (No PHP code referenced these columns by name anywhere,
--     since every query already used the meaningful foreign
--     keys like member_id/batch_id — so this rename is a
--     pure schema cleanup with zero risk to existing queries.)
--   - New setting: gym_upi_id, shown on the payment page.
--
-- CHANGES FROM v1 (still in effect):
--   - membership_plans table REMOVED entirely
--   - members.plan_id REMOVED, members.batch_id REMOVED
--   - member_batches junction table (a member can be in
--     several batches at once, as long as their times don't
--     overlap — enforced in PHP, not in SQL)
--   - No membership-tier pricing anywhere. Fee is set per
--     batch (batches.monthly_fee). Admin logs each payment
--     manually after collecting it — there is still no real
--     payment gateway integrated; the "Pending Payment" gate
--     is an access-control step, not an online checkout.
-- ======================================================

CREATE DATABASE IF NOT EXISTS gym_pro;
USE gym_pro;

-- ------------------------------------------------------
-- 1) admins
-- ------------------------------------------------------
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(50) NOT NULL UNIQUE,
    admin_password VARCHAR(255) NOT NULL
);

-- Default admin account (username: admin)
-- The password column below is a PLACEHOLDER, not a real hash.
-- After importing this file, open seed-passwords.php ONCE in your
-- browser (http://localhost/gym-management/seed-passwords.php) —
-- it replaces every placeholder with a real password_hash() value.
-- After that, log in with: admin / password
INSERT INTO admins (admin_username, admin_password) VALUES
('admin', 'PLEASE_RUN_seed-passwords.php');

-- ------------------------------------------------------
-- 2) trainers
-- ------------------------------------------------------
CREATE TABLE trainers (
    trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female','Other') DEFAULT 'Male',
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    specialization VARCHAR(100),
    photo VARCHAR(255) DEFAULT 'default-trainer.png',
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO trainers (trainer_code, full_name, gender, phone, email, specialization, username, password, status) VALUES
('TR0001', 'Rahul Sharma', 'Male', '9000000001', 'rahul.trainer@gympro.com', 'Strength & Conditioning', 'trainer1', 'PLEASE_RUN_seed-passwords.php', 'Active'),
('TR0002', 'Priya Nair', 'Female', '9000000002', 'priya.trainer@gympro.com', 'Yoga & Flexibility', 'trainer2', 'PLEASE_RUN_seed-passwords.php', 'Active');

-- ------------------------------------------------------
-- 3) batches
-- Dummy data deliberately includes TWO batches with the
-- EXACT same time slot (Morning Yoga vs Morning Strength,
-- both 06:00-07:00) so you can immediately test the
-- overlap-prevention rule without creating data yourself.
-- ------------------------------------------------------
CREATE TABLE batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    capacity INT NOT NULL DEFAULT 20,
    monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('Active','Inactive') DEFAULT 'Active'
);

INSERT INTO batches (batch_name, start_time, end_time, capacity, monthly_fee, status) VALUES
('Morning Yoga', '06:00:00', '07:00:00', 20, 800.00, 'Active'),
('Morning Strength', '06:00:00', '07:00:00', 25, 1000.00, 'Active'),
('Morning Cardio', '07:15:00', '08:15:00', 20, 700.00, 'Active'),
('Evening Zumba', '17:00:00', '18:00:00', 20, 900.00, 'Active'),
('Evening Strength', '18:15:00', '19:15:00', 25, 1000.00, 'Active');

-- ------------------------------------------------------
-- 4) batch_trainers (many-to-many: batches <-> trainers)
-- ------------------------------------------------------
CREATE TABLE batch_trainers (
    batch_trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    trainer_id INT NOT NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (batch_id, trainer_id)
);

INSERT INTO batch_trainers (batch_id, trainer_id) VALUES
(1, 2),
(2, 1),
(3, 1),
(4, 2),
(5, 1);

-- ------------------------------------------------------
-- 5) members
-- No plan_id, no single batch_id — batches are linked via
-- the member_batches junction table below.
-- ------------------------------------------------------
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(20) NOT NULL UNIQUE,
    photo VARCHAR(255) DEFAULT 'default-member.png',
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female','Other') DEFAULT 'Male',
    dob DATE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    address VARCHAR(255),
    emergency_contact VARCHAR(20),
    duration_months INT NOT NULL DEFAULT 1,
    membership_start_date DATE NOT NULL,
    membership_expiry_date DATE NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('Active','Expired','Inactive','Pending Payment') DEFAULT 'Active',
    created_by VARCHAR(50),
    created_method ENUM('Request','Admin') DEFAULT 'Admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO members (member_code, full_name, gender, dob, phone, email, address, emergency_contact, duration_months, membership_start_date, membership_expiry_date, username, password, status, created_by, created_method) VALUES
('GM0001', 'Amit Verma', 'Male', '1998-05-12', '9111111111', 'amit.member@gympro.com', 'Jamnagar, Gujarat', '9111111112', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'member1', 'PLEASE_RUN_seed-passwords.php', 'Active', 'admin', 'Admin'),
('GM0002', 'Sneha Patel', 'Female', '2000-09-03', '9111111113', 'sneha.member@gympro.com', 'Rajkot, Gujarat', '9111111114', 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'member2', 'PLEASE_RUN_seed-passwords.php', 'Active', 'admin', 'Admin'),
('GM0003', 'Kiran Joshi', 'Male', '1999-11-20', '9111111115', 'kiran.member@gympro.com', 'Jamnagar, Gujarat', '9111111116', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'member3', 'PLEASE_RUN_seed-passwords.php', 'Pending Payment', 'admin', 'Request');

-- ------------------------------------------------------
-- 6) member_batches (many-to-many: members <-> batches)
-- ------------------------------------------------------
CREATE TABLE member_batches (
    member_batch_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    batch_id INT NOT NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    UNIQUE KEY unique_member_batch (member_id, batch_id)
);

-- Amit: Morning Yoga only
INSERT INTO member_batches (member_id, batch_id) VALUES (1, 1);
-- Sneha: Morning Cardio + Evening Zumba (different times, no overlap)
INSERT INTO member_batches (member_id, batch_id) VALUES (2, 3), (2, 4);
-- Kiran: Evening Strength — awaiting payment (status is 'Pending Payment' above)
INSERT INTO member_batches (member_id, batch_id) VALUES (3, 5);

-- ------------------------------------------------------
-- 6b) payments (admin-logged, in-person payment records)
-- Fee is per-batch (batches.monthly_fee), so "amount due" for
-- a member is calculated on the fly in PHP as:
--   SUM(monthly_fee of their batches) * duration_months
-- This table only stores what was ACTUALLY PAID and when —
-- there is no automatic billing, admin logs each payment
-- manually after collecting it in person.
-- ------------------------------------------------------
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(20) NOT NULL UNIQUE,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('Cash','UPI','Card','Bank Transfer','Other') DEFAULT 'Cash',
    notes VARCHAR(255),
    recorded_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES admins(admin_id) ON DELETE CASCADE
);

-- Amit (member 1, Morning Yoga @ Rs.800/mo, 1 month) — fully paid
INSERT INTO payments (receipt_number, member_id, amount, payment_date, payment_method, notes, recorded_by) VALUES
('RC0001', 1, 800.00, CURDATE(), 'Cash', 'First month payment', 1);

-- Sneha (member 2, Morning Cardio Rs.700 + Evening Zumba Rs.900 = Rs.1600/mo x 3 = Rs.4800) — partially paid
INSERT INTO payments (receipt_number, member_id, amount, payment_date, payment_method, notes, recorded_by) VALUES
('RC0002', 2, 3000.00, CURDATE(), 'UPI', 'Partial payment for 3-month renewal', 1);

-- ------------------------------------------------------
-- 6c) payment_submissions (member-submitted online payment
-- claims, awaiting Admin verification)
--
-- A member never enters an amount — the "amount" column here
-- is a SNAPSHOT of their pending balance at the moment they
-- submitted, calculated automatically by the system. The
-- member only supplies the transaction ID/UTR and the date
-- they paid. Nothing here is treated as a confirmed payment
-- until Admin verifies it — verifying creates the real row in
-- the `payments` table (see admin/payment/verify-submission.php).
-- ------------------------------------------------------
CREATE TABLE payment_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(20) NOT NULL DEFAULT 'UPI',
    status ENUM('Pending Verification','Verified','Rejected') DEFAULT 'Pending Verification',
    reject_reason VARCHAR(255),
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admins(admin_id) ON DELETE SET NULL
);

-- Kiran (member 3, Pending Payment, owes Rs.1000 for Evening Strength)
-- submitted an online payment that's awaiting Admin verification —
-- this lets you test the notification + verify flow immediately.
INSERT INTO payment_submissions (member_id, amount, transaction_id, payment_date, payment_method, status) VALUES
(3, 1000.00, '123456789012', CURDATE(), 'UPI', 'Pending Verification');


-- ------------------------------------------------------
-- 7) requested_members (public registration requests)
-- ------------------------------------------------------
CREATE TABLE requested_members (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female','Other') DEFAULT 'Male',
    dob DATE,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    emergency_contact VARCHAR(20),
    duration INT DEFAULT 1,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_date DATETIME NULL,
    approved_by INT NULL,
    FOREIGN KEY (approved_by) REFERENCES admins(admin_id) ON DELETE SET NULL
);

-- ------------------------------------------------------
-- 8) requested_member_batches (batches requested at signup)
-- ------------------------------------------------------
CREATE TABLE requested_member_batches (
    req_mem_btc_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    batch_id INT NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requested_members(request_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
);

-- ------------------------------------------------------
-- 9) renewal_requests
-- ------------------------------------------------------
CREATE TABLE renewal_requests (
    renewal_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    requested_duration INT DEFAULT 1,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_date DATETIME NULL,
    approved_by INT NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admins(admin_id) ON DELETE SET NULL
);

-- ------------------------------------------------------
-- 10) renewal_request_batches (batches requested at renewal)
-- ------------------------------------------------------
CREATE TABLE renewal_request_batches (
    renwl_req_btc_id INT AUTO_INCREMENT PRIMARY KEY,
    renewal_id INT NOT NULL,
    batch_id INT NOT NULL,
    FOREIGN KEY (renewal_id) REFERENCES renewal_requests(renewal_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
);

-- ------------------------------------------------------
-- 11) announcements
-- ------------------------------------------------------
CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    publish_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    posted_by_role ENUM('Admin','Trainer') DEFAULT 'Admin',
    posted_by_id INT NOT NULL
);

INSERT INTO announcements (title, description, posted_by_role, posted_by_id) VALUES
('Welcome to Gym-Pro', 'You can now join more than one batch — as long as their timings do not overlap. Check the Batches page to see what is available.', 'Admin', 1);

-- ------------------------------------------------------
-- 12) workout_plans (one row per batch, per weekday)
-- ------------------------------------------------------
CREATE TABLE workout_plans (
    workout_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    day_name ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    focus_area VARCHAR(100),
    details TEXT,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    UNIQUE KEY unique_batch_day (batch_id, day_name)
);

INSERT INTO workout_plans (batch_id, day_name, focus_area, details) VALUES
(2, 'Monday', 'Chest & Triceps', 'Bench press 4x10, Incline dumbbell press 3x12, Tricep pushdown 3x15'),
(2, 'Wednesday', 'Back & Biceps', 'Pull-ups 4x8, Barbell rows 4x10, Bicep curls 3x12'),
(1, 'Tuesday', 'Flexibility & Breathing', 'Sun salutations, standing poses, pranayama 15 min');

-- ------------------------------------------------------
-- 13) attendance
-- ------------------------------------------------------
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    batch_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present','Absent') DEFAULT 'Present',
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (member_id, batch_id, date)
);

INSERT INTO attendance (member_id, batch_id, date, status) VALUES
(1, 1, CURDATE(), 'Present'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Absent'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Present'),
(2, 3, CURDATE(), 'Present'),
(2, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present'),
(2, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Present'),
(2, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Absent'),
(2, 4, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Present');

-- ------------------------------------------------------
-- 14) settings (generic key-value site settings)
-- ------------------------------------------------------
CREATE TABLE settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL
);

INSERT INTO settings (setting_key, setting_value) VALUES
('gym_name', 'Gym-Pro'),
('gym_address', 'Jamnagar, Gujarat, India'),
('gym_email', 'contact@gympro.com'),
('gym_phone', '9876543210'),
('max_batches_per_member', '3'),
('gym_upi_id', 'gympro@upi');

-- ======================================================
-- END OF GYM_PRO DATABASE (v2)
--
-- IMPORTANT ONE-TIME STEP:
-- The password columns above are placeholders (not real hashes),
-- because a hash must be generated by PHP's password_hash().
-- After importing this file, visit seed-passwords.php ONCE in
-- your browser to set real, working passwords. Then log in with:
--   Admin    -> username: admin     password: password
--   Trainer  -> username: trainer1  password: password  (also trainer2)
--   Member   -> username: member1   password: password  (also member2)
-- Delete seed-passwords.php after running it once.
--
-- TEST DATA NOTES:
--   - "Morning Yoga" and "Morning Strength" are BOTH 06:00-07:00 —
--     use these two to test that the app correctly BLOCKS selecting
--     both at once (overlapping times).
--   - "Morning Cardio" (07:15-08:15) and "Evening Zumba" (17:00-18:00)
--     do NOT overlap — member2 (Sneha) is already enrolled in both,
--     proving multi-batch selection works end to end.
-- ======================================================
