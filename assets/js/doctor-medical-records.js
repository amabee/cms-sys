// Global variables
let currentPatientId = null;
let currentAppointmentId = null;
let allPatients = [];

// Version check - ensure we're loading the latest JavaScript
console.log('🔄 Loading doctor-medical-records.js v20251015002');

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
    // Get patient/appointment from URL (optional)
    const urlParams = new URLSearchParams(window.location.search);
    const patientIdFromUrl = urlParams.get('patient_id');
    const appointmentIdFromUrl = urlParams.get('appointment_id');
    
    console.log('Initializing medical records page');
    console.log('Patient ID from URL:', patientIdFromUrl);
    console.log('Appointment ID from URL:', appointmentIdFromUrl);
    
    // Always load patient list first
    loadPatientList();
    
    // If patient_id or appointment_id provided, auto-select that patient
    if (patientIdFromUrl) {
        currentPatientId = patientIdFromUrl;
        loadPatientInfo();
        loadMedicalRecords();
    } else if (appointmentIdFromUrl) {
        currentAppointmentId = appointmentIdFromUrl;
        loadAppointmentAndPatient();
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
    
    // Search patients
    $('#searchPatients').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPatients(searchTerm);
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
 * Load patient list
 */
function loadPatientList() {
    console.log('Loading patient list for doctor...');
    
    $.ajax({
        url: '../ajax/get_doctor_patients.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Patients response:', response);
            
            // Handle response format
            let patients = [];
            if (response.data && Array.isArray(response.data)) {
                patients = response.data;
            } else if (Array.isArray(response)) {
                patients = response;
            }
            
            allPatients = patients;
            displayPatientList(patients);
            $('#patientCount').text(patients.length);
            
            if (patients.length === 0) {
                console.log('No patients found for this doctor');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading patients:', error);
            $('#patientListContainer').html(`
                <div class="alert alert-danger m-3">
                    <i class="bx bx-error me-2"></i>
                    Failed to load patients
                </div>
            `);
        }
    });
}

/**
 * Display patient list
 */
