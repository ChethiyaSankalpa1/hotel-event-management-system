<?php
/**
 * Customer Self-Registration Portal
 * Hotel Event Management System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'customer/dashboard.php');
    exit;
}

$error = null;
$fullName = '';
$email = '';
$phone = '';
$address = '';
$city = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
            $error = 'Please fill in all required fields (Name, Email, Phone, and Password).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match. Please re-enter carefully.';
        } else {
            try {
                $pdo = Database::getConnection();

                // Check for existing user with this email
                $chkStmt = $pdo->prepare("SELECT id FROM `users` WHERE `email` = :email LIMIT 1");
                $chkStmt->execute([':email' => $email]);
                if ($chkStmt->fetch()) {
                    $error = 'An account with that email address already exists. Please sign in instead.';
                } else {
                    $pdo->beginTransaction();

                    // 1. Insert into users table
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $uStmt = $pdo->prepare("INSERT INTO `users` (`email`, `password_hash`, `role`, `status`) VALUES (:email, :hash, 'customer', 'active')");
                    $uStmt->execute([
                        ':email' => $email,
                        ':hash'  => $hash
                    ]);
                    $userId = (int)$pdo->lastInsertId();

                    // 2. Insert into customers table
                    $cStmt = $pdo->prepare("INSERT INTO `customers` (`user_id`, `full_name`, `phone`, `address`, `city`) VALUES (:uid, :name, :phone, :address, :city)");
                    $cStmt->execute([
                        ':uid'     => $userId,
                        ':name'    => $fullName,
                        ':phone'   => $phone,
                        ':address' => $address,
                        ':city'    => $city
                    ]);
                    $customerId = (int)$pdo->lastInsertId();

                    $pdo->commit();

                    // Auto-login user
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = ROLE_CUSTOMER;
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['entity_id'] = $customerId;

                    set_flash('success', 'Registration successful! Welcome to ' . APP_NAME . '. You can now submit your event booking request.');
                    header('Location: ' . BASE_URL . 'customer/dashboard.php');
                    exit;
                }
            } catch (Exception $e) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Customer Account';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5" style="background: linear-gradient(135deg, #071224 0%, #0b1a30 50%, #162a4d 100%); min-height: 85vh;">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-navy text-white text-center py-4 border-bottom border-2" style="background:#0b1a30; border-color: var(--hotel-gold) !important;">
                        <span class="hotel-crest mx-auto mb-2" style="width:46px; height:46px; font-size:1.3rem;">
                            <i class="bi bi-person-check"></i>
                        </span>
                        <h4 class="luxury-heading text-white mb-1">Create Customer Account</h4>
                        <p class="text-white-50 small mb-0">Join <?= e($settings['hotel_name']) ?> to reserve banquet halls and manage events</p>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <?php require __DIR__ . '/includes/alerts.php'; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                                <div class="small"><?= e($error) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="register.php">
                            <?= csrf_field() ?>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold text-dark small">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" value="<?= e($fullName) ?>" placeholder="e.g. Eleanor Montgomery" required autofocus>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-dark small">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="name@domain.com" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-dark small">Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($phone) ?>" placeholder="+1 (555) 000-0000" required>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label fw-semibold text-dark small">Residential / Office Address</label>
                                    <input type="text" name="address" class="form-control" value="<?= e($address) ?>" placeholder="Street address">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-dark small">City</label>
                                    <input type="text" name="city" class="form-control" value="<?= e($city) ?>" placeholder="e.g. Metropolis">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-dark small">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-dark small">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-gold py-2 fw-bold shadow-sm">
                                    <i class="bi bi-check2-circle me-2"></i> Register Account
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top small text-muted">
                            Already have an account? <a href="<?= BASE_URL ?>login.php" class="fw-bold text-decoration-none" style="color:#0b1a30;">Sign in here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
