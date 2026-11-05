/**
 * The Grand Horizon Hotel & Banquet - Master Client-side JavaScript
 * Handles Dynamic Availability Checking, Pricing Calculation, and UI Helpers
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Auto-dismiss alerts after 6 seconds
    const autoAlerts = document.querySelectorAll('.alert-dismissible');
    autoAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 6000);
    });

    // -------------------------------------------------------------
    // DYNAMIC BOOKING CALCULATOR & CONFLICT CHECKER
    // -------------------------------------------------------------
    const bookingForm = document.getElementById('eventBookingForm');
    if (bookingForm) {
        const hallSelect    = document.getElementById('bookingHall');
        const packageSelect = document.getElementById('bookingPackage');
        const guestInput    = document.getElementById('bookingGuests');
        const dateInput     = document.getElementById('bookingDate');
        const startTimeInput= document.getElementById('bookingStartTime');
        const endTimeInput  = document.getElementById('bookingEndTime');
        const availBadge    = document.getElementById('availabilityStatusBadge');
        const submitBtn     = document.getElementById('submitBookingBtn');

        // Price summary elements
        const hallChargeSpan    = document.getElementById('calcHallCharge');
        const packageChargeSpan = document.getElementById('calcPackageCharge');
        const taxChargeSpan     = document.getElementById('calcTaxCharge');
        const totalChargeSpan   = document.getElementById('calcTotalCharge');
        const advanceChargeSpan = document.getElementById('calcAdvanceCharge');

        function triggerCalculationAndAvailability() {
            const hallId    = hallSelect ? hallSelect.value : '';
            const packageId = packageSelect ? packageSelect.value : '';
            const guests    = guestInput ? guestInput.value : '';
            const date      = dateInput ? dateInput.value : '';
            const start     = startTimeInput ? startTimeInput.value : '';
            const end       = endTimeInput ? endTimeInput.value : '';

            // Check Availability if Hall, Date, and Times are selected
            if (hallId && date && start && end && availBadge) {
                availBadge.innerHTML = '<span class="text-muted"><i class="bi bi-arrow-repeat spin"></i> Checking venue availability...</span>';
                
                fetch(`${window.BASE_URL || ''}api/check_availability.php?hall_id=${encodeURIComponent(hallId)}&date=${encodeURIComponent(date)}&start_time=${encodeURIComponent(start)}&end_time=${encodeURIComponent(end)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            availBadge.innerHTML = `<span class="badge bg-success-subtle text-success border border-success p-2 d-inline-block"><i class="bi bi-check-circle-fill me-1"></i> ${data.message}</span>`;
                            if (submitBtn) submitBtn.disabled = false;
                        } else {
                            availBadge.innerHTML = `<span class="badge bg-danger-subtle text-danger border border-danger p-2 d-inline-block"><i class="bi bi-x-circle-fill me-1"></i> ${data.message}</span>`;
                            if (submitBtn) submitBtn.disabled = true;
                        }
                    })
                    .catch(err => {
                        console.error('Availability check error:', err);
                        availBadge.innerHTML = '';
                    });
            } else if (availBadge) {
                availBadge.innerHTML = '';
                if (submitBtn) submitBtn.disabled = false;
            }

            // Calculate live pricing if Hall, Package, Guests, and Times are present
            if (hallId && packageId && guests && start && end && hallChargeSpan) {
                fetch(`${window.BASE_URL || ''}api/calculate_price.php?hall_id=${encodeURIComponent(hallId)}&package_id=${encodeURIComponent(packageId)}&guests=${encodeURIComponent(guests)}&start_time=${encodeURIComponent(start)}&end_time=${encodeURIComponent(end)}`)
                    .then(res => res.json())
                    .then(calc => {
                        if (calc.success) {
                            const d = calc.data;
                            if (hallChargeSpan)    hallChargeSpan.textContent    = d.formatted_hall_charge;
                            if (packageChargeSpan) packageChargeSpan.textContent = d.formatted_package_charge;
                            if (taxChargeSpan)     taxChargeSpan.textContent     = d.formatted_tax_amount;
                            if (totalChargeSpan)   totalChargeSpan.textContent   = d.formatted_total_amount;
                            if (advanceChargeSpan) advanceChargeSpan.textContent = d.formatted_advance_required;
                        }
                    })
                    .catch(err => console.error('Price calculation error:', err));
            }
        }

        // Attach listeners
        [hallSelect, packageSelect, guestInput, dateInput, startTimeInput, endTimeInput].forEach(elem => {
            if (elem) {
                elem.addEventListener('change', triggerCalculationAndAvailability);
                elem.addEventListener('input', triggerCalculationAndAvailability);
            }
        });

        // Trigger on initial page load if values are prefilled
        triggerCalculationAndAvailability();
    }
});
