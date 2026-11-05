<?php
/**
 * Staff Booking Dossier & Status Transition Manager
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/BookingService.php';
require_once __DIR__ . '/../services/PaymentService.php';

require_role([ROLE_STAFF, ROLE_ADMIN]);

$pdo = Database::getConnection();
$user = current_user();
$userId = (int)$user['id'];
$settings = get_system_settings();

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT b.*, 
           h.hall_name, h.hall_code, h.dimensions, h.capacity_max,
           p.package_name, p.package_code, p.category, p.menu_details, p.inclusions,
           c.full_name as customer_name, c.phone as customer_phone, c.alternate_phone as customer_alt_phone,
           c.address as customer_address, c.city as customer_city, c.id_number as customer_id_num,
           u.email as customer_email,
           rev.email as reviewer_email, stf.full_name as reviewer_name
    FROM `bookings` b
    JOIN `function_halls` h ON b.hall_id = h.id
    JOIN `event_packages` p ON b.package_id = p.id
    JOIN `customers` c ON b.customer_id = c.id
    JOIN `users` u ON c.user_id = u.id
    LEFT JOIN `users` rev ON b.reviewed_by = rev.id
    LEFT JOIN `staff` stf ON stf.user_id = rev.id
    WHERE b.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Booking dossier not found.');
    header('Location: ' . BASE_URL . 'staff/bookings.php');
    exit;
}

// Check real-time conflict status for this booking
$conflictCheck = BookingService::checkHallAvailability(
    (int)$booking['hall_id'],
    $booking['event_date'],
    $booking['start_time'],
    $booking['end_time'],
    $bookingId
);

// Handle Status Transitions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $action = $_POST['action_status'];
        $notes = trim($_POST['review_notes'] ?? '');
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');

        try {
            if ($action === 'approve') {
                BookingService::updateStatus($bookingId, 'approved', $userId, $notes);
                set_flash('success', 'Booking has been approved! The client can now remit their advance deposit.');
            } elseif ($action === 'confirm') {
                BookingService::updateStatus($bookingId, 'confirmed', $userId, $notes);
                set_flash('success', 'Booking marked as Confirmed & Locked onto banquet calendar.');
            } elseif ($action === 'complete') {
                BookingService::updateStatus($bookingId, 'completed', $userId, $notes);
                set_flash('success', 'Booking marked as Completed.');
            } elseif ($action === 'reject') {
                if (empty($rejectionReason)) {
                    throw new Exception('Please provide a reason for declining this reservation.');
                }
                BookingService::updateStatus($bookingId, 'rejected', $userId, $notes, $rejectionReason);
                set_flash('warning', 'Booking has been declined.');
            } elseif ($action === 'cancel') {
                BookingService::updateStatus($bookingId, 'cancelled', $userId, $notes, 'Cancelled by staff');
                set_flash('secondary', 'Booking has been marked as cancelled.');
            }

            header('Location: ' . BASE_URL . 'staff/booking_detail.php?id=' . $bookingId);
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error updating booking: ' . $e->getMessage());
        }
    }
}

// Fetch ledger payments
$payments = PaymentService::getBookingPayments($bookingId);

$pageTitle = 'Review Booking - ' . $booking['booking_reference'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div>
                <a href="<?= BASE_URL ?>staff/bookings.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Back to Queue
                </a>
                <h4 class="mb-0 fw-bold text-dark">Reservation Dossier: <?= e($booking['booking_reference']) ?></h4>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>staff/record_payment.php?booking_id=<?= $booking['id'] ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-cash-stack me-1"></i> Record Payment
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Availability Conflict Status Box -->
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-4">
                            <?php if ($conflictCheck['available']): ?>
                                <i class="bi bi-shield-check text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-exclamation-octagon-fill text-danger"></i>
                            <?php endif; ?>
                        </span>
                        <div>
                            <strong>Hall Conflict Check:</strong>
                            <span class="<?= $conflictCheck['available'] ? 'text-success' : 'text-danger fw-bold' ?>">
                                <?= e($conflictCheck['message']) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <?= render_booking_badge($booking['booking_status']) ?>
                        <?= render_payment_badge($booking['payment_status']) ?>
                    </div>
                </div>
            </div>

            <!-- Workflow Action Box (For Pending / Approved Bookings) -->
            <?php if (in_array($booking['booking_status'], ['pending', 'approved', 'confirmed'])): ?>
                <div class="card shadow-sm border-0 rounded-3 mb-4 p-4 bg-white border-start border-primary border-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-gear-wide-connected text-primary me-2"></i> Banquet Director Actions
                    </h5>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($booking['booking_status'] === 'pending'): ?>
                            <!-- Approve Action -->
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bi bi-check-circle me-1"></i> Approve Booking Request
                            </button>
                            <!-- Reject Action -->
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> Decline Request
                            </button>
                        <?php elseif ($booking['booking_status'] === 'approved'): ?>
                            <!-- Confirm Action -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                <i class="bi bi-shield-check me-1"></i> Lock as Confirmed
                            </button>
                            <!-- Reject/Cancel -->
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                Decline / Cancel
                            </button>
                        <?php elseif ($booking['booking_status'] === 'confirmed'): ?>
                            <!-- Mark Completed -->
                            <form method="POST" action="booking_detail.php?id=<?= $booking['id'] ?>" class="d-inline" onsubmit="return confirm('Mark this banquet as successfully completed?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action_status" value="complete">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-flag-fill me-1"></i> Mark Banquet Completed
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                Cancel Reservation
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dossier Overview -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 rounded-3 p-4 bg-white h-100">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3">Customer Information</h6>
                        <h5 class="fw-bold text-dark mb-1"><?= e($booking['customer_name']) ?></h5>
                        <p class="mb-1"><i class="bi bi-envelope text-muted me-2"></i><?= e($booking['customer_email']) ?></p>
                        <p class="mb-1"><i class="bi bi-telephone text-muted me-2"></i><?= e($booking['customer_phone']) ?></p>
                        <?php if ($booking['customer_alt_phone']): ?>
                            <p class="mb-1 text-muted small"><i class="bi bi-telephone-plus me-2"></i>Alt: <?= e($booking['customer_alt_phone']) ?></p>
                        <?php endif; ?>
                        <?php if ($booking['customer_address']): ?>
                            <p class="mb-1 text-muted small"><i class="bi bi-geo-alt me-2"></i><?= e($booking['customer_address']) ?>, <?= e($booking['customer_city']) ?></p>
                        <?php endif; ?>
                        <?php if ($booking['customer_id_num']): ?>
                            <p class="mb-0 text-muted small"><i class="bi bi-card-heading me-2"></i>ID / Passport: <?= e($booking['customer_id_num']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm border-0 rounded-3 p-4 bg-white h-100">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3">Event Parameters & Venue</h6>
                        <p class="mb-2"><strong>Venue:</strong> <span class="fw-bold text-navy"><?= e($booking['hall_name']) ?></span> (<?= e($booking['hall_code']) ?>)</p>
                        <p class="mb-2"><strong>Occasion:</strong> <?= e($booking['event_type']) ?></p>
                        <p class="mb-2"><strong>Scheduled Date:</strong> <span class="badge bg-light text-dark border fs-6"><?= format_date($booking['event_date']) ?></span></p>
                        <p class="mb-2"><strong>Time Interval:</strong> <?= format_time($booking['start_time']) ?> to <?= format_time($booking['end_time']) ?></p>
                        <p class="mb-0"><strong>Guest Count:</strong> <?= number_format($booking['guest_count']) ?> / max <?= number_format($booking['capacity_max']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Financial Ledger Statement -->
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-3">Financial Statement & Balances</h5>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Function Hall Rental</strong></td>
                                <td><?= e($booking['hall_name']) ?></td>
                                <td class="text-end fw-semibold"><?= format_currency($booking['hall_charge']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Package Catering</strong></td>
                                <td><?= e($booking['package_name']) ?> (<?= number_format($booking['guest_count']) ?> guests)</td>
                                <td class="text-end fw-semibold"><?= format_currency($booking['package_charge']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end">Taxes (<?= $settings['tax_percentage'] ?>%)</td>
                                <td class="text-end"><?= format_currency($booking['tax_amount']) ?></td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="2" class="text-end fw-bold">Total Invoiced Amount</td>
                                <td class="text-end fw-bold text-navy" style="color:#0b1a30; font-size:1.15rem;"><?= format_currency($booking['total_amount']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end">Required Advance (<?= $settings['advance_percentage'] ?>%)</td>
                                <td class="text-end"><?= format_currency($booking['advance_required']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end fw-bold text-success">Advance / Total Received</td>
                                <td class="text-end fw-bold text-success"><?= format_currency($booking['advance_paid']) ?></td>
                            </tr>
                            <tr class="table-warning">
                                <td colspan="2" class="text-end fw-bold text-danger">Outstanding Balance Due</td>
                                <td class="text-end fw-bold text-danger fs-6"><?= format_currency($booking['balance_due']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments Ledger for this Booking -->
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Payment Ledger</h5>
                    <a href="<?= BASE_URL ?>staff/record_payment.php?booking_id=<?= $booking['id'] ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Add Payment
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Recorded By</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">No transactions logged yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td><strong><?= e($p['receipt_number']) ?></strong></td>
                                        <td><?= format_date($p['payment_date']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e(ucfirst(str_replace('_', ' ', $p['payment_type']))) ?></span></td>
                                        <td><?= e(ucfirst(str_replace('_', ' ', $p['payment_method']))) ?></td>
                                        <td><code><?= e($p['transaction_reference'] ?? 'Counter') ?></code></td>
                                        <td><?= e($p['staff_name'] ?? $p['staff_email'] ?? 'Staff') ?></td>
                                        <td class="text-end text-success fw-bold"><?= format_currency($p['amount']) ?></td>
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="booking_detail.php?id=<?= $booking['id'] ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action_status" value="approve">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle me-1"></i> Approve Booking Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this reservation for <strong><?= e($booking['customer_name']) ?></strong> on <strong><?= format_date($booking['event_date']) ?></strong>?</p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Internal Coordinator Notes (Optional)</label>
                    <textarea name="review_notes" class="form-control" rows="3" placeholder="Venue assigned, kitchen notified..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success btn-sm">Confirm Approval</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="booking_detail.php?id=<?= $booking['id'] ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action_status" value="reject">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle me-1"></i> Decline Booking Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger small">The customer will see this rejection reason on their dashboard.</p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason for Declining <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Hall unavailable during that window / Maintenance scheduled / Capacity limit exceeded..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm">Decline Reservation</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="booking_detail.php?id=<?= $booking['id'] ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action_status" value="confirm">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-shield-check me-1"></i> Lock as Confirmed</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This locks the venue on the master calendar. Any overlapping requests will be rejected by the conflict engine.</p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notes</label>
                    <textarea name="review_notes" class="form-control" rows="2" placeholder="Deposit received or approved under corporate billing..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="booking_detail.php?id=<?= $booking['id'] ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action_status" value="cancel">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="bi bi-slash-circle me-1"></i> Cancel Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Cancel this confirmed booking? The reserved time slot will become available again.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-danger btn-sm">Yes, Cancel Booking</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
