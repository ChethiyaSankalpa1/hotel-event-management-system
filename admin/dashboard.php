<?php
/**
 * Administrator Executive Dashboard & Analytics
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$user = current_user();

// Executive KPI metrics
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `payments`")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn();
$activeBookings = $pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `booking_status` IN ('confirmed', 'approved')")->fetchColumn();
$pendingReview = $pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `booking_status` = 'pending'")->fetchColumn();
$outstandingDue = $pdo->query("SELECT COALESCE(SUM(balance_due), 0) FROM `bookings` WHERE `booking_status` = 'confirmed'")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
$totalHalls = $pdo->query("SELECT COUNT(*) FROM `function_halls` WHERE `status` = 'active'")->fetchColumn();

// Hall popularity / occupancy breakdown
$hallStats = $pdo->query("
    SELECT h.hall_name, COUNT(b.id) as booking_count, COALESCE(SUM(b.total_amount), 0) as total_revenue
    FROM `function_halls` h
    LEFT JOIN `bookings` b ON b.hall_id = h.id AND b.booking_status IN ('confirmed', 'completed')
    GROUP BY h.id
    ORDER BY total_revenue DESC
")->fetchAll();

// Recent payment transactions
$recentPayments = $pdo->query("
    SELECT p.*, b.booking_reference, c.full_name as customer_name
    FROM `payments` p
    JOIN `bookings` b ON p.booking_id = b.id
    JOIN `customers` c ON b.customer_id = c.id
    ORDER BY p.id DESC
    LIMIT 6
")->fetchAll();

$pageTitle = 'Administrator Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div>
                <h4 class="mb-0 fw-bold text-dark">Executive Administration Dashboard</h4>
                <small class="text-muted">Business intelligence, financial analytics, and venue capacity metrics</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-navy btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate Reports
                </a>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Executive KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Gross Revenue Collected</div>
                            <div class="stat-value text-success" style="font-size:1.5rem;"><?= format_currency($totalRevenue) ?></div>
                        </div>
                        <div class="stat-icon green">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Active Reservations</div>
                            <div class="stat-value text-primary"><?= (int)$activeBookings ?></div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Outstanding Receivables</div>
                            <div class="stat-value text-danger" style="font-size:1.5rem;"><?= format_currency($outstandingDue) ?></div>
                        </div>
                        <div class="stat-icon red">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Pending Submissions</div>
                            <div class="stat-value text-warning"><?= (int)$pendingReview ?></div>
                        </div>
                        <div class="stat-icon gold">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hall Revenue Breakdown & Recent Transactions -->
            <div class="row g-4 mb-4">
                <!-- Hall Occupancy & Revenue -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-3 p-4 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0">Venue Revenue & Booking Distribution</h5>
                            <a href="<?= BASE_URL ?>admin/halls.php" class="small text-decoration-none">Manage Halls</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Function Hall</th>
                                        <th class="text-center">Bookings</th>
                                        <th class="text-end">Revenue Generated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hallStats as $hs): ?>
                                        <tr>
                                            <td><strong class="text-dark"><?= e($hs['hall_name']) ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border"><?= (int)$hs['booking_count'] ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-navy" style="color:#0b1a30;">
                                                <?= format_currency($hs['total_revenue']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Ledger Receipts -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-3 p-4 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0">Recent Payment Receipts</h5>
                            <a href="<?= BASE_URL ?>admin/payments.php" class="small text-decoration-none">All Receipts</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Receipt #</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentPayments)): ?>
                                        <tr><td colspan="4" class="text-center py-3 text-muted">No payments logged yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentPayments as $rp): ?>
                                            <tr>
                                                <td><strong class="text-navy"><?= e($rp['receipt_number']) ?></strong></td>
                                                <td><?= e($rp['customer_name']) ?></td>
                                                <td><small class="text-muted"><?= format_date($rp['payment_date']) ?></small></td>
                                                <td class="text-end text-success fw-bold"><?= format_currency($rp['amount']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Management Shortcuts -->
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <a href="<?= BASE_URL ?>admin/halls.php" class="card shadow-sm border-0 p-3 text-decoration-none text-dark bg-white hover-shadow text-center">
                        <i class="bi bi-buildings fs-2 text-warning mb-2 d-inline-block"></i>
                        <h6 class="fw-bold mb-0">Manage Halls</h6>
                        <small class="text-muted">Capacities & rates</small>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="<?= BASE_URL ?>admin/packages.php" class="card shadow-sm border-0 p-3 text-decoration-none text-dark bg-white hover-shadow text-center">
                        <i class="bi bi-box-seam fs-2 text-warning mb-2 d-inline-block"></i>
                        <h6 class="fw-bold mb-0">Manage Packages</h6>
                        <small class="text-muted">Catering & pricing</small>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="<?= BASE_URL ?>admin/staff.php" class="card shadow-sm border-0 p-3 text-decoration-none text-dark bg-white hover-shadow text-center">
                        <i class="bi bi-people fs-2 text-warning mb-2 d-inline-block"></i>
                        <h6 class="fw-bold mb-0">Staff Accounts</h6>
                        <small class="text-muted">Banquet managers</small>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="<?= BASE_URL ?>admin/settings.php" class="card shadow-sm border-0 p-3 text-decoration-none text-dark bg-white hover-shadow text-center">
                        <i class="bi bi-gear fs-2 text-warning mb-2 d-inline-block"></i>
                        <h6 class="fw-bold mb-0">System Settings</h6>
                        <small class="text-muted">Tax & advance rates</small>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
