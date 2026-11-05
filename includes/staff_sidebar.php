<?php
/**
 * Event Manager / Staff Sidebar Component
 * Hotel Event Management System
 */

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$staffUser = current_user();
?>
<!-- Sidebar Overlay Backdrop for Mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-icon-box">
                <i class="bi bi-person-workspace text-warning"></i>
            </div>
            <div class="brand-text">
                <h5 class="mb-0">The Grand Horizon</h5>
                <span class="badge-role">Staff Portal</span>
            </div>
        </div>
        <!-- Mobile Close Button -->
        <button type="button" class="btn btn-sidebar-close d-lg-none" id="sidebarCloseBtn" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-heading">Workflow Center</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/bookings.php" class="nav-link <?= in_array($currentScript, ['bookings.php', 'booking_detail.php']) ? 'active' : '' ?>">
                <i class="bi bi-inbox"></i> <span>Booking Requests</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/record_payment.php" class="nav-link <?= $currentScript === 'record_payment.php' ? 'active' : '' ?>">
                <i class="bi bi-credit-card-2-front"></i> <span>Record Payment</span>
            </a>
        </li>

        <li class="sidebar-heading">Schedule & Clients</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/calendar.php" class="nav-link <?= $currentScript === 'calendar.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> <span>Hall Schedule</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/customers.php" class="nav-link <?= $currentScript === 'customers.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> <span>Customer Contacts</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-chip mb-3">
            <div class="user-avatar">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div class="user-info">
                <div class="user-name text-truncate"><?= e($staffUser['name'] ?? $staffUser['email'] ?? 'Event Staff') ?></div>
                <div class="user-role"><span class="badge bg-primary-subtle text-primary border border-primary-subtle">Staff</span></div>
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
