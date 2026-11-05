<?php
/**
 * Unified Authentication & Login Portal
 * Hotel Event Management System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = current_user_role();
    if ($role === ROLE_ADMIN) {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif ($role === ROLE_STAFF) {
        header('Location: ' . BASE_URL . 'staff/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'customer/dashboard.php');
    }
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user && verify_user_password($password, $user['password_hash'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated or suspended. Please contact hotel administration.';
                    } else {
                        // Successful login: regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];

                        // Retrieve profile name and entity ID based on role
                        $entityId = null;
                        $displayName = 'User';

                        if ($user['role'] === ROLE_CUSTOMER) {
                            $cStmt = $pdo->prepare("SELECT id, full_name FROM `customers` WHERE `user_id` = ? LIMIT 1");
                            $cStmt->execute([$user['id']]);
                            $c = $cStmt->fetch();
                            if ($c) {
                                $entityId = (int)$c['id'];
                                $displayName = $c['full_name'];
                            }
                        } elseif (in_array($user['role'], [ROLE_STAFF, ROLE_ADMIN])) {
                            $sStmt = $pdo->prepare("SELECT id, full_name, designation FROM `staff` WHERE `user_id` = ? LIMIT 1");
                            $sStmt->execute([$user['id']]);
                            $s = $sStmt->fetch();
                            if ($s) {
                                $entityId = (int)$s['id'];
                                $displayName = $s['full_name'];
                            } else {
                                $displayName = ($user['role'] === ROLE_ADMIN) ? 'Administrator' : 'Staff Member';
                            }
                        }

                        $_SESSION['entity_id'] = $entityId;
                        $_SESSION['user_name'] = $displayName;

                        // Update last_login timestamp
                        $upStmt = $pdo->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = ?");
                        $upStmt->execute([$user['id']]);

                        set_flash('success', 'Welcome back, ' . htmlspecialchars($displayName) . '!');

                        // Check for saved redirect
                        $redirectUrl = $_SESSION['redirect_after_login'] ?? null;
                        unset($_SESSION['redirect_after_login']);

                        if ($redirectUrl && !str_contains($redirectUrl, 'login.php') && !str_contains($redirectUrl, 'register.php')) {
                            header('Location: ' . $redirectUrl);
                            exit;
                        }

                        // Role-based portal redirect
                        if ($user['role'] === ROLE_ADMIN) {
                            header('Location: ' . BASE_URL . 'admin/dashboard.php');
                        } elseif ($user['role'] === ROLE_STAFF) {
                            header('Location: ' . BASE_URL . 'staff/dashboard.php');
                        } else {
                            header('Location: ' . BASE_URL . 'customer/dashboard.php');
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid email address or password. Please verify your credentials.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Sign In to Portal';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5" style="background: linear-gradient(135deg, #0b1a30 0%, #162a4d 100%); min-height: 85vh;">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
                    <div class="card-header bg-navy text-white text-center py-4 border-bottom border-warning border-3" style="background:#0b1a30;">
                        <i class="bi bi-buildings fs-1 text-warning d-inline-block mb-2"></i>
                        <h4 class="luxury-heading text-white mb-1" style="font-size: 1.5rem;">The Grand Horizon</h4>
                        <p class="text-white-50 small mb-0">Event & Banquet Management Portal</p>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <?php require __DIR__ . '/includes/alerts.php'; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                                <div class="small"><?= e($error) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" id="loginForm">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold text-dark small">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control border-start-0" 
                                           value="<?= e($email) ?>" placeholder="e.g. name@example.com" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold text-dark small">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control border-start-0" 
                                           placeholder="Enter your password" required>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-gold py-2 fw-bold shadow-sm">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Account
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-3 pt-3 border-top small text-muted">
                            New customer? <a href="<?= BASE_URL ?>register.php" class="fw-bold text-decoration-none" style="color:#0b1a30;">Create a Customer Account</a>
                        </div>

                        <!-- Academic Quick-Login Helper Card -->
                        <div class="mt-4 pt-3 border-top">
                            <span class="d-block text-uppercase fw-bold text-muted text-center mb-2" style="font-size:0.7rem; letter-spacing:1px;">
                                <i class="bi bi-mortarboard me-1"></i> Academic Demo One-Click Fill
                            </span>
                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" 
                                        onclick="fillCredentials('admin@grandhorizon.com', 'admin123')">
                                    <i class="bi bi-shield-lock me-1"></i> Admin
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" 
                                        onclick="fillCredentials('staff@grandhorizon.com', 'staff123')">
                                    <i class="bi bi-person-workspace me-1"></i> Staff
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" 
                                        onclick="fillCredentials('customer@gmail.com', 'customer123')">
                                    <i class="bi bi-person me-1"></i> Customer
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
