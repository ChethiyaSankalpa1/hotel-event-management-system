<?php
/**
 * Administrator Staff Accounts Management
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$error = null;

// Handle Add Staff Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $name        = trim($_POST['full_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $empCode     = trim($_POST['employee_code'] ?? '');
        $designation = trim($_POST['designation'] ?? 'Banquet Coordinator');
        $department  = trim($_POST['department'] ?? 'Banquet Operations');
        $phone       = trim($_POST['phone'] ?? '');
        $password    = $_POST['password'] ?? 'staff123';

        if (empty($name) || empty($email) || empty($empCode) || empty($phone)) {
            $error = 'Name, email, employee code, and phone are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Check email uniqueness
                $chk = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ?");
                $chk->execute([$email]);
                if ($chk->fetch()) {
                    $error = 'An account with that email address already exists.';
                } else {
                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $uStmt = $pdo->prepare("INSERT INTO `users` (`email`, `password_hash`, `role`, `status`) VALUES (:e, :h, 'staff', 'active')");
                    $uStmt->execute([':e' => $email, ':h' => $hash]);
                    $userId = (int)$pdo->lastInsertId();

                    $sStmt = $pdo->prepare("
                        INSERT INTO `staff` (`user_id`, `employee_code`, `full_name`, `designation`, `phone`, `department`)
                        VALUES (:uid, :code, :name, :desig, :phone, :dept)
                    ");
                    $sStmt->execute([
                        ':uid'   => $userId,
                        ':code'  => $empCode,
                        ':name'  => $name,
                        ':desig' => $designation,
                        ':phone' => $phone,
                        ':dept'  => $department
                    ]);

                    $pdo->commit();

                    set_flash('success', "Staff member \"$name\" added successfully.");
                    header('Location: ' . BASE_URL . 'admin/staff.php');
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['uid'])) {
    $targetUid = (int)$_GET['uid'];
    $currentStatus = $_GET['toggle_status'];
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

    $up = $pdo->prepare("UPDATE `users` SET `status` = :st WHERE `id` = :uid AND `role` = 'staff'");
    $up->execute([':st' => $newStatus, ':uid' => $targetUid]);
    set_flash('info', "Staff account status updated to $newStatus.");
    header('Location: ' . BASE_URL . 'admin/staff.php');
    exit;
}

// Fetch staff list
$staffList = $pdo->query("
    SELECT s.*, u.email, u.status as account_status, u.last_login
    FROM `staff` s
    JOIN `users` u ON s.user_id = u.id
    ORDER BY s.id ASC
")->fetchAll();

$pageTitle = 'Manage Staff Accounts';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div>
                <h4 class="mb-0 fw-bold text-dark">Staff & Coordinator Accounts</h4>
                <small class="text-muted">Manage banquet managers, coordinators, and desk staff login privileges</small>
            </div>
            <div>
                <button type="button" class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                    <i class="bi bi-person-plus me-1"></i> Add Staff Account
                </button>
            </div>
        </header>

        <div class="content-body">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="card-table">
                <div class="card-table-header">
                    <h5 class="mb-0 fw-bold text-dark">Banquet Team Members (<?= count($staffList) ?>)</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Emp Code</th>
                                <th>Full Name</th>
                                <th>Designation & Dept</th>
                                <th>Email & Phone</th>
                                <th>Last Login</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffList as $st): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?= e($st['employee_code']) ?></span></td>
                                    <td><strong class="text-dark"><?= e($st['full_name']) ?></strong></td>
                                    <td>
                                        <div><?= e($st['designation']) ?></div>
                                        <small class="text-muted"><?= e($st['department']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= e($st['email']) ?></div>
                                        <small class="text-muted"><?= e($st['phone']) ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $st['last_login'] ? format_date($st['last_login'], 'M j, g:i A') : 'Never' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $st['account_status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= e(ucfirst($st['account_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="staff.php?toggle_status=<?= $st['account_status'] ?>&uid=<?= $st['user_id'] ?>" 
                                           class="btn btn-outline-<?= $st['account_status'] === 'active' ? 'warning' : 'success' ?> btn-sm py-1 px-2">
                                            <?= $st['account_status'] === 'active' ? 'Deactivate' : 'Activate' ?>
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

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="staff.php" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-navy text-white" style="background:#0b1a30; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i> Add Banquet Staff Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Julian Reynolds" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Employee Code <span class="text-danger">*</span></label>
                        <input type="text" name="employee_code" class="form-control" placeholder="EMP-1003" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" class="form-control" value="Banquet Coordinator" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Department</label>
                        <input type="text" name="department" class="form-control" value="Banquet Operations">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="staff@grandhorizon.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 789-2103" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Initial Password</label>
                        <input type="text" name="password" class="form-control" value="staff123" required>
                        <small class="text-muted">Default is <code>staff123</code>. The staff member can change it later.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_staff" value="1" class="btn btn-gold btn-sm">Create Account</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
