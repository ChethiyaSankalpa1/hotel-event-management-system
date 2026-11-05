<?php
/**
 * Staff Customer Directory & Contact Roster
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_STAFF, ROLE_ADMIN]);

$pdo = Database::getConnection();

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, u.email, u.status as account_status,
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

$sql .= " GROUP BY c.id ORDER BY c.full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customer Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div>
                <h4 class="mb-0 fw-bold text-dark">Customer Directory</h4>
                <small class="text-muted">Client contacts, registered accounts, and reservation history</small>
            </div>
            <div>
                <span class="badge bg-light text-dark border p-2">
                    Total Clients: <?= count($customers) ?>
                </span>
            </div>
        </header>

        <div class="content-body">
            <!-- Search Bar -->
            <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
                <form method="GET" action="customers.php" class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   value="<?= e($search) ?>" placeholder="Search client by name, email, phone number, city...">
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

            <!-- Customer Directory Table -->
            <div class="card-table">
                <div class="card-table-header">
                    <h5 class="mb-0 fw-bold text-dark">Registered Banquet Clients</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Email Address</th>
                                <th>Phone Number</th>
                                <th>Location</th>
                                <th>Total Bookings</th>
                                <th>Total Revenue</th>
                                <th>Status</th>
                                <th class="text-end">Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No customers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark"><?= e($c['full_name']) ?></strong>
                                            <?php if ($c['id_number']): ?>
                                                <small class="d-block text-muted">ID: <?= e($c['id_number']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><a href="mailto:<?= e($c['email']) ?>" class="text-decoration-none"><?= e($c['email']) ?></a></td>
                                        <td><a href="tel:<?= e($c['phone']) ?>" class="text-decoration-none text-dark"><?= e($c['phone']) ?></a></td>
                                        <td><?= e($c['city'] ?? 'Metropolis') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= (int)$c['total_bookings'] ?> events</span></td>
                                        <td><strong><?= format_currency($c['total_spent']) ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?= $c['account_status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= e(ucfirst($c['account_status'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="mailto:<?= e($c['email']) ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Send Email">
                                                <i class="bi bi-envelope"></i>
                                            </a>
                                            <a href="tel:<?= e($c['phone']) ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Call">
                                                <i class="bi bi-telephone"></i>
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
