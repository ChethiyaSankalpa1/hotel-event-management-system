<?php
/**
 * Administrator Reports & Financial Analytics
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$settings = get_system_settings();

// Default date range: current year to date
$fromDate = $_GET['from_date'] ?? date('Y-01-01');
$toDate   = $_GET['to_date']   ?? date('Y-12-31');

// 1. Report metrics for selected range
$summaryStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reservations,
        SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
        COALESCE(SUM(total_amount), 0) as total_invoiced,
        COALESCE(SUM(advance_paid), 0) as total_collected,
        COALESCE(SUM(balance_due), 0) as total_outstanding
    FROM `bookings`
    WHERE `event_date` BETWEEN :from_d AND :to_d
");
$summaryStmt->execute([':from_d' => $fromDate, ':to_d' => $toDate]);
$summary = $summaryStmt->fetch();

// 2. Revenue and bookings by Function Hall
$hallReportStmt = $pdo->prepare("
    SELECT h.hall_name, h.hall_code,
           COUNT(b.id) as bookings_count,
           COALESCE(SUM(b.total_amount), 0) as hall_revenue,
           COALESCE(SUM(b.advance_paid), 0) as hall_collected
    FROM `function_halls` h
    LEFT JOIN `bookings` b ON b.hall_id = h.id 
         AND b.event_date BETWEEN :from_d AND :to_d
         AND b.booking_status NOT IN ('cancelled', 'rejected')
    GROUP BY h.id
    ORDER BY hall_revenue DESC
");
$hallReportStmt->execute([':from_d' => $fromDate, ':to_d' => $toDate]);
$hallReports = $hallReportStmt->fetchAll();

// 3. Detailed bookings within range
$detailStmt = $pdo->prepare("
    SELECT b.*, h.hall_name, p.package_name, c.full_name as customer_name
    FROM `bookings` b
    JOIN `function_halls` h ON b.hall_id = h.id
    JOIN `event_packages` p ON b.package_id = p.id
    JOIN `customers` c ON b.customer_id = c.id
    WHERE b.event_date BETWEEN :from_d AND :to_d
    ORDER BY b.event_date ASC
");
$detailStmt->execute([':from_d' => $fromDate, ':to_d' => $toDate]);
$rangeBookings = $detailStmt->fetchAll();

$pageTitle = 'Executive Reports & Analytics';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sidebar-toggle d-lg-none" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">Executive Reports & Audits</h4>
                    <small class="text-muted d-none d-sm-inline">Financial revenue statements, hall utilization, and reservation breakdowns</small>
                </div>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-navy btn-sm">
                    <i class="bi bi-printer me-1"></i> <span class="d-none d-sm-inline">Print</span> Report
                </button>
            </div>
        </header>

        <div class="content-body">
            <!-- Formal Report Header (Visible in print) -->
            <div class="d-none d-print-block mb-4 text-center border-bottom pb-3">
                <h2 class="luxury-heading mb-1"><?= e($settings['hotel_name']) ?></h2>
                <h5 class="text-dark">Official Banquet & Revenue Audit Report</h5>
                <p class="text-muted small mb-0">Period: <?= format_date($fromDate) ?> through <?= format_date($toDate) ?> | Generated on: <?= date('F j, Y, g:i A') ?></p>
            </div>

            <!-- Date Range Selector -->
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white no-print">
                <form method="GET" action="reports.php" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Reporting Period: From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?= e($fromDate) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?= e($toDate) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-navy btn-sm flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Generate Report
                        </button>
                        <a href="reports.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Summary Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Period Invoiced</div>
                            <div class="stat-value text-navy" style="font-size:1.4rem;"><?= format_currency($summary['total_invoiced']) ?></div>
                        </div>
                        <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Collected Revenue</div>
                            <div class="stat-value text-success" style="font-size:1.4rem;"><?= format_currency($summary['total_collected']) ?></div>
                        </div>
                        <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Outstanding Due</div>
                            <div class="stat-value text-danger" style="font-size:1.4rem;"><?= format_currency($summary['total_outstanding']) ?></div>
                        </div>
                        <div class="stat-icon red"><i class="bi bi-wallet2"></i></div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div>
                            <div class="stat-title">Total Functions</div>
                            <div class="stat-value"><?= (int)$summary['total_reservations'] ?></div>
                        </div>
                        <div class="stat-icon gold"><i class="bi bi-calendar-event"></i></div>
                    </div>
                </div>
            </div>

            <!-- Hall Performance Analysis Table -->
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-3">Function Hall Revenue & Utilization Performance</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Function Hall Venue</th>
                                <th>Hall Code</th>
                                <th class="text-center">Functions Hosted</th>
                                <th class="text-end">Total Invoiced</th>
                                <th class="text-end">Revenue Realized</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hallReports as $hr): ?>
                                <tr>
                                    <td><strong class="text-dark"><?= e($hr['hall_name']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($hr['hall_code']) ?></span></td>
                                    <td class="text-center fw-semibold"><?= (int)$hr['bookings_count'] ?></td>
                                    <td class="text-end fw-bold"><?= format_currency($hr['hall_revenue']) ?></td>
                                    <td class="text-end text-success fw-bold"><?= format_currency($hr['hall_collected']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Bookings Register -->
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h5 class="fw-bold text-dark mb-3">Event Register for Selected Period (<?= count($rangeBookings) ?> Events)</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Venue</th>
                                <th>Occasion</th>
                                <th>Guests</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rangeBookings)): ?>
                                <tr><td colspan="10" class="text-center py-4 text-muted">No reservations fall within this reporting period.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rangeBookings as $rb): ?>
                                    <tr>
                                        <td><strong><?= e($rb['booking_reference']) ?></strong></td>
                                        <td><?= format_date($rb['event_date']) ?></td>
                                        <td><?= e($rb['customer_name']) ?></td>
                                        <td><?= e($rb['hall_name']) ?></td>
                                        <td><?= e($rb['event_type']) ?></td>
                                        <td><?= number_format($rb['guest_count']) ?></td>
                                        <td><?= format_currency($rb['total_amount']) ?></td>
                                        <td class="text-success"><?= format_currency($rb['advance_paid']) ?></td>
                                        <td class="text-danger"><?= format_currency($rb['balance_due']) ?></td>
                                        <td><?= render_booking_badge($rb['booking_status']) ?></td>
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
