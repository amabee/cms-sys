// Global variables
let currentPatientId = null;
let currentRecordId = null;

$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Get patient/record from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentPatientId = urlParams.get('patient_id');
    currentRecordId = urlParams.get('record_id');
    
    if (currentPatientId) {
        loadPatientInfo();
        loadPrescriptions();
    }
    
    // Form submit handler
    $('#prescriptionForm').on('submit', function(e) {
        e.preventDefault();
        savePrescription();
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
                const patient = response.data.patient_info;
                $('#patientName').text(patient.first_name + ' ' + patient.last_name);
                $('#patientId').text(patient.patient_id || patient.id);
                $('#patientInfoCard').show();
            }
        }
    });
}

/**
 * Load prescriptions
 */
function loadPrescriptions() {
    $('#prescriptionsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    const params = { patient_id: currentPatientId };
    if (currentRecordId) {
        params.record_id = currentRecordId;
    }
    
    $.ajax({
        url: '../ajax/get_prescriptions.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayPrescriptions(response.data);
            } else {
                showNoPrescriptions();
            }
        },
        error: function() {
            showErrorMessage();
        }
    });
}

/**
 * Display prescriptions
 */
function displayPrescriptions(prescriptions) {
    if (!prescriptions || prescriptions.length === 0) {
        showNoPrescriptions();
        return;
    }
    
    $('#prescriptionCount').text(prescriptions.length);
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    prescriptions.forEach(function(rx) {
        const statusClass = rx.status === 'active' ? 'bg-label-success' : 'bg-label-secondary';
        const statusText = capitalize(rx.status || 'completed');
        
        html += `
            <tr>
                <td>${formatDate(rx.created_at || rx.prescription_date)}</td>
                <td><strong>${escapeHtml(rx.medication_name)}</strong></td>
                <td>${escapeHtml(rx.dosage)}</td>
                <td>${escapeHtml(rx.frequency)}</td>
                <td>${escapeHtml(rx.duration)}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewPrescriptionDetails(${rx.id})">
                                <i class="bx bx-show me-1"></i> View Details
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="printPrescription(${rx.id})">
                                <i class="bx bx-printer me-1"></i> Print
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
    
    $('#prescriptionsContainer').html(html);
}

/**
 * Show create prescription modal
 */
function showCreatePrescriptionModal() {
    if (!currentPatientId) {
        alert('No patient selected');
        return;
    }
    
    $('#prescriptionForm')[0].reset();
    const modal = new bootstrap.Modal(document.getElementById('prescriptionModal'));
    modal.show();
}

/**
 * Save prescription
 */
function savePrescription() {
    const formData = {
        patient_id: currentPatientId,
        record_id: currentRecordId,
        medication_name: $('#medicationName').val(),
        dosage: $('#dosage').val(),
        frequency: $('#frequency').val(),
        duration: $('#duration').val(),
        quantity: $('#quantity').val(),
        instructions: $('#instructions').val()
    };
    
    $.ajax({
        url: '../ajax/create_prescription.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Prescription saved successfully');
                bootstrap.Modal.getInstance(document.getElementById('prescriptionModal')).hide();
                loadPrescriptions();
            } else {
                alert(response.message || 'Failed to save prescription');
            }
        },
        error: function() {
            alert('Error saving prescription. Please try again.');
        }
    });
}

/**
 * View prescription details
 */
function viewPrescriptionDetails(prescriptionId) {
    // For now, just alert - could open a modal with full details
    alert('View prescription details: ' + prescriptionId);
}

/**
 * Print prescription
 */
function printPrescription(prescriptionId) {
    window.open(`../reports/prescription_print.php?id=${prescriptionId}`, '_blank');
}

/**
 * Show no prescriptions message
 */
function showNoPrescriptions() {
    $('#prescriptionCount').text('0');
    $('#prescriptionsContainer').html(`
        <div class="text-center py-5">
            <i class="bx bx-receipt" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">No prescriptions found</p>
            <button type="button" class="btn btn-primary mt-2" onclick="showCreatePrescriptionModal()">
                <i class="bx bx-plus me-2"></i>Create First Prescription
            </button>
        </div>
    `);
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#prescriptionsContainer').html(`
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error me-2"></i>
            Failed to load prescriptions. Please try refreshing the page.
        </div>
    `);
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
