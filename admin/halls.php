<?php
/**
 * Administrator Function Halls Management (CRUD)
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$error = null;

// Handle Add Hall
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hall'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $name        = trim($_POST['hall_name'] ?? '');
        $code        = trim($_POST['hall_code'] ?? '');
        $capMin      = (int)($_POST['capacity_min'] ?? 30);
        $capMax      = (int)($_POST['capacity_max'] ?? 500);
        $priceHour   = (float)($_POST['price_per_hour'] ?? 0);
        $priceHalf   = (float)($_POST['base_price_half_day'] ?? 0);
        $priceFull   = (float)($_POST['base_price_full_day'] ?? 0);
        $dimensions  = trim($_POST['dimensions'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amenities   = trim($_POST['amenities'] ?? '');
        $status      = $_POST['status'] ?? 'active';

        if (empty($name) || empty($code) || $priceHour <= 0) {
            $error = 'Hall name, unique code, and hourly price are required.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `function_halls` (
                        `hall_name`, `hall_code`, `capacity_min`, `capacity_max`,
                        `price_per_hour`, `base_price_half_day`, `base_price_full_day`,
                        `dimensions`, `description`, `amenities`, `status`
                    ) VALUES (
                        :name, :code, :cap_min, :cap_max,
                        :p_hour, :p_half, :p_full,
                        :dim, :descr, :amenities, :status
                    )
                ");
                $stmt->execute([
                    ':name'      => $name,
                    ':code'      => $code,
                    ':cap_min'   => $capMin,
                    ':cap_max'   => $capMax,
                    ':p_hour'    => $priceHour,
                    ':p_half'    => $priceHalf,
                    ':p_full'    => $priceFull,
                    ':dim'       => $dimensions,
                    ':descr'     => $description,
                    ':amenities' => $amenities,
                    ':status'    => $status
                ]);

                set_flash('success', "Function hall \"$name\" added successfully.");
                header('Location: ' . BASE_URL . 'admin/halls.php');
                exit;
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle Edit Hall
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hall'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $id          = (int)($_POST['hall_id'] ?? 0);
        $name        = trim($_POST['hall_name'] ?? '');
        $code        = trim($_POST['hall_code'] ?? '');
        $capMin      = (int)($_POST['capacity_min'] ?? 30);
        $capMax      = (int)($_POST['capacity_max'] ?? 500);
        $priceHour   = (float)($_POST['price_per_hour'] ?? 0);
        $priceHalf   = (float)($_POST['base_price_half_day'] ?? 0);
        $priceFull   = (float)($_POST['base_price_full_day'] ?? 0);
        $dimensions  = trim($_POST['dimensions'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amenities   = trim($_POST['amenities'] ?? '');
        $status      = $_POST['status'] ?? 'active';

        try {
            $stmt = $pdo->prepare("
                UPDATE `function_halls` SET
                    `hall_name` = :name,
                    `hall_code` = :code,
                    `capacity_min` = :cap_min,
                    `capacity_max` = :cap_max,
                    `price_per_hour` = :p_hour,
                    `base_price_half_day` = :p_half,
                    `base_price_full_day` = :p_full,
                    `dimensions` = :dim,
                    `description` = :descr,
                    `amenities` = :amenities,
                    `status` = :status
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':name'      => $name,
                ':code'      => $code,
                ':cap_min'   => $capMin,
                ':cap_max'   => $capMax,
                ':p_hour'    => $priceHour,
                ':p_half'    => $priceHalf,
                ':p_full'    => $priceFull,
                ':dim'       => $dimensions,
                ':descr'     => $description,
                ':amenities' => $amenities,
                ':status'    => $status,
                ':id'        => $id
            ]);

            set_flash('success', "Function hall \"$name\" updated successfully.");
            header('Location: ' . BASE_URL . 'admin/halls.php');
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Delete Hall
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    // Verify no bookings exist for this hall
    $chk = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `hall_id` = ?");
    $chk->execute([$deleteId]);
    $bookingCount = $chk->fetchColumn();

    if ($bookingCount > 0) {
        set_flash('danger', "Cannot delete this hall because it is linked to $bookingCount reservation records. You can set its status to 'inactive' or 'maintenance' instead.");
    } else {
        $delStmt = $pdo->prepare("DELETE FROM `function_halls` WHERE `id` = ?");
        $delStmt->execute([$deleteId]);
        set_flash('success', 'Function hall removed successfully.');
    }
    header('Location: ' . BASE_URL . 'admin/halls.php');
    exit;
}

// Fetch all halls
$halls = $pdo->query("SELECT * FROM `function_halls` ORDER BY `capacity_max` DESC")->fetchAll();

$pageTitle = 'Manage Function Halls';
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
                    <h4 class="mb-0 fw-bold text-dark">Function Halls Directory</h4>
                    <small class="text-muted d-none d-sm-inline">Manage banquet spaces, dimensions, guest capacities, and rental rates</small>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addHallModal">
                    <i class="bi bi-plus-circle me-1"></i> <span class="d-none d-sm-inline">Add New</span> Function Hall
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
                    <h5 class="mb-0 fw-bold text-dark">Configured Banquet Halls (<?= count($halls) ?>)</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Hall Code</th>
                                <th>Name & Dimensions</th>
                                <th>Capacity</th>
                                <th>Hourly Rate</th>
                                <th>Half-Day</th>
                                <th>Full-Day</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($halls as $h): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?= e($h['hall_code']) ?></span></td>
                                    <td>
                                        <strong class="text-dark"><?= e($h['hall_name']) ?></strong>
                                        <small class="d-block text-muted"><?= e($h['dimensions'] ?? 'Standard size') ?></small>
                                    </td>
                                    <td>
                                        <i class="bi bi-people me-1 text-muted"></i>
                                        <?= number_format($h['capacity_min']) ?> - <?= number_format($h['capacity_max']) ?>
                                    </td>
                                    <td><strong><?= format_currency($h['price_per_hour']) ?></strong></td>
                                    <td><?= format_currency($h['base_price_half_day']) ?></td>
                                    <td><?= format_currency($h['base_price_full_day']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $h['status'] === 'active' ? 'success' : ($h['status'] === 'maintenance' ? 'warning' : 'secondary') ?>">
                                            <?= e(ucfirst($h['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-navy btn-sm py-1 px-2" 
                                                onclick='openEditModal(<?= json_encode($h) ?>)'>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="halls.php?delete=<?= $h['id'] ?>" class="btn btn-outline-danger btn-sm py-1 px-2"
                                           onclick="return confirm('Delete this function hall? This action cannot be undone.');">
                                            <i class="bi bi-trash"></i>
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

<!-- Add Hall Modal -->
<div class="modal fade" id="addHallModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="halls.php" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-navy text-white" style="background:#0b1a30; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Add New Function Hall</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Hall Name <span class="text-danger">*</span></label>
                        <input type="text" name="hall_name" class="form-control" placeholder="e.g. The Grand Imperial Ballroom" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Hall Code <span class="text-danger">*</span></label>
                        <input type="text" name="hall_code" class="form-control" placeholder="e.g. HALL-BLRM-05" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Min Capacity (Guests)</label>
                        <input type="number" name="capacity_min" class="form-control" value="50" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Max Capacity (Guests) <span class="text-danger">*</span></label>
                        <input type="number" name="capacity_max" class="form-control" value="500" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Price Per Hour ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price_per_hour" class="form-control" placeholder="250.00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Half-Day Rate ($)</label>
                        <input type="number" step="0.01" name="base_price_half_day" class="form-control" placeholder="1200.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Full-Day Rate ($)</label>
                        <input type="number" step="0.01" name="base_price_full_day" class="form-control" placeholder="2200.00">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Dimensions / Area</label>
                        <input type="text" name="dimensions" class="form-control" placeholder="e.g. 100 x 60 ft (6,000 sq ft)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Architectural features, aesthetic styling..." required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Amenities (Comma separated)</label>
                        <input type="text" name="amenities" class="form-control" placeholder="e.g. Crystal Chandeliers, Elevated Stage, PA System, Bridal Suite">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_hall" value="1" class="btn btn-gold btn-sm">Save Function Hall</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Hall Modal -->
<div class="modal fade" id="editHallModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="halls.php" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="hall_id" id="edit_hall_id">
            <div class="modal-header bg-navy text-white" style="background:#0b1a30; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Function Hall</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Hall Name</label>
                        <input type="text" name="hall_name" id="edit_hall_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Hall Code</label>
                        <input type="text" name="hall_code" id="edit_hall_code" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Min Capacity</label>
                        <input type="number" name="capacity_min" id="edit_capacity_min" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Max Capacity</label>
                        <input type="number" name="capacity_max" id="edit_capacity_max" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Price Per Hour ($)</label>
                        <input type="number" step="0.01" name="price_per_hour" id="edit_price_per_hour" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Half-Day Rate ($)</label>
                        <input type="number" step="0.01" name="base_price_half_day" id="edit_base_price_half_day" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Full-Day Rate ($)</label>
                        <input type="number" step="0.01" name="base_price_full_day" id="edit_base_price_full_day" class="form-control">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Dimensions</label>
                        <input type="text" name="dimensions" id="edit_dimensions" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Amenities</label>
                        <input type="text" name="amenities" id="edit_amenities" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_hall" value="1" class="btn btn-navy btn-sm">Update Function Hall</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(h) {
    document.getElementById('edit_hall_id').value = h.id;
    document.getElementById('edit_hall_name').value = h.hall_name;
    document.getElementById('edit_hall_code').value = h.hall_code;
    document.getElementById('edit_capacity_min').value = h.capacity_min;
    document.getElementById('edit_capacity_max').value = h.capacity_max;
    document.getElementById('edit_price_per_hour').value = h.price_per_hour;
    document.getElementById('edit_base_price_half_day').value = h.base_price_half_day;
    document.getElementById('edit_base_price_full_day').value = h.base_price_full_day;
    document.getElementById('edit_dimensions').value = h.dimensions || '';
    document.getElementById('edit_status').value = h.status;
    document.getElementById('edit_description').value = h.description;
    document.getElementById('edit_amenities').value = h.amenities || '';

    const bsModal = new bootstrap.Modal(document.getElementById('editHallModal'));
    bsModal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
