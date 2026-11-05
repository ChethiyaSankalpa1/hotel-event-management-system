<?php
/**
 * Payment & Ledger Service
 * Handles Advance Deposits, Balance Settlements, and Receipt Generation
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

class PaymentService {

    /**
     * Record a new payment against a booking and recalculate balances
     */
    public static function recordPayment(array $data): array {
        $pdo = Database::getConnection();

        $bookingId = (int)$data['booking_id'];
        $amount = (float)$data['amount'];
        $paymentType = $data['payment_type'];
        $paymentMethod = $data['payment_method'];
        $transactionRef = $data['transaction_reference'] ?? null;
        $paymentDate = $data['payment_date'] ?? date('Y-m-d');
        $notes = $data['notes'] ?? '';
        $receivedBy = (int)$data['received_by'];

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
        }

        try {
            $pdo->beginTransaction();

            // Fetch current booking financial state with row lock
            $stmt = $pdo->prepare("SELECT * FROM `bookings` WHERE `id` = :id FOR UPDATE");
            $stmt->execute([':id' => $bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Booking record not found.'];
            }

            // Generate unique receipt reference (e.g., RCPT-2026-0812)
            $year = date('Y');
            $receiptNum = sprintf("RCPT-%s-%04d", $year, mt_rand(1000, 9999));
            $chk = $pdo->prepare("SELECT id FROM `payments` WHERE `receipt_number` = ?");
            $chk->execute([$receiptNum]);
            if ($chk->fetch()) {
                $receiptNum = sprintf("RCPT-%s-%05d", $year, mt_rand(10000, 99999));
            }

            // Insert payment ledger entry
            $insertSql = "INSERT INTO `payments` (
                `receipt_number`, `booking_id`, `payment_type`, `amount`,
                `payment_method`, `transaction_reference`, `payment_date`, `notes`,
                `received_by`
            ) VALUES (
                :receipt_number, :booking_id, :payment_type, :amount,
                :payment_method, :transaction_ref, :payment_date, :notes,
                :received_by
            )";

            $pStmt = $pdo->prepare($insertSql);
            $pStmt->execute([
                ':receipt_number' => $receiptNum,
                ':booking_id'     => $bookingId,
                ':payment_type'   => $paymentType,
                ':amount'         => $amount,
                ':payment_method' => $paymentMethod,
                ':transaction_ref'=> $transactionRef,
                ':payment_date'   => $paymentDate,
                ':notes'          => $notes,
                ':received_by'    => $receivedBy
            ]);

            // Re-calculate all payments made for this booking
            $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total_paid FROM `payments` WHERE `booking_id` = :id");
            $sumStmt->execute([':id' => $bookingId]);
            $totalPaid = (float)$sumStmt->fetch()['total_paid'];

            $totalAmount = (float)$booking['total_amount'];
            $advanceRequired = (float)$booking['advance_required'];
            $balanceDue = max(0.00, round($totalAmount - $totalPaid, 2));

            // Determine new payment status
            if ($balanceDue <= 0.00) {
                $paymentStatus = 'fully_paid';
            } elseif ($totalPaid > 0.00) {
                $paymentStatus = 'partially_paid';
            } else {
                $paymentStatus = 'unpaid';
            }

            // Determine if booking status should transition to 'confirmed'
            // If the booking was 'approved' and the customer paid the required advance, automatically confirm it!
            $newBookingStatus = $booking['booking_status'];
            if ($booking['booking_status'] === 'approved' && $totalPaid >= $advanceRequired) {
                $newBookingStatus = 'confirmed';
            }

            // Update booking financial totals
            $upSql = "UPDATE `bookings` SET
                        `advance_paid`   = :paid,
                        `balance_due`    = :balance,
                        `payment_status` = :pstatus,
                        `booking_status` = :bstatus,
                        `updated_at`     = NOW()
                      WHERE `id` = :id";
            $upStmt = $pdo->prepare($upSql);
            $upStmt->execute([
                ':paid'    => $totalPaid,
                ':balance' => $balanceDue,
                ':pstatus' => $paymentStatus,
                ':bstatus' => $newBookingStatus,
                ':id'      => $bookingId
            ]);

            $pdo->commit();

            return [
                'success'        => true,
                'receipt_number' => $receiptNum,
                'total_paid'     => $totalPaid,
                'balance_due'    => $balanceDue,
                'payment_status' => $paymentStatus,
                'booking_status' => $newBookingStatus,
                'message'        => 'Payment of ' . format_currency($amount) . ' has been recorded successfully. Receipt #' . $receiptNum . ' generated.'
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve list of payments for a booking
     */
    public static function getBookingPayments(int $bookingId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, u.email as staff_email, s.full_name as staff_name
            FROM `payments` p
            LEFT JOIN `users` u ON p.received_by = u.id
            LEFT JOIN `staff` s ON s.user_id = u.id
            WHERE p.booking_id = :id
            ORDER BY p.payment_date DESC, p.id DESC
        ");
        $stmt->execute([':id' => $bookingId]);
        return $stmt->fetchAll();
    }
}
