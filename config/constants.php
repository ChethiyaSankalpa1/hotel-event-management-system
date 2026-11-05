<?php
/**
 * Application Constants and Global Settings
 * Hotel Event Management System
 */

// Application Information
define('APP_NAME', 'The Grand Horizon');
define('APP_FULL_NAME', 'The Grand Horizon Hotel & Banquet');
define('APP_VERSION', '1.0.0');

// Calculate base URL automatically (with reverse proxy HTTPS detection for Railway, Cloudflare, etc.)
if (!defined('BASE_URL')) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
        (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    );
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine the base folder from current script path
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If the script is in a subfolder like /admin, /staff, /customer, /api, trim back to root
    $scriptDir = preg_replace('/(\/(admin|staff|customer|api|includes|config|services))$/i', '', $scriptDir);
    $baseUrl = rtrim($protocol . $host . $scriptDir, '/') . '/';
    
    define('BASE_URL', $baseUrl);
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
