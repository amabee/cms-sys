/**
 * Doctor Dashboard - AJAX Implementation
 */

$(document).ready(function() {
    // Wait for layout to load before initializing
    waitForLayout().then(function() {
        initializeDashboard();
    });
});

/**
 * Wait for layout to be loaded
 */
function waitForLayout() {
    return new Promise(function(resolve) {
        if (window.layoutLoaded && window.currentUser) {
            resolve();
        } else {
            // Poll every 100ms until layout is loaded
            var checkInterval = setInterval(function() {
                if (window.layoutLoaded && window.currentUser) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);
        }
    });
}

/**
 * Initialize dashboard after layout is ready
 */
function initializeDashboard() {
    // Update doctor name
    if (window.currentUser) {
        const name = window.currentUser.last_name 
            ? 'Dr. ' + window.currentUser.last_name 
            : window.currentUser.username;
        $('#doctorName').text(name);
    }
    
    // Load dashboard data
    loadDashboardStats();
    loadUpcomingAppointments();
}

/**
 * Load dashboard statistics
 */
function loadDashboardStats() {
    $.ajax({
        url: '../ajax/get_doctor_statistics.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#todayAppointments').text(response.data.today_appointments || 0);
                $('#totalPatients').text(response.data.total_patients || 0);
                $('#pendingActions').text(response.data.pending_actions || 0);
            }
        },
        error: function() {
            console.error('Failed to load dashboard statistics');
        }
    });
}

/**
 * Load upcoming appointments
 */
function loadUpcomingAppointments() {
    $.ajax({
        url: '../ajax/get_doctor_appointments.php',
        method: 'GET',
        data: { limit: 5, upcoming: true },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                displayUpcomingAppointments(response.data);
            } else {
                showNoAppointments();
            }
        },
        error: function() {
            showNoAppointments();
        }
    });
}

/**
 * Display upcoming appointments in table
 */
function displayUpcomingAppointments(appointments) {
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Patient ID</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        appointments.forEach(function(apt) {
            const statusColors = {
                'scheduled': 'info',
                'in_progress': 'warning',
                'completed': 'success',
                'cancelled': 'danger',
                'no_show': 'secondary'
            };
            const statusColor = statusColors[apt.status] || 'secondary';
            
            html += `
                <tr>
                    <td>
                        <strong>${formatDate(apt.appointment_date)}</strong><br>
                        <small class="text-muted">${formatTime(apt.appointment_time)}</small>
                    </td>
                    <td>${escapeHtml(apt.patient_name || apt.first_name + ' ' + apt.last_name)}</td>
                    <td>${escapeHtml(apt.patient_number || apt.patient_id)}</td>
                    <td>${escapeHtml(apt.reason || 'General Consultation')}</td>
                    <td>
                        <span class="badge bg-label-${statusColor}">${capitalize(apt.status)}</span>
                    </td>
                    <td>
                        <a href="doctor-appointments.html?id=${apt.id}" class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-show"></i> View
                        </a>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
    $('#upcomingAppointmentsContainer').html(html);
}

/**
 * Show no appointments message
 */
function showNoAppointments() {
    $('#upcomingAppointmentsContainer').html(`
        <div class="text-center py-4">
            <i class="bx bx-calendar-x display-4 text-muted"></i>
            <p class="text-muted mt-2">No upcoming appointments</p>
        </div>
    `);
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Format time
 */
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

/**
 * Capitalize first letter
 */
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

