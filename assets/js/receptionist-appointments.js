// Global variables
let currentFilter = 'today';
let doctors = [];
let patients = [];

$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Load dropdown data
    loadDoctors();
    loadPatients();
    
    // Load appointments
    loadAppointments();
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    $('#filterDate').val(today);
    $('#appointmentDate').val(today);
    
    // Form submit handler
    $('#appointmentForm').on('submit', function(e) {
        e.preventDefault();
        saveAppointment();
    });
});

/**
 * Check authentication
 */
function checkAuth() {
    $.ajax({
        url: '../ajax/check_session.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!response.success || response.user_type !== 'receptionist') {
                window.location.href = '../login-new.html';
            }
        },
        error: function() {
            window.location.href = '../login-new.html';
        }
    });
}

/**
 * Load doctors for dropdown
 */
function loadDoctors() {
    $.ajax({
        url: '../ajax/get_doctors.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                doctors = response.data;
                populateDoctorDropdowns();
            }
        }
    });
}

/**
 * Populate doctor dropdowns
 */
function populateDoctorDropdowns() {
    let options = '<option value="">Select doctor</option>';
    doctors.forEach(function(doctor) {
        options += `<option value="${doctor.id}">${escapeHtml(doctor.first_name + ' ' + doctor.last_name)} - ${escapeHtml(doctor.specialization || 'General')}</option>`;
    });
    $('#doctorId').html(options);
    
    // Filter dropdown
    let filterOptions = '<option value="">All Doctors</option>';
    doctors.forEach(function(doctor) {
        filterOptions += `<option value="${doctor.id}">${escapeHtml(doctor.first_name + ' ' + doctor.last_name)}</option>`;
    });
    $('#filterDoctor').html(filterOptions);
}

/**
 * Load patients for dropdown
 */
function loadPatients() {
    $.ajax({
        url: '../ajax/get_patients.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                patients = response.data;
                populatePatientDropdown();
            }
        }
    });
}

/**
 * Populate patient dropdown
 */
function populatePatientDropdown() {
    let options = '<option value="">Select patient</option>';
    patients.forEach(function(patient) {
        options += `<option value="${patient.id}">${escapeHtml(patient.first_name + ' ' + patient.last_name)} - ${escapeHtml(patient.patient_id || patient.id)}</option>`;
    });
    $('#patientId').html(options);
}

/**
 * Load appointments based on filter
 */
function loadAppointments() {
    const params = { filter: currentFilter };
    
    // Add additional filters if set
    const filterDate = $('#filterDate').val();
    const filterDoctor = $('#filterDoctor').val();
    const filterStatus = $('#filterStatus').val();
    
    if (filterDate) params.date = filterDate;
    if (filterDoctor) params.doctor_id = filterDoctor;
    if (filterStatus) params.status = filterStatus;
    
    $('#appointmentsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    $.ajax({
        url: '../ajax/get_appointments.php',
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
        error: function() {
            showErrorMessage();
        }
    });
}

/**
 * Display appointments
 */
