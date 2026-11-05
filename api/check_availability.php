<?php
/**
 * AJAX Endpoint: Check Hall Availability & Prevent Double-Booking
 * Hotel Event Management System
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../services/BookingService.php';

$hallId    = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;
$eventDate = $_GET['date'] ?? '';
$startTime = $_GET['start_time'] ?? '';
$endTime   = $_GET['end_time'] ?? '';
$excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

if (!$hallId || !$eventDate || !$startTime || !$endTime) {
    echo json_encode([
        'available' => false,
        'message'   => 'Please provide hall, date, start time, and end time.'
    ]);
    exit;
}

try {
    $result = BookingService::checkHallAvailability($hallId, $eventDate, $startTime, $endTime, $excludeId);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'available' => false,
        'message'   => 'Error checking availability: ' . $e->getMessage()
    ]);
}
