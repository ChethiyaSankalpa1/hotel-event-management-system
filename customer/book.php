<?php
/**
 * Customer Event Booking Request Form
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/BookingService.php';

require_role(ROLE_CUSTOMER);

$pdo = Database::getConnection();
$user = current_user();
$customerId = (int)($user['entity_id'] ?? 0);
$settings = get_system_settings();

// Fetch active halls and packages
$halls = $pdo->query("SELECT * FROM `function_halls` WHERE `status` = 'active' ORDER BY `capacity_max` DESC")->fetchAll();
$packages = $pdo->query("SELECT * FROM `event_packages` WHERE `status` = 'active' ORDER BY `price_per_person` DESC")->fetchAll();

$error = null;
$selectedHallId    = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : ($halls[0]['id'] ?? 0);
$selectedPackageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : ($packages[0]['id'] ?? 0);
$eventDate         = $_GET['date'] ?? date('Y-m-d', strtotime('+7 days'));
$startTime         = $_GET['start'] ?? '16:00';
$endTime           = $_GET['end'] ?? '22:00';
$guestCount        = (int)($_POST['guest_count'] ?? 100);
$eventType         = $_POST['event_type'] ?? 'Wedding Reception';
$specialRequests   = $_POST['special_requests'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security validation failed (CSRF token mismatch). Please retry.';
    } else {
        $selectedHallId    = (int)($_POST['hall_id'] ?? 0);
        $selectedPackageId = (int)($_POST['package_id'] ?? 0);
        $eventDate         = $_POST['event_date'] ?? '';
        $startTime         = $_POST['start_time'] ?? '';
        $endTime           = $_POST['end_time'] ?? '';
        $guestCount        = (int)($_POST['guest_count'] ?? 0);
        $eventType         = trim($_POST['event_type'] ?? '');
        $specialRequests   = trim($_POST['special_requests'] ?? '');

        // Validations
        if (!$selectedHallId || !$selectedPackageId || !$eventDate || !$startTime || !$endTime || $guestCount <= 0 || empty($eventType)) {
            $error = 'Please fill in all required booking fields.';
        } elseif (strtotime($eventDate) < strtotime(date('Y-m-d'))) {
            $error = 'Event date cannot be in the past.';
        } elseif (strtotime($endTime) <= strtotime($startTime)) {
            $error = 'End time must be later than start time.';
        } else {
            // Find hall to validate capacity
            $hallKey = array_search($selectedHallId, array_column($halls, 'id'));
            if ($hallKey !== false) {
                $chosenHall = $halls[$hallKey];
                if ($guestCount > (int)$chosenHall['capacity_max']) {
                    $error = sprintf('The selected hall "%s" has a maximum capacity of %d guests. Your count is %d.', 
                        $chosenHall['hall_name'], $chosenHall['capacity_max'], $guestCount);
                }
            }

            if (!$error) {
                $result = BookingService::createBooking([
                    'customer_id'      => $customerId,
                    'hall_id'          => $selectedHallId,
                    'package_id'       => $selectedPackageId,
                    'event_date'       => $eventDate,
                    'start_time'       => $startTime,
                    'end_time'         => $endTime,
                    'guest_count'      => $guestCount,
                    'event_type'       => $eventType,
                    'special_requests' => $specialRequests
                ]);

                if ($result['success']) {
                    set_flash('success', $result['message']);
                    header('Location: ' . BASE_URL . 'customer/booking_detail.php?id=' . $result['booking_id']);
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

$pageTitle = 'Reserve an Event Hall';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="customer-hero">
    <div class="container">
        <span class="badge-portal">Reservation System</span>
        <h2 class="luxury-heading mb-0 mt-1">Book an Event Hall</h2>
        <p class="text-white-50 small mb-0">Select your venue, catering package, schedule, and guest count</p>
    </div>
</div>

<div class="container py-4">
    <?php require __DIR__ . '/../includes/alerts.php'; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="book.php" id="eventBookingForm">
        <?= csrf_field() ?>

        <div class="row g-4">
            <!-- Left Column: Booking Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-3 mb-4 p-4 bg-white">
                    <h5 class="fw-bold text-navy mb-3 border-bottom pb-2" style="color:#0b1a30;">
                        <i class="bi bi-geo-alt-fill text-gold me-2"></i> 1. Select Venue &amp; Event Type
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Function Hall <span class="text-danger">*</span></label>
                            <select name="hall_id" id="bookingHall" class="form-select" required>
                                <?php foreach ($halls as $h): ?>
                                    <option value="<?= $h['id'] ?>" <?= $selectedHallId === (int)$h['id'] ? 'selected' : '' ?>>
                                        <?= e($h['hall_name']) ?> (Max <?= number_format($h['capacity_max']) ?> Guests - <?= format_currency($h['price_per_hour']) ?>/hr)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Occasion / Event Type <span class="text-danger">*</span></label>
                            <select name="event_type" class="form-select" required>
                                <option value="Wedding Reception" <?= $eventType === 'Wedding Reception' ? 'selected' : '' ?>>Wedding Reception</option>
                                <option value="Corporate Conference / Summit" <?= $eventType === 'Corporate Conference / Summit' ? 'selected' : '' ?>>Corporate Conference / Summit</option>
                                <option value="Milestone Birthday Party" <?= $eventType === 'Milestone Birthday Party' ? 'selected' : '' ?>>Milestone Birthday Party</option>
                                <option value="Anniversary Banquet" <?= $eventType === 'Anniversary Banquet' ? 'selected' : '' ?>>Anniversary Banquet</option>
                                <option value="Cocktail Soirée & Networking" <?= $eventType === 'Cocktail Soirée & Networking' ? 'selected' : '' ?>>Cocktail Soirée & Networking</option>
                                <option value="Product Launch / Gala Dinner" <?= $eventType === 'Product Launch / Gala Dinner' ? 'selected' : '' ?>>Product Launch / Gala Dinner</option>
                                <option value="Other Special Celebration" <?= $eventType === 'Other Special Celebration' ? 'selected' : '' ?>>Other Special Celebration</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold text-navy mb-3 border-bottom pb-2" style="color:#0b1a30;">
                        <i class="bi bi-clock-history text-gold me-2"></i> 2. Date &amp; Time Scheduling
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Event Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="bookingDate" class="form-control" 
                                   min="<?= date('Y-m-d') ?>" value="<?= e($eventDate) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="bookingStartTime" class="form-control" 
                                   value="<?= e($startTime) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="bookingEndTime" class="form-control" 
                                   value="<?= e($endTime) ?>" required>
                        </div>

                        <!-- Live Availability Verification Badge -->
                        <div class="col-12">
                            <div id="availabilityStatusBadge" class="mt-1"></div>
                        </div>
                    </div>

                    <h5 class="fw-bold text-navy mb-3 border-bottom pb-2" style="color:#0b1a30;">
                        <i class="bi bi-cup-straw text-gold me-2"></i> 3. Package &amp; Guest Capacity
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Event Package <span class="text-danger">*</span></label>
                            <select name="package_id" id="bookingPackage" class="form-select" required>
                                <?php foreach ($packages as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $selectedPackageId === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['package_name']) ?> (<?= format_currency($p['price_per_person']) ?> / person)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Estimated Guests <span class="text-danger">*</span></label>
                            <input type="number" name="guest_count" id="bookingGuests" class="form-control" 
                                   min="10" max="1000" value="<?= e((string)$guestCount) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Special Requests & Inquiries</label>
                            <textarea name="special_requests" class="form-control" rows="3" 
                                      placeholder="Dietary requirements, thematic colors, AV setup, audio-visual needs, early suite access..."><?= e($specialRequests) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Pricing Quotation & Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-3 p-4 bg-white sticky-top" style="top: 90px;">
                    <h5 class="luxury-heading mb-1" style="font-size: 1.3rem;">Quotation Summary</h5>
                    <p class="text-muted small mb-3">Live calculated price for your event</p>

                    <ul class="list-group list-group-flush small mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Function Hall Rental:</span>
                            <strong id="calcHallCharge">LKR 0.00</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Catering &amp; Package:</span>
                            <strong id="calcPackageCharge">LKR 0.00</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Taxes &amp; Service (<?= $settings['tax_percentage'] ?>%):</span>
                            <strong id="calcTaxCharge">LKR 0.00</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-3 border-top">
                            <span class="fs-6 fw-bold text-dark">Estimated Total:</span>
                            <span class="fs-5 fw-bold text-navy" id="calcTotalCharge" style="color:#0b1a30;">LKR 0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 bg-light p-2 rounded">
                            <span class="fw-semibold text-warning-emphasis">Advance Due (<?= $settings['advance_percentage'] ?>%):</span>
                            <strong class="text-warning-emphasis" id="calcAdvanceCharge">LKR 0.00</strong>
                        </li>
                    </ul>

                    <div class="small text-muted mb-4">
                        <i class="bi bi-info-circle me-1"></i> Submitting this request locks the time slot pending banquet director confirmation. You will be prompted to deposit the <?= $settings['advance_percentage'] ?>% advance upon review.
                    </div>

                    <div class="d-grid">
                        <button type="submit" id="submitBookingBtn" class="btn btn-gold btn-lg py-2 fw-bold shadow-sm">
                            <i class="bi bi-send-check me-2"></i> Submit Booking Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
