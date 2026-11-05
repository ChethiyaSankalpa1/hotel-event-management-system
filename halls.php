<?php
/**
 * Public Function Halls Directory
 * Hotel Event Management System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$minCapacity = isset($_GET['min_capacity']) ? (int)$_GET['min_capacity'] : 0;

$sql = "SELECT * FROM `function_halls` WHERE `status` = 'active'";
$params = [];

if ($search !== '') {
    $sql .= " AND (`hall_name` LIKE :search OR `description` LIKE :search OR `amenities` LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($minCapacity > 0) {
    $sql .= " AND `capacity_max` >= :min_cap";
    $params[':min_cap'] = $minCapacity;
}

$sql .= " ORDER BY `capacity_max` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$halls = $stmt->fetchAll();

$pageTitle = 'Function Halls & Ballrooms';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 bg-navy text-white text-center" style="background:#0b1a30; border-bottom: 3px solid #c5a880;">
    <div class="container py-3">
        <span class="text-warning text-uppercase fw-bold small" style="letter-spacing: 2px;">Venues & Banquet Spaces</span>
        <h1 class="luxury-heading text-white mt-1 mb-2">Our Grand Function Halls</h1>
        <p class="text-white-50 mx-auto mb-0" style="max-width: 650px;">
            Explore our magnificent spaces featuring soaring ceilings, intelligent ambient lighting, modular staging, and full AV support.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-5 p-3 bg-white">
        <form method="GET" action="halls.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted">Search Venue Name or Feature</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           value="<?= e($search) ?>" placeholder="e.g. Ballroom, Projector, Garden...">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Minimum Guest Capacity</label>
                <select name="min_capacity" class="form-select">
                    <option value="0">All Capacities</option>
                    <option value="50" <?= $minCapacity === 50 ? 'selected' : '' ?>>50+ Guests</option>
                    <option value="150" <?= $minCapacity === 150 ? 'selected' : '' ?>>150+ Guests</option>
                    <option value="300" <?= $minCapacity === 300 ? 'selected' : '' ?>>300+ Guests</option>
                    <option value="500" <?= $minCapacity === 500 ? 'selected' : '' ?>>500+ Guests</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-navy flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search || $minCapacity): ?>
                    <a href="halls.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Halls Grid -->
    <div class="row g-4">
        <?php if (empty($halls)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
                <h4 class="text-dark">No function halls match your search criteria</h4>
                <p class="text-muted">Try adjusting your filters or search terms.</p>
                <a href="halls.php" class="btn btn-gold btn-sm">Reset All Filters</a>
            </div>
        <?php else: ?>
            <?php foreach ($halls as $hall): ?>
                <div class="col-lg-6">
                    <div class="venue-card h-100">
                        <div class="venue-card-img-wrap d-flex align-items-center justify-content-center text-white" 
                             style="background: linear-gradient(135deg, #0b1a30 0%, #162a4d 100%); min-height: 200px;">
                            <div class="text-center p-3">
                                <i class="bi bi-buildings fs-1 text-warning mb-2 d-block"></i>
                                <span class="badge bg-warning text-dark fw-bold px-3 py-1"><?= e($hall['hall_code']) ?></span>
                            </div>
                            <span class="venue-card-badge">
                                <i class="bi bi-people-fill me-1"></i> <?= $hall['capacity_min'] ?> - <?= $hall['capacity_max'] ?> Guests
                            </span>
                        </div>

                        <div class="venue-card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3 class="venue-card-title mb-0"><?= e($hall['hall_name']) ?></h3>
                            </div>

                            <p class="text-muted small mb-3"><?= nl2br(e($hall['description'])) ?></p>

                            <div class="row g-2 mb-3 py-2 border-top border-bottom bg-light rounded px-2">
                                <div class="col-4 text-center">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">HOURLY RATE</small>
                                    <strong class="text-navy"><?= format_currency($hall['price_per_hour']) ?></strong>
                                </div>
                                <div class="col-4 text-center border-start border-end">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">HALF DAY (4h+)</small>
                                    <strong class="text-navy"><?= format_currency($hall['base_price_half_day']) ?></strong>
                                </div>
                                <div class="col-4 text-center">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">FULL DAY (8h+)</small>
                                    <strong class="text-navy"><?= format_currency($hall['base_price_full_day']) ?></strong>
                                </div>
                            </div>

                            <h6 class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:0.8px;">Amenities & Features</h6>
                            <div class="d-flex flex-wrap gap-1 mb-4">
                                <?php 
                                    $amenities = explode(',', $hall['amenities'] ?? '');
                                    foreach ($amenities as $am):
                                        $am = trim($am);
                                        if (empty($am)) continue;
                                ?>
                                    <span class="badge bg-white text-dark border py-1 px-2 small">
                                        <i class="bi bi-check text-success me-1"></i><?= e($am) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <div class="venue-price-tag mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <a href="<?= BASE_URL ?>availability.php?hall_id=<?= $hall['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-calendar3 me-1"></i> View Calendar
                                </a>
                                <a href="<?= BASE_URL ?>customer/book.php?hall_id=<?= $hall['id'] ?>" class="btn btn-gold btn-sm px-3">
                                    <i class="bi bi-calendar-plus me-1"></i> Reserve This Hall
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
