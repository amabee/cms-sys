/**
 * Doctor Patient Search JavaScript
 * Enhanced patient search functionality for doctors
 */

$(document).ready(function() {
    let selectedPatients = new Set();
    let searchTimeout;
    let currentPatientId = null;

    // Initialize search functionality
    setupEventListeners();
});

function setupEventListeners() {
    // Search input with debouncing
    $('#patientSearchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    });

    // Advanced search toggle
    $('#advancedSearchBtn').on('click', function() {
        $('#advancedSearchPanel').toggle();
        $(this).toggleClass('active');
    });

    // Filter changes
    $('#filterGender, #filterAgeRange, #filterLastVisit').on('change', performSearch);

    // Clear filters
    $('#clearFiltersBtn').on('click', function() {
        $('#filterGender, #filterAgeRange, #filterLastVisit').val('');
        $('#patientSearchInput').val('');
        $('#searchResultsContainer').html(getEmptySearchMessage());
        selectedPatients.clear();
        updateSelectedPatientsUI();
    });

    // Quick add to queue
    $('#quickAddToQueueBtn').on('click', addSelectedToQueue);

    // Patient selection
    $(document).on('change', '.patient-checkbox', function() {
        const patientId = $(this).val();
        if ($(this).is(':checked')) {
            selectedPatients.add(patientId);
        } else {
            selectedPatients.delete(patientId);
        }
        updateSelectedPatientsUI();
    });

    // Patient details
    $(document).on('click', '.view-patient-details', function() {
        const patientId = $(this).data('patient-id');
        showPatientDetails(patientId);
    });

    // Modal actions
    $('#addToQueueFromModal').on('click', function() {
        if (currentPatientId) {
            addToQueue(currentPatientId);
            $('#patientDetailsModal').modal('hide');
        }
    });

    $('#newMedicalRecordFromModal').on('click', function() {
        if (currentPatientId) {
            window.open(`medical-records.php?patient_id=${currentPatientId}`, '_blank');
        }
    });

    $('#newLabTestFromModal').on('click', function() {
        if (currentPatientId) {
            window.open(`lab-tests.php?patient_id=${currentPatientId}`, '_blank');
        }
    });
}

function performSearch() {
    const query = $('#patientSearchInput').val().trim();
    const gender = $('#filterGender').val();
    const ageRange = $('#filterAgeRange').val();
    const lastVisit = $('#filterLastVisit').val();

    if (query.length < 2 && !gender && !ageRange && !lastVisit) {
        $('#searchResultsContainer').html(getEmptySearchMessage());
        return;
    }

    // Show loading
    $('#searchResultsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Searching...</span>
            </div>
            <p class="mt-2 text-muted">Searching patients...</p>
        </div>
    `);

    // Build search parameters
    const params = {};
    if (query) params.search = query;
    if (gender) params.gender = gender;
    if (ageRange) params.age_range = ageRange;
    if (lastVisit) params.last_visit = lastVisit;

    $.get('ajax/get_patients.php', params)
        .done(function(response) {
            if (response.success && response.data && response.data.length > 0) {
                displaySearchResults(response.data);
            } else {
                $('#searchResultsContainer').html(getNoResultsMessage(query));
            }
        })
        .fail(function() {
            $('#searchResultsContainer').html(`
                <div class="alert alert-danger">
                    <i class="bx bx-error me-2"></i>
                    Error occurred while searching. Please try again.
                </div>
            `);
        });
}

function displaySearchResults(patients) {
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">${patients.length} patient(s) found</h6>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPatients()">
                    <i class="bx bx-check-square me-1"></i>Select All
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="clearAllSelections()">
                    <i class="bx bx-square me-1"></i>Clear All
                </button>
            </div>
        </div>
        <div class="row">
    `;

    patients.forEach(patient => {
        const age = calculateAge(patient.date_of_birth);
        const isSelected = selectedPatients.has(patient.id.toString());
        
        html += `
            <div class="col-lg-6 col-12 mb-3">
                <div class="card h-100 ${isSelected ? 'border-primary' : ''}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="form-check">
                                <input class="form-check-input patient-checkbox" type="checkbox" 
                                       value="${patient.id}" ${isSelected ? 'checked' : ''}>
                                <label class="form-check-label">
                                    <h6 class="mb-1">${patient.first_name} ${patient.last_name}</h6>
                                </label>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item view-patient-details" href="#" 
                                           data-patient-id="${patient.id}">
                                            <i class="bx bx-user me-2"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" 
                                           onclick="addToQueue('${patient.id}')">
                                            <i class="bx bx-plus me-2"></i>Add to Queue
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="medical-records.php?patient_id=${patient.id}" target="_blank">
                                            <i class="bx bx-file-plus me-2"></i>New Medical Record
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="lab-tests.php?patient_id=${patient.id}" target="_blank">
                                            <i class="bx bx-test-tube me-2"></i>New Lab Test
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <div class="row text-muted small">
                                <div class="col-sm-6">
                                    <i class="bx bx-id-card me-1"></i>ID: ${patient.patient_id || 'N/A'}
                                </div>
                                <div class="col-sm-6">
                                    <i class="bx bx-calendar me-1"></i>Age: ${age} years
                                </div>
                            </div>
                            <div class="row text-muted small mt-1">
                                <div class="col-sm-6">
                                    <i class="bx bx-phone me-1"></i>${patient.phone || 'No phone'}
                                </div>
                                <div class="col-sm-6">
                                    <i class="bx bx-user me-1"></i>${patient.gender || 'N/A'}
                                </div>
                            </div>
                            ${patient.email ? `
                                <div class="text-muted small mt-1">
                                    <i class="bx bx-envelope me-1"></i>${patient.email}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    $('#searchResultsContainer').html(html);
}

function showPatientDetails(patientId) {
    currentPatientId = patientId;
    
    $('#patientDetailsModal').modal('show');
    $('#patientDetailsBody').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading patient details...</p>
        </div>
    `);

    $.get('ajax/get_patient.php', { id: patientId })
        .done(function(response) {
            if (response.success && response.data) {
                displayPatientDetails(response.data);
            } else {
                $('#patientDetailsBody').html(`
                    <div class="alert alert-danger">
                        Failed to load patient details
                    </div>
                `);
            }
        })
        .fail(function() {
            $('#patientDetailsBody').html(`
                <div class="alert alert-danger">
                    Error loading patient details
                </div>
            `);
        });
}

function displayPatientDetails(patient) {
    const age = calculateAge(patient.date_of_birth);
    
    const html = `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Personal Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Full Name:</strong></td>
                                <td>${patient.first_name} ${patient.last_name}</td>
                            </tr>
                            <tr>
                                <td><strong>Patient ID:</strong></td>
                                <td>${patient.patient_id || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Date of Birth:</strong></td>
                                <td>${patient.date_of_birth} (${age} years old)</td>
                            </tr>
                            <tr>
                                <td><strong>Gender:</strong></td>
                                <td>${patient.gender || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td>${patient.phone || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>${patient.email || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Address:</strong></td>
                                <td>${patient.address || 'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Medical Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Blood Group:</strong></td>
                                <td>${patient.blood_group || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Emergency Contact:</strong></td>
                                <td>${patient.emergency_contact_name || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Emergency Phone:</strong></td>
                                <td>${patient.emergency_contact_phone || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Allergies:</strong></td>
                                <td>${patient.allergies || 'None reported'}</td>
                            </tr>
                            <tr>
                                <td><strong>Medical History:</strong></td>
                                <td>${patient.medical_history || 'None reported'}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge ${patient.is_active ? 'bg-success' : 'bg-danger'}">
                                        ${patient.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity (placeholder for future enhancement) -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Recent Activity</h6>
            </div>
            <div class="card-body">
                <div class="text-muted text-center py-3">
                    Recent appointments, medical records, and lab tests will be displayed here
                </div>
            </div>
        </div>
    `;
    
    $('#patientDetailsBody').html(html);
}

function addToQueue(patientId) {
    $.post('ajax/add_to_queue.php', {
        patient_id: patientId
    })
    .done(function(response) {
        if (response.success) {
            showSuccess('Patient added to queue successfully');
        } else {
            showError('Failed to add patient to queue: ' + (response.message || 'Unknown error'));
        }
    })
    .fail(function() {
        showError('Error adding patient to queue');
    });
}

function addSelectedToQueue() {
    if (selectedPatients.size === 0) {
        showWarning('Please select at least one patient');
        return;
    }

    const patients = Array.from(selectedPatients);
    let completed = 0;
    let errors = 0;

    // Add each patient to queue
    patients.forEach(patientId => {
        $.post('ajax/add_to_queue.php', { patient_id: patientId })
            .done(function(response) {
                completed++;
                if (!response.success) errors++;
                
                if (completed === patients.length) {
                    if (errors === 0) {
                        showSuccess(`Successfully added ${patients.length} patients to queue`);
                        selectedPatients.clear();
                        updateSelectedPatientsUI();
                        // Refresh search to update UI
                        performSearch();
                    } else {
                        showWarning(`Added ${patients.length - errors} patients to queue. ${errors} failed.`);
                    }
                }
            })
            .fail(function() {
                completed++;
                errors++;
                
                if (completed === patients.length) {
                    showError(`Failed to add patients to queue. ${errors} errors occurred.`);
                }
            });
    });
}

function selectAllPatients() {
    $('.patient-checkbox').each(function() {
        $(this).prop('checked', true);
        selectedPatients.add($(this).val());
    });
    updateSelectedPatientsUI();
}

function clearAllSelections() {
    $('.patient-checkbox').prop('checked', false);
    selectedPatients.clear();
    updateSelectedPatientsUI();
}

function updateSelectedPatientsUI() {
    const count = selectedPatients.size;
    const btn = $('#quickAddToQueueBtn');
    
    if (count > 0) {
        btn.prop('disabled', false)
           .html(`<i class="bx bx-plus me-1"></i>Add ${count} Selected to Queue`);
    } else {
        btn.prop('disabled', true)
           .html('<i class="bx bx-plus me-1"></i>Add Selected to Queue');
    }
}

// Utility functions
function calculateAge(birthDate) {
    if (!birthDate) return 'N/A';
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
}

function getEmptySearchMessage() {
    return `
        <div class="text-center py-5 text-muted">
            <i class="bx bx-search fs-1"></i>
            <p class="mt-2">Enter a search term to find patients</p>
            <small>Search by name, patient ID, phone number, or email address</small>
        </div>
    `;
}

function getNoResultsMessage(query) {
    return `
        <div class="text-center py-5 text-muted">
            <i class="bx bx-search-alt fs-1"></i>
            <p class="mt-2">No patients found matching "${query}"</p>
            <small>Try different search terms or check the spelling</small>
        </div>
    `;
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

function showWarning(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Warning',
            text: message
        });
    } else {
        alert('Warning: ' + message);
    }
}
