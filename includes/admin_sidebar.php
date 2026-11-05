<?php
/**
 * Administrator Sidebar Component
 * Hotel Event Management System
 */

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$adminUser = current_user();
?>
<!-- Sidebar Overlay Backdrop for Mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-icon-box">
                <i class="bi bi-shield-shaded text-warning"></i>
            </div>
            <div class="brand-text">
                <h5 class="mb-0">The Grand Horizon</h5>
                <span class="badge-role">Admin Control</span>
            </div>
        </div>
        <!-- Mobile Close Button -->
        <button type="button" class="btn btn-sidebar-close d-lg-none" id="sidebarCloseBtn" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-heading">Core Management</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/bookings.php" class="nav-link <?= $currentScript === 'bookings.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> <span>Master Bookings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/payments.php" class="nav-link <?= $currentScript === 'payments.php' ? 'active' : '' ?>">
                <i class="bi bi-cash-stack"></i> <span>Payment Ledger</span>
            </a>
        </li>

        <li class="sidebar-heading">Inventory & Services</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/halls.php" class="nav-link <?= $currentScript === 'halls.php' ? 'active' : '' ?>">
                <i class="bi bi-buildings"></i> <span>Function Halls</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/packages.php" class="nav-link <?= $currentScript === 'packages.php' ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> <span>Event Packages</span>
            </a>
        </li>

        <li class="sidebar-heading">Accounts & Staff</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/staff.php" class="nav-link <?= $currentScript === 'staff.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> <span>Staff Accounts</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/customers.php" class="nav-link <?= $currentScript === 'customers.php' ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i> <span>Customers</span>
            </a>
        </li>

        <li class="sidebar-heading">Business Intelligence</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> <span>Analytics & Reports</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/settings.php" class="nav-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                <i class="bi bi-gear"></i> <span>System Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-chip mb-3">
            <div class="user-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="user-info">
                <div class="user-name text-truncate"><?= e($adminUser['name'] ?? $adminUser['email'] ?? 'Administrator') ?></div>
                <div class="user-role"><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Admin</span></div>
            </div>
        </div>
        <div class="d-flex flex-column gap-2">
            <a href="<?= BASE_URL ?>index.php" target="_blank" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                <i class="bi bi-globe"></i> <span>Public Website</span>
            </a>
            <a href="<?= BASE_URL ?>logout.php" class="btn btn-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                <i class="bi bi-box-arrow-right"></i> <span>Sign Out</span>
            </a>
        </div>
    </div>
</aside>
