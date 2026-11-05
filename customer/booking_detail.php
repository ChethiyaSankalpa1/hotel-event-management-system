<?php
/**
 * Customer Booking Dossier & Invoice View
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/PaymentService.php';

require_role(ROLE_CUSTOMER);

$pdo = Database::getConnection();
$user = current_user();
$customerId = (int)($user['entity_id'] ?? 0);
$settings = get_system_settings();

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT b.*, 
           h.hall_name, h.hall_code, h.dimensions, h.description as hall_desc,
           p.package_name, p.package_code, p.category, p.description as pkg_desc, p.menu_details, p.inclusions,
           c.full_name as customer_name, c.phone as customer_phone, c.address as customer_address, c.city as customer_city,
           u.email as customer_email,
           rev.email as reviewer_email, stf.full_name as reviewer_name
    FROM `bookings` b
    JOIN `function_halls` h ON b.hall_id = h.id
    JOIN `event_packages` p ON b.package_id = p.id
    JOIN `customers` c ON b.customer_id = c.id
    JOIN `users` u ON c.user_id = u.id
    LEFT JOIN `users` rev ON b.reviewed_by = rev.id
    LEFT JOIN `staff` stf ON stf.user_id = rev.id
    WHERE b.id = :id AND b.customer_id = :cid
    LIMIT 1
");
$stmt->execute([':id' => $bookingId, ':cid' => $customerId]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Booking reservation not found or access denied.');
    header('Location: ' . BASE_URL . 'customer/my_bookings.php');
    exit;
}

// Fetch payments for this booking
$payments = PaymentService::getBookingPayments($bookingId);

$pageTitle = 'Reservation Details - ' . $booking['booking_reference'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <a href="<?= BASE_URL ?>customer/my_bookings.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to My Bookings
            </a>
            <h2 class="luxury-heading mb-0 mt-1">Reservation Dossier</h2>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Print Invoice
            </button>
            <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold btn-sm">
                <i class="bi bi-calendar-plus me-1"></i> New Booking
            </a>
        </div>
    </div>

    <?php require __DIR__ . '/../includes/alerts.php'; ?>

    <!-- Status Alert Banner -->
    <?php if ($booking['booking_status'] === 'pending'): ?>
        <div class="alert alert-warning d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-hourglass-split fs-3 me-3 flex-shrink-0"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Booking Request Under Review</h6>
                <p class="mb-0 small">Our Banquet Coordination team is evaluating your date and venue request. You will be prompted to remit the advance deposit once approved.</p>
            </div>
        </div>
    <?php elseif ($booking['booking_status'] === 'approved'): ?>
        <div class="alert alert-info d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-3 me-3 flex-shrink-0"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Booking Approved! Advance Payment Required</h6>
                <p class="mb-0 small">Your requested time slot is approved. Please contact our banquet desk or transfer the advance deposit of <strong><?= format_currency($booking['advance_required']) ?></strong> to confirm your date.</p>
            </div>
        </div>
    <?php elseif ($booking['booking_status'] === 'confirmed'): ?>
        <div class="alert alert-success d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-shield-check fs-3 me-3 flex-shrink-0"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Reservation Confirmed & Secured</h6>
                <p class="mb-0 small">Your event is confirmed on our master calendar. Remaining balance of <strong><?= format_currency($booking['balance_due']) ?></strong> is due prior to the event date.</p>
            </div>
        </div>
    <?php elseif ($booking['booking_status'] === 'rejected'): ?>
        <div class="alert alert-danger d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-x-octagon-fill fs-3 me-3 flex-shrink-0"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Booking Request Declined</h6>
                <p class="mb-0 small"><strong>Reason:</strong> <?= e($booking['rejection_reason'] ?? 'Date or capacity conflict.') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Printable Invoice & Dossier Document -->
    <div class="card shadow-sm border-0 rounded-3 p-4 p-md-5 bg-white print-receipt">
        <!-- Document Header -->
        <div class="row align-items-start border-bottom pb-4 mb-4">
            <div class="col-sm-6">
                <h4 class="luxury-heading mb-1" style="font-size: 1.6rem; color:#0b1a30;"><?= e($settings['hotel_name']) ?></h4>
                <p class="text-muted small mb-1"><?= e($settings['address']) ?>, <?= e($settings['city_state_zip']) ?></p>
                <p class="text-muted small mb-0"><i class="bi bi-telephone me-1"></i> <?= e($settings['phone']) ?> | <i class="bi bi-envelope me-1"></i> <?= e($settings['email']) ?></p>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <span class="badge bg-navy text-warning px-3 py-2 fs-6 mb-2" style="background:#0b1a30;">
                    <?= e($booking['booking_reference']) ?>
                </span>
                <div class="small text-muted">Date of Request: <?= date('F j, Y', strtotime($booking['created_at'])) ?></div>
                <div class="mt-2">
                    <?= render_booking_badge($booking['booking_status']) ?>
                    <?= render_payment_badge($booking['payment_status']) ?>
                </div>
            </div>
        </div>

        <!-- Customer & Event Schedule Summary -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <h6 class="fw-bold text-uppercase small text-muted mb-2">Reserved For</h6>
                    <h5 class="fw-bold text-dark mb-1"><?= e($booking['customer_name']) ?></h5>
                    <p class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i> <?= e($booking['customer_email']) ?></p>
                    <p class="small text-muted mb-1"><i class="bi bi-telephone me-1"></i> <?= e($booking['customer_phone']) ?></p>
                    <?php if (!empty($booking['customer_address'])): ?>
                        <p class="small text-muted mb-0"><i class="bi bi-geo-alt me-1"></i> <?= e($booking['customer_address']) ?>, <?= e($booking['customer_city']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <h6 class="fw-bold text-uppercase small text-muted mb-2">Event Schedule & Logistics</h6>
                    <p class="mb-1"><strong>Event Occasion:</strong> <?= e($booking['event_type']) ?></p>
                    <p class="mb-1"><strong>Scheduled Date:</strong> <span class="text-navy fw-bold"><?= format_date($booking['event_date']) ?></span></p>
                    <p class="mb-1"><strong>Time Window:</strong> <?= format_time($booking['start_time']) ?> to <?= format_time($booking['end_time']) ?></p>
                    <p class="mb-0"><strong>Guest Count:</strong> <?= number_format($booking['guest_count']) ?> Attendees</p>
                </div>
            </div>
        </div>

        <!-- Venue & Package Breakdown Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Item Description</th>
                        <th>Details</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong class="text-dark"><?= e($booking['hall_name']) ?></strong>
                            <small class="d-block text-muted">Venue rental & setup</small>
                        </td>
                        <td><?= e($booking['dimensions']) ?> (Capacity: Up to <?= number_format($booking['guest_count']) ?> guests)</td>
                        <td class="text-end fw-semibold"><?= format_currency($booking['hall_charge']) ?></td>
                    </tr>
                    <tr>
                        <td>
                            <strong class="text-dark"><?= e($booking['package_name']) ?></strong>
                            <small class="d-block text-muted">Catering & service package</small>
                        </td>
                        <td><?= number_format($booking['guest_count']) ?> guests included</td>
                        <td class="text-end fw-semibold"><?= format_currency($booking['package_charge']) ?></td>
                    </tr>
                    <?php if ((float)$booking['additional_charge'] > 0): ?>
                        <tr>
                            <td><strong>Additional Services & Equipment</strong></td>
                            <td>Audio-visual, special staging or technical staffing</td>
                            <td class="text-end fw-semibold"><?= format_currency($booking['additional_charge']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="2" class="text-end fw-semibold">Taxes & Service (<?= $settings['tax_percentage'] ?>%)</td>
                        <td class="text-end"><?= format_currency($booking['tax_amount']) ?></td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="2" class="text-end fs-6 fw-bold text-dark">Total Event Cost</td>
                        <td class="text-end fs-6 fw-bold text-navy" style="color:#0b1a30;"><?= format_currency($booking['total_amount']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-end text-muted">Advance Deposit Required (<?= $settings['advance_percentage'] ?>%)</td>
                        <td class="text-end text-muted"><?= format_currency($booking['advance_required']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-end fw-bold text-success">Total Amount Paid to Date</td>
                        <td class="text-end fw-bold text-success"><?= format_currency($booking['advance_paid']) ?></td>
                    </tr>
                    <tr class="table-warning">
                        <td colspan="2" class="text-end fw-bold text-danger">Remaining Balance Due</td>
                        <td class="text-end fw-bold text-danger fs-6"><?= format_currency($booking['balance_due']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Special Requests -->
        <?php if (!empty($booking['special_requests'])): ?>
            <div class="mb-4 p-3 bg-light rounded-3 border">
                <h6 class="fw-bold small text-uppercase text-muted mb-1">Customer Special Requests</h6>
                <p class="small text-dark mb-0"><?= nl2br(e($booking['special_requests'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- Payment History Table -->
        <h5 class="luxury-heading mb-3" style="font-size: 1.25rem;">Payment Transactions & Receipts</h5>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Reference / Transaction ID</th>
                        <th class="text-end">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No payments recorded for this booking yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><strong class="text-navy"><?= e($p['receipt_number']) ?></strong></td>
                                <td><?= format_date($p['payment_date']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e(ucfirst(str_replace('_', ' ', $p['payment_type']))) ?></span></td>
                                <td><?= e(ucfirst(str_replace('_', ' ', $p['payment_method']))) ?></td>
                                <td><code><?= e($p['transaction_reference'] ?? 'Counter Cash') ?></code></td>
                                <td class="text-end text-success fw-bold"><?= format_currency($p['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="border-top pt-3 text-muted small text-center">
            Thank you for choosing <?= e($settings['hotel_name']) ?>. This document serves as your official banquet booking statement.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
