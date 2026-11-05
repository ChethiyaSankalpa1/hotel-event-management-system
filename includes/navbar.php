<?php
/**
 * Navigation Bar Component (Public Website & Customer Portal)
 * Hotel Event Management System
 */

$currentUser = current_user();
$currentRole = current_user_role();
$settings = get_system_settings();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="navbar navbar-expand-lg navbar-grand sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-buildings me-2 text-warning fs-3"></i>
            <div>
                <span class="d-block text-white fw-bold lh-1"><?= e($settings['hotel_name']) ?></span>
                <small class="text-white-50" style="font-size: 0.72rem; letter-spacing: 1px; font-family: sans-serif; text-transform: uppercase;">Banquet & Events</small>
            </div>
        </a>
        
        <button class="navbar-toggler text-white border-white" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2 text-white"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'halls.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>halls.php">Function Halls</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'packages.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>packages.php">Packages & Menus</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'availability.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>availability.php">
                        <i class="bi bi-calendar3 me-1 text-warning"></i> Hall Availability
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($currentUser): ?>
                    <?php if ($currentRole === ROLE_CUSTOMER): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle btn-sm d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle text-warning fs-5"></i>
                                <span><?= e($currentUser['name']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>customer/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>customer/book.php"><i class="bi bi-calendar-plus me-2"></i> Book an Event</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>customer/my_bookings.php"><i class="bi bi-journal-bookmark me-2"></i> My Bookings</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>customer/profile.php"><i class="bi bi-person-gear me-2"></i> My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                            </ul>
                        </div>
                        <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold btn-sm ms-2">
                            <i class="bi bi-plus-circle me-1"></i> Reserve Hall
                        </a>
                    <?php elseif ($currentRole === ROLE_STAFF): ?>
                        <a href="<?= BASE_URL ?>staff/dashboard.php" class="btn btn-gold btn-sm">
                            <i class="bi bi-kanban me-1"></i> Staff Portal
                        </a>
                        <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-light btn-sm ms-1">Sign Out</a>
                    <?php elseif ($currentRole === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-gold btn-sm">
                            <i class="bi bi-shield-lock me-1"></i> Admin Panel
                        </a>
                        <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-light btn-sm ms-1">Sign Out</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-light btn-sm px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-gold btn-sm px-3">
                        <i class="bi bi-person-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
