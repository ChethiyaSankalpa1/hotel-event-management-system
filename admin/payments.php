<?php
/**
 * Administrator Payment Ledger & Receipts Directory
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();

$method = trim($_GET['method'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT p.*, b.booking_reference, b.event_date,
           c.full_name as customer_name,
           u.email as cashier_email, s.full_name as cashier_name
    FROM `payments` p
    JOIN `bookings` b ON p.booking_id = b.id
    JOIN `customers` c ON b.customer_id = c.id
    LEFT JOIN `users` u ON p.received_by = u.id
    LEFT JOIN `staff` s ON s.user_id = u.id
    WHERE 1=1
";

$params = [];
if (!empty($method)) {
    $sql .= " AND p.payment_method = :m";
    $params[':m'] = $method;
}

if (!empty($search)) {
    $sql .= " AND (p.receipt_number LIKE :s OR b.booking_reference LIKE :s OR c.full_name LIKE :s OR p.transaction_reference LIKE :s)";
    $params[':s'] = "%$search%";
}

$sql .= " ORDER BY p.payment_date DESC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Totals calculation
$totalCollected = array_sum(array_column($payments, 'amount'));

$pageTitle = 'Master Payment Ledger';
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
                    <h4 class="mb-0 fw-bold text-dark">Master Payment Ledger</h4>
                    <small class="text-muted d-none d-sm-inline">Financial audit receipts, transaction identifiers, and deposit ledger</small>
                </div>
            </div>
            <div>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-3 py-2">
                    <span class="d-none d-sm-inline">Filtered Total: </span><?= format_currency($totalCollected) ?>
                </span>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Filter Bar -->
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
                <form method="GET" action="payments.php" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   value="<?= e($search) ?>" placeholder="Receipt #, booking ref, client name, bank ref...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="method" class="form-select form-select-sm">
                            <option value="">All Payment Methods</option>
                            <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Counter Cash</option>
                            <option value="bank_transfer" <?= $method === 'bank_transfer' ? 'selected' : '' ?>>Bank Wire Transfer</option>
                            <option value="credit_card" <?= $method === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                            <option value="debit_card" <?= $method === 'debit_card' ? 'selected' : '' ?>>Debit Card</option>
                            <option value="cheque" <?= $method === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-navy btn-sm flex-grow-1">Filter</button>
                        <?php if ($search || $method): ?>
                            <a href="payments.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Ledger Table -->
            <div class="card-table">
                <div class="card-table-header">
                    <h5 class="mb-0 fw-bold text-dark">Payment Transactions (<?= count($payments) ?>)</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Booking Ref</th>
                                <th>Client Name</th>
                                <th>Payment Type</th>
                                <th>Method</th>
                                <th>Reference / Notes</th>
                                <th>Cashier</th>
                                <th>Date</th>
                                <th class="text-end">Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">No payment records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td><strong class="text-navy"><?= e($p['receipt_number']) ?></strong></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>staff/booking_detail.php?id=<?= $p['booking_id'] ?>" class="text-decoration-none fw-semibold">
                                                <?= e($p['booking_reference']) ?>
                                            </a>
                                        </td>
                                        <td><?= e($p['customer_name']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= e(ucfirst(str_replace('_', ' ', $p['payment_type']))) ?>
                                            </span>
                                        </td>
                                        <td><?= e(ucfirst(str_replace('_', ' ', $p['payment_method']))) ?></td>
                                        <td>
                                            <code><?= e($p['transaction_reference'] ?? 'Counter') ?></code>
                                            <?php if ($p['notes']): ?>
                                                <small class="d-block text-muted"><?= e($p['notes']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?= e($p['cashier_name'] ?? $p['cashier_email'] ?? 'Staff') ?></small></td>
                                        <td><small><?= format_date($p['payment_date']) ?></small></td>
                                        <td class="text-end fw-bold text-success fs-6"><?= format_currency($p['amount']) ?></td>
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
