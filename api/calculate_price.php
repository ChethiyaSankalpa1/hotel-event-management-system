<?php
/**
 * AJAX Endpoint: Calculate Live Event Pricing Breakdown
 * Hotel Event Management System
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/BookingService.php';

$hallId    = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;
$packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$guests    = isset($_GET['guests']) ? (int)$_GET['guests'] : 0;
$startTime = $_GET['start_time'] ?? '10:00:00';
$endTime   = $_GET['end_time'] ?? '16:00:00';

if (!$hallId || !$packageId || $guests <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide valid hall, package, and guest count.'
    ]);
    exit;
}

try {
    $pricing = BookingService::calculatePricing($hallId, $packageId, $guests, $startTime, $endTime);
    
    // Append formatted currency representations
    $pricing['formatted_hall_charge']      = format_currency($pricing['hall_charge']);
    $pricing['formatted_package_charge']   = format_currency($pricing['package_charge']);
    $pricing['formatted_tax_amount']       = format_currency($pricing['tax_amount']);
    $pricing['formatted_total_amount']     = format_currency($pricing['total_amount']);
    $pricing['formatted_advance_required'] = format_currency($pricing['advance_required']);

    echo json_encode([
        'success' => true,
        'data'    => $pricing
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
