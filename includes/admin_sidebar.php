<?php
/**
 * Administrator Sidebar Component
 * Hotel Event Management System
 */

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$adminUser = current_user();
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-shield-shaded text-warning fs-2"></i>
        <div>
            <h5>The Grand Horizon</h5>
            <span>Administrator Control</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-heading">Core Management</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/bookings.php" class="nav-link <?= $currentScript === 'bookings.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> Master Bookings
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/payments.php" class="nav-link <?= $currentScript === 'payments.php' ? 'active' : '' ?>">
                <i class="bi bi-cash-stack"></i> Payment Ledger
            </a>
        </li>

        <li class="sidebar-heading">Inventory & Services</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/halls.php" class="nav-link <?= $currentScript === 'halls.php' ? 'active' : '' ?>">
                <i class="bi bi-buildings"></i> Function Halls
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/packages.php" class="nav-link <?= $currentScript === 'packages.php' ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Event Packages
            </a>
        </li>

        <li class="sidebar-heading">Accounts & Staff</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/staff.php" class="nav-link <?= $currentScript === 'staff.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Staff Accounts
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/customers.php" class="nav-link <?= $currentScript === 'customers.php' ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i> Customers
            </a>
        </li>

        <li class="sidebar-heading">Business Intelligence</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> Analytics & Reports
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/settings.php" class="nav-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                <i class="bi bi-gear"></i> System Settings
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between text-white-50 small mb-2">
            <span>Logged in as:</span>
            <span class="badge bg-danger">Admin</span>
        </div>
        <div class="text-white fw-bold text-truncate small mb-3">
            <?= e($adminUser['email'] ?? 'Administrator') ?>
        </div>
        <div class="d-grid gap-2">
            <a href="<?= BASE_URL ?>index.php" target="_blank" class="btn btn-outline-light btn-sm text-start">
                <i class="bi bi-box-arrow-up-right me-1"></i> Public Website
            </a>
            <a href="<?= BASE_URL ?>logout.php" class="btn btn-danger btn-sm text-start">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
            </a>
        </div>
    </div>
</aside>
