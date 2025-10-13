// Patient Portal JavaScript
let currentPatientId = window.currentPatientId || null;
let isAdminView = window.isAdminView || false;

// Initialize patient portal
$(document).ready(function() {
    if (!currentPatientId) {
        showAlert('Patient ID not found', 'danger');
        return;
    }
    
    // Load initial dashboard data
    loadDashboardData();
    
    // Set up tab switching handlers
    $('#portalTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target).attr('data-bs-target').substring(1);
        
        switch(targetTab) {
            case 'appointments':
                loadAppointments();
                break;
            case 'records':
                loadMedicalRecords();
                break;
            case 'lab-results':
                loadLabResults();
                break;
            case 'billing':
                loadBillingInfo();
                break;
            case 'messages':
                loadMessages();
                break;
        }
    });
    
    // Auto-refresh dashboard every 5 minutes
    setInterval(function() {
        if ($('#dashboard-tab').hasClass('active')) {
            loadDashboardData(true); // Silent refresh
        }
    }, 300000);
    
    // Set up real-time notifications check
    setInterval(checkUnreadMessages, 60000); // Every minute
});

// Load dashboard data
function loadDashboardData(silent = false) {
    if (!silent) {
        showLoadingStates();
    }
    
    $.ajax({
        url: '../ajax/get_patient_dashboard.php',
        type: 'GET',
        data: { patient_id: currentPatientId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateDashboardStats(response.data);
                displayUpcomingAppointments(response.data.upcoming_appointments);
                displayRecentMessages(response.data.recent_messages);
                displayHealthGoals(response.data.active_health_goals);
                displayRecentLabResults(response.data.recent_lab_results);
                updateWelcomeMessage(response.data.patient_info);
            } else {
                showAlert('Error loading dashboard: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to load dashboard data', 'danger');
        },
        complete: function() {
            hideLoadingStates();
        }
    });
}

// Update dashboard statistics
function updateDashboardStats(data) {
    $('#upcoming-appointments-count').text(data.upcoming_appointments ? data.upcoming_appointments.length : 0);
    $('#unread-messages-stat').text(data.unread_messages || 0);
    $('#recent-lab-results-count').text(data.recent_lab_results ? data.recent_lab_results.length : 0);
    $('#outstanding-bills-amount').text(formatCurrency(data.total_outstanding || 0));
    
    // Update unread messages badge
    const unreadCount = data.unread_messages || 0;
    if (unreadCount > 0) {
        $('#unread-messages-count').text(unreadCount).show();
    } else {
        $('#unread-messages-count').hide();
    }
}

// Display upcoming appointments
function displayUpcomingAppointments(appointments) {
    const container = $('#upcoming-appointments-list');
    container.empty();
    
    if (!appointments || appointments.length === 0) {
        container.html(`
            <div class="text-center py-4 text-muted">
                <i class="bx bx-calendar-x display-4"></i>
                <p class="mt-2">No upcoming appointments</p>
                <button class="btn btn-sm btn-primary" onclick="requestAppointment()">
                    Request Appointment
                </button>
            </div>
        `);
        return;
    }
    
    appointments.forEach(appointment => {
        const card = `
            <div class="appointment-card upcoming p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${escapeHtml(appointment.appointment_type || 'Consultation')}</h6>
                        <p class="mb-1 text-muted">
                            <i class="bx bx-user-md me-1"></i>
                            Dr. ${escapeHtml(appointment.doctor_name)} ${escapeHtml(appointment.doctor_last_name)}
                        </p>
                        <small class="text-muted">
                            <i class="bx bx-calendar me-1"></i>
                            ${formatDate(appointment.appointment_date)} at ${formatTime(appointment.appointment_time)}
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary">${appointment.status}</span>
                    </div>
                </div>
            </div>
        `;
        container.append(card);
    });
}

// Display recent messages
function displayRecentMessages(messages) {
    const container = $('#recent-messages-list');
    container.empty();
    
    if (!messages || messages.length === 0) {
        container.html(`
            <div class="text-center py-4 text-muted">
                <i class="bx bx-message-x display-4"></i>
                <p class="mt-2">No recent messages</p>
                <button class="btn btn-sm btn-primary" onclick="composeMessage()">
                    Send Message
                </button>
            </div>
        `);
        return;
    }
    
    messages.forEach(message => {
        const unreadClass = message.is_read ? '' : 'unread';
        const messagePreview = `
            <div class="message-preview ${unreadClass}" onclick="viewMessage(${message.id})">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">${escapeHtml(message.subject)}</h6>
                    <small class="text-muted">${formatDateTime(message.created_at)}</small>
                </div>
                <p class="mb-1 text-muted">${truncateText(escapeHtml(message.message), 100)}</p>
                <small class="text-muted">
                    <i class="bx bx-user me-1"></i>
                    ${escapeHtml(message.sender_name)}
                </small>
            </div>
        `;
        container.append(messagePreview);
    });
}

