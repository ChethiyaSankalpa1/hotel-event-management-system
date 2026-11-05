<?php
/**
 * Application Constants and Global Settings
 * Hotel Event Management System
 */

// Application Information
define('APP_NAME', 'The Grand Horizon');
define('APP_FULL_NAME', 'The Grand Horizon Hotel & Banquet');
define('APP_VERSION', '1.0.0');

// Calculate base URL automatically (Root-relative path prevents Mixed Content blocking behind HTTPS proxies)
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If the script is in a subfolder like /admin, /staff, /customer, /api, trim back to root
    $scriptDir = preg_replace('/(\/(admin|staff|customer|api|includes|config|services))$/i', '', $scriptDir);
    $rootPath = rtrim($scriptDir, '/') . '/';
    
    define('BASE_URL', $rootPath);
}

// Security constants
define('CSRF_TOKEN_KEY', '_csrf_token');

// Booking Statuses & Color Badges
const BOOKING_STATUSES = [
    'pending'   => ['label' => 'Pending Review', 'badge' => 'warning', 'icon' => 'hourglass-split'],
    'approved'  => ['label' => 'Approved (Awaiting Advance)', 'badge' => 'info', 'icon' => 'check-circle'],
    'confirmed' => ['label' => 'Confirmed & Booked', 'badge' => 'success', 'icon' => 'shield-check'],
    'completed' => ['label' => 'Completed', 'badge' => 'primary', 'icon' => 'flag-fill'],
    'rejected'  => ['label' => 'Rejected', 'badge' => 'danger', 'icon' => 'x-circle'],
    'cancelled' => ['label' => 'Cancelled', 'badge' => 'secondary', 'icon' => 'slash-circle'],
];

// Payment Statuses & Color Badges
const PAYMENT_STATUSES = [
    'unpaid'         => ['label' => 'Unpaid', 'badge' => 'danger'],
    'partially_paid' => ['label' => 'Partially Paid', 'badge' => 'warning'],
    'fully_paid'     => ['label' => 'Fully Paid', 'badge' => 'success'],
    'refunded'       => ['label' => 'Refunded', 'badge' => 'secondary'],
];

// User Roles
const ROLE_ADMIN    = 'admin';
const ROLE_STAFF    = 'staff';
const ROLE_CUSTOMER = 'customer';