function displayAppointments(appointments) {
    if (!appointments || appointments.length === 0) {
        showNoAppointments();
        return;
    }
    
    $('#appointmentCount').text(appointments.length);
    
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
                <td>${escapeHtml(apt.doctor_name || 'N/A')}</td>
                <td>${escapeHtml(apt.reason || 'General checkup')}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="editAppointment(${apt.id})">
                                <i class="bx bx-edit me-1"></i> Edit
                            </a>
                            ${apt.status === 'scheduled' ? `
                                <a class="dropdown-item" href="javascript:void(0);" onclick="confirmAppointment(${apt.id})">
                                    <i class="bx bx-check me-1"></i> Confirm
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" onclick="checkInPatient(${apt.id})">
                                    <i class="bx bx-user-check me-1"></i> Check In
                                </a>
                            ` : ''}
                            ${apt.status !== 'completed' && apt.status !== 'cancelled' ? `
                                <a class="dropdown-item" href="javascript:void(0);" onclick="rescheduleAppointment(${apt.id})">
                                    <i class="bx bx-calendar-edit me-1"></i> Reschedule
                                </a>
                                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="cancelAppointment(${apt.id})">
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
    
    $('#appointmentsContainer').html(html);
}

/**
 * Show create appointment modal
 */
function showCreateAppointmentModal() {
    $('#modalTitle').text('New Appointment');
    $('#appointmentForm')[0].reset();
    $('#appointmentId').val('');
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    $('#appointmentDate').val(today);
    
    const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
    modal.show();
}

/**
 * Edit appointment
 */
function editAppointment(appointmentId) {
    $.ajax({
        url: '../ajax/get_appointment.php',
        type: 'GET',
        data: { appointment_id: appointmentId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const apt = response.data;
                
                $('#modalTitle').text('Edit Appointment');
                $('#appointmentId').val(apt.id);
                $('#patientId').val(apt.patient_id);
                $('#doctorId').val(apt.doctor_id);
                $('#appointmentDate').val(apt.appointment_date);
                $('#appointmentTime').val(apt.appointment_time);
                $('#reason').val(apt.reason);
                $('#notes').val(apt.notes);
                
                const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
                modal.show();
            } else {
                alert('Failed to load appointment details');
            }
        },
        error: function() {
            alert('Error loading appointment details');
        }
    });
}

/**
 * Save appointment
 */
function saveAppointment() {
    const formData = {
        patient_id: $('#patientId').val(),
        doctor_id: $('#doctorId').val(),
        appointment_date: $('#appointmentDate').val(),
        appointment_time: $('#appointmentTime').val(),
        reason: $('#reason').val(),
        notes: $('#notes').val()
    };
    
    const appointmentId = $('#appointmentId').val();
    if (appointmentId) {
        formData.appointment_id = appointmentId;
    }
    
    const url = appointmentId ? '../ajax/update_appointment.php' : '../ajax/create_appointment.php';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Appointment saved successfully');
                bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
                loadAppointments();
            } else {
                alert(response.message || 'Failed to save appointment');
            }
        },
        error: function() {
            alert('Error saving appointment. Please try again.');
        }
    });
}

/**
 * Confirm appointment
 */
function confirmAppointment(appointmentId) {
    updateAppointmentStatus(appointmentId, 'confirmed', 'Appointment confirmed successfully');
}

/**
 * Check in patient
 */
function checkInPatient(appointmentId) {
    if (!confirm('Check in this patient and add to queue?')) return;
    
    // Update status to in-progress
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
                $.ajax({
                    url: '../ajax/add_to_queue.php',
                    type: 'POST',
                    data: { appointment_id: appointmentId },
                    dataType: 'json',
                    success: function(queueResponse) {
                        if (queueResponse.success) {
                            alert('Patient checked in and added to queue');
                            loadAppointments();
                        } else {
                            alert(queueResponse.message || 'Failed to add to queue');
                        }
                    }
                });
            } else {
                alert(response.message || 'Failed to check in patient');
            }
        }
    });
}

/**
 * Reschedule appointment
 */
function rescheduleAppointment(appointmentId) {
    editAppointment(appointmentId);
}

/**
 * Cancel appointment
 */
function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) return;
    updateAppointmentStatus(appointmentId, 'cancelled', 'Appointment cancelled successfully');
}

/**
 * Update appointment status
 */
function updateAppointmentStatus(appointmentId, status, successMessage) {
    $.ajax({
        url: '../ajax/update_appointment_status.php',
        type: 'POST',
        data: {
            appointment_id: appointmentId,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(successMessage);
                loadAppointments();
            } else {
                alert(response.message || 'Failed to update appointment');
            }
        },
        error: function() {
            alert('Error updating appointment. Please try again.');
        }
    });
}

/**
 * Filter appointments by tab
 */
function filterAppointments(filter) {
    currentFilter = filter;
    
    // Update active tab
    $('.nav-pills button').removeClass('active');
    $(`.nav-pills button[data-filter="${filter}"]`).addClass('active');
    
    loadAppointments();
}

/**
 * Apply filters
 */
function applyFilters() {
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