// Display health goals
function displayHealthGoals(goals) {
    const container = $('#health-goals-list');
    container.empty();
    
    if (!goals || goals.length === 0) {
        container.html(`
            <div class="text-center py-4 text-muted">
                <i class="bx bx-target-lock display-4"></i>
                <p class="mt-2">No active health goals</p>
                <button class="btn btn-sm btn-success" onclick="addHealthGoal()">
                    Add Health Goal
                </button>
            </div>
        `);
        return;
    }
    
    goals.forEach(goal => {
        const progress = Math.min(100, Math.max(0, goal.progress_percentage || 0));
        const goalCard = `
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">${escapeHtml(goal.title)}</h6>
                    <span class="text-primary">${progress}%</span>
                </div>
                <div class="health-goal-progress">
                    <div class="progress-bar" style="width: ${progress}%"></div>
                </div>
                <small class="text-muted mt-1 d-block">
                    Target: ${goal.target_value} ${goal.target_unit}
                    ${goal.target_date ? ' by ' + formatDate(goal.target_date) : ''}
                </small>
            </div>
        `;
        container.append(goalCard);
    });
}

// Display recent lab results
function displayRecentLabResults(results) {
    const container = $('#recent-lab-results-list');
    container.empty();
    
    if (!results || results.length === 0) {
        container.html(`
            <div class="text-center py-4 text-muted">
                <i class="bx bx-test-tube display-4"></i>
                <p class="mt-2">No recent lab results</p>
            </div>
        `);
        return;
    }
    
    results.forEach(result => {
        const statusBadge = getLabResultStatusBadge(result.status);
        const resultCard = `
            <div class="mb-3 p-3 border rounded" onclick="viewLabResult(${result.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${escapeHtml(result.test_name)}</h6>
                        <p class="mb-1 text-muted">
                            <i class="bx bx-calendar me-1"></i>
                            ${formatDate(result.test_date)}
                        </p>
                        ${result.doctor_name ? `
                            <small class="text-muted">
                                <i class="bx bx-user-md me-1"></i>
                                Dr. ${escapeHtml(result.doctor_name)} ${escapeHtml(result.doctor_last_name)}
                            </small>
                        ` : ''}
                    </div>
                    <div class="text-end">
                        ${statusBadge}
                    </div>
                </div>
            </div>
        `;
        container.append(resultCard);
    });
}

