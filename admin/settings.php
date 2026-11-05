<?php
/**
 * Administrator System Settings & Hotel Profile
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$error = null;

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $name        = trim($_POST['hotel_name'] ?? '');
        $tagline     = trim($_POST['hotel_tagline'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $cityZip     = trim($_POST['city_state_zip'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $website     = trim($_POST['website'] ?? '');
        $currSym     = trim($_POST['currency_symbol'] ?? '$');
        $taxPct      = (float)($_POST['tax_percentage'] ?? 10.00);
        $advPct      = (float)($_POST['advance_percentage'] ?? 30.00);
        $cancelPol   = trim($_POST['cancellation_policy'] ?? '');

        if (empty($name) || empty($phone) || empty($email)) {
            $error = 'Hotel name, contact phone, and email are required.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE `system_settings` SET
                        `hotel_name`         = :name,
                        `hotel_tagline`      = :tagline,
                        `address`            = :address,
                        `city_state_zip`     = :city_zip,
                        `phone`              = :phone,
                        `email`              = :email,
                        `website`            = :website,
                        `currency_symbol`    = :curr,
                        `tax_percentage`     = :tax,
                        `advance_percentage` = :adv,
                        `cancellation_policy`= :cancel
                    WHERE `id` = 1
                ");
                $stmt->execute([
                    ':name'     => $name,
                    ':tagline'  => $tagline,
                    ':address'  => $address,
                    ':city_zip' => $cityZip,
                    ':phone'    => $phone,
                    ':email'    => $email,
                    ':website'  => $website,
                    ':curr'     => $currSym,
                    ':tax'      => $taxPct,
                    ':adv'      => $advPct,
                    ':cancel'   => $cancelPol
                ]);

                set_flash('success', 'Hotel system parameters and pricing policies have been updated successfully.');
                header('Location: ' . BASE_URL . 'admin/settings.php');
                exit;
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$settings = get_system_settings();

$pageTitle = 'System Settings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div>
                <h4 class="mb-0 fw-bold text-dark">System Configuration & Hotel Profile</h4>
                <small class="text-muted">Master hotel credentials, tax percentages, deposit rules, and policies</small>
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

            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white" style="max-width: 900px;">
                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>

                    <h5 class="fw-bold text-navy mb-3 border-bottom pb-2" style="color:#0b1a30;">
                        <i class="bi bi-buildings text-warning me-2"></i> Hotel Brand & Profile
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Hotel / Banquet Property Name <span class="text-danger">*</span></label>
                            <input type="text" name="hotel_name" class="form-control" value="<?= e($settings['hotel_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Brand Tagline</label>
                            <input type="text" name="hotel_tagline" class="form-control" value="<?= e($settings['hotel_tagline'] ?? '') ?>">
                        </div>

                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Street Address</label>
                            <input type="text" name="address" class="form-control" value="<?= e($settings['address']) ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">City, State & Postal Code</label>
                            <input type="text" name="city_state_zip" class="form-control" value="<?= e($settings['city_state_zip'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Primary Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="<?= e($settings['phone']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Inquiry Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= e($settings['email']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Official Website</label>
                            <input type="text" name="website" class="form-control" value="<?= e($settings['website'] ?? '') ?>">
                        </div>
                    </div>

                    <h5 class="fw-bold text-navy mb-3 border-bottom pb-2" style="color:#0b1a30;">
                        <i class="bi bi-percent text-warning me-2"></i> Financial & Deposit Parameters
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Currency Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control text-center" value="<?= e($settings['currency_symbol']) ?>" style="max-width:100px;" required>
                            <small class="text-muted">e.g. $, €, £, Rs</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tax & Service Percentage (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="50" name="tax_percentage" class="form-control" value="<?= e((string)$settings['tax_percentage']) ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Automatically added to invoice subtotal</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Advance Deposit Required (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="1" max="100" name="advance_percentage" class="form-control" value="<?= e((string)$settings['advance_percentage']) ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Minimum deposit to confirm booking</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Cancellation Policy Notice</label>
                            <textarea name="cancellation_policy" class="form-control" rows="3"><?= e($settings['cancellation_policy'] ?? '') ?></textarea>
                            <small class="text-muted">Printed on invoice receipts and customer booking dossiers</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" name="save_settings" value="1" class="btn btn-navy px-4 py-2">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
