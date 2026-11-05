<?php
/**
 * Customer Profile Management
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_CUSTOMER);

$pdo = Database::getConnection();
$user = current_user();
$userId = (int)$user['id'];
$customerId = (int)($user['entity_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT c.*, u.email 
    FROM `customers` c 
    JOIN `users` u ON c.user_id = u.id 
    WHERE c.id = :cid LIMIT 1
");
$stmt->execute([':cid' => $customerId]);
$customer = $stmt->fetch();

$error = null;
$success = null;

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $altPhone = trim($_POST['alternate_phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $idNum    = trim($_POST['id_number'] ?? '');

        if (empty($fullName) || empty($phone)) {
            $error = 'Full Name and Phone Number are required.';
        } else {
            $upStmt = $pdo->prepare("
                UPDATE `customers` SET
                    `full_name` = :name,
                    `phone` = :phone,
                    `alternate_phone` = :alt,
                    `address` = :addr,
                    `city` = :city,
                    `id_number` = :idnum
                WHERE `id` = :cid
            ");
            $upStmt->execute([
                ':name'  => $fullName,
                ':phone' => $phone,
                ':alt'   => $altPhone,
                ':addr'  => $address,
                ':city'  => $city,
                ':idnum' => $idNum,
                ':cid'   => $customerId
            ]);

            $_SESSION['user_name'] = $fullName;
            $customer['full_name'] = $fullName;
            $customer['phone'] = $phone;
            $customer['alternate_phone'] = $altPhone;
            $customer['address'] = $address;
            $customer['city'] = $city;
            $customer['id_number'] = $idNum;

            set_flash('success', 'Your profile details have been updated successfully.');
            header('Location: ' . BASE_URL . 'customer/profile.php');
            exit;
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $uStmt = $pdo->prepare("SELECT password_hash FROM `users` WHERE `id` = :id");
        $uStmt->execute([':id' => $userId]);
        $hash = $uStmt->fetch()['password_hash'];

        if (!verify_user_password($currentPass, $hash)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New passwords do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $pUp = $pdo->prepare("UPDATE `users` SET `password_hash` = :p WHERE `id` = :id");
            $pUp->execute([':p' => $newHash, ':id' => $userId]);

            set_flash('success', 'Your password has been changed successfully.');
            header('Location: ' . BASE_URL . 'customer/profile.php');
            exit;
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="customer-hero">
    <div class="container">
        <span class="badge-portal">Customer Portal</span>
        <h2 class="luxury-heading mb-0 mt-1">Profile & Account Settings</h2>
    </div>
</div>

<div class="container py-4">
    <?php require __DIR__ . '/../includes/alerts.php'; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Personal Profile Form -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h5 class="fw-bold text-navy mb-3 border-bottom pb-2" style="color:#0b1a30;">
                    <i class="bi bi-person-lines-fill text-warning me-2"></i> Contact & Identity Details
                </h5>

                <form method="POST" action="profile.php">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Email Address (Account ID)</label>
                            <input type="text" class="form-control bg-light" value="<?= e($customer['email']) ?>" readonly disabled>
                            <small class="text-muted">Email cannot be altered as it is tied to reservation receipts.</small>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Full Legal Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($customer['full_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primary Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="<?= e($customer['phone']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Alternate Phone</label>
                            <input type="text" name="alternate_phone" class="form-control" value="<?= e($customer['alternate_phone'] ?? '') ?>">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Street Address</label>
                            <input type="text" name="address" class="form-control" value="<?= e($customer['address'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= e($customer['city'] ?? '') ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">National ID / Passport Number</label>
                            <input type="text" name="id_number" class="form-control" value="<?= e($customer['id_number'] ?? '') ?>" placeholder="e.g. ID-492019">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="update_profile" value="1" class="btn btn-navy">
                            <i class="bi bi-check2-circle me-1"></i> Save Profile Details
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security / Password Change -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h5 class="fw-bold text-navy mb-3 border-bottom pb-2" style="color:#0b1a30;">
                    <i class="bi bi-shield-lock text-warning me-2"></i> Change Password
                </h5>

                <form method="POST" action="profile.php">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" name="change_password" value="1" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-key me-1"></i> Update Security Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
