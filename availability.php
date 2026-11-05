<?php
/**
 * Public Hall Availability Calendar & Real-Time Conflict Checker
 * Hotel Event Management System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getConnection();

// Fetch all active halls for dropdown filter
$halls = $pdo->query("SELECT id, hall_name, hall_code, capacity_max FROM `function_halls` WHERE `status` = 'active' ORDER BY `hall_name` ASC")->fetchAll();

$selectedHallId = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;

$pageTitle = 'Hall Availability Calendar';
$includeCalendarJs = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 bg-navy text-white text-center" style="background:#0b1a30; border-bottom: 3px solid #c5a880;">
    <div class="container py-3">
        <span class="text-warning text-uppercase fw-bold small" style="letter-spacing: 2px;">Live Schedule & Conflict Prevention</span>
        <h1 class="luxury-heading text-white mt-1 mb-2">Hall Availability Calendar</h1>
        <p class="text-white-50 mx-auto mb-0" style="max-width: 650px;">
            Check event schedules across our function halls in real time. We strictly enforce a single-event policy per time window to guarantee dedicated service.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Quick Checker Tool -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-navy text-white py-3" style="background:#0b1a30;">
                    <h5 class="mb-0 fs-6 fw-bold text-white"><i class="bi bi-search me-2 text-warning"></i> Quick Availability Lookup</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Test if your desired venue is open for your preferred date and time range:
                    </p>

                    <form id="quickCheckForm" onsubmit="event.preventDefault(); runQuickCheck();">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Target Venue</label>
                            <select id="qcHall" class="form-select form-select-sm" required>
                                <option value="">Select a hall...</option>
                                <?php foreach ($halls as $h): ?>
                                    <option value="<?= $h['id'] ?>" <?= $selectedHallId === (int)$h['id'] ? 'selected' : '' ?>>
                                        <?= e($h['hall_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Event Date</label>
                            <input type="date" id="qcDate" class="form-control form-select-sm" 
                                   min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Start Time</label>
                                <input type="time" id="qcStart" class="form-control form-select-sm" value="10:00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">End Time</label>
                                <input type="time" id="qcEnd" class="form-control form-select-sm" value="16:00" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-navy btn-sm w-100 py-2">
                            <i class="bi bi-clock-history me-1"></i> Verify Time Slot
                        </button>

                        <div id="quickCheckResult" class="mt-3"></div>
                    </form>
                </div>
            </div>

            <!-- Calendar Legend -->
            <div class="card shadow-sm border-0 rounded-3 p-4">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">Schedule Color Key</h6>
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex align-items-center">
                        <span class="legend-dot legend-confirmed"></span>
                        <strong class="text-dark me-2">Green:</strong> Confirmed & Booked Event
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="legend-dot legend-approved"></span>
                        <strong class="text-dark me-2">Blue:</strong> Approved (Awaiting Advance)
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="legend-dot legend-pending"></span>
                        <strong class="text-dark me-2">Amber:</strong> Booking Request Under Review
                    </div>
                </div>

                <hr class="my-3">
                <div class="text-center">
                    <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold btn-sm w-100">
                        <i class="bi bi-calendar-plus me-1"></i> Book an Open Date Now
                    </a>
                </div>
            </div>
        </div>

        <!-- Interactive FullCalendar View -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                    <div>
                        <h4 class="luxury-heading mb-1" style="font-size: 1.4rem;">Banquet Master Schedule</h4>
                        <p class="text-muted small mb-0">Showing events across all halls or by selected venue</p>
                    </div>
                    <div style="min-width: 240px;">
                        <label class="form-label small fw-semibold text-muted d-block mb-1">Filter Calendar By Hall:</label>
                        <select id="calendarHallFilter" class="form-select form-select-sm">
                            <option value="">All Function Halls (Consolidated)</option>
                            <?php foreach ($halls as $h): ?>
                                <option value="<?= $h['id'] ?>" <?= $selectedHallId === (int)$h['id'] ? 'selected' : '' ?>>
                                    <?= e($h['hall_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Calendar Canvas -->
                <div id="hallScheduleCalendar" style="min-height: 600px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
function runQuickCheck() {
    const hallId = document.getElementById('qcHall').value;
    const date   = document.getElementById('qcDate').value;
    const start  = document.getElementById('qcStart').value;
    const end    = document.getElementById('qcEnd').value;
    const result = document.getElementById('quickCheckResult');

    if (!hallId || !date || !start || !end) {
        result.innerHTML = '<div class="alert alert-warning small p-2 mb-0">Please fill all fields.</div>';
        return;
    }

    result.innerHTML = '<div class="text-muted small"><i class="bi bi-arrow-repeat spin"></i> Checking...</div>';

    fetch(`api/check_availability.php?hall_id=${encodeURIComponent(hallId)}&date=${encodeURIComponent(date)}&start_time=${encodeURIComponent(start)}&end_time=${encodeURIComponent(end)}`)
        .then(res => res.json())
        .then(data => {
            if (data.available) {
                result.innerHTML = `
                    <div class="alert alert-success small p-2 mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i> ${data.message}
                        <div class="mt-2">
                            <a href="customer/book.php?hall_id=${hallId}&date=${date}&start=${start}&end=${end}" class="btn btn-sm btn-success w-100 py-1" style="font-size:0.8rem;">
                                Reserve This Slot <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                `;
            } else {
                result.innerHTML = `
                    <div class="alert alert-danger small p-2 mb-0">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> ${data.message}
                    </div>
                `;
            }
        })
        .catch(err => {
            result.innerHTML = `<div class="alert alert-danger small p-2 mb-0">Request failed: ${err.message}</div>`;
        });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
