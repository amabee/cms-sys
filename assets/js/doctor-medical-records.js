// Global variables
let currentPatientId = null;
let currentAppointmentId = null;

$(document).ready(function() {
    // Wait for layout to load before initializing
    waitForLayout().then(function() {
        initializeMedicalRecordsPage();
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
 * Initialize medical records page
 */
function initializeMedicalRecordsPage() {
    // Get patient/appointment from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentPatientId = urlParams.get('patient_id');
    currentAppointmentId = urlParams.get('appointment_id');
    
    console.log('Initializing medical records page');
    console.log('Patient ID:', currentPatientId);
    console.log('Appointment ID:', currentAppointmentId);
    
    if (currentPatientId) {
        loadPatientInfo();
        loadMedicalRecords();
    } else if (currentAppointmentId) {
        loadAppointmentAndPatient();
    } else {
        console.warn('No patient_id or appointment_id provided in URL');
        showPatientError('Please select a patient from the appointments or patients page.');
    }
    
    // Form submit handler
    $('#medicalRecordForm').on('submit', function(e) {
        e.preventDefault();
        saveMedicalRecord();
    });
    
    // Search records
    $('#searchRecords').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterRecords(searchTerm);
    });
}

/**
 * Filter medical records by search term
 */
function filterRecords(searchTerm) {
    let visibleCount = 0;
    
    if (!searchTerm) {
        $('.accordion-item').show();
        visibleCount = $('.accordion-item').length;
    } else {
        $('.accordion-item').each(function() {
            const text = $(this).text().toLowerCase();
            if (text.includes(searchTerm)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
    }
    
    // Update badge count
    $('#recordsCount').text(visibleCount);
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
        url: '../ajax/get_patient.php',
        type: 'GET',
        data: { id: currentPatientId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayPatientInfo(response.data);
            } else {
                console.error('Failed to load patient info:', response.message);
                showPatientError('Unable to load patient information');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load patient info:', error);
            showPatientError('Error loading patient information');
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
            console.log('Medical records response:', response);
            
            // Handle DataTables format
            let records = [];
            if (response.data && Array.isArray(response.data)) {
                records = response.data;
            } else if (response.success && response.data) {
                records = response.data;
            }
            
            if (records.length > 0) {
                displayMedicalRecords(records);
            } else {
                showNoRecords();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading medical records:', error);
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
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editMedicalRecord(${record.id})">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMedicalRecord(${record.id})">
                                    <i class="bx bx-trash me-1"></i>Delete
                                </button>
                                ${record.attachment_url ? `
                                <a href="${escapeHtml(record.attachment_url)}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-download me-1"></i>Download Attachment
                                </a>` : ''}
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
    const recordId = $('#recordId').val();
    const url = recordId 
        ? '../ajax/update_medical_record.php' 
        : '../ajax/create_medical_record.php';
    
    // Create FormData to handle file upload
    const formData = new FormData();
    formData.append('patient_id', currentPatientId);
    if (currentAppointmentId) formData.append('appointment_id', currentAppointmentId);
    if (recordId) formData.append('record_id', recordId);
    
    formData.append('chief_complaint', $('#chiefComplaint').val());
    formData.append('diagnosis', $('#diagnosis').val());
    formData.append('treatment_plan', $('#treatmentPlan').val());
    formData.append('blood_pressure', $('#bloodPressure').val());
    formData.append('heart_rate', $('#heartRate').val());
    formData.append('temperature', $('#temperature').val());
    formData.append('weight', $('#weight').val());
    formData.append('notes', $('#notes').val());
    
    // Handle file upload if present
    const fileInput = document.getElementById('attachment');
    if (fileInput && fileInput.files.length > 0) {
        formData.append('attachment', fileInput.files[0]);
    }
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(recordId ? 'Medical record updated successfully' : 'Medical record created successfully');
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
 * Edit medical record
 */
function editMedicalRecord(recordId) {
    $.ajax({
        url: '../ajax/get_medical_record.php',
        type: 'GET',
        data: { record_id: recordId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const record = response.data;
                
                $('#modalTitle').text('Edit Medical Record');
                $('#recordId').val(record.id);
                $('#chiefComplaint').val(record.chief_complaint);
                $('#diagnosis').val(record.diagnosis);
                $('#treatmentPlan').val(record.treatment_plan);
                $('#bloodPressure').val(record.blood_pressure);
                $('#heartRate').val(record.heart_rate);
                $('#temperature').val(record.temperature);
                $('#weight').val(record.weight);
                $('#notes').val(record.notes);
                
                const modal = new bootstrap.Modal(document.getElementById('medicalRecordModal'));
                modal.show();
            } else {
                alert('Failed to load medical record details');
            }
        },
        error: function() {
            alert('Error loading medical record');
        }
    });
}

/**
 * Delete medical record
 */
function deleteMedicalRecord(recordId) {
    if (!confirm('Are you sure you want to delete this medical record? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: '../ajax/delete_medical_record.php',
        type: 'POST',
        data: { record_id: recordId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Medical record deleted successfully');
                loadMedicalRecords();
            } else {
                alert(response.message || 'Failed to delete medical record');
            }
        },
        error: function() {
            alert('Error deleting medical record');
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
 * Show patient error message
 */
function showPatientError(message) {
    $('#patientInfoCard').html(`
        <div class="alert alert-danger">
            <i class="bx bx-error me-2"></i>
            ${message}
        </div>
    `).show();
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#recordsContainer').html(`
        <div class="alert alert-danger text-center">
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
