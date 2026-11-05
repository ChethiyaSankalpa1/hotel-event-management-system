<?php
/**
 * Staff Payment Recording Counter
 * Handles Advance Deposits, Balance Settlements, and Receipt Issuance
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/PaymentService.php';

require_role([ROLE_STAFF, ROLE_ADMIN]);

$pdo = Database::getConnection();
$user = current_user();
$userId = (int)$user['id'];

$selectedBookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$error = null;
$success = null;

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $bookingId      = (int)($_POST['booking_id'] ?? 0);
        $amount         = (float)($_POST['amount'] ?? 0);
        $paymentType    = $_POST['payment_type'] ?? 'advance';
        $paymentMethod  = $_POST['payment_method'] ?? 'cash';
        $transactionRef = trim($_POST['transaction_reference'] ?? '');
        $paymentDate    = $_POST['payment_date'] ?? date('Y-m-d');
        $notes          = trim($_POST['notes'] ?? '');

        if (!$bookingId || $amount <= 0) {
            $error = 'Please select a booking and enter a valid positive payment amount.';
        } else {
            $res = PaymentService::recordPayment([
                'booking_id'            => $bookingId,
                'amount'                => $amount,
                'payment_type'          => $paymentType,
                'payment_method'        => $paymentMethod,
                'transaction_reference' => $transactionRef,
                'payment_date'          => $paymentDate,
                'notes'                 => $notes,
                'received_by'           => $userId
            ]);

            if ($res['success']) {
                set_flash('success', $res['message']);
                header('Location: ' . BASE_URL . 'staff/booking_detail.php?id=' . $bookingId);
                exit;
            } else {
                $error = $res['message'];
            }
        }
    }
}

// Fetch list of active bookings that have balance due or need advance
$activeBookingsStmt = $pdo->query("
    SELECT b.id, b.booking_reference, b.event_date, b.total_amount, b.advance_paid, b.balance_due, b.advance_required,
           c.full_name as customer_name, h.hall_name
    FROM `bookings` b
    JOIN `customers` c ON b.customer_id = c.id
    JOIN `function_halls` h ON b.hall_id = h.id
    WHERE b.booking_status NOT IN ('cancelled', 'rejected')
    ORDER BY b.event_date ASC
");
$allActiveBookings = $activeBookingsStmt->fetchAll();

$pageTitle = 'Record Banquet Payment';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sidebar-toggle d-lg-none" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">Payment Counter & Ledger</h4>
                    <small class="text-muted d-none d-sm-inline">Record client advance deposits, partial payments, and full settlements</small>
                </div>
            </div>
            <div>
                <a href="<?= BASE_URL ?>staff/bookings.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-inbox me-1"></i> <span class="d-none d-sm-inline">Bookings</span> Queue
                </a>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold text-navy mb-1" style="color:#0b1a30;">
                                <i class="bi bi-credit-card-2-front text-warning me-2"></i> Record Client Payment
                            </h5>
                            <p class="text-muted small mb-0">System automatically calculates remaining balance and updates payment status</p>
                        </div>

                        <form method="POST" action="record_payment.php" id="paymentRecordForm">
                            <?= csrf_field() ?>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Select Target Booking <span class="text-danger">*</span></label>
                                <select name="booking_id" id="paymentBookingSelect" class="form-select" required onchange="updateBookingFinancials(this)">
                                    <option value="">-- Choose a booking reference --</option>
                                    <?php foreach ($allActiveBookings as $b): ?>
                                        <option value="<?= $b['id'] ?>" 
                                                data-total="<?= $b['total_amount'] ?>"
                                                data-paid="<?= $b['advance_paid'] ?>"
                                                data-balance="<?= $b['balance_due'] ?>"
                                                data-advance-req="<?= $b['advance_required'] ?>"
                                                <?= $selectedBookingId === (int)$b['id'] ? 'selected' : '' ?>>
                                            <?= e($b['booking_reference']) ?> - <?= e($b['customer_name']) ?> (<?= e($b['hall_name']) ?> on <?= format_date($b['event_date']) ?>) [Balance: <?= format_currency($b['balance_due']) ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Financial Breakdown Display Box -->
                            <div class="p-3 bg-light rounded-3 border mb-4" id="financialPreviewBox">
                                <div class="row g-2 text-center">
                                    <div class="col-3">
                                        <small class="text-muted d-block" style="font-size:0.75rem;">TOTAL INVOICE</small>
                                        <strong class="text-dark" id="dispTotal">$0.00</strong>
                                    </div>
                                    <div class="col-3 border-start">
                                        <small class="text-muted d-block" style="font-size:0.75rem;">ADVANCE REQ.</small>
                                        <strong class="text-warning-emphasis" id="dispAdvanceReq">$0.00</strong>
                                    </div>
                                    <div class="col-3 border-start">
                                        <small class="text-muted d-block" style="font-size:0.75rem;">ALREADY PAID</small>
                                        <strong class="text-success" id="dispPaid">$0.00</strong>
                                    </div>
                                    <div class="col-3 border-start">
                                        <small class="text-muted d-block" style="font-size:0.75rem;">BALANCE DUE</small>
                                        <strong class="text-danger" id="dispBalance">$0.00</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Payment Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold">$</span>
                                        <input type="number" step="0.01" min="1" name="amount" id="paymentAmountInput" class="form-control" placeholder="0.00" required>
                                    </div>
                                    <div class="mt-1 d-flex gap-1">
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:0.75rem;" onclick="fillAmount('advance')">Pay Advance</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:0.75rem;" onclick="fillAmount('balance')">Pay Full Balance</button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Payment Type <span class="text-danger">*</span></label>
                                    <select name="payment_type" id="paymentTypeSelect" class="form-select" required>
                                        <option value="advance">Advance Deposit (30%)</option>
                                        <option value="balance_settlement">Balance Settlement</option>
                                        <option value="installment">Installment / Interim</option>
                                        <option value="full_payment">Full Settlement</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash">Counter Cash</option>
                                        <option value="bank_transfer">Bank Wire / Electronic Transfer</option>
                                        <option value="credit_card">Credit Card (POS Terminal)</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="cheque">Bank Cheque / Corporate PO</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Transaction Reference / Cheque #</label>
                                    <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. WIRE-CHASE-99281, AUTH-49102, or Cheque #8819">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Internal Remarks / Cashier Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Received at front desk, customer receipt issued..."></textarea>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-gold btn-lg py-2 fw-bold shadow-sm">
                                    <i class="bi bi-printer me-2"></i> Record Payment & Generate Receipt
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function updateBookingFinancials(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) {
        document.getElementById('dispTotal').textContent = '$0.00';
        document.getElementById('dispAdvanceReq').textContent = '$0.00';
        document.getElementById('dispPaid').textContent = '$0.00';
        document.getElementById('dispBalance').textContent = '$0.00';
        return;
    }

    const total = parseFloat(opt.getAttribute('data-total') || 0);
    const advanceReq = parseFloat(opt.getAttribute('data-advance-req') || 0);
    const paid = parseFloat(opt.getAttribute('data-paid') || 0);
    const balance = parseFloat(opt.getAttribute('data-balance') || 0);

    document.getElementById('dispTotal').textContent = '$' + total.toFixed(2);
    document.getElementById('dispAdvanceReq').textContent = '$' + advanceReq.toFixed(2);
    document.getElementById('dispPaid').textContent = '$' + paid.toFixed(2);
    document.getElementById('dispBalance').textContent = '$' + balance.toFixed(2);
}

function fillAmount(type) {
    const select = document.getElementById('paymentBookingSelect');
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    if (type === 'advance') {
        const adv = parseFloat(opt.getAttribute('data-advance-req') || 0);
        document.getElementById('paymentAmountInput').value = adv.toFixed(2);
        document.getElementById('paymentTypeSelect').value = 'advance';
    } else if (type === 'balance') {
        const bal = parseFloat(opt.getAttribute('data-balance') || 0);
        document.getElementById('paymentAmountInput').value = bal.toFixed(2);
        document.getElementById('paymentTypeSelect').value = 'balance_settlement';
    }
}

// Trigger once on page load if booking is preselected
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('paymentBookingSelect');
    if (sel && sel.value) {
        updateBookingFinancials(sel);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
