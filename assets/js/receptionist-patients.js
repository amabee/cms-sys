$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Check if we should show register modal (from URL param)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'new') {
        showRegisterPatientModal();
    }
    
    // Enter key to search
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            searchPatients();
        }
    });
    
    // Form submit handler
    $('#patientForm').on('submit', function(e) {
        e.preventDefault();
        savePatient();
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
 * Search patients
 */
function searchPatients() {
    const searchTerm = $('#searchInput').val().trim();
    
    if (searchTerm.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }
    
    $('#patientsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Searching...</span>
            </div>
        </div>
    `);
    
    $.ajax({
        url: '../ajax/get_patients.php',
        type: 'GET',
        data: { search: searchTerm },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayPatients(response.data);
            } else {
                showNoResults();
            }
        },
        error: function() {
            showErrorMessage();
        }
    });
}

/**
 * Display patients
 */
function displayPatients(patients) {
    if (!patients || patients.length === 0) {
        showNoResults();
        return;
    }
    
    $('#patientCount').text(patients.length);
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Age/Gender</th>
                        <th>Contact</th>
                        <th>Blood Type</th>
                        <th>Last Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    patients.forEach(function(patient) {
        const age = calculateAge(patient.date_of_birth);
        const lastVisit = patient.last_visit ? formatDate(patient.last_visit) : 'Never';
        
        html += `
            <tr>
                <td><strong>${escapeHtml(patient.patient_id || patient.id)}</strong></td>
                <td>
                    <div>${escapeHtml(patient.first_name + ' ' + patient.last_name)}</div>
                    <small class="text-muted">${escapeHtml(patient.email || '')}</small>
                </td>
                <td>${age} yrs / ${capitalize(patient.gender || 'N/A')}</td>
                <td>${escapeHtml(patient.phone || 'N/A')}</td>
                <td>${escapeHtml(patient.blood_type || 'N/A')}</td>
                <td>${lastVisit}</td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewPatientDetails(${patient.id})">
                                <i class="bx bx-show me-1"></i> View Details
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="editPatient(${patient.id})">
                                <i class="bx bx-edit me-1"></i> Edit
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="createAppointmentForPatient(${patient.id})">
                                <i class="bx bx-calendar-plus me-1"></i> Book Appointment
                            </a>
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
    
    $('#patientsContainer').html(html);
}

/**
 * Show register patient modal
 */
function showRegisterPatientModal() {
    $('#modalTitle').text('Register New Patient');
    $('#patientForm')[0].reset();
    $('#patientIdHidden').val('');
    
    const modal = new bootstrap.Modal(document.getElementById('patientModal'));
    modal.show();
}

/**
 * Edit patient
 */
function editPatient(patientId) {
    $.ajax({
        url: '../ajax/get_patient_dashboard.php',
        type: 'GET',
        data: { patient_id: patientId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const patient = response.data.patient_info;
                
                $('#modalTitle').text('Edit Patient');
                $('#patientIdHidden').val(patient.id);
                $('#firstName').val(patient.first_name);
                $('#lastName').val(patient.last_name);
                $('#dateOfBirth').val(patient.date_of_birth);
                $('#gender').val(patient.gender);
                $('#bloodType').val(patient.blood_type);
                $('#maritalStatus').val(patient.marital_status);
                $('#phone').val(patient.phone);
                $('#email').val(patient.email);
                $('#address').val(patient.address);
                $('#emergencyContactName').val(patient.emergency_contact_name);
                $('#emergencyContactPhone').val(patient.emergency_contact_phone);
                $('#medicalNotes').val(patient.medical_notes);
                
                const modal = new bootstrap.Modal(document.getElementById('patientModal'));
                modal.show();
            } else {
                alert('Failed to load patient details');
            }
        },
        error: function() {
            alert('Error loading patient details');
        }
    });
}

/**
 * Save patient
 */
function savePatient() {
    const formData = {
        first_name: $('#firstName').val(),
        last_name: $('#lastName').val(),
        date_of_birth: $('#dateOfBirth').val(),
        gender: $('#gender').val(),
        blood_type: $('#bloodType').val(),
        marital_status: $('#maritalStatus').val(),
        phone: $('#phone').val(),
        email: $('#email').val(),
        address: $('#address').val(),
        emergency_contact_name: $('#emergencyContactName').val(),
        emergency_contact_phone: $('#emergencyContactPhone').val(),
        medical_notes: $('#medicalNotes').val()
    };
    
    const patientId = $('#patientIdHidden').val();
    if (patientId) {
        formData.patient_id = patientId;
    }
    
    const url = patientId ? '../ajax/update_patient.php' : '../ajax/create_patient.php';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Patient saved successfully');
                bootstrap.Modal.getInstance(document.getElementById('patientModal')).hide();
                
                // Refresh search if there was a search term
                const searchTerm = $('#searchInput').val();
                if (searchTerm.length >= 2) {
                    searchPatients();
                }
            } else {
                alert(response.message || 'Failed to save patient');
            }
        },
        error: function() {
            alert('Error saving patient. Please try again.');
        }
    });
}

/**
 * View patient details
 */
function viewPatientDetails(patientId) {
    alert('View full patient details: ' + patientId);
    // Could open a detailed modal or redirect to a patient profile page
}

/**
 * Create appointment for patient
 */
function createAppointmentForPatient(patientId) {
    window.location.href = `receptionist-appointments.html?patient_id=${patientId}&action=new`;
}

/**
 * Show no results
 */
function showNoResults() {
    $('#patientCount').text('0');
    $('#patientsContainer').html(`
        <div class="text-center py-5">
            <i class="bx bx-user-x" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">No patients found</p>
        </div>
    `);
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#patientsContainer').html(`
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error me-2"></i>
            Failed to search patients. Please try again.
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
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
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
