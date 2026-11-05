<?php
/**
 * Public Event Packages & Menus Catalog
 * Hotel Event Management System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

$category = trim($_GET['category'] ?? '');

$sql = "SELECT * FROM `event_packages` WHERE `status` = 'active'";
$params = [];

if (!empty($category) && in_array($category, ['wedding', 'corporate', 'birthday', 'anniversary', 'special'])) {
    $sql .= " AND `category` = :cat";
    $params[':cat'] = $category;
}

$sql .= " ORDER BY `price_per_person` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();

$pageTitle = 'Event Packages & Menus';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 bg-navy text-white text-center" style="background:#0b1a30; border-bottom: 3px solid #c5a880;">
    <div class="container py-3">
        <span class="text-warning text-uppercase fw-bold small" style="letter-spacing: 2px;">Catering, Decor & Production</span>
        <h1 class="luxury-heading text-white mt-1 mb-2">Curated Event Packages</h1>
        <p class="text-white-50 mx-auto mb-0" style="max-width: 650px;">
            Designed by our executive chefs and banquet directors to deliver comprehensive culinary excellence, stage design, and attentive service.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Category Tabs -->
    <div class="d-flex justify-content-center gap-2 flex-wrap mb-5">
        <a href="packages.php" class="btn <?= empty($category) ? 'btn-gold' : 'btn-outline-navy' ?> btn-sm px-3">
            All Packages
        </a>
        <a href="packages.php?category=wedding" class="btn <?= $category === 'wedding' ? 'btn-gold' : 'btn-outline-navy' ?> btn-sm px-3">
            💍 Wedding Galas
        </a>
        <a href="packages.php?category=corporate" class="btn <?= $category === 'corporate' ? 'btn-gold' : 'btn-outline-navy' ?> btn-sm px-3">
            💼 Corporate Summits
        </a>
        <a href="packages.php?category=birthday" class="btn <?= $category === 'birthday' ? 'btn-gold' : 'btn-outline-navy' ?> btn-sm px-3">
            🎂 Birthdays & Parties
        </a>
        <a href="packages.php?category=anniversary" class="btn <?= $category === 'anniversary' ? 'btn-gold' : 'btn-outline-navy' ?> btn-sm px-3">
            🥂 Anniversaries
        </a>
        <a href="packages.php?category=special" class="btn <?= $category === 'special' ? 'btn-gold' : 'btn-outline-navy' ?> btn-sm px-3">
            🍸 Cocktail Receptions
        </a>
    </div>

    <!-- Packages Grid -->
    <div class="row g-4">
        <?php if (empty($packages)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-box-seam fs-1 text-muted d-block mb-3"></i>
                <h4 class="text-dark">No packages found for this category</h4>
                <a href="packages.php" class="btn btn-gold btn-sm mt-2">View All Packages</a>
            </div>
        <?php else: ?>
            <?php foreach ($packages as $pkg): ?>
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="card-header bg-navy text-white p-4 border-bottom border-warning border-2" style="background:#0b1a30;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-warning text-dark text-uppercase fw-bold" style="font-size:0.75rem;">
                                    <?= e($pkg['category']) ?>
                                </span>
                                <small class="text-white-50"><?= e($pkg['package_code']) ?></small>
                            </div>
                            <h3 class="luxury-heading text-white mb-0" style="font-size: 1.45rem;"><?= e($pkg['package_name']) ?></h3>
                        </div>

                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-baseline gap-2 mb-3">
                                <span class="fs-2 fw-bold text-navy" style="color:#0b1a30;"><?= format_currency($pkg['price_per_person']) ?></span>
                                <span class="text-muted">/ person</span>
                                <span class="badge bg-light text-muted border ms-auto">Base Fee: <?= format_currency($pkg['base_package_price']) ?></span>
                            </div>

                            <p class="text-muted small mb-4"><?= nl2br(e($pkg['description'])) ?></p>

                            <!-- Menu Details -->
                            <?php if (!empty($pkg['menu_details'])): ?>
                                <div class="mb-4 p-3 bg-light rounded-3 border">
                                    <h6 class="fw-bold small text-uppercase text-dark mb-2">
                                        <i class="bi bi-cup-hot me-1 text-warning"></i> Menu Highlights & Courses
                                    </h6>
                                    <p class="small text-muted mb-0"><?= nl2br(e($pkg['menu_details'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Inclusions List -->
                            <h6 class="fw-bold small text-uppercase text-muted mb-2">Package Inclusions & Services</h6>
                            <ul class="list-unstyled small mb-4 flex-grow-1">
                                <?php 
                                    $inclusions = explode(',', $pkg['inclusions'] ?? '');
                                    foreach ($inclusions as $inc):
                                        $inc = trim($inc);
                                        if (empty($inc)) continue;
                                ?>
                                    <li class="mb-2 d-flex align-items-start">
                                        <i class="bi bi-check-circle-fill text-success me-2 mt-1 flex-shrink-0"></i>
                                        <span><?= e($inc) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted">Prices subject to <?= get_system_settings()['tax_percentage'] ?>% tax</small>
                                <a href="<?= BASE_URL ?>customer/book.php?package_id=<?= $pkg['id'] ?>" class="btn btn-gold btn-sm px-4">
                                    <i class="bi bi-calendar-plus me-1"></i> Book With This Package
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
