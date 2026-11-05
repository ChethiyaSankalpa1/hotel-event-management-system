<?php
/**
 * Booking Business Logic Service
 * Handles Double-Booking Prevention, Pricing Calculations, and Reservation Lifecycle
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

class BookingService {

    /**
     * Check if a function hall is available for a specified date and time interval.
     * Prevents double-booking: returns TRUE if available, FALSE if conflict exists.
     * 
     * Overlap condition between [start_time, end_time] and existing [existing_start, existing_end]:
     *   (start_time < existing_end) AND (end_time > existing_start)
     * Active blocking statuses: 'confirmed', 'approved' (and optionally 'pending' depending on strictness)
     */
    public static function checkHallAvailability(
        int $hallId, 
        string $eventDate, 
        string $startTime, 
        string $endTime, 
        ?int $excludeBookingId = null
    ): array {
        $pdo = Database::getConnection();
        
        // Normalize time formats (HH:MM:SS)
        $startTime = date('H:i:s', strtotime($startTime));
        $endTime   = date('H:i:s', strtotime($endTime));

        if (strtotime($endTime) <= strtotime($startTime)) {
            return [
                'available' => false,
                'message'   => 'End time must be later than start time.'
            ];
        }

        $sql = "SELECT b.id, b.booking_reference, b.event_type, b.start_time, b.end_time, b.booking_status,
                       h.hall_name
                FROM `bookings` b
                JOIN `function_halls` h ON b.hall_id = h.id
                WHERE b.hall_id = :hall_id
                  AND b.event_date = :event_date
                  AND b.booking_status IN ('confirmed', 'approved')
                  AND (:start_time < b.end_time AND :end_time > b.start_time)";

        $params = [
            ':hall_id'    => $hallId,
            ':event_date' => $eventDate,
            ':start_time' => $startTime,
            ':end_time'   => $endTime,
        ];

        if ($excludeBookingId !== null) {
            $sql .= " AND b.id != :exclude_id";
            $params[':exclude_id'] = $excludeBookingId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $conflicts = $stmt->fetchAll();

        if (count($conflicts) > 0) {
            $first = $conflicts[0];
            return [
                'available' => false,
                'conflicts' => $conflicts,
                'message'   => sprintf(
                    'Hall "%s" is already booked on %s between %s and %s (%s).',
                    $first['hall_name'],
                    format_date($eventDate),
                    format_time($first['start_time']),
                    format_time($first['end_time']),
                    $first['booking_reference']
                )
            ];
        }

        return [
            'available' => true,
            'message'   => 'The hall is available for the selected date and time.'
        ];
    }

    /**
     * Compute comprehensive financial quote based on hall duration, guests, package, and taxes.
     */
    public static function calculatePricing(
        int $hallId, 
        int $packageId, 
        int $guestCount, 
        string $startTime, 
        string $endTime
    ): array {
        $pdo = Database::getConnection();
        $settings = get_system_settings();

        // Fetch Hall Details
        $stmtHall = $pdo->prepare("SELECT * FROM `function_halls` WHERE `id` = :id LIMIT 1");
        $stmtHall->execute([':id' => $hallId]);
        $hall = $stmtHall->fetch();
        if (!$hall) {
            throw new InvalidArgumentException("Selected function hall does not exist.");
        }

        // Fetch Package Details
        $stmtPkg = $pdo->prepare("SELECT * FROM `event_packages` WHERE `id` = :id LIMIT 1");
        $stmtPkg->execute([':id' => $packageId]);
        $package = $stmtPkg->fetch();
        if (!$package) {
            throw new InvalidArgumentException("Selected event package does not exist.");
        }

        // Calculate hours
        $startSec = strtotime($startTime);
        $endSec   = strtotime($endTime);
        $durationHours = max(1, round(($endSec - $startSec) / 3600, 1));

        // Hall charge logic:
        // If >= 8 hours, apply full day rate if cheaper than hourly
        // If >= 4 hours, apply half day rate if cheaper
        $hourlyTotal = $durationHours * (float)$hall['price_per_hour'];
        $hallCharge = $hourlyTotal;

        if ($durationHours >= 8 && (float)$hall['base_price_full_day'] > 0) {
            $hallCharge = min($hourlyTotal, (float)$hall['base_price_full_day']);
        } elseif ($durationHours >= 4 && (float)$hall['base_price_half_day'] > 0) {
            $hallCharge = min($hourlyTotal, (float)$hall['base_price_half_day']);
        }

        // Package charge = Base package fee + (price per person * guest count)
        $packageBase = (float)$package['base_package_price'];
        $packagePerGuest = (float)$package['price_per_person'] * $guestCount;
        $packageCharge = $packageBase + $packagePerGuest;

        $subtotal = $hallCharge + $packageCharge;
        $taxRate = (float)($settings['tax_percentage'] ?? 10.00);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $totalAmount = $subtotal + $taxAmount;

        $advancePct = (float)($settings['advance_percentage'] ?? 30.00);
        $advanceRequired = round($totalAmount * ($advancePct / 100), 2);

        return [
            'duration_hours'   => $durationHours,
            'hall_name'        => $hall['hall_name'],
            'hall_charge'      => round($hallCharge, 2),
            'package_name'     => $package['package_name'],
            'package_base'     => round($packageBase, 2),
            'package_per_guest'=> round($packagePerGuest, 2),
            'package_charge'   => round($packageCharge, 2),
            'subtotal'         => round($subtotal, 2),
            'tax_percentage'   => $taxRate,
            'tax_amount'       => $taxAmount,
            'total_amount'     => $totalAmount,
            'advance_percentage' => $advancePct,
            'advance_required' => $advanceRequired,
            'balance_due'      => $totalAmount
        ];
    }

    /**
     * Create a new booking request in the database
     */
    public static function createBooking(array $data): array {
        $pdo = Database::getConnection();

        // 1. Double check availability
        $avail = self::checkHallAvailability(
            (int)$data['hall_id'], 
            $data['event_date'], 
            $data['start_time'], 
            $data['end_time']
        );

        if (!$avail['available']) {
            return ['success' => false, 'message' => $avail['message']];
        }

        // 2. Compute accurate pricing
        $calc = self::calculatePricing(
            (int)$data['hall_id'],
            (int)$data['package_id'],
            (int)$data['guest_count'],
            $data['start_time'],
            $data['end_time']
        );

        // Generate unique reference (e.g., EVT-2026-9812)
        $year = date('Y');
        $random = mt_rand(1000, 9999);
        $bookingReference = sprintf("EVT-%s-%04d", $year, $random);

        // Check if reference exists
        $chkStmt = $pdo->prepare("SELECT id FROM `bookings` WHERE `booking_reference` = ?");
        $chkStmt->execute([$bookingReference]);
        if ($chkStmt->fetch()) {
            $bookingReference = sprintf("EVT-%s-%04d", $year, mt_rand(10000, 99999));
        }

        $sql = "INSERT INTO `bookings` (
            `booking_reference`, `customer_id`, `hall_id`, `package_id`,
            `event_date`, `start_time`, `end_time`, `guest_count`, `event_type`,
            `special_requests`, `hall_charge`, `package_charge`, `additional_charge`,
            `tax_amount`, `total_amount`, `advance_required`, `advance_paid`, `balance_due`,
            `booking_status`, `payment_status`
        ) VALUES (
            :ref, :customer_id, :hall_id, :package_id,
            :event_date, :start_time, :end_time, :guest_count, :event_type,
            :special_requests, :hall_charge, :package_charge, :additional_charge,
            :tax_amount, :total_amount, :advance_required, 0.00, :balance_due,
            'pending', 'unpaid'
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ref'              => $bookingReference,
            ':customer_id'      => $data['customer_id'],
            ':hall_id'          => $data['hall_id'],
            ':package_id'       => $data['package_id'],
            ':event_date'       => $data['event_date'],
            ':start_time'       => $data['start_time'],
            ':end_time'         => $data['end_time'],
            ':guest_count'      => $data['guest_count'],
            ':event_type'       => $data['event_type'],
            ':special_requests' => $data['special_requests'] ?? '',
            ':hall_charge'      => $calc['hall_charge'],
            ':package_charge'   => $calc['package_charge'],
            ':additional_charge'=> 0.00,
            ':tax_amount'       => $calc['tax_amount'],
            ':total_amount'     => $calc['total_amount'],
            ':advance_required' => $calc['advance_required'],
            ':balance_due'      => $calc['total_amount']
        ]);

        $bookingId = (int)$pdo->lastInsertId();

        return [
            'success'           => true,
            'booking_id'        => $bookingId,
            'booking_reference' => $bookingReference,
            'message'           => 'Your booking request has been submitted successfully! Our event management team will review and confirm availability shortly.'
        ];
    }

    /**
     * Update booking status with reviewer audit information
     */
    public static function updateStatus(
        int $bookingId, 
        string $newStatus, 
        int $reviewerUserId, 
        string $notes = '', 
        string $rejectionReason = ''
    ): bool {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM `bookings` WHERE `id` = :id");
        $stmt->execute([':id' => $bookingId]);
        $booking = $stmt->fetch();
        if (!$booking) return false;

        // If approving or confirming, re-verify availability to prevent conflicts
        if (in_array($newStatus, ['approved', 'confirmed'])) {
            $avail = self::checkHallAvailability(
                (int)$booking['hall_id'],
                $booking['event_date'],
                $booking['start_time'],
                $booking['end_time'],
                $bookingId
            );
            if (!$avail['available']) {
                throw new Exception("Conflict detected: " . $avail['message']);
            }
        }

        $sql = "UPDATE `bookings` SET 
                    `booking_status` = :status,
                    `reviewed_by`    = :reviewer,
                    `review_notes`   = :notes,
                    `rejection_reason` = :reason,
                    `updated_at`     = NOW()
                WHERE `id` = :id";
                
        $upStmt = $pdo->prepare($sql);
        return $upStmt->execute([
            ':status'   => $newStatus,
            ':reviewer' => $reviewerUserId,
            ':notes'    => $notes,
            ':reason'   => $rejectionReason,
            ':id'       => $bookingId
        ]);
    }
}
