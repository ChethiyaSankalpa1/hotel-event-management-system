/**
 * Interactive Hall Availability & Events Calendar
 * Hotel Event Management System
 */

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('hallScheduleCalendar');
    if (!calendarEl) return;

    const hallFilter = document.getElementById('calendarHallFilter');

    function initCalendar() {
        if (typeof FullCalendar === 'undefined') {
            calendarEl.innerHTML = '<div class="alert alert-info">Calendar library loading...</div>';
            return;
        }

        const hallId = hallFilter ? hallFilter.value : '';
        const eventFeedUrl = `${window.BASE_URL || ''}api/get_hall_events.php?hall_id=${encodeURIComponent(hallId)}`;

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            themeSystem: 'bootstrap5',
            events: eventFeedUrl,
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
            eventClick: function (info) {
                const props = info.event.extendedProps;
                let modalHtml = `
                    <div class="modal fade" id="eventDetailModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-navy text-white" style="background:#0b1a30; color:#fff;">
                                    <h5 class="modal-title">${info.event.title}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-2"><strong>Venue:</strong> ${props.hall_name || 'N/A'}</p>
                                    <p class="mb-2"><strong>Event Type:</strong> ${props.event_type || 'Reserved Event'}</p>
                                    <p class="mb-2"><strong>Time:</strong> ${info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${info.event.end ? info.event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'N/A'}</p>
                                    <p class="mb-0"><strong>Status:</strong> <span class="badge bg-${props.badge_color || 'primary'}">${props.status_label || 'Booked'}</span></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if any
                const oldModal = document.getElementById('eventDetailModal');
                if (oldModal) oldModal.remove();

                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const bsModal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
                bsModal.show();
            }
        });

        calendar.render();

        if (hallFilter) {
            hallFilter.addEventListener('change', function () {
                calendar.removeAllEventSources();
                const newHallId = hallFilter.value;
                calendar.addEventSource(`${window.BASE_URL || ''}api/get_hall_events.php?hall_id=${encodeURIComponent(newHallId)}`);
            });
        }
    }

    initCalendar();
});