// Load appointments
function loadAppointments() {
    const container = $('#appointments-content');
    container.html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading appointments...</p>
        </div>
    `);
    
    $.ajax({
        url: '../ajax/get_patient_appointments.php',
        type: 'GET',
        data: { patient_id: currentPatientId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayAppointmentsList(response.appointments);
            } else {
                showAlert('Error loading appointments: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to load appointments', 'danger');
        }
    });
}

// Display appointments list
function displayAppointmentsList(appointments) {
    const container = $('#appointments-content');
    container.empty();
    
    if (!appointments || appointments.length === 0) {
        container.html(`
            <div class="text-center py-5">
                <i class="bx bx-calendar-x display-1 text-muted"></i>
                <h4 class="mt-3">No appointments found</h4>
                <p class="text-muted">You don't have any appointments yet.</p>
                <button class="btn btn-portal-primary" onclick="requestAppointment()">
                    <i class="bx bx-plus me-2"></i>Request Appointment
                </button>
            </div>
        `);
        return;
    }
    
    // Group appointments by status
    const grouped = appointments.reduce((acc, appointment) => {
        const status = appointment.status || 'scheduled';
        if (!acc[status]) acc[status] = [];
        acc[status].push(appointment);
        return acc;
    }, {});
    
    // Display each group
    Object.keys(grouped).forEach(status => {
        const statusTitle = status.charAt(0).toUpperCase() + status.slice(1);
        let statusClass = 'secondary';
        
        switch(status) {
            case 'scheduled': statusClass = 'primary'; break;
            case 'completed': statusClass = 'success'; break;
            case 'cancelled': statusClass = 'danger'; break;
        }
        
        container.append(`<h5 class="mt-4 mb-3"><span class="badge bg-${statusClass}">${statusTitle}</span></h5>`);
        
        grouped[status].forEach(appointment => {
            const card = createAppointmentCard(appointment);
            container.append(card);
        });
    });
}

// Create appointment card
function createAppointmentCard(appointment) {
    const statusClass = getAppointmentStatusClass(appointment.status);
    
    return `
        <div class="card appointment-card ${statusClass} mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="card-title">${escapeHtml(appointment.appointment_type || 'Consultation')}</h6>
                        <p class="card-text">
                            <i class="bx bx-user-md me-2"></i>
                            Dr. ${escapeHtml(appointment.doctor_name)} ${escapeHtml(appointment.doctor_last_name)}
                            ${appointment.doctor_specialization ? `(${escapeHtml(appointment.doctor_specialization)})` : ''}
                        </p>
                        <p class="card-text">
                            <i class="bx bx-calendar me-2"></i>
                            ${formatDate(appointment.appointment_date)} at ${formatTime(appointment.appointment_time)}
                        </p>
                        ${appointment.notes ? `
                            <p class="card-text">
                                <i class="bx bx-note me-2"></i>
                                ${escapeHtml(appointment.notes)}
                            </p>
                        ` : ''}
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-${statusClass === 'upcoming' ? 'primary' : (statusClass === 'completed' ? 'success' : 'secondary')} mb-2">
                            ${appointment.status}
                        </span>
                        <div class="btn-group-vertical btn-group-sm d-grid">
                            ${appointment.status === 'scheduled' ? `
                                <button class="btn btn-outline-warning" onclick="rescheduleAppointment(${appointment.id})">
                                    <i class="bx bx-calendar-edit me-1"></i>Reschedule
                                </button>
                                <button class="btn btn-outline-danger" onclick="cancelAppointment(${appointment.id})">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            ` : ''}
                            <button class="btn btn-outline-info" onclick="viewAppointmentDetails(${appointment.id})">
                                <i class="bx bx-show me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Request new appointment
function requestAppointment() {
    // Show appointment request modal
    const modalHtml = `
        <div class="modal fade" id="appointmentRequestModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Request New Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="appointmentRequestForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Appointment Type</label>
                                    <select class="form-control" id="appointment-type" required>
                                        <option value="">Select Type</option>
                                        <option value="consultation">Consultation</option>
                                        <option value="follow_up">Follow-up</option>
                                        <option value="routine_checkup">Routine Checkup</option>
                                        <option value="specialist">Specialist Consultation</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Urgency</label>
                                    <select class="form-control" id="appointment-urgency" required>
                                        <option value="routine">Routine</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Preferred Date</label>
                                    <input type="date" class="form-control" id="preferred-date" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Preferred Time (Start)</label>
                                    <input type="time" class="form-control" id="preferred-time-start">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Preferred Time (End)</label>
                                    <input type="time" class="form-control" id="preferred-time-end">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preferred Doctor (Optional)</label>
                                <select class="form-control" id="preferred-doctor">
                                    <option value="">Any Available Doctor</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reason for Visit</label>
                                <textarea class="form-control" id="appointment-reason" rows="4" required placeholder="Please describe the reason for your appointment..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-portal-primary" onclick="submitAppointmentRequest()">Submit Request</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#modal-container').html(modalHtml);
    
    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    $('#preferred-date').attr('min', tomorrow.toISOString().split('T')[0]);
    
    // Load doctors list
    loadDoctorsList();
    
    $('#appointmentRequestModal').modal('show');
}

// Submit appointment request
function submitAppointmentRequest() {
    const form = document.getElementById('appointmentRequestForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        patient_id: currentPatientId,
        appointment_type: $('#appointment-type').val(),
        urgency: $('#appointment-urgency').val(),
        preferred_date: $('#preferred-date').val(),
        preferred_time_start: $('#preferred-time-start').val() || null,
        preferred_time_end: $('#preferred-time-end').val() || null,
        requested_doctor_id: $('#preferred-doctor').val() || null,
        reason: $('#appointment-reason').val()
    };
    
    $.ajax({
        url: '../ajax/create_appointment_request.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Appointment request submitted successfully! We will contact you soon.', 'success');
                $('#appointmentRequestModal').modal('hide');
                loadAppointments(); // Refresh appointments
            } else {
                showAlert('Error submitting request: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to submit appointment request', 'danger');
        }
    });
}

// Utility functions
function showTab(tabId) {
    $(`#${tabId}`).tab('show');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit'
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: 'numeric',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

function getAppointmentStatusClass(status) {
    switch(status) {
        case 'scheduled': return 'upcoming';
        case 'completed': return 'completed';
        case 'cancelled': return 'cancelled';
        default: return '';
    }
}

function getLabResultStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'critical': '<span class="badge bg-danger">Critical</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function showLoadingStates() {
    // Add loading spinners to dashboard cards
}

function hideLoadingStates() {
    // Remove loading spinners
}

function checkUnreadMessages() {
    // Check for new messages periodically
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

// Placeholder functions for features to be implemented
function loadMedicalRecords() { console.log('Loading medical records...'); }
function loadLabResults() { console.log('Loading lab results...'); }
function loadBillingInfo() { console.log('Loading billing info...'); }
function loadMessages() { console.log('Loading messages...'); }
function composeMessage() { console.log('Compose message...'); }
function viewMessage(id) { console.log('View message:', id); }
function addHealthGoal() { console.log('Add health goal...'); }
function viewLabResult(id) { console.log('View lab result:', id); }
function viewRecords() { showTab('records-tab'); }
function sendMessage() { showTab('messages-tab'); }
function payBills() { showTab('billing-tab'); }
function loadDoctorsList() { console.log('Loading doctors list...'); }
function showProfileSettings() { console.log('Show profile settings...'); }
function showPreferences() { console.log('Show preferences...'); }
function updateWelcomeMessage(patientInfo) { console.log('Update welcome message:', patientInfo); }
