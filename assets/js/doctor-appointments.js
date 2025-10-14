// Global variables
let currentFilter = 'all';
let currentAppointmentId = null;

$(document).ready(function() {
    // Wait for layout to load before initializing
    waitForLayout().then(function() {
        initializeAppointments();
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
 * Initialize appointments page after layout is ready
 */
function initializeAppointments() {
    // Load appointments
    loadAppointments();
    
    // Set default dates (last 30 days to today)
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    $('#endDate').val(formatDateForInput(today));
    $('#startDate').val(formatDateForInput(lastMonth));
    
    // Event listeners for date inputs
    $('#startDate, #endDate').on('change', function() {
        loadAppointments();
    });
    
    // Search button (if exists)
    $('#searchBtn').on('click', function() {
        loadAppointments();
    });
}

/**
 * Load appointments based on current filter
 */
function loadAppointments() {
    const params = {
        filter: currentFilter
    };
    
    // Add date range if specified
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    if (startDate) params.start_date = startDate;
    if (endDate) params.end_date = endDate;
    
    $.ajax({
        url: '../ajax/get_doctor_appointments.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayAppointments(response.data);
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
 * Filter appointments by type (called from HTML buttons)
 */
function filterAppointments(filter) {
    currentFilter = filter;
    
    // Update active tab
    $('.nav-link[data-filter]').removeClass('active');
    $(`.nav-link[data-filter="${filter}"]`).addClass('active');
    
    loadAppointments();
}

/**
 * Display appointments
 */
function displayAppointments(appointments) {
    const container = $('#appointmentsContainer');
    
    if (!appointments || appointments.length === 0) {
        showNoAppointments();
        return;
    }
    
    // Update count
    $('#appointmentCount').text(appointments.length);
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    appointments.forEach(function(apt) {
        const statusClass = getStatusClass(apt.status);
        const statusText = capitalize(apt.status);
        
        html += `
            <tr>
                <td>
                    <strong>${formatDate(apt.appointment_date)}</strong><br>
                    <small class="text-muted">${formatTime(apt.appointment_time)}</small>
                </td>
                <td>
                    <div>${escapeHtml(apt.patient_name || 'N/A')}</div>
                    <small class="text-muted">${escapeHtml(apt.patient_phone || '')}</small>
                </td>
                <td>${escapeHtml(apt.reason || 'General checkup')}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="viewAppointmentDetails(${apt.id})">
                        <i class="bx bx-show me-1"></i>View
                    </button>
                    ${apt.status === 'scheduled' || apt.status === 'confirmed' ? `
                        <button type="button" class="btn btn-sm btn-success" onclick="startConsultationDirect(${apt.id})">
                            <i class="bx bx-play me-1"></i>Start
                        </button>
                    ` : ''}
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
 * View appointment details in modal
 */
function viewAppointmentDetails(appointmentId) {
    currentAppointmentId = appointmentId;
    
    // Show modal with loading
    const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
    modal.show();
    
    $('#appointmentDetailsContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    // Load appointment details
    $.ajax({
        url: '../ajax/get_appointment.php',
        type: 'GET',
        data: { appointment_id: appointmentId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayAppointmentDetails(response.data);
            } else {
                $('#appointmentDetailsContent').html(`
                    <div class="alert alert-danger">Failed to load appointment details</div>
                `);
            }
        },
        error: function() {
            $('#appointmentDetailsContent').html(`
                <div class="alert alert-danger">Error loading appointment details</div>
            `);
        }
    });
}

/**
 * Display appointment details in modal
 */
function displayAppointmentDetails(apt) {
    const statusClass = getStatusClass(apt.status);
    const statusText = capitalize(apt.status);
    
    const html = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Patient Name</label>
                <p class="mb-0">${escapeHtml(apt.patient_name || 'N/A')}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Contact</label>
                <p class="mb-0">${escapeHtml(apt.patient_phone || 'N/A')}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Date</label>
                <p class="mb-0">${formatDate(apt.appointment_date)}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Time</label>
                <p class="mb-0">${formatTime(apt.appointment_time)}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Status</label>
                <p class="mb-0"><span class="badge ${statusClass}">${statusText}</span></p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Appointment ID</label>
                <p class="mb-0">${escapeHtml(apt.appointment_id || apt.id)}</p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Reason for Visit</label>
                <p class="mb-0">${escapeHtml(apt.reason || 'General checkup')}</p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Notes</label>
                <p class="mb-0">${escapeHtml(apt.notes || 'No additional notes')}</p>
            </div>
        </div>
    `;
    
    $('#appointmentDetailsContent').html(html);
    
    // Show/hide start button based on status
    if (apt.status === 'scheduled' || apt.status === 'confirmed') {
        $('#btnStartConsultation').show();
    } else {
        $('#btnStartConsultation').hide();
    }
}

/**
 * Start consultation from modal
 */
function startConsultation() {
    if (!currentAppointmentId) return;
    startConsultationDirect(currentAppointmentId);
}

/**
 * Start consultation directly
 */
function startConsultationDirect(appointmentId) {
    // Redirect to patient medical records page
    window.location.href = `doctor-medical-records.html?appointment_id=${appointmentId}`;
}

/**
 * Filter appointments
 */
function filterAppointments(filter) {
    currentFilter = filter;
    
    // Update active tab
    $('.nav-pills .nav-link').removeClass('active');
    $(`.nav-pills .nav-link[data-filter="${filter}"]`).addClass('active');
    
    // Reload appointments
    loadAppointments();
}

/**
 * Apply date filter
 */
function applyDateFilter() {
    loadAppointments();
}

/**
 * Show no appointments message
 */
function showNoAppointments() {
    $('#appointmentCount').text('0');
    $('#appointmentsContainer').html(`
        <div class="text-center py-5">
            <i class="bx bx-calendar-x" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">No appointments found</p>
        </div>
    `);
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#appointmentsContainer').html(`
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error me-2"></i>
            Failed to load appointments. Please try refreshing the page.
        </div>
    `);
}

/**
 * Get status badge class
 */
function getStatusClass(status) {
    const classes = {
        'scheduled': 'bg-label-primary',
        'confirmed': 'bg-label-info',
        'in-progress': 'bg-label-warning',
        'completed': 'bg-label-success',
        'cancelled': 'bg-label-danger',
        'no-show': 'bg-label-secondary'
    };
    return classes[status] || 'bg-label-secondary';
}

// ===== Helper Functions =====

function formatDate(date) {
    if (!date) return 'N/A';
    const d = new Date(date);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return d.toLocaleDateString('en-US', options);
}

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

function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
