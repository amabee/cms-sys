$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Load dashboard data
    loadDashboardStats();
    loadTodayAppointments();
});

/**
 * Check if user is authenticated and has receptionist role
 */
function checkAuth() {
    $.ajax({
        url: '../ajax/check_session.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!response.success || response.user_type !== 'receptionist') {
                window.location.href = '../login-new.html';
                return;
            }
            
            // Update UI with user info
            const fullName = response.first_name && response.last_name 
                ? `${response.first_name} ${response.last_name}`
                : response.username;
            $('#receptionistName').text(fullName);
        },
        error: function() {
            window.location.href = '../login-new.html';
        }
    });
}

/**
 * Load dashboard statistics
 */
function loadDashboardStats() {
    $.ajax({
        url: '../ajax/get_receptionist_statistics.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const stats = response.data;
                
                // Update stats cards
                $('#todayAppointments').text(stats.today_appointments || 0);
                $('#patientsInQueue').text(stats.patients_in_queue || 0);
                $('#totalPatients').text(stats.total_patients || 0);
                $('#pendingPayments').text(stats.pending_payments || 0);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading dashboard stats:', error);
            // Set default values on error
            $('#todayAppointments').text('--');
            $('#patientsInQueue').text('--');
            $('#totalPatients').text('--');
            $('#pendingPayments').text('--');
        }
    });
}

/**
 * Load today's appointments
 */
function loadTodayAppointments() {
    $.ajax({
        url: '../ajax/get_appointments.php',
        type: 'GET',
        data: {
            date: new Date().toISOString().split('T')[0],
            limit: 10
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayTodayAppointments(response.data);
            } else {
                showNoAppointments();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading appointments:', error);
            showErrorMessage();
        }
    });
}

/**
 * Display today's appointments in a table
 */
function displayTodayAppointments(appointments) {
    const container = $('#todayAppointmentsContainer');
    
    if (!appointments || appointments.length === 0) {
        showNoAppointments();
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    appointments.forEach(function(appointment) {
        const statusClass = getStatusClass(appointment.status);
        const statusText = capitalize(appointment.status);
        
        html += `
            <tr>
                <td><strong>${formatTime(appointment.appointment_time)}</strong></td>
                <td>
                    <div>${escapeHtml(appointment.patient_name || 'N/A')}</div>
                    <small class="text-muted">${escapeHtml(appointment.patient_phone || '')}</small>
                </td>
                <td>${escapeHtml(appointment.doctor_name || 'N/A')}</td>
                <td>${escapeHtml(appointment.reason || 'General checkup')}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewAppointment(${appointment.id})">
                                <i class="bx bx-show me-1"></i> View Details
                            </a>
                            ${appointment.status === 'scheduled' ? `
                                <a class="dropdown-item" href="javascript:void(0);" onclick="checkInPatient(${appointment.id})">
                                    <i class="bx bx-check me-1"></i> Check In
                                </a>
                            ` : ''}
                            ${appointment.status !== 'completed' && appointment.status !== 'cancelled' ? `
                                <a class="dropdown-item" href="javascript:void(0);" onclick="rescheduleAppointment(${appointment.id})">
                                    <i class="bx bx-calendar-edit me-1"></i> Reschedule
                                </a>
                                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="cancelAppointment(${appointment.id})">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                            ` : ''}
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.html(html);
}

/**
 * Show message when no appointments
 */
function showNoAppointments() {
    $('#todayAppointmentsContainer').html(`
        <div class="text-center py-5">
            <i class="bx bx-calendar-x" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">No appointments scheduled for today</p>
        </div>
    `);
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#todayAppointmentsContainer').html(`
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error me-2"></i>
            Failed to load appointments. Please try refreshing the page.
        </div>
    `);
}

/**
 * Get Bootstrap class for appointment status
 */
function getStatusClass(status) {
    const statusClasses = {
        'scheduled': 'bg-label-primary',
        'confirmed': 'bg-label-info',
        'in-progress': 'bg-label-warning',
        'completed': 'bg-label-success',
        'cancelled': 'bg-label-danger',
        'no-show': 'bg-label-secondary'
    };
    return statusClasses[status] || 'bg-label-secondary';
}

/**
 * View appointment details (placeholder)
 */
function viewAppointment(appointmentId) {
    // Redirect to appointments page with ID
    window.location.href = `receptionist-appointments.html?id=${appointmentId}`;
}

/**
 * Check in patient (placeholder)
 */
function checkInPatient(appointmentId) {
    if (!confirm('Check in this patient?')) return;
    
    $.ajax({
        url: '../ajax/update_appointment_status.php',
        type: 'POST',
        data: {
            appointment_id: appointmentId,
            status: 'in-progress'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Add to queue
                addToQueue(appointmentId);
            } else {
                alert(response.message || 'Failed to check in patient');
            }
        },
        error: function() {
            alert('Failed to check in patient. Please try again.');
        }
    });
}

/**
 * Add patient to queue
 */
function addToQueue(appointmentId) {
    $.ajax({
        url: '../ajax/add_to_queue.php',
        type: 'POST',
        data: {
            appointment_id: appointmentId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Patient checked in and added to queue');
                loadDashboardStats();
                loadTodayAppointments();
            } else {
                alert(response.message || 'Failed to add to queue');
            }
        },
        error: function() {
            alert('Failed to add patient to queue');
        }
    });
}

/**
 * Reschedule appointment (placeholder)
 */
function rescheduleAppointment(appointmentId) {
    window.location.href = `receptionist-appointments.html?action=reschedule&id=${appointmentId}`;
}

/**
 * Cancel appointment
 */
function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) return;
    
    $.ajax({
        url: '../ajax/update_appointment_status.php',
        type: 'POST',
        data: {
            appointment_id: appointmentId,
            status: 'cancelled'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Appointment cancelled successfully');
                loadDashboardStats();
                loadTodayAppointments();
            } else {
                alert(response.message || 'Failed to cancel appointment');
            }
        },
        error: function() {
            alert('Failed to cancel appointment. Please try again.');
        }
    });
}

// ===== Helper Functions =====

/**
 * Format time (HH:MM:SS to HH:MM AM/PM)
 */
function formatTime(time) {
    if (!time) return 'N/A';
    
    try {
        const parts = time.split(':');
        let hours = parseInt(parts[0]);
        const minutes = parts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    } catch (e) {
        return time;
    }
}

/**
 * Capitalize first letter
 */
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