function displayPatientList(patients) {
    if (!patients || patients.length === 0) {
        $('#patientListContainer').html(`
            <div class="text-center py-5 text-muted">
                <i class="bx bx-user-x" style="font-size: 48px;"></i>
                <p class="mt-3">No patients found</p>
            </div>
        `);
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    
    patients.forEach(function(patient) {
        const age = calculateAge(patient.date_of_birth);
        const fullName = `${patient.first_name} ${patient.last_name}`;
        const isActive = currentPatientId == patient.id ? 'active' : '';
        
        html += `
            <a href="javascript:void(0)" 
               class="list-group-item list-group-item-action patient-item ${isActive}" 
               data-patient-id="${patient.id}"
               onclick="selectPatient(${patient.id})">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${escapeHtml(fullName)}</h6>
                    <small>${age} yrs</small>
                </div>
                <p class="mb-1 text-muted small">
                    <i class="bx bx-id-card me-1"></i>ID: ${patient.id} | 
                    <i class="bx ${patient.gender === 'male' ? 'bx-male' : 'bx-female'} me-1"></i>${capitalize(patient.gender || 'N/A')}
                </p>
                ${patient.phone ? `<small class="text-muted"><i class="bx bx-phone me-1"></i>${escapeHtml(patient.phone)}</small>` : ''}
            </a>
        `;
    });
    
    html += '</div>';
    $('#patientListContainer').html(html);
}

/**
 * Filter patients by search term
 */
function filterPatients(searchTerm) {
    if (!searchTerm) {
        displayPatientList(allPatients);
        return;
    }
    
    const filtered = allPatients.filter(function(patient) {
        const fullName = `${patient.first_name} ${patient.last_name}`.toLowerCase();
        const phone = (patient.phone || '').toLowerCase();
        const id = patient.id.toString();
        
        return fullName.includes(searchTerm) || 
               phone.includes(searchTerm) || 
               id.includes(searchTerm);
    });
    
    displayPatientList(filtered);
}

/**
 * Select a patient and load their medical records
 */
function selectPatient(patientId) {
    console.log('Selecting patient:', patientId);
    currentPatientId = patientId;
    
    // Update active state in list
    $('.patient-item').removeClass('active');
    $(`.patient-item[data-patient-id="${patientId}"]`).addClass('active');
    
    // Load patient info and medical records
    loadPatientInfo();
    loadMedicalRecords();
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
    if (!currentPatientId) {
        console.error('No patient ID selected');
        showPatientError('No patient selected');
        return;
    }
    
    // Use get_patient.php endpoint with cache-busting parameter
    const url = '../ajax/get_patient.php?v=' + Date.now();
    console.log('Calling URL:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        data: { id: currentPatientId },
        dataType: 'json',
        cache: false,
        success: function(response) {
            console.log('Patient info response:', response);
            
            if (response.success && response.data) {
                displayPatientInfo(response.data);
            } else {
                console.error('Failed to load patient info:', response.message);
                showPatientError('Unable to load patient information: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading patient info:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            showPatientError('Error loading patient details');
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
    if (!currentPatientId) {
        showNoRecords();
        return;
    }
    
    $('#recordsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    // Get doctor_id from current user (available from layout-loader)
    const doctorId = window.currentUser && window.currentUser.doctor_id ? window.currentUser.doctor_id : null;
    
    $.ajax({
        url: '../ajax/get_medical_records.php',
        type: 'GET',
        data: { 
            patient_id: currentPatientId,
            doctor_id: doctorId  // Filter by current doctor
        },
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
        
        // Parse vital signs from JSON if needed
        let vitals = [];
        if (record.vital_signs) {
            let vitalData;
            if (typeof record.vital_signs === 'string') {
                try {
                    vitalData = JSON.parse(record.vital_signs);
                } catch (e) {
                    console.error('Error parsing vital signs:', e);
                    vitalData = {};
                }
            } else {
                vitalData = record.vital_signs;
            }
            
            if (vitalData.blood_pressure) vitals.push(`BP: ${vitalData.blood_pressure}`);
            if (vitalData.heart_rate) vitals.push(`HR: ${vitalData.heart_rate}`);
            if (vitalData.temperature) vitals.push(`Temp: ${vitalData.temperature}`);
            if (vitalData.weight) vitals.push(`Weight: ${vitalData.weight}`);
        }
        
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
                                <p>${escapeHtml(record.treatment_plan || record.treatment || 'N/A')}</p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="fw-bold">Prescription:</label>
                                <p>${escapeHtml(record.prescription || 'No prescription')}</p>
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
    
    // Set patient and doctor IDs
    $('#patientIdField').val(currentPatientId);
    
    // Set doctor ID from current user
    const doctorId = window.currentUser && window.currentUser.doctor_id ? window.currentUser.doctor_id : null;
    $('#doctorIdField').val(doctorId);
    
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    $('#visitDate').val(today);
    
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
    
    console.log('Saving medical record, ID:', recordId, 'URL:', url);
    
    // Create FormData to handle file upload
    const formData = new FormData();
    
    // Get form and add all basic fields
    const form = document.getElementById('medicalRecordForm');
    const formDataFromForm = new FormData(form);
    
    // Copy all form data EXCEPT vital sign fields (we'll handle those separately)
    const vitalSignFields = ['bloodPressure', 'heartRate', 'temperature', 'weight'];
    for (let [key, value] of formDataFromForm.entries()) {
        formData.append(key, value);
    }
    
    // Combine vital signs into a JSON object
    const vitalSigns = {};
    const bloodPressure = $('#bloodPressure').val().trim();
    const heartRate = $('#heartRate').val().trim();
    const temperature = $('#temperature').val().trim();
    const weight = $('#weight').val().trim();
    
    if (bloodPressure) vitalSigns.blood_pressure = bloodPressure;
    if (heartRate) vitalSigns.heart_rate = heartRate;
    if (temperature) vitalSigns.temperature = temperature;
    if (weight) vitalSigns.weight = weight;
    
    // Only add vital_signs if at least one value exists
    if (Object.keys(vitalSigns).length > 0) {
        formData.append('vital_signs', JSON.stringify(vitalSigns));
    }
    
    // Handle file upload if present
    const fileInput = document.getElementById('attachment');
    if (fileInput && fileInput.files[0]) {
        formData.append('attachment', fileInput.files[0]);
    }
    
    // Debug: log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Save response:', response);
            
            if (response.success) {
                $('#medicalRecordModal').modal('hide');
                loadMedicalRecords();
                alert('Medical record saved successfully');
            } else {
                alert('Failed to save: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error saving medical record:', error);
            console.error('Response:', xhr.responseText);
            alert('Error saving medical record: ' + error);
        }
    });
}

/**
 * Edit medical record
 */
function editMedicalRecord(recordId) {
    console.log('Editing medical record:', recordId);
    
    $.ajax({
        url: '../ajax/get_medical_record.php',
        type: 'GET',
        data: { id: recordId },
        dataType: 'json',
        success: function(response) {
            console.log('Edit record response:', response);
            
            if (response.success && response.data) {
                const record = response.data;
                
                $('#modalTitle').text('Edit Medical Record');
                $('#recordId').val(record.id);
                $('#patientIdField').val(record.patient_id);
                $('#doctorIdField').val(record.doctor_id);
                $('#visitDate').val(record.visit_date || record.record_date || '');
                $('#chiefComplaint').val(record.chief_complaint || '');
                $('#diagnosis').val(record.diagnosis || '');
                $('#treatmentPlan').val(record.treatment_plan || record.treatment || '');
                $('#prescription').val(record.prescription || '');
                
                // Parse vital signs from JSON
                if (record.vital_signs) {
                    let vitals;
                    if (typeof record.vital_signs === 'string') {
                        try {
                            vitals = JSON.parse(record.vital_signs);
                        } catch (e) {
                            console.error('Error parsing vital signs:', e);
                            vitals = {};
                        }
                    } else {
                        vitals = record.vital_signs;
                    }
                    
                    $('#bloodPressure').val(vitals.blood_pressure || '');
                    $('#heartRate').val(vitals.heart_rate || '');
                    $('#temperature').val(vitals.temperature || '');
                    $('#weight').val(vitals.weight || '');
                } else {
                    // Clear vital signs if none exist
                    $('#bloodPressure').val('');
                    $('#heartRate').val('');
                    $('#temperature').val('');
                    $('#weight').val('');
                }
                
                $('#notes').val(record.notes || '');
                
                const modal = new bootstrap.Modal(document.getElementById('medicalRecordModal'));
                modal.show();
            } else {
                alert('Failed to load medical record details: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading medical record:', error);
            alert('Error loading medical record: ' + error);
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
    
    console.log('Deleting medical record:', recordId);
    
    $.ajax({
        url: '../ajax/delete_medical_record.php',
        type: 'POST',
        data: { id: recordId },
        dataType: 'json',
        success: function(response) {
            console.log('Delete response:', response);
            
            if (response.success) {
                alert('Medical record deleted successfully');
                loadMedicalRecords();
            } else {
                alert('Failed to delete medical record: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error deleting medical record:', error);
            alert('Error deleting medical record: ' + error);
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
        <div class="card-body">
            <div class="alert alert-danger mb-0">
                <i class="bx bx-error me-2"></i>
                ${message}
            </div>
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
