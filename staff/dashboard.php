<?php
/**
 * Event Manager / Staff Dashboard
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_STAFF, ROLE_ADMIN]);

$pdo = Database::getConnection();
$user = current_user();

// Fetch metrics
$pendingCount = $pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `booking_status` = 'pending'")->fetchColumn();
$todayEventsCount = $pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `event_date` = CURRENT_DATE() AND `booking_status` IN ('confirmed', 'approved')")->fetchColumn();
$upcomingCount = $pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `event_date` > CURRENT_DATE() AND `booking_status` = 'confirmed'")->fetchColumn();
$unpaidBalances = $pdo->query("SELECT COALESCE(SUM(balance_due), 0) FROM `bookings` WHERE `booking_status` = 'confirmed' AND `balance_due` > 0")->fetchColumn();

// Fetch pending booking requests requiring attention
$pendingStmt = $pdo->query("
    SELECT b.*, h.hall_name, p.package_name, c.full_name as customer_name, c.phone as customer_phone
    FROM `bookings` b
    JOIN `function_halls` h ON b.hall_id = h.id
    JOIN `event_packages` p ON b.package_id = p.id
    JOIN `customers` c ON b.customer_id = c.id
    WHERE b.booking_status = 'pending'
    ORDER BY b.created_at ASC
    LIMIT 6
");
$pendingBookings = $pendingStmt->fetchAll();

// Fetch events scheduled for the next 7 days
$upcomingStmt = $pdo->query("
    SELECT b.*, h.hall_name, c.full_name as customer_name, c.phone as customer_phone
    FROM `bookings` b
    JOIN `function_halls` h ON b.hall_id = h.id
    JOIN `customers` c ON b.customer_id = c.id
    WHERE b.event_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
      AND b.booking_status IN ('confirmed', 'approved')
    ORDER BY b.event_date ASC, b.start_time ASC
    LIMIT 6
");
$upcomingSchedule = $upcomingStmt->fetchAll();

$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Staff Sidebar -->
    <?php require __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sidebar-toggle d-lg-none" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">Banquet Operations Desk</h4>
                    <small class="text-muted d-none d-sm-inline">Daily Event Coordination & Reservations Queue</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border p-2 d-none d-md-inline-flex">
                    <i class="bi bi-clock me-1 text-primary"></i> <?= date('l, M j, Y') ?>
                </span>
                <a href="<?= BASE_URL ?>staff/record_payment.php" class="btn btn-navy btn-sm">
                    <i class="bi bi-credit-card-2-front me-1"></i> <span class="d-none d-sm-inline">Record</span> Payment
                </a>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Pending Requests</div>
                            <div class="stat-value text-warning"><?= (int)$pendingCount ?></div>
                        </div>
                        <div class="stat-icon gold">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Today's Banquets</div>
                            <div class="stat-value text-primary"><?= (int)$todayEventsCount ?></div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Confirmed Upcoming</div>
                            <div class="stat-value text-success"><?= (int)$upcomingCount ?></div>
                        </div>
                        <div class="stat-icon green">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Confirmed Balances</div>
                            <div class="stat-value text-danger" style="font-size:1.45rem;"><?= format_currency($unpaidBalances) ?></div>
                        </div>
                        <div class="stat-icon red">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Booking Approvals Table -->
            <div class="card-table mb-4">
                <div class="card-table-header bg-warning bg-opacity-10 border-bottom border-warning">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-exclamation-circle text-warning me-2"></i> Pending Booking Requests Requiring Review
                        </h5>
                        <small class="text-muted">Review client requests, inspect hall availability, and approve or decline</small>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>staff/bookings.php?status=pending" class="btn btn-outline-dark btn-sm">
                            View All (<?= (int)$pendingCount ?>)
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Customer</th>
                                <th>Function Hall</th>
                                <th>Event Date & Time</th>
                                <th>Guests</th>
                                <th>Total Cost</th>
                                <th>Advance Req.</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingBookings)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-check2-all fs-3 text-success d-block mb-1"></i>
                                        All booking requests are processed! No pending submissions.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingBookings as $pb): ?>
                                    <tr>
                                        <td><strong><?= e($pb['booking_reference']) ?></strong></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($pb['customer_name']) ?></div>
                                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($pb['customer_phone']) ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold"><?= e($pb['hall_name']) ?></span>
                                            <small class="d-block text-muted"><?= e($pb['event_type']) ?></small>
                                        </td>
                                        <td>
                                            <div><strong><?= format_date($pb['event_date']) ?></strong></div>
                                            <small class="text-muted"><?= format_time($pb['start_time']) ?> - <?= format_time($pb['end_time']) ?></small>
                                        </td>
                                        <td><?= number_format($pb['guest_count']) ?></td>
                                        <td><strong><?= format_currency($pb['total_amount']) ?></strong></td>
                                        <td><span class="badge bg-warning text-dark"><?= format_currency($pb['advance_required']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>staff/booking_detail.php?id=<?= $pb['id'] ?>" class="btn btn-gold btn-sm px-3">
                                                Review Request <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Upcoming Events Schedule (Next 7 Days) -->
            <div class="card-table">
                <div class="card-table-header">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-calendar3-week text-primary me-2"></i> Upcoming Schedule (Next 7 Days)
                        </h5>
                        <small class="text-muted">Approved & confirmed banquet functions</small>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>staff/calendar.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-calendar3 me-1"></i> Interactive Calendar
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Date & Window</th>
                                <th>Hall Venue</th>
                                <th>Occasion & Customer</th>
                                <th>Guests</th>
                                <th>Status</th>
                                <th>Payment Balance</th>
                                <th class="text-end">Dossier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($upcomingSchedule)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No banquets scheduled in the upcoming 7 days.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcomingSchedule as $up): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-navy"><?= format_date($up['event_date']) ?></div>
                                            <small class="text-muted"><?= format_time($up['start_time']) ?> - <?= format_time($up['end_time']) ?></small>
                                        </td>
                                        <td><strong class="text-dark"><?= e($up['hall_name']) ?></strong></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($up['event_type']) ?></div>
                                            <small class="text-muted"><?= e($up['customer_name']) ?> (<?= e($up['customer_phone']) ?>)</small>
                                        </td>
                                        <td><?= number_format($up['guest_count']) ?></td>
                                        <td><?= render_booking_badge($up['booking_status']) ?></td>
                                        <td>
                                            <?php if ((float)$up['balance_due'] > 0): ?>
                                                <span class="text-danger fw-bold"><?= format_currency($up['balance_due']) ?> due</span>
                                            <?php else: ?>
                                                <span class="text-success small fw-semibold"><i class="bi bi-check-circle me-1"></i> Paid in Full</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>staff/booking_detail.php?id=<?= $up['id'] ?>" class="btn btn-outline-navy btn-sm py-1 px-2">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
