/**
 * Doctor Dashboard JavaScript
 * Handles queue management, appointments, and patient search
 */

$(document).ready(function() {
    // Initialize dashboard
    initializeDashboard();
    
    // Set up event listeners
    setupEventListeners();
    
    // Auto-refresh queue every 30 seconds
    setInterval(loadQueue, 30000);
});

function initializeDashboard() {
    // Set current date
    $('#appointmentsDate').text(new Date().toLocaleDateString());
    
    // Load initial data
    loadQueue();
    loadAppointments();
    loadQueueStats();
}

function setupEventListeners() {
    // Queue actions
    $('#refreshQueue').on('click', loadQueue);
    $('#callNextPatient').on('click', callNextPatient);
    
    // Quick actions
    $('#searchPatientBtn').on('click', function() {
        $('#searchPatientModal').modal('show');
    });
    
    $('#newMedicalRecordBtn').on('click', function() {
        window.location.href = 'medical-records.php';
    });
    
    $('#newLabTestBtn').on('click', function() {
        window.location.href = 'lab-tests.php';
    });
    
    $('#viewAppointmentsBtn').on('click', function() {
        window.location.href = 'appointments.php';
    });
    
    // Patient search
    $('#patientSearchInput').on('input', debounce(searchPatients, 300));
    
    // Queue item actions
    $(document).on('click', '.btn-serve-patient', function() {
        const queueId = $(this).data('queue-id');
        servePatient(queueId);
    });
    
    $(document).on('click', '.btn-view-patient', function() {
        const patientId = $(this).data('patient-id');
        viewPatientDetails(patientId);
    });
}

function loadQueue() {
    $.get('./../ajax/get_queue.php')
        .done(function(response) {
            if (response.success) {
                displayQueue(response.data);
                updateQueueStatus(response.data.length);
            } else {
                showError('Failed to load queue: ' + response.message);
            }
        })
        .fail(function() {
            showError('Error connecting to server');
        });
}

