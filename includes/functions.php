<?php
/**
 * Global Helper Functions & Utility Routines
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Sanitize string for HTML output (prevents XSS)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Retrieve cached or fresh hotel settings from system_settings table
 */
function get_system_settings(): array {
    static $settings = null;
    if ($settings === null) {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT * FROM `system_settings` WHERE `id` = 1 LIMIT 1");
            $data = $stmt->fetch();
            if ($data) {
                $settings = $data;
                // Normalize currency symbol to LKR
                if (empty($settings['currency_symbol']) || $settings['currency_symbol'] === '$' || $settings['currency_symbol'] === 'Rs.') {
                    $settings['currency_symbol'] = 'LKR';
                }
            } else {
                $settings = [
                    'hotel_name'         => 'The Grand Horizon Hotel & Banquet',
                    'hotel_tagline'      => 'Exquisite Venues & Unforgettable Celebrations',
                    'address'            => '742 Oceanfront Promenade',
                    'city_state_zip'     => 'Colombo 03, Sri Lanka',
                    'phone'              => '+94 11 234 5678',
                    'email'              => 'events@grandhorizonhotel.com',
                    'website'            => 'www.grandhorizonhotel.com',
                    'currency_symbol'    => 'LKR',
                    'tax_percentage'     => 10.00,
                    'advance_percentage' => 30.00,
                    'cancellation_policy'=> 'Standard 14-day cancellation policy applies.'
                ];
            }
        } catch (Exception $e) {
            $settings = [
                'hotel_name'         => 'The Grand Horizon Hotel & Banquet',
                'currency_symbol'    => 'LKR',
                'tax_percentage'     => 10.00,
                'advance_percentage' => 30.00,
            ];
        }
    }
    return $settings;
}

/**
 * Format monetary amount with currency symbol (LKR)
 */
function format_currency($amount): string {
    $settings = get_system_settings();
    $sym = trim($settings['currency_symbol'] ?? 'LKR');
    if ($sym === '$' || $sym === 'Rs.') {
        $sym = 'LKR';
    }
    return $sym . ' ' . number_format((float)$amount, 2);
}

/**
 * Retrieve curated real luxury photography for a function hall
 */
function get_hall_image_url(array $hall): string {
    $code = strtoupper($hall['hall_code'] ?? '');
    $name = strtolower($hall['hall_name'] ?? '');

    // Curated high-resolution 5-star hotel banquet hall photography
    $unsplashPhotos = [
        'HALL-BLRM-01' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=1200&q=85', // Imperial Ballroom with chandeliers
        'HALL-CONF-02' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=85', // Sapphire Conference & Banquet Hall
        'HALL-PAV-03'  => 'https://images.unsplash.com/photo-1544077960-604201fe74bc?auto=format&fit=crop&w=1200&q=85', // Royal Orchid Pavilion
        'HALL-TRC-04'  => 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?auto=format&fit=crop&w=1200&q=85', // Palm Garden Terrace & Lawn
    ];

    if (!empty($code) && isset($unsplashPhotos[$code])) {
        return $unsplashPhotos[$code];
    }

    if (str_contains($name, 'ballroom') || str_contains($name, 'imperial')) {
        return 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=1200&q=85';
    }
    if (str_contains($name, 'conference') || str_contains($name, 'sapphire')) {
        return 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=85';
    }
    if (str_contains($name, 'pavilion') || str_contains($name, 'orchid') || str_contains($name, 'glass')) {
        return 'https://images.unsplash.com/photo-1544077960-604201fe74bc?auto=format&fit=crop&w=1200&q=85';
    }
    if (str_contains($name, 'terrace') || str_contains($name, 'garden') || str_contains($name, 'lawn')) {
        return 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?auto=format&fit=crop&w=1200&q=85';
    }

    return 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=1200&q=85';
}

/**
 * Retrieve curated photography for event packages
 */
function get_package_image_url(array $pkg): string {
    $cat = strtolower($pkg['category'] ?? '');
    switch ($cat) {
        case 'wedding':
            return 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=800&q=85';
        case 'corporate':
            return 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=800&q=85';
        case 'birthday':
            return 'https://images.unsplash.com/photo-1464349153735-7db50ed83c84?auto=format&fit=crop&w=800&q=85';
        case 'anniversary':
            return 'https://images.unsplash.com/photo-1520854221256-17451cc331bf?auto=format&fit=crop&w=800&q=85';
        case 'special':
            return 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&w=800&q=85';
        default:
            return 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=800&q=85';
    }
}

/**
 * Format date for clean display (e.g. "Sat, Nov 15, 2026")
 */
function format_date(?string $dateStr, string $format = 'D, M j, Y'): string {
    if (!$dateStr) return 'N/A';
    $time = strtotime($dateStr);
    return $time ? date($format, $time) : $dateStr;
}

/**
 * Format time for clean display (e.g. "04:30 PM")
 */
function format_time(?string $timeStr): string {
    if (!$timeStr) return 'N/A';
    $time = strtotime($timeStr);
    return $time ? date('g:i A', $time) : $timeStr;
}

/**
 * Render HTML badge for booking status
 */
function render_booking_badge(string $status): string {
    $info = BOOKING_STATUSES[$status] ?? ['label' => ucfirst($status), 'badge' => 'secondary', 'icon' => 'circle'];
    return sprintf(
        '<span class="badge bg-%s text-white px-2 py-1"><i class="bi bi-%s me-1"></i>%s</span>',
        e($info['badge']),
        e($info['icon']),
        e($info['label'])
    );
}

/**
 * Render HTML badge for payment status
 */
function render_payment_badge(string $status): string {
    $info = PAYMENT_STATUSES[$status] ?? ['label' => ucfirst($status), 'badge' => 'secondary'];
    return sprintf(
        '<span class="badge bg-%s px-2 py-1">%s</span>',
        e($info['badge']),
        e($info['label'])
    );
}

/**
 * Verify user password against password_hash or fallback seeds
 */
function verify_user_password(string $plainPassword, string $storedHash): bool {
    // 1. Standard native PHP verify
    if (password_verify($plainPassword, $storedHash)) {
        return true;
    }
    // 2. Fallback for demo seed accounts if initial raw password was imported
    if (in_array($plainPassword, ['admin123', 'staff123', 'customer123']) && 
        str_contains($storedHash, '12345678901')) {
        return true;
    }
    if ($storedHash === hash('sha256', $plainPassword)) {
        return true;
    }
    if ($plainPassword === $storedHash) {
        return true;
    }
    return false;
}
