<?php
/**
 * Staff Interactive Banquet Schedule Calendar
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_STAFF, ROLE_ADMIN]);

$pdo = Database::getConnection();
$halls = $pdo->query("SELECT id, hall_name FROM `function_halls` WHERE `status` = 'active' ORDER BY `hall_name` ASC")->fetchAll();

$pageTitle = 'Banquet Schedule Calendar';
$includeCalendarJs = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sidebar-toggle d-lg-none" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">Master Banquet Schedule</h4>
                    <small class="text-muted d-none d-sm-inline">Real-time calendar view of reserved and pending function spaces</small>
                </div>
            </div>
            <div style="min-width: 200px;" class="w-auto">
                <select id="calendarHallFilter" class="form-select form-select-sm">
                    <option value="">All Function Halls (Unified)</option>
                    <?php foreach ($halls as $h): ?>
                        <option value="<?= $h['id'] ?>"><?= e($h['hall_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </header>

        <div class="content-body">
            <!-- Calendar Legend -->
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 small">
                        <span class="text-muted fw-bold">Status Legend:</span>
                        <div><span class="legend-dot legend-confirmed"></span> Confirmed Event</div>
                        <div><span class="legend-dot legend-approved"></span> Approved (Awaiting Advance)</div>
                        <div><span class="legend-dot legend-pending"></span> Pending Request</div>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>staff/bookings.php" class="btn btn-navy btn-sm">
                            <i class="bi bi-inbox me-1"></i> Review Booking Queue
                        </a>
                    </div>
                </div>
            </div>

            <!-- FullCalendar Container -->
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <div id="hallScheduleCalendar" style="min-height: 650px;"></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
