<?php
/**
 * Customer Booking History Directory
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

$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT b.*, h.hall_name, h.hall_code, p.package_name
        FROM `bookings` b
        JOIN `function_halls` h ON b.hall_id = h.id
        JOIN `event_packages` p ON b.package_id = p.id
        WHERE b.customer_id = :cid";

$params = [':cid' => $customerId];

if (!empty($statusFilter) && array_key_exists($statusFilter, BOOKING_STATUSES)) {
    $sql .= " AND b.booking_status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY b.event_date DESC, b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Bookings History';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="py-4 bg-navy text-white" style="background:#0b1a30; border-bottom: 3px solid #c5a880;">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="text-warning text-uppercase fw-bold small" style="letter-spacing: 1.5px;">Customer Portal</span>
                <h2 class="luxury-heading text-white mb-0 mt-1">My Booking History</h2>
            </div>
            <div>
                <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold shadow-sm">
                    <i class="bi bi-calendar-plus me-1"></i> New Booking Request
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <?php require __DIR__ . '/../includes/alerts.php'; ?>

    <!-- Filter Tabs -->
    <div class="d-flex gap-2 flex-wrap mb-4">
        <a href="my_bookings.php" class="btn <?= empty($statusFilter) ? 'btn-navy' : 'btn-outline-secondary' ?> btn-sm">
            All Reservations
        </a>
        <a href="my_bookings.php?status=pending" class="btn <?= $statusFilter === 'pending' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?> btn-sm">
            Pending Review
        </a>
        <a href="my_bookings.php?status=approved" class="btn <?= $statusFilter === 'approved' ? 'btn-info text-white' : 'btn-outline-secondary' ?> btn-sm">
            Approved (Awaiting Advance)
        </a>
        <a href="my_bookings.php?status=confirmed" class="btn <?= $statusFilter === 'confirmed' ? 'btn-success' : 'btn-outline-secondary' ?> btn-sm">
            Confirmed & Booked
        </a>
        <a href="my_bookings.php?status=completed" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm">
            Completed
        </a>
    </div>

    <!-- Bookings Table -->
    <div class="card-table">
        <div class="card-table-header">
            <h5 class="mb-0 fw-bold text-dark">Reservation Ledger</h5>
            <small class="text-muted"><?= count($bookings) ?> records found</small>
        </div>

        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Function Hall</th>
                        <th>Event Occasion</th>
                        <th>Date & Timing</th>
                        <th>Guests</th>
                        <th>Total Amount</th>
                        <th>Balance Due</th>
                        <th>Booking Status</th>
                        <th>Payment Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar2-x fs-2 d-block mb-2"></i>
                                No event bookings found under this filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong class="text-navy"><?= e($b['booking_reference']) ?></strong>
                                    <small class="d-block text-muted"><?= date('M j, Y', strtotime($b['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= e($b['hall_name']) ?></span>
                                    <small class="d-block text-muted"><?= e($b['package_name']) ?></small>
                                </td>
                                <td><?= e($b['event_type']) ?></td>
                                <td>
                                    <div><strong><?= format_date($b['event_date']) ?></strong></div>
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
                                <td><?= render_payment_badge($b['payment_status']) ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>customer/booking_detail.php?id=<?= $b['id'] ?>" class="btn btn-navy btn-sm py-1 px-2" title="View Full Contract & Invoice">
                                        <i class="bi bi-eye me-1"></i> Details
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