function displayQueue(patients) {
    const container = $('#queueContainer');
    
    if (!patients || patients.length === 0) {
        container.html(`
            <div class="text-center py-4">
                <i class="bx bx-user-check text-muted fs-1"></i>
                <p class="mt-2 text-muted">No patients in queue</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    patients.forEach((patient, index) => {
        const isNext = index === 0;
        const cardClass = isNext ? 'border-primary' : '';
        const badgeClass = isNext ? 'bg-primary' : 'bg-secondary';
        
        html += `
            <div class="card mb-2 ${cardClass}">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center">
                                <span class="badge ${badgeClass} me-2">${index + 1}</span>
                                <div>
                                    <h6 class="mb-0">${patient.patient_name}</h6>
                                    <small class="text-muted">ID: ${patient.patient_code || 'N/A'}</small>
                                </div>
                            </div>
                            ${patient.reason ? `<small class="text-muted ms-4">Reason: ${patient.reason}</small>` : ''}
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-patient" 
                                    data-patient-id="${patient.patient_id}" title="View Patient">
                                <i class="bx bx-user"></i>
                            </button>
                            ${isNext ? `
                                <button type="button" class="btn btn-sm btn-success btn-serve-patient" 
                                        data-queue-id="${patient.queue_id}" title="Mark as Served">
                                    <i class="bx bx-check"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

function updateQueueStatus(count) {
    $('#waitingCount').text(count);
    $('#queueStatus').text(count > 0 ? `${count} waiting` : 'No patients waiting');
}

function callNextPatient() {
    const btn = $('#callNextPatient');
    btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Calling...');
    
    $.post('ajax/get_next_patient.php')
        .done(function(response) {
            if (response.success && response.data) {
                const patient = response.data;
                
                // Update queue status to 'called'
                $.post('ajax/update_queue.php', {
                    queue_id: patient.queue_id,
                    status: 'called'
                })
                .done(function(updateResponse) {
                    if (updateResponse.success) {
                        showSuccess(`Called ${patient.patient_name} to consultation room`);
                        loadQueue();
                        loadQueueStats();
                    } else {
                        showError('Failed to update queue status');
                    }
                });
            } else {
                showInfo('No patients waiting in queue');
            }
        })
        .fail(function() {
            showError('Error calling next patient');
        })
        .always(function() {
            btn.prop('disabled', false).html('<i class="bx bx-phone-call me-1"></i>Call Next');
        });
}

function servePatient(queueId) {
    $.post('ajax/update_queue.php', {
        queue_id: queueId,
        status: 'served'
    })
    .done(function(response) {
        if (response.success) {
            showSuccess('Patient marked as served');
            loadQueue();
            loadQueueStats();
        } else {
            showError('Failed to update patient status: ' + response.message);
        }
    })
    .fail(function() {
        showError('Error updating patient status');
    });
}

function loadAppointments() {
    const today = new Date().toISOString().split('T')[0];
    
    $.get('ajax/get_doctor_appointments.php', { 
        date: today
    })
    .done(function(response) {
        if (response.success) {
            displayAppointments(response.data);
            updateAppointmentsCount(response.data.length);
        } else {
            showError('Failed to load appointments');
        }
    })
    .fail(function() {
        showError('Error loading appointments');
    });
}

function displayAppointments(appointments) {
    const tbody = $('#appointmentsTable tbody');
    
    if (!appointments || appointments.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    No appointments scheduled for today
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    appointments.forEach(appointment => {
        const statusBadge = getStatusBadge(appointment.status);
        const time = formatTime(appointment.appointment_time);
        
        html += `
            <tr>
                <td>${time}</td>
                <td>${appointment.patient_name}</td>
                <td>${appointment.reason || 'N/A'}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="viewAppointment(${appointment.id})" title="View">
                            <i class="bx bx-show"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" 
                                onclick="addToQueue(${appointment.patient_id}, ${appointment.id})" title="Add to Queue">
                            <i class="bx bx-plus"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

function updateAppointmentsCount(count) {
    $('#appointmentsCount').text(count);
    $('#appointmentsStatus').text(count > 0 ? `${count} scheduled` : 'No appointments');
}

function loadQueueStats() {
    $.get('ajax/get_queue.php', { stats: true })
        .done(function(response) {
            if (response.success && response.stats) {
                $('#servedCount').text(response.stats.served_today || 0);
            }
        });
}

function searchPatients() {
    const query = $('#patientSearchInput').val().trim();
    const resultsContainer = $('#patientSearchResults');
    
    if (query.length < 2) {
        resultsContainer.html(`
            <div class="text-center py-4 text-muted">
                Start typing to search for patients...
            </div>
        `);
        return;
    }
    
    resultsContainer.html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Searching...</span>
            </div>
        </div>
    `);
    
    $.get('ajax/get_patients.php', { search: query })
        .done(function(response) {
            if (response.success && response.data.length > 0) {
                displaySearchResults(response.data);
            } else {
                resultsContainer.html(`
                    <div class="text-center py-4 text-muted">
                        No patients found matching "${query}"
                    </div>
                `);
            }
        })
        .fail(function() {
            resultsContainer.html(`
                <div class="text-center py-4 text-danger">
                    Error searching patients
                </div>
            `);
        });
}

function displaySearchResults(patients) {
    const container = $('#patientSearchResults');
    let html = '';
    
    patients.forEach(patient => {
        html += `
            <div class="card mb-2">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${patient.first_name} ${patient.last_name}</h6>
                            <small class="text-muted">
                                ID: ${patient.patient_id || 'N/A'} | 
                                Phone: ${patient.phone || 'N/A'}
                            </small>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPatientDetails(${patient.id})">
                                <i class="bx bx-show me-1"></i>View
                            </button>
                            <button type="button" class="btn btn-sm btn-success" 
                                    onclick="addToQueue(${patient.id})">
                                <i class="bx bx-plus me-1"></i>Add to Queue
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

function viewPatientDetails(patientId) {
    // Open patient details in new tab/window or modal
    window.open(`patients.php?view=${patientId}`, '_blank');
}

function addToQueue(patientId, appointmentId = null) {
    $.post('ajax/add_to_queue.php', {
        patient_id: patientId,
        appointment_id: appointmentId
    })
    .done(function(response) {
        if (response.success) {
            showSuccess('Patient added to queue');
            loadQueue();
            loadQueueStats();
            $('#searchPatientModal').modal('hide');
        } else {
            showError('Failed to add patient to queue: ' + response.message);
        }
    })
    .fail(function() {
        showError('Error adding patient to queue');
    });
}

function viewAppointment(appointmentId) {
    // Open appointment details
    window.location.href = `appointments.php?view=${appointmentId}`;
}

// Utility functions
function getStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'confirmed': '<span class="badge bg-success">Confirmed</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>',
        'completed': '<span class="badge bg-info">Completed</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function formatTime(datetime) {
    if (!datetime) return 'N/A';
    const date = new Date(datetime);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Toast notifications
function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert('Success: ' + message);
    }
}

function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    } else {
        alert('Error: ' + message);
    }
}

function showInfo(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: 'Info',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert('Info: ' + message);
    }
}
