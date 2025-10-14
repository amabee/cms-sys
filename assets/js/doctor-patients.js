// Global variables
let currentPatientId = null;

$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Enter key to search
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            searchPatients();
        }
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
 * Search patients
 */
function searchPatients() {
    const searchTerm = $('#searchInput').val().trim();
    
    if (searchTerm.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }
    
    // Show loading
    $('#patientsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Searching...</span>
            </div>
            <p class="text-muted mt-2">Searching patients...</p>
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
        error: function(xhr, status, error) {
            console.error('Error searching patients:', error);
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
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Contact</th>
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
                <td>${age} years</td>
                <td>${capitalize(patient.gender || 'N/A')}</td>
                <td>${escapeHtml(patient.phone || 'N/A')}</td>
                <td>${lastVisit}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="viewPatientDetails(${patient.id})">
                        <i class="bx bx-show me-1"></i>View
                    </button>
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
 * View patient details
 */
function viewPatientDetails(patientId) {
    currentPatientId = patientId;
    
    // Show modal with loading
    const modal = new bootstrap.Modal(document.getElementById('patientModal'));
    modal.show();
    
    $('#patientDetailsContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    // Load patient details
    $.ajax({
        url: '../ajax/get_patient_dashboard.php',
        type: 'GET',
        data: { patient_id: patientId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayPatientDetails(response.data);
            } else {
                $('#patientDetailsContent').html(`
                    <div class="alert alert-danger">Failed to load patient details</div>
                `);
            }
        },
        error: function() {
            $('#patientDetailsContent').html(`
                <div class="alert alert-danger">Error loading patient details</div>
            `);
        }
    });
}

/**
 * Display patient details in modal
 */
function displayPatientDetails(data) {
    const patient = data.patient_info || {};
    const allergies = data.allergies || [];
    const recentVisits = data.recent_appointments || [];
    
    const age = calculateAge(patient.date_of_birth);
    
    let html = `
        <!-- Patient Info -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="text-primary mb-3">Personal Information</h6>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <p class="mb-0">${escapeHtml(patient.first_name + ' ' + patient.last_name)}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Patient ID</label>
                <p class="mb-0">${escapeHtml(patient.patient_id || patient.id)}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Age / Gender</label>
                <p class="mb-0">${age} years / ${capitalize(patient.gender || 'N/A')}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Phone</label>
                <p class="mb-0">${escapeHtml(patient.phone || 'N/A')}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Email</label>
                <p class="mb-0">${escapeHtml(patient.email || 'N/A')}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Blood Type</label>
                <p class="mb-0">${escapeHtml(patient.blood_type || 'N/A')}</p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Address</label>
                <p class="mb-0">${escapeHtml(patient.address || 'N/A')}</p>
            </div>
        </div>
        
        <!-- Allergies -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="text-primary mb-3">Allergies</h6>
            </div>
            <div class="col-12">
    `;
    
    if (allergies.length > 0) {
        allergies.forEach(function(allergy) {
            const severityClass = allergy.severity === 'severe' ? 'danger' : 
                                 allergy.severity === 'moderate' ? 'warning' : 'info';
            html += `
                <span class="badge bg-${severityClass} me-2 mb-2">
                    ${escapeHtml(allergy.allergen)} (${capitalize(allergy.severity)})
                </span>
            `;
        });
    } else {
        html += `<p class="text-muted mb-0">No known allergies</p>`;
    }
    
    html += `
            </div>
        </div>
        
        <!-- Recent Visits -->
        <div class="row">
            <div class="col-12">
                <h6 class="text-primary mb-3">Recent Visits</h6>
            </div>
            <div class="col-12">
    `;
    
    if (recentVisits.length > 0) {
        html += `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        recentVisits.slice(0, 5).forEach(function(visit) {
            const statusClass = getStatusClass(visit.status);
            html += `
                <tr>
                    <td>${formatDate(visit.appointment_date)}</td>
                    <td>${escapeHtml(visit.reason || 'N/A')}</td>
                    <td><span class="badge ${statusClass}">${capitalize(visit.status)}</span></td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        html += `<p class="text-muted mb-0">No recent visits</p>`;
    }
    
    html += `
            </div>
        </div>
    `;
    
    $('#patientDetailsContent').html(html);
}

/**
 * View full medical record
 */
function viewFullMedicalRecord() {
    if (!currentPatientId) return;
    window.location.href = `doctor-medical-records.html?patient_id=${currentPatientId}`;
}

/**
 * Show no results message
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

/**
 * Get status badge class
 */
function getStatusClass(status) {
    const classes = {
        'scheduled': 'bg-label-primary',
        'confirmed': 'bg-label-info',
        'in-progress': 'bg-label-warning',
        'completed': 'bg-label-success',
        'cancelled': 'bg-label-danger'
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
