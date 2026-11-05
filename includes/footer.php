<?php
/**
 * Shared Footer Component
 * Hotel Event Management System
 */

$settings = get_system_settings();
?>
<footer class="bg-navy text-white pt-5 pb-4 mt-auto" style="background-color: #081426; border-top: 3px solid #c5a880;">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="luxury-heading text-white mb-3" style="font-size: 1.35rem;">
                    <i class="bi bi-buildings text-warning me-2"></i><?= e($settings['hotel_name']) ?>
                </h5>
                <p class="text-white-50 small mb-3">
                    <?= e($settings['hotel_tagline']) ?>. We specialize in making your wedding receptions, corporate conferences, and private banquets unforgettable.
                </p>
                <div class="text-white-50 small">
                    <p class="mb-1"><i class="bi bi-geo-alt text-warning me-2"></i> <?= e($settings['address']) ?>, <?= e($settings['city_state_zip']) ?></p>
                    <p class="mb-1"><i class="bi bi-telephone text-warning me-2"></i> <?= e($settings['phone']) ?></p>
                    <p class="mb-0"><i class="bi bi-envelope text-warning me-2"></i> <?= e($settings['email']) ?></p>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase fw-bold text-warning small mb-3" style="letter-spacing: 1.5px;">Navigation</h6>
                <ul class="list-unstyled text-white-50 small mb-0">
                    <li class="mb-2"><a href="<?= BASE_URL ?>index.php" class="text-white-50 text-decoration-none hover-gold">Home</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>halls.php" class="text-white-50 text-decoration-none hover-gold">Function Halls</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>packages.php" class="text-white-50 text-decoration-none hover-gold">Event Packages</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>availability.php" class="text-white-50 text-decoration-none hover-gold">Live Calendar</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>customer/book.php" class="text-white-50 text-decoration-none hover-gold">Reserve a Hall</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-uppercase fw-bold text-warning small mb-3" style="letter-spacing: 1.5px;">Portals & Access</h6>
                <ul class="list-unstyled text-white-50 small mb-0">
                    <li class="mb-2"><a href="<?= BASE_URL ?>login.php" class="text-white-50 text-decoration-none hover-gold">Customer Login</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>register.php" class="text-white-50 text-decoration-none hover-gold">Create Account</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>staff/dashboard.php" class="text-white-50 text-decoration-none hover-gold">Staff Workspace</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>admin/dashboard.php" class="text-white-50 text-decoration-none hover-gold">Administration</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>install.php" class="text-white-50 text-decoration-none hover-gold">System Installer</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-uppercase fw-bold text-warning small mb-3" style="letter-spacing: 1.5px;">Reservation Hours</h6>
                <p class="text-white-50 small mb-2">
                    Our event planning and banquet coordination office is open daily:
                </p>
                <ul class="list-unstyled text-white-50 small mb-3">
                    <li><strong class="text-white">Monday - Friday:</strong> 08:00 AM - 09:00 PM</li>
                    <li><strong class="text-white">Saturday - Sunday:</strong> 09:00 AM - 10:00 PM</li>
                </ul>
                <a href="<?= BASE_URL ?>customer/book.php" class="btn btn-gold btn-sm w-100">
                    <i class="bi bi-calendar-check me-1"></i> Request a Booking
                </a>
            </div>
        </div>

        <hr class="border-secondary my-4 opacity-25">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-white-50">
            <div>
                &copy; <?= date('Y') ?> <?= e($settings['hotel_name']) ?>. Academic Project Implementation.
            </div>
            <div class="mt-2 mt-md-0">
                <span class="badge bg-secondary opacity-75">PHP 8.x & MySQL</span>
                <span class="badge bg-secondary opacity-75 ms-1">Bootstrap 5.3</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- FullCalendar Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<!-- Custom App Scripts -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

<?php if (isset($includeCalendarJs) && $includeCalendarJs): ?>
    <script src="<?= BASE_URL ?>assets/js/calendar.js"></script>
<?php endif; ?>

</body>
</html>
