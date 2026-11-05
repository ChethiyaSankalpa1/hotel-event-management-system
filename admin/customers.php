<?php
/**
 * Administrator Customer Directory & Account Management
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();

// Toggle Customer Status
if (isset($_GET['toggle_status']) && isset($_GET['uid'])) {
    $targetUid = (int)$_GET['uid'];
    $currentStatus = $_GET['toggle_status'];
    $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';

    $up = $pdo->prepare("UPDATE `users` SET `status` = :st WHERE `id` = :uid AND `role` = 'customer'");
    $up->execute([':st' => $newStatus, ':uid' => $targetUid]);
    set_flash('info', "Customer account status updated to $newStatus.");
    header('Location: ' . BASE_URL . 'admin/customers.php');
    exit;
}

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, u.email, u.status as account_status, u.last_login,
           COUNT(b.id) as total_bookings,
           COALESCE(SUM(b.total_amount), 0) as total_spent
    FROM `customers` c
    JOIN `users` u ON c.user_id = u.id
    LEFT JOIN `bookings` b ON b.customer_id = c.id
    WHERE 1=1
";

$params = [];
if (!empty($search)) {
    $sql .= " AND (c.full_name LIKE :search OR u.email LIKE :search OR c.phone LIKE :search OR c.city LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " GROUP BY c.id ORDER BY total_spent DESC, c.full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Manage Customers';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div>
                <h4 class="mb-0 fw-bold text-dark">Customer Directory & Account Management</h4>
                <small class="text-muted">Registered clients, reservation frequency, spending metrics, and account status</small>
            </div>
            <div>
                <span class="badge bg-light text-dark border p-2">
                    Total Registered Clients: <?= count($customers) ?>
                </span>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search Bar -->
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
                <form method="GET" action="customers.php" class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   value="<?= e($search) ?>" placeholder="Search client by name, email, phone number...">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-navy btn-sm flex-grow-1">Search</button>
                        <?php if ($search): ?>
                            <a href="customers.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Customers Table -->
            <div class="card-table">
                <div class="card-table-header">
                    <h5 class="mb-0 fw-bold text-dark">Client Accounts Directory</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Email Address</th>
                                <th>Phone</th>
                                <th>City / Address</th>
                                <th>Bookings</th>
                                <th>Total Revenue</th>
                                <th>Account Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark"><?= e($c['full_name']) ?></strong>
                                        <?php if ($c['id_number']): ?>
                                            <small class="d-block text-muted">ID: <?= e($c['id_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></td>
                                    <td><a href="tel:<?= e($c['phone']) ?>" class="text-dark"><?= e($c['phone']) ?></a></td>
                                    <td><?= e($c['city'] ?? 'Metropolis') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$c['total_bookings'] ?></span></td>
                                    <td><strong class="text-navy" style="color:#0b1a30;"><?= format_currency($c['total_spent']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= $c['account_status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= e(ucfirst($c['account_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="customers.php?toggle_status=<?= $c['account_status'] ?>&uid=<?= $c['user_id'] ?>" 
                                           class="btn btn-outline-<?= $c['account_status'] === 'active' ? 'danger' : 'success' ?> btn-sm py-1 px-2"
                                           onclick="return confirm('Change status for this client?');">
                                            <?= $c['account_status'] === 'active' ? 'Suspend' : 'Reactivate' ?>
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
