<?php
/**
 * Administrator Master Bookings Ledger
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

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
    $sql .= " AND (b.booking_reference LIKE :search OR c.full_name LIKE :search OR h.hall_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND b.event_date BETWEEN :start_d AND :end_d";
    $params[':start_d'] = $startDate;
    $params[':end_d'] = $endDate;
}

$sql .= " ORDER BY b.event_date DESC, b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Master Bookings Ledger';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sidebar-toggle d-lg-none" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">Master Bookings Ledger</h4>
                    <small class="text-muted d-none d-sm-inline">Consolidated reservation records across all hotel function halls</small>
                </div>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i> <span class="d-none d-sm-inline">Print</span> Roster
                </button>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Advanced Filter Bar -->
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
                <form method="GET" action="bookings.php" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" 
                               value="<?= e($search) ?>" placeholder="Ref #, client, venue...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <?php foreach (BOOKING_STATUSES as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= e($v['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">From Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">To Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-navy btn-sm flex-grow-1">Filter</button>
                        <?php if ($search || $statusFilter || $startDate || $endDate): ?>
                            <a href="bookings.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Bookings Table -->
            <div class="card-table">
                <div class="card-table-header">
                    <h5 class="mb-0 fw-bold text-dark">Master Reservation Entries (<?= count($bookings) ?>)</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Client Details</th>
                                <th>Function Hall</th>
                                <th>Date & Window</th>
                                <th>Guests</th>
                                <th>Total Cost</th>
                                <th>Paid</th>
                                <th>Balance Due</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <strong class="text-navy"><?= e($b['booking_reference']) ?></strong>
                                        <small class="d-block text-muted"><?= date('M j, Y', strtotime($b['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= e($b['customer_name']) ?></strong>
                                        <small class="d-block text-muted"><?= e($b['customer_phone']) ?></small>
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
                                    <td class="text-success"><?= format_currency($b['advance_paid']) ?></td>
                                    <td>
                                        <?php if ((float)$b['balance_due'] > 0): ?>
                                            <span class="text-danger fw-bold"><?= format_currency($b['balance_due']) ?></span>
                                        <?php else: ?>
                                            <span class="text-success small fw-semibold">Settled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= render_booking_badge($b['booking_status']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>staff/booking_detail.php?id=<?= $b['id'] ?>" class="btn btn-outline-navy btn-sm py-1 px-2" title="Inspect Booking">
                                            <i class="bi bi-eye"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
