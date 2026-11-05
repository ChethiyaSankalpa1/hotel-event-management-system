<?php
/**
 * Customer Personal Dashboard
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_CUSTOMER);

$pdo = Database::getConnection();
$user = current_user();
$customerId = (int)($user['entity_id'] ?? 0);

// Fetch customer statistics
$statStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN booking_status IN ('pending', 'approved', 'confirmed') THEN 1 ELSE 0 END) AS active_bookings,
        COALESCE(SUM(total_amount), 0) AS total_spent,
        COALESCE(SUM(advance_paid), 0) AS total_paid,
        COALESCE(SUM(balance_due), 0) AS total_balance
    FROM `bookings` 
    WHERE `customer_id` = :cid
");
$statStmt->execute([':cid' => $customerId]);
$stats = $statStmt->fetch();

// Fetch customer bookings
$bStmt = $pdo->prepare("
    SELECT b.*, h.hall_name, p.package_name
    FROM `bookings` b
    JOIN `function_halls` h ON b.hall_id = h.id
    JOIN `event_packages` p ON b.package_id = p.id
    WHERE b.customer_id = :cid
    ORDER BY b.event_date DESC, b.id DESC
    LIMIT 10
");
$bStmt->execute([':cid' => $customerId]);
$recentBookings = $bStmt->fetchAll();

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="customer-hero">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge-portal">Customer Portal</span>
                <h2 class="luxury-heading mb-0 mt-1">Welcome, <?= e($user['name']) ?></h2>
            </div>
            <div>
                <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold shadow-sm">
                    <i class="bi bi-calendar-plus me-1"></i> Book New Event
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <?php require __DIR__ . '/../includes/alerts.php'; ?>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Active Bookings</div>
                    <div class="stat-value"><?= (int)$stats['active_bookings'] ?></div>
                </div>
                <div class="stat-icon blue">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Total Invoiced</div>
                    <div class="stat-value text-navy"><?= format_currency($stats['total_spent']) ?></div>
                </div>
                <div class="stat-icon gold">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Total Paid</div>
                    <div class="stat-value text-success"><?= format_currency($stats['total_paid']) ?></div>
                </div>
                <div class="stat-icon green">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Outstanding Balance</div>
                    <div class="stat-value text-danger"><?= format_currency($stats['total_balance']) ?></div>
                </div>
                <div class="stat-icon red">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card-table">
        <div class="card-table-header">
            <div>
                <h5 class="mb-0 fw-bold text-dark">My Event Reservations</h5>
                <small class="text-muted">Track status, scheduled times, and invoice balances</small>
            </div>
            <div>
                <a href="<?= BASE_URL ?>customer/my_bookings.php" class="btn btn-outline-secondary btn-sm">
                    View All History
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th>Booking Ref</th>
                        <th>Venue & Event</th>
                        <th>Date & Time</th>
                        <th>Guests</th>
                        <th>Total Amount</th>
                        <th>Balance Due</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentBookings)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                You have not placed any event bookings yet.
                                <div class="mt-2">
                                    <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold btn-sm">Make Your First Reservation</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $b): ?>
                            <tr>
                                <td>
                                    <strong class="text-navy"><?= e($b['booking_reference']) ?></strong>
                                    <small class="d-block text-muted"><?= date('M j, Y', strtotime($b['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($b['hall_name']) ?></div>
                                    <small class="text-muted"><?= e($b['event_type']) ?></small>
                                </td>
                                <td>
                                    <div><i class="bi bi-calendar3 me-1 text-muted"></i><?= format_date($b['event_date']) ?></div>
                                    <small class="text-muted"><?= format_time($b['start_time']) ?> - <?= format_time($b['end_time']) ?></small>
                                </td>
                                <td><?= number_format($b['guest_count']) ?></td>
                                <td><strong><?= format_currency($b['total_amount']) ?></strong></td>
                                <td>
                                    <?php if ((float)$b['balance_due'] > 0): ?>
                                        <span class="text-danger fw-bold"><?= format_currency($b['balance_due']) ?></span>
                                    <?php else: ?>
                                        <span class="text-success small fw-semibold">Settled</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= render_booking_badge($b['booking_status']) ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>customer/booking_detail.php?id=<?= $b['id'] ?>" class="btn btn-outline-navy btn-sm py-1 px-2" title="View Dossier">
                                        <i class="bi bi-file-earmark-text me-1"></i> Details
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
