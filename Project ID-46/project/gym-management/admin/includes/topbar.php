<?php
// ==========================================================
// admin/includes/topbar.php
// Top bar shown above the page content, inside the main column.
// Also opens the .panel-content wrapper (closed in footer.php).
// ==========================================================
require_once __DIR__ . "/../../includes/notification-functions.php";
require_once __DIR__ . "/../../includes/payment-functions.php";

// Gather notification counts — runs on every admin page since
// this file is included everywhere, so the bell is always current.
$notif_expiring = get_expiring_soon_members($conn, 7);
$notif_expired_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM members WHERE status='Expired'");
if ($res) { $notif_expired_count = mysqli_fetch_assoc($res)['c']; }
$notif_pending_reg = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM requested_members WHERE status='Pending'");
if ($res) { $notif_pending_reg = mysqli_fetch_assoc($res)['c']; }
$notif_pending_renew = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM renewal_requests WHERE status='Pending'");
if ($res) { $notif_pending_renew = mysqli_fetch_assoc($res)['c']; }
$notif_payment_submissions = get_pending_payment_submissions($conn);

$notif_total = count($notif_expiring) + (int)$notif_expired_count + (int)$notif_pending_reg + (int)$notif_pending_renew + count($notif_payment_submissions);
?>
<div class="flex-grow-1">
    <div class="panel-topbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <button class="btn panel-hamburger d-lg-none" id="sidebarToggle" aria-label="Toggle menu"><i class="bi bi-list fs-4"></i></button>
            <h5 class="mb-0"><?php echo htmlspecialchars((isset($page_title) ? $page_title : 'Admin')); ?></h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn position-relative panel-bell" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <?php if ($notif_total > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notif_total > 99 ? '99+' : $notif_total; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:320px; max-height:400px; overflow-y:auto;">
                    <h6 class="dropdown-header">Notifications</h6>
                    <?php if ($notif_total === 0): ?>
                        <p class="text-muted small px-3 mb-1">Nothing needs your attention right now.</p>
                    <?php else: ?>
                        <?php foreach ($notif_payment_submissions as $ps): ?>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>admin/payment/verify-submission.php?id=<?php echo $ps['submission_id']; ?>">
                                <i class="bi bi-cash-coin text-success me-1"></i>
                                <?php echo htmlspecialchars($ps['full_name']); ?> submitted Rs. <?php echo number_format($ps['amount'], 2); ?> — verify
                            </a>
                        <?php endforeach; ?>
                        <?php foreach ($notif_expiring as $ex): ?>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>admin/member/view-member.php?id=<?php echo $ex['member_id']; ?>">
                                <i class="bi bi-clock-history text-warning me-1"></i>
                                <?php echo htmlspecialchars($ex['full_name']); ?> — <?php echo format_days_left($ex['days_left']); ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($notif_expired_count > 0): ?>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>admin/member/members.php">
                                <i class="bi bi-exclamation-octagon text-danger me-1"></i>
                                <?php echo $notif_expired_count; ?> membership(s) expired
                            </a>
                        <?php endif; ?>
                        <?php if ($notif_pending_reg > 0): ?>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>admin/request/registration_request/requests.php">
                                <i class="bi bi-person-plus text-primary me-1"></i>
                                <?php echo $notif_pending_reg; ?> pending registration request(s)
                            </a>
                        <?php endif; ?>
                        <?php if ($notif_pending_renew > 0): ?>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>admin/request/renewable_request/requests.php">
                                <i class="bi bi-arrow-repeat text-primary me-1"></i>
                                <?php echo $notif_pending_renew; ?> pending renewal request(s)
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <i class="bi bi-person-circle fs-4 text-secondary"></i>
            <span class="fw-semibold d-none d-sm-inline"><?php echo htmlspecialchars((isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin')); ?></span>
        </div>
    </div>
    <div class="panel-content">
