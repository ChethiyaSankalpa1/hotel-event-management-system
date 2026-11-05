<?php
/**
 * AJAX Endpoint: Retrieve Hall Bookings Feed for FullCalendar
 * Hotel Event Management System
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getConnection();

$hallId = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;
$startRange = $_GET['start'] ?? '';
$endRange   = $_GET['end'] ?? '';

$sql = "SELECT b.id, b.booking_reference, b.event_type, b.event_date, b.start_time, b.end_time,
               b.booking_status, b.guest_count,
               h.hall_name, h.hall_code,
               p.package_name
        FROM `bookings` b
        JOIN `function_halls` h ON b.hall_id = h.id
        JOIN `event_packages` p ON b.package_id = p.id
        WHERE b.booking_status IN ('confirmed', 'approved', 'pending')";

$params = [];

if ($hallId > 0) {
    $sql .= " AND b.hall_id = :hall_id";
    $params[':hall_id'] = $hallId;
}

if ($startRange && $endRange) {
    $sql .= " AND b.event_date BETWEEN :start_date AND :end_date";
    $params[':start_date'] = date('Y-m-d', strtotime($startRange));
    $params[':end_date']   = date('Y-m-d', strtotime($endRange));
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$events = [];

foreach ($bookings as $b) {
    $startDateTime = $b['event_date'] . 'T' . $b['start_time'];
    $endDateTime   = $b['event_date'] . 'T' . $b['end_time'];

    // Color code by status
    $color = '#64748b'; // default secondary
    $badgeColor = 'secondary';
    $statusLabel = 'Pending Review';

    if ($b['booking_status'] === 'confirmed') {
        $color = '#198754'; // green
        $badgeColor = 'success';
        $statusLabel = 'Confirmed';
    } elseif ($b['booking_status'] === 'approved') {
        $color = '#0284c7'; // blue
        $badgeColor = 'info';
        $statusLabel = 'Approved (Awaiting Advance)';
    } elseif ($b['booking_status'] === 'pending') {
        $color = '#d97706'; // amber
        $badgeColor = 'warning';
        $statusLabel = 'Pending Review';
    }

    $events[] = [
        'id'              => $b['id'],
        'title'           => $b['hall_name'] . ' - ' . $b['event_type'],
        'start'           => $startDateTime,
        'end'             => $endDateTime,
        'backgroundColor' => $color,
        'borderColor'     => $color,
        'textColor'       => '#ffffff',
        'extendedProps'   => [
            'booking_ref'  => $b['booking_reference'],
            'hall_name'    => $b['hall_name'],
            'event_type'   => $b['event_type'],
            'package_name' => $b['package_name'],
            'guest_count'  => $b['guest_count'],
            'status'       => $b['booking_status'],
            'status_label' => $statusLabel,
            'badge_color'  => $badgeColor
        ]
    ];
}

echo json_encode($events);
