<?php
/**
 * Staff Bookings Management & Review Queue
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_STAFF, ROLE_ADMIN]);

$pdo = Database::getConnection();

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT b.*, h.hall_name, p.package_name, c.full_name as customer_name, c.phone as customer_phone
        FROM `bookings` b
        JOIN `function_halls` h ON b.hall_id = h.id
        JOIN `event_packages` p ON b.package_id = p.id
        JOIN `customers` c ON b.customer_id = c.id
        WHERE 1=1";

$params = [];

if (!empty($statusFilter) && array_key_exists($statusFilter, BOOKING_STATUSES)) {
    $sql .= " AND b.booking_status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (b.booking_reference LIKE :search OR c.full_name LIKE :search OR c.phone LIKE :search OR h.hall_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY b.event_date DESC, b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Manage Bookings Queue';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div>
                <h4 class="mb-0 fw-bold text-dark">Bookings & Reservation Queue</h4>
                <small class="text-muted">Review, approve, reject, and update banquet reservations</small>
            </div>
            <div>
                <a href="<?= BASE_URL ?>staff/record_payment.php" class="btn btn-navy btn-sm">
                    <i class="bi bi-credit-card-2-front me-1"></i> Record Payment
                </a>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search and Filter Bar -->
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
                <form method="GET" action="bookings.php" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   value="<?= e($search) ?>" placeholder="Search by reference, client name, phone...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <?php foreach (BOOKING_STATUSES as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= e($v['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-navy btn-sm flex-grow-1">Filter</button>
                        <?php if ($search || $statusFilter): ?>
                            <a href="bookings.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Bookings Master Table -->
            <div class="card-table">
                <div class="card-table-header">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">Reservation Dossiers</h5>
                        <small class="text-muted"><?= count($bookings) ?> reservations listed</small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Client Details</th>
                                <th>Venue</th>
                                <th>Date & Timing</th>
                                <th>Guests</th>
                                <th>Total Cost</th>
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
                                        No bookings matched your filter criteria.
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
                                            <div class="fw-semibold text-dark"><?= e($b['customer_name']) ?></div>
                                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($b['customer_phone']) ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold"><?= e($b['hall_name']) ?></span>
                                            <small class="d-block text-muted"><?= e($b['event_type']) ?></small>
                                        </td>
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
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>staff/booking_detail.php?id=<?= $b['id'] ?>" class="btn btn-outline-navy" title="Review Dossier">
                                                    <i class="bi bi-eye"></i> Review
                                                </a>
                                                <a href="<?= BASE_URL ?>staff/record_payment.php?booking_id=<?= $b['id'] ?>" class="btn btn-outline-success" title="Record Payment">
                                                    <i class="bi bi-cash"></i>
                                                </a>
                                            </div>
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
