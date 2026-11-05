<?php
/**
 * 1-Click Database Setup & Diagnostics Installer
 * Hotel Event Management System
 * Supports Local XAMPP and Cloud Environments (Railway, Render, etc.)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$installed = false;
$error = null;
$messages = [];

// Detect cloud environment credentials (e.g. Railway MySQL)
$cloudDbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
$defaultHost = 'localhost';
$defaultPort = '3306';
$defaultUser = 'root';
$defaultPass = '';
$defaultName = 'hotel_event_db';

if ($cloudDbUrl && ($urlParts = parse_url($cloudDbUrl))) {
    $defaultHost = $urlParts['host'] ?? 'localhost';
    $defaultPort = (string)($urlParts['port'] ?? 3306);
    $defaultUser = $urlParts['user'] ?? 'root';
    $defaultPass = $urlParts['pass'] ?? '';
    $defaultName = ltrim($urlParts['path'] ?? 'hotel_event_db', '/');
} elseif (getenv('MYSQLHOST')) {
    $defaultHost = getenv('MYSQLHOST');
    $defaultPort = getenv('MYSQLPORT') ?: '3306';
    $defaultUser = getenv('MYSQLUSER') ?: 'root';
    $defaultPass = getenv('MYSQLPASSWORD') ?: '';
    $defaultName = getenv('MYSQLDATABASE') ?: 'railway';
}

$dbHost = $_POST['db_host'] ?? $defaultHost;
$dbPort = $_POST['db_port'] ?? $defaultPort;
$dbUser = $_POST['db_user'] ?? $defaultUser;
$dbPass = $_POST['db_pass'] ?? $defaultPass;
$dbName = $_POST['db_name'] ?? $defaultName;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    try {
        // 1. Test basic MySQL connection without selecting database
        $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $messages[] = "Successfully connected to MySQL server.";

        // 2. Create database if allowed, or use existing database
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            // Some cloud hosts don't permit CREATE DATABASE; continue using pre-provisioned DB
        }
        $pdo->exec("USE `$dbName`;");
        $messages[] = "Database `$dbName` selected.";

        // 3. Read and execute clean schema
        $cleanSql = file_get_contents(__DIR__ . '/database/infinityfree_import.sql');
        if (!$cleanSql) {
            $cleanSql = file_get_contents(__DIR__ . '/database/schema.sql');
        }
        $pdo->exec($cleanSql);
        $messages[] = "Database schema and sample banquet records created successfully.";

        // 4. Update user passwords using current server's native password_hash() for 100% login guarantee
        $passAdmin    = password_hash('admin123', PASSWORD_DEFAULT);
        $passStaff    = password_hash('staff123', PASSWORD_DEFAULT);
        $passCustomer = password_hash('customer123', PASSWORD_DEFAULT);

        $upStmt = $pdo->prepare("UPDATE `users` SET `password_hash` = :p WHERE `email` = :e");
        $upStmt->execute([':p' => $passAdmin, ':e' => 'admin@grandhorizon.com']);
        $upStmt->execute([':p' => $passStaff, ':e' => 'staff@grandhorizon.com']);
        $upStmt->execute([':p' => $passStaff, ':e' => 'coordinator@grandhorizon.com']);
        $upStmt->execute([':p' => $passCustomer, ':e' => 'customer@gmail.com']);
        $upStmt->execute([':p' => $passCustomer, ':e' => 'sarah.jenkins@outlook.com']);

        $messages[] = "All demo accounts initialized with native bcrypt hashes.";
        $installed = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard - The Grand Horizon Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0b1a30;
            color: #333;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            max-width: 680px;
            width: 100%;
            overflow: hidden;
        }
        .setup-header {
            background: #0d1b2a;
            color: #ffffff;
            padding: 30px;
            border-bottom: 4px solid #c5a880;
            text-align: center;
        }
        .setup-header h2 {
            font-size: 1.6rem;
            margin-bottom: 5px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .setup-header p {
            color: #c5a880;
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        .setup-body {
            padding: 35px;
        }
        .btn-gold {
            background: #c5a880;
            color: #0d1b2a;
            font-weight: 600;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .btn-gold:hover {
            background: #b59567;
            color: #0d1b2a;
        }
        .account-badge {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="setup-header">
        <i class="bi bi-buildings fs-1 mb-2 d-inline-block text-warning"></i>
        <h2>The Grand Horizon Hotel & Banquet</h2>
        <p>1-Click Database Setup & Environment Diagnostics</p>
    </div>

    <div class="setup-body">
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <strong>Setup Failed:</strong> <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($installed): ?>
            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div>
                    <h5 class="mb-1">Installation Completed Successfully!</h5>
                    <p class="mb-0">The database <code><?= htmlspecialchars($dbName) ?></code> and sample records are now ready.</p>
                </div>
            </div>

            <h6 class="text-uppercase fw-bold text-muted small mb-3">Pre-Configured Demo Credentials:</h6>
            
            <div class="account-badge d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-danger me-2">Administrator</span>
                    <strong>admin@grandhorizon.com</strong>
                </div>
                <code>admin123</code>
            </div>

            <div class="account-badge d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-primary me-2">Banquet Staff</span>
                    <strong>staff@grandhorizon.com</strong>
                </div>
                <code>staff123</code>
            </div>

            <div class="account-badge d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-success me-2">Customer</span>
                    <strong>customer@gmail.com</strong>
                </div>
                <code>customer123</code>
            </div>

            <div class="mt-4 text-center">
                <a href="login.php" class="btn btn-gold btn-lg w-100 shadow-sm">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Proceed to Login Portal
                </a>
                <a href="index.php" class="btn btn-link text-decoration-none mt-2 text-muted small d-block">
                    Or visit Public Homepage
                </a>
            </div>

        <?php else: ?>

            <p class="text-muted mb-4">
                This installer will automatically connect to your MySQL database (detected from your environment or local configuration), create the tables, set up foreign keys, and seed realistic hotel data.
            </p>

            <form method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">MySQL Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($dbHost) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Port</label>
                        <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($dbPort) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">MySQL Username</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($dbUser) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">MySQL Password</label>
                        <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($dbPass) ?>" placeholder="(Leave blank if none)">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Target Database Name</label>
                    <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($dbName) ?>" required>
                </div>

                <div class="d-grid">
                    <button type="submit" name="run_install" value="1" class="btn btn-gold btn-lg shadow-sm">
                        <i class="bi bi-play-circle me-2"></i> Initialize Database & Demo Data
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

</body>
</html>
