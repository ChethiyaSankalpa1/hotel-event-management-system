<?php
/**
 * Public Homepage - The Grand Horizon Hotel & Banquet
 * Hotel Event Management System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();
$settings = get_system_settings();

// Fetch Active Halls
$hallsStmt = $pdo->query("SELECT * FROM `function_halls` WHERE `status` = 'active' ORDER BY `capacity_max` DESC LIMIT 4");
$halls = $hallsStmt->fetchAll();

// Fetch Active Event Packages
$pkgsStmt = $pdo->query("SELECT * FROM `event_packages` WHERE `status` = 'active' ORDER BY `price_per_person` DESC LIMIT 3");
$packages = $pkgsStmt->fetchAll();

$pageTitle = 'Luxury Venues & Banquets';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Banner Section -->
<section class="hero-section text-center text-lg-start">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-subtitle d-inline-block">Welcome to <?= e($settings['hotel_name']) ?></span>
                <h1 class="hero-title">Exquisite Venues for Unforgettable Milestones</h1>
                <p class="hero-text">
                    From royal wedding receptions and milestone anniversaries to high-impact corporate summits, our grand ballrooms and landscaped outdoor terraces offer timeless luxury and bespoke banquet coordination.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold btn-lg shadow">
                        <i class="bi bi-calendar-check me-2"></i> Reserve an Event Hall
                    </a>
                    <a href="<?= BASE_URL ?>availability.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-calendar3 me-2"></i> Check Hall Availability
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="p-3 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-25 shadow-lg backdrop-blur">
                    <div class="card bg-white border-0 shadow rounded-3 p-4 text-dark">
                        <h5 class="luxury-heading mb-1" style="font-size: 1.3rem;">Instant Venue Quote</h5>
                        <p class="text-muted small mb-3">Estimate banquet costs in seconds</p>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Select Function Hall</label>
                            <select class="form-select form-select-sm" id="heroHallSelect" onchange="window.location.href='<?= BASE_URL ?>customer/book.php?hall_id=' + this.value">
                                <option value="">Choose a hall...</option>
                                <?php foreach ($halls as $h): ?>
                                    <option value="<?= $h['id'] ?>"><?= e($h['hall_name']) ?> (Up to <?= $h['capacity_max'] ?> guests)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Select Event Package</label>
                            <select class="form-select form-select-sm" onchange="window.location.href='<?= BASE_URL ?>customer/book.php?package_id=' + this.value">
                                <option value="">Choose a package...</option>
                                <?php foreach ($packages as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['package_name']) ?> (<?= format_currency($p['price_per_person']) ?> / guest)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-navy btn-sm w-100">
                            Launch Booking Wizard <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Flash Alerts if any -->
<div class="container pt-4">
    <?php require __DIR__ . '/includes/alerts.php'; ?>
</div>

<!-- Key Highlights / Feature Badges -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="bi bi-award fs-1 text-warning mb-2 d-inline-block"></i>
                    <h6 class="fw-bold mb-1 text-dark">5-Star Culinary Team</h6>
                    <p class="text-muted small mb-0">Plated, buffet, and artisanal pass-arounds</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="bi bi-soundwave fs-1 text-warning mb-2 d-inline-block"></i>
                    <h6 class="fw-bold mb-1 text-dark">State-of-the-Art AV</h6>
                    <p class="text-muted small mb-0">Laser projection, stages & intelligent lighting</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="bi bi-calendar-check fs-1 text-warning mb-2 d-inline-block"></i>
                    <h6 class="fw-bold mb-1 text-dark">Zero Double-Bookings</h6>
                    <p class="text-muted small mb-0">Automated real-time hall conflict prevention</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <i class="bi bi-shield-check fs-1 text-warning mb-2 d-inline-block"></i>
                    <h6 class="fw-bold mb-1 text-dark">Flexible Payment</h6>
                    <p class="text-muted small mb-0">Pay 30% advance deposit to lock your date</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Function Halls Section -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title-wrap">
            <span class="section-badge">Exclusive Event Spaces</span>
            <h2 class="section-title">Our Premier Function Halls</h2>
            <div class="section-divider"></div>
        </div>

        <div class="row g-4">
            <?php foreach ($halls as $hall): ?>
                <div class="col-lg-6">
                    <div class="venue-card">
                        <div class="venue-card-img-wrap d-flex align-items-center justify-content-center text-white" 
                             style="background: linear-gradient(135deg, #0b1a30 0%, #162a4d 100%);">
                            <div class="text-center p-3">
                                <i class="bi bi-buildings fs-1 text-warning mb-2 d-block"></i>
                                <span class="badge bg-warning text-dark fw-bold px-3 py-1"><?= e($hall['hall_code']) ?></span>
                            </div>
                            <span class="venue-card-badge"><?= $hall['capacity_min'] ?> - <?= $hall['capacity_max'] ?> Guests</span>
                        </div>
                        <div class="venue-card-body">
                            <h3 class="venue-card-title"><?= e($hall['hall_name']) ?></h3>
                            <p class="text-muted small mb-3"><?= e(mb_strimwidth($hall['description'], 0, 140, '...')) ?></p>
                            
                            <ul class="venue-specs">
                                <li><i class="bi bi-arrows-fullscreen"></i> <?= e($hall['dimensions'] ?? 'Spacious banquet layout') ?></li>
                                <li><i class="bi bi-people"></i> Accommodates up to <?= number_format($hall['capacity_max']) ?> guests</li>
                                <li><i class="bi bi-check2-circle"></i> <?= e(mb_strimwidth($hall['amenities'] ?? 'Climate Control, AV Ready', 0, 80, '...')) ?></li>
                            </ul>

                            <div class="venue-price-tag">
                                <div>
                                    <small class="text-muted d-block" style="font-size:0.75rem;">STARTING FROM</small>
                                    <span class="price-figure"><?= format_currency($hall['price_per_hour']) ?></span> <small class="text-muted">/ hr</small>
                                </div>
                                <div>
                                    <a href="<?= BASE_URL ?>customer/book.php?hall_id=<?= $hall['id'] ?>" class="btn btn-navy btn-sm">
                                        <i class="bi bi-calendar-plus me-1"></i> Book Venue
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>halls.php" class="btn btn-outline-gold px-4 py-2">
                View All Function Halls & Details <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- Featured Event Packages Section -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="section-title-wrap">
            <span class="section-badge">All-Inclusive Banquets</span>
            <h2 class="section-title">Curated Event Packages</h2>
            <div class="section-divider"></div>
        </div>

        <div class="row g-4">
            <?php foreach ($packages as $pkg): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="package-card <?= $pkg['category'] === 'wedding' ? 'featured' : '' ?>">
                        <span class="badge bg-secondary text-uppercase align-self-start mb-2" style="font-size:0.7rem;">
                            <?= e($pkg['category']) ?>
                        </span>
                        <h4 class="fw-bold mb-1" style="font-size: 1.25rem; color: #0b1a30;"><?= e($pkg['package_name']) ?></h4>
                        <div class="package-price">
                            <?= format_currency($pkg['price_per_person']) ?> <small class="text-muted fs-6 fw-normal">/ guest</small>
                        </div>
                        <small class="text-muted mb-3 d-block">Base setup fee: <?= format_currency($pkg['base_package_price']) ?></small>
                        
                        <p class="text-muted small mb-3"><?= e(mb_strimwidth($pkg['description'], 0, 110, '...')) ?></p>
                        
                        <ul class="package-inclusions">
                            <?php 
                                $incList = explode(',', $pkg['inclusions'] ?? 'Standard Inclusions');
                                foreach (array_slice($incList, 0, 4) as $item):
                            ?>
                                <li><?= e(trim($item)) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="<?= BASE_URL ?>customer/book.php?package_id=<?= $pkg['id'] ?>" class="btn <?= $pkg['category'] === 'wedding' ? 'btn-gold' : 'btn-navy' ?> w-100 mt-auto">
                            Select This Package
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>packages.php" class="btn btn-outline-gold px-4 py-2">
                Browse All 5 Packages & Full Menus <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 text-white text-center" style="background: linear-gradient(135deg, #0b1a30 0%, #1b263b 100%); border-top: 3px solid #c5a880;">
    <div class="container py-3">
        <h2 class="luxury-heading text-white mb-2" style="font-size: 2.2rem;">Ready to Host Your Next Celebrated Event?</h2>
        <p class="text-white-50 mx-auto mb-4" style="max-width: 650px;">
            Check our real-time availability calendar or submit your reservation request online. Our banquet team responds within 24 hours.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold btn-lg px-4 shadow">
                <i class="bi bi-calendar-plus me-2"></i> Book an Event Online
            </a>
            <a href="<?= BASE_URL ?>availability.php" class="btn btn-outline-light btn-lg px-4">
                <i class="bi bi-calendar3 me-2"></i> View Calendar
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
