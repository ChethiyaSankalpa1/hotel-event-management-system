<?php
/**
 * Administrator Event Packages Management (CRUD)
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$error = null;

// Handle Add Package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_package'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $name        = trim($_POST['package_name'] ?? '');
        $code        = trim($_POST['package_code'] ?? '');
        $category    = $_POST['category'] ?? 'special';
        $perPerson   = (float)($_POST['price_per_person'] ?? 0);
        $basePrice   = (float)($_POST['base_package_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $menu        = trim($_POST['menu_details'] ?? '');
        $inclusions  = trim($_POST['inclusions'] ?? '');
        $status      = $_POST['status'] ?? 'active';

        if (empty($name) || empty($code) || $perPerson <= 0) {
            $error = 'Package name, unique code, and per-person price are required.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `event_packages` (
                        `package_name`, `package_code`, `category`,
                        `price_per_person`, `base_package_price`,
                        `description`, `menu_details`, `inclusions`, `status`
                    ) VALUES (
                        :name, :code, :cat,
                        :per_person, :base_p,
                        :descr, :menu, :inc, :status
                    )
                ");
                $stmt->execute([
                    ':name'       => $name,
                    ':code'       => $code,
                    ':cat'        => $category,
                    ':per_person' => $perPerson,
                    ':base_p'     => $basePrice,
                    ':descr'      => $description,
                    ':menu'       => $menu,
                    ':inc'        => $inclusions,
                    ':status'     => $status
                ]);

                set_flash('success', "Event package \"$name\" created successfully.");
                header('Location: ' . BASE_URL . 'admin/packages.php');
                exit;
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle Edit Package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_package'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $id          = (int)($_POST['package_id'] ?? 0);
        $name        = trim($_POST['package_name'] ?? '');
        $code        = trim($_POST['package_code'] ?? '');
        $category    = $_POST['category'] ?? 'special';
        $perPerson   = (float)($_POST['price_per_person'] ?? 0);
        $basePrice   = (float)($_POST['base_package_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $menu        = trim($_POST['menu_details'] ?? '');
        $inclusions  = trim($_POST['inclusions'] ?? '');
        $status      = $_POST['status'] ?? 'active';

        try {
            $stmt = $pdo->prepare("
                UPDATE `event_packages` SET
                    `package_name` = :name,
                    `package_code` = :code,
                    `category` = :cat,
                    `price_per_person` = :per_person,
                    `base_package_price` = :base_p,
                    `description` = :descr,
                    `menu_details` = :menu,
                    `inclusions` = :inc,
                    `status` = :status
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':name'       => $name,
                ':code'       => $code,
                ':cat'        => $category,
                ':per_person' => $perPerson,
                ':base_p'     => $basePrice,
                ':descr'      => $description,
                ':menu'       => $menu,
                ':inc'        => $inclusions,
                ':status'     => $status,
                ':id'         => $id
            ]);

            set_flash('success', "Event package \"$name\" updated successfully.");
            header('Location: ' . BASE_URL . 'admin/packages.php');
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all packages
$packages = $pdo->query("SELECT * FROM `event_packages` ORDER BY `price_per_person` DESC")->fetchAll();

$pageTitle = 'Manage Event Packages';
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
                    <h4 class="mb-0 fw-bold text-dark">Event Packages & Catering Menus</h4>
                    <small class="text-muted d-none d-sm-inline">Manage preset banquet tiers, dining courses, audio-visual inclusions, and per-guest fees</small>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                    <i class="bi bi-plus-circle me-1"></i> <span class="d-none d-sm-inline">Add Event</span> Package
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
                    <h5 class="mb-0 fw-bold text-dark">Configured Banquet Packages (<?= count($packages) ?>)</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Package Name</th>
                                <th>Category</th>
                                <th>Per Person</th>
                                <th>Base Fee</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $p): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?= e($p['package_code']) ?></span></td>
                                    <td>
                                        <strong class="text-dark"><?= e($p['package_name']) ?></strong>
                                        <small class="d-block text-muted"><?= e(mb_strimwidth($p['description'], 0, 80, '...')) ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary text-uppercase"><?= e($p['category']) ?></span></td>
                                    <td><strong><?= format_currency($p['price_per_person']) ?></strong></td>
                                    <td><?= format_currency($p['base_package_price']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= e(ucfirst($p['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-navy btn-sm py-1 px-2"
                                                onclick='openEditPackageModal(<?= json_encode($p) ?>)'>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
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

<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="packages.php" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-navy text-white" style="background:#0b1a30; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Add Event Package</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Package Name <span class="text-danger">*</span></label>
                        <input type="text" name="package_name" class="form-control" placeholder="e.g. Imperial Diamond Wedding Gala" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Package Code <span class="text-danger">*</span></label>
                        <input type="text" name="package_code" class="form-control" placeholder="e.g. PKG-WED-09" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="wedding">Wedding</option>
                            <option value="corporate">Corporate</option>
                            <option value="birthday">Birthday</option>
                            <option value="anniversary">Anniversary</option>
                            <option value="special">Special / Cocktail</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Price Per Person ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price_per_person" class="form-control" placeholder="75.00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Base Setup Price ($)</label>
                        <input type="number" step="0.01" name="base_package_price" class="form-control" placeholder="1000.00" value="0.00">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Overview Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Theme, atmosphere, dining style..." required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Menu Details & Courses</label>
                        <textarea name="menu_details" class="form-control" rows="3" placeholder="Appetizers, Mains, Dessert station, Beverages..."></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Inclusions (Comma separated)</label>
                        <input type="text" name="inclusions" class="form-control" placeholder="Champagne Toast, Custom Cake, Floral Centerpieces, Stage Audio Rig">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_package" value="1" class="btn btn-gold btn-sm">Save Package</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="packages.php" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="package_id" id="edit_pkg_id">
            <div class="modal-header bg-navy text-white" style="background:#0b1a30; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Event Package</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Package Name</label>
                        <input type="text" name="package_name" id="edit_pkg_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Package Code</label>
                        <input type="text" name="package_code" id="edit_pkg_code" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category" id="edit_pkg_category" class="form-select" required>
                            <option value="wedding">Wedding</option>
                            <option value="corporate">Corporate</option>
                            <option value="birthday">Birthday</option>
                            <option value="anniversary">Anniversary</option>
                            <option value="special">Special / Cocktail</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Price Per Person ($)</label>
                        <input type="number" step="0.01" name="price_per_person" id="edit_pkg_per_person" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Base Setup Price ($)</label>
                        <input type="number" step="0.01" name="base_package_price" id="edit_pkg_base_price" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" id="edit_pkg_description" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Menu Details</label>
                        <textarea name="menu_details" id="edit_pkg_menu" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Inclusions</label>
                        <input type="text" name="inclusions" id="edit_pkg_inclusions" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" id="edit_pkg_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_package" value="1" class="btn btn-navy btn-sm">Update Package</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditPackageModal(p) {
    document.getElementById('edit_pkg_id').value = p.id;
    document.getElementById('edit_pkg_name').value = p.package_name;
    document.getElementById('edit_pkg_code').value = p.package_code;
    document.getElementById('edit_pkg_category').value = p.category;
    document.getElementById('edit_pkg_per_person').value = p.price_per_person;
    document.getElementById('edit_pkg_base_price').value = p.base_package_price;
    document.getElementById('edit_pkg_description').value = p.description;
    document.getElementById('edit_pkg_menu').value = p.menu_details || '';
    document.getElementById('edit_pkg_inclusions').value = p.inclusions || '';
    document.getElementById('edit_pkg_status').value = p.status;

    const bsModal = new bootstrap.Modal(document.getElementById('editPackageModal'));
    bsModal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
