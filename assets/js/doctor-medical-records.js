// Global variables
let currentPatientId = null;
let currentAppointmentId = null;

$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Get patient/appointment from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentPatientId = urlParams.get('patient_id');
    currentAppointmentId = urlParams.get('appointment_id');
    
    if (currentPatientId) {
        loadPatientInfo();
        loadMedicalRecords();
    } else if (currentAppointmentId) {
        loadAppointmentAndPatient();
    }
    
    // Form submit handler
    $('#medicalRecordForm').on('submit', function(e) {
        e.preventDefault();
        saveMedicalRecord();
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
            if (!response.success || response.user_type !== 'doctor') {
                window.location.href = '../login-new.html';
            }
        },
        error: function() {
            window.location.href = '../login-new.html';
        }
    });
}

/**
 * Load appointment and get patient ID
 */
function loadAppointmentAndPatient() {
    $.ajax({
        url: '../ajax/get_appointment.php',
        type: 'GET',
        data: { appointment_id: currentAppointmentId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                currentPatientId = response.data.patient_id;
                loadPatientInfo();
                loadMedicalRecords();
            }
        },
        error: function() {
            alert('Failed to load appointment details');
        }
    });
}

/**
 * Load patient information
 */
function loadPatientInfo() {
    $.ajax({
        url: '../ajax/get_patient_dashboard.php',
        type: 'GET',
        data: { patient_id: currentPatientId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayPatientInfo(response.data.patient_info);
            }
        },
        error: function() {
            console.error('Failed to load patient info');
        }
    });
}

/**
 * Display patient information
 */
function displayPatientInfo(patient) {
    const age = calculateAge(patient.date_of_birth);
    
    $('#patientName').text(patient.first_name + ' ' + patient.last_name);
    $('#patientId').text(patient.patient_id || patient.id);
    $('#patientAge').text(age + ' years');
    $('#patientGender').text(capitalize(patient.gender || 'N/A'));
    $('#patientInfoCard').show();
}

/**
 * Load medical records
 */
function loadMedicalRecords() {
    $('#recordsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    $.ajax({
        url: '../ajax/get_medical_records.php',
        type: 'GET',
        data: { patient_id: currentPatientId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayMedicalRecords(response.data);
            } else {
                showNoRecords();
            }
        },
        error: function() {
            showErrorMessage();
        }
    });
}

/**
 * Display medical records
 */
function displayMedicalRecords(records) {
    if (!records || records.length === 0) {
        showNoRecords();
        return;
    }
    
    $('#recordCount').text(records.length);
    
    let html = '<div class="accordion" id="recordsAccordion">';
    
    records.forEach(function(record, index) {
        const recordDate = formatDate(record.created_at || record.record_date);
        const vitals = [];
        
        if (record.blood_pressure) vitals.push(`BP: ${record.blood_pressure}`);
        if (record.heart_rate) vitals.push(`HR: ${record.heart_rate}`);
        if (record.temperature) vitals.push(`Temp: ${record.temperature}`);
        if (record.weight) vitals.push(`Weight: ${record.weight}`);
        
        const vitalsText = vitals.length > 0 ? vitals.join(' | ') : 'No vitals recorded';
        
        html += `
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading${index}">
                    <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${recordDate}</strong> - ${escapeHtml(record.chief_complaint || 'N/A')}
                                </div>
                                <small class="text-muted me-3">Dr. ${escapeHtml(record.doctor_name || 'Unknown')}</small>
                            </div>
                            <small class="text-muted">${vitalsText}</small>
                        </div>
                    </button>
                </h2>
                <div id="collapse${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#recordsAccordion">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Chief Complaint:</label>
                                <p>${escapeHtml(record.chief_complaint || 'N/A')}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Diagnosis:</label>
                                <p>${escapeHtml(record.diagnosis || 'N/A')}</p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="fw-bold">Treatment Plan:</label>
                                <p>${escapeHtml(record.treatment_plan || 'N/A')}</p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="fw-bold">Notes:</label>
                                <p>${escapeHtml(record.notes || 'No additional notes')}</p>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-sm btn-primary" onclick="viewPrescriptions(${record.id})">
                                    <i class="bx bx-receipt me-1"></i>View Prescriptions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    $('#recordsContainer').html(html);
}

/**
 * Show create record modal
 */
function showCreateRecordModal() {
    if (!currentPatientId) {
        alert('No patient selected');
        return;
    }
    
    $('#modalTitle').text('Add Medical Record');
    $('#medicalRecordForm')[0].reset();
    $('#recordId').val('');
    
    const modal = new bootstrap.Modal(document.getElementById('medicalRecordModal'));
    modal.show();
}

/**
 * Save medical record
 */
function saveMedicalRecord() {
    const formData = {
        patient_id: currentPatientId,
        appointment_id: currentAppointmentId,
        chief_complaint: $('#chiefComplaint').val(),
        diagnosis: $('#diagnosis').val(),
        treatment_plan: $('#treatmentPlan').val(),
        blood_pressure: $('#bloodPressure').val(),
        heart_rate: $('#heartRate').val(),
        temperature: $('#temperature').val(),
        weight: $('#weight').val(),
        notes: $('#notes').val()
    };
    
    const recordId = $('#recordId').val();
    if (recordId) {
        formData.record_id = recordId;
    }
    
    $.ajax({
        url: '../ajax/create_medical_record.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Medical record saved successfully');
                bootstrap.Modal.getInstance(document.getElementById('medicalRecordModal')).hide();
                loadMedicalRecords();
            } else {
                alert(response.message || 'Failed to save medical record');
            }
        },
        error: function() {
            alert('Error saving medical record. Please try again.');
        }
    });
}

/**
 * View prescriptions for a medical record
 */
function viewPrescriptions(recordId) {
    window.location.href = `doctor-prescriptions.html?record_id=${recordId}&patient_id=${currentPatientId}`;
}

/**
 * Show no records message
 */
function showNoRecords() {
    $('#recordCount').text('0');
    $('#recordsContainer').html(`
        <div class="text-center py-5">
            <i class="bx bx-folder-open" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">No medical records found</p>
            <button type="button" class="btn btn-primary mt-2" onclick="showCreateRecordModal()">
                <i class="bx bx-plus me-2"></i>Add First Record
            </button>
        </div>
    `);
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#recordsContainer').html(`
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error me-2"></i>
            Failed to load medical records. Please try refreshing the page.
        </div>
    `);
}

/**
 * Calculate age from date of birth
 */
function calculateAge(dateOfBirth) {
    if (!dateOfBirth) return 'N/A';
    
    const today = new Date();
    const birthDate = new Date(dateOfBirth);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

// ===== Helper Functions =====

function formatDate(date) {
    if (!date) return 'N/A';
    const d = new Date(date);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return d.toLocaleDateString('en-US', options);
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
