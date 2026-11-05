<?php
/**
 * Event Manager / Staff Sidebar Component
 * Hotel Event Management System
 */

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$staffUser = current_user();
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-person-workspace text-warning fs-2"></i>
        <div>
            <h5>The Grand Horizon</h5>
            <span>Staff / Event Manager</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-heading">Workflow Center</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/bookings.php" class="nav-link <?= in_array($currentScript, ['bookings.php', 'booking_detail.php']) ? 'active' : '' ?>">
                <i class="bi bi-inbox"></i> Booking Requests
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/record_payment.php" class="nav-link <?= $currentScript === 'record_payment.php' ? 'active' : '' ?>">
                <i class="bi bi-credit-card-2-front"></i> Record Payment
            </a>
        </li>

        <li class="sidebar-heading">Schedule & Clients</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/calendar.php" class="nav-link <?= $currentScript === 'calendar.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> Hall Schedule
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>staff/customers.php" class="nav-link <?= $currentScript === 'customers.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Customer Contacts
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between text-white-50 small mb-2">
            <span>Logged in as:</span>
            <span class="badge bg-primary">Event Staff</span>
        </div>
        <div class="text-white fw-bold text-truncate small mb-3">
            <?= e($staffUser['name'] ?? $staffUser['email']) ?>
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
