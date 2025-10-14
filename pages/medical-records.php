<?php
$page_title = 'Medical Records Management';
$additional_css = [];
$additional_js = ['https://cdn.jsdelivr.net/npm/sweetalert2@11'];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor','patient']);

ob_start();
?>
<!-- Statistics Cards -->
<?php if (in_array($user_type, ['admin','doctor'])): ?>
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-file-blank bx-sm text-primary"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">Total Records</span>
                <h3 class="card-title text-nowrap mb-1" id="totalRecords">0</h3>
                <small class="text-primary fw-semibold"><i class="bx bx-file"></i> All Time</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-calendar bx-sm text-success"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">This Month</span>
                <h3 class="card-title text-nowrap mb-1" id="recordsThisMonth">0</h3>
                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> Records</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-time-five bx-sm text-info"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">Today</span>
                <h3 class="card-title text-nowrap mb-1" id="recordsToday">0</h3>
                <small class="text-info fw-semibold"><i class="bx bx-calendar-check"></i> Records</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-search bx-sm text-warning"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">Advanced Search</span>
                <button class="btn btn-sm btn-outline-warning mt-1" id="advancedSearchBtn">
                    <i class="bx bx-search me-1"></i>Search
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Medical Records Table -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Medical Records</h4>
                <div class="d-flex gap-2">
                    <?php if (in_array($user_type, ['admin','doctor'])): ?>
                        <button type="button" class="btn btn-primary" id="addRecordBtn">
                            <i class="bx bx-plus me-1"></i> New Record
                        </button>
                    <?php endif; ?>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bx bx-filter me-1"></i> Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item filter-date" href="#" data-filter="today">Today</a></li>
                            <li><a class="dropdown-item filter-date" href="#" data-filter="week">This Week</a></li>
                            <li><a class="dropdown-item filter-date" href="#" data-filter="month">This Month</a></li>
                            <li><a class="dropdown-item filter-date" href="#" data-filter="all">All Records</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="customFilterBtn">Custom Filter</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Quick Search -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" id="recordsSearch" placeholder="Search records (press Enter)">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                <i class="bx bx-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="viewMode" id="tableView" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="tableView">
                                <i class="bx bx-table me-1"></i>Table
                            </label>
                            
                            <input type="radio" class="btn-check" name="viewMode" id="cardView" autocomplete="off">
                            <label class="btn btn-outline-secondary" for="cardView">
                                <i class="bx bx-grid-alt me-1"></i>Cards
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div id="tableViewContainer">
                    <div class="table-responsive">
                        <table class="table table-striped" id="recordsTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Record ID</th>
                                    <th>Patient</th>
                                    <th>Chief Complaint</th>
                                    <th>Diagnosis</th>
                                    <th>Visit Date</th>
                                    <th>Doctor</th>
                                    <th>Attachments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Content loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Card View (Initially Hidden) -->
                <div id="cardViewContainer" style="display: none;">
                    <div class="row" id="recordsCardContainer">
                        <!-- Cards loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- View Record Modal -->
<div class="modal fade" id="viewRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-file-blank me-2"></i>Medical Record Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewRecordBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading medical record...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printRecordBtn">
                    <i class="bx bx-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit/Create Record Modal -->
<div class="modal fade" id="editRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-edit me-2"></i><span id="modalTitle">Add Medical Record</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="mrForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="mr_id" name="id" />
                    
                    <!-- Patient and Doctor Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Patient <span class="text-danger">*</span></label>
                            <select id="mr_patient_id" name="patient_id" class="form-select" required>
                                <option value="">Select Patient</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Doctor <span class="text-danger">*</span></label>
                            <select id="mr_doctor_id" name="doctor_id" class="form-select" required>
                                <option value="">Select Doctor</option>
                            </select>
                        </div>
                    </div>

                    <!-- Visit Date and Chief Complaint -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Visit Date <span class="text-danger">*</span></label>
                            <input type="date" id="mr_visit_date" name="visit_date" class="form-control" required />
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Chief Complaint</label>
                            <input type="text" id="mr_chief_complaint" name="chief_complaint" class="form-control" placeholder="Patient's main concern or reason for visit" />
                        </div>
                    </div>

                    <!-- Vital Signs -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bx bx-pulse me-2"></i>Vital Signs
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Blood Pressure</label>
                                    <input type="text" id="vital_bp" class="form-control" placeholder="120/80">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Heart Rate (bpm)</label>
                                    <input type="number" id="vital_hr" class="form-control" placeholder="72">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Temperature (°C)</label>
                                    <input type="number" id="vital_temp" class="form-control" step="0.1" placeholder="36.5">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" id="vital_weight" class="form-control" step="0.1" placeholder="70">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Height (cm)</label>
                                    <input type="number" id="vital_height" class="form-control" placeholder="170">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Respiratory Rate</label>
                                    <input type="number" id="vital_rr" class="form-control" placeholder="16">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Oxygen Saturation (%)</label>
                                    <input type="number" id="vital_spo2" class="form-control" placeholder="98">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Pain Level (0-10)</label>
                                    <input type="number" id="vital_pain_level" class="form-control" min="0" max="10" placeholder="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">BMI</label>
                                    <input type="text" id="vital_bmi" class="form-control" readonly placeholder="Auto calculated">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Diagnosis</label>
                            <textarea id="mr_diagnosis" name="diagnosis" class="form-control" rows="4" placeholder="Medical diagnosis and findings"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Treatment</label>
                            <textarea id="mr_treatment" name="treatment" class="form-control" rows="4" placeholder="Treatment plan and procedures"></textarea>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Prescription</label>
                            <textarea id="mr_prescription" name="prescription" class="form-control" rows="4" placeholder="Medications and dosages"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <textarea id="mr_notes" name="notes" class="form-control" rows="4" placeholder="Additional notes and observations"></textarea>
                        </div>
                    </div>

                    <!-- File Attachment -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bx bx-paperclip me-2"></i>Attachments
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Upload File (Optional)</label>
                                <input type="file" id="mr_attachment" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                                <div class="form-text">Supported formats: PDF, DOC, DOCX, JPG, PNG (Max: 5MB)</div>
                            </div>
                            <div id="existingAttachments"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="mrSaveBtn">
                        <i class="bx bx-save me-1"></i>Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Advanced Search Modal -->
<div class="modal fade" id="advancedSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-search-alt me-2"></i>Advanced Search
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="advancedSearchForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Search Terms</label>
                            <input type="text" id="advanced_search_term" class="form-control" placeholder="Search in diagnosis, treatment, notes...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Patient</label>
                            <select id="advanced_patient_id" class="form-select">
                                <option value="">All Patients</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Doctor</label>
                            <select id="advanced_doctor_id" class="form-select">
                                <option value="">All Doctors</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="date" id="advanced_date_from" class="form-control">
                                </div>
                                <div class="col-6">
                                    <input type="date" id="advanced_date_to" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-warning" id="clearSearchBtn">Clear</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-search me-1"></i>Search
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Filter Modal -->
<div class="modal fade" id="customFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Custom Filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customFilterForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <select id="filter_patient_id" class="form-select">
                            <option value="">All Patients</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select id="filter_doctor_id" class="form-select">
                            <option value="">All Doctors</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="date" id="filter_date_from" class="form-control" placeholder="From">
                            </div>
                            <div class="col-6">
                                <input type="date" id="filter_date_to" class="form-control" placeholder="To">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-warning" id="clearFilterBtn">Clear Filter</button>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>

<script>
let recordsTable;
let currentFilters = {};
let currentViewMode = 'table';

$(document).ready(function() {
    // Initialize components
    initializeMedicalRecordsTable();
    loadStatistics();
    loadDropdownOptions();
    
    // Set default date
    const today = new Date().toISOString().split('T')[0];
    $('#mr_visit_date').val(today);
    
    // Event handlers
    setupEventHandlers();
    
    // Auto-calculate BMI
    $('#vital_weight, #vital_height').on('input', calculateBMI);
});

function initializeMedicalRecordsTable() {
    recordsTable = $('#recordsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../ajax/get_medical_records.php',
            data: function(d) {
                // Add search value
                d.search = d.search || {};
                d.search.value = $('#recordsSearch').val();
                
                // Add custom filters
                Object.assign(d, currentFilters);
                
                return d;
            }
        },
        columns: [
            { 
                data: 'record_id', 
                name: 'record_id',
                render: function(data, type, row) {
                    return `<span class="badge bg-primary">${data || 'N/A'}</span>`;
                }
            },
            { 
                data: 'patient_name', 
                name: 'patient_name', 
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div>
                            <strong>${data}</strong><br>
                            <small class="text-muted">${row.patient_code || ''}</small>
                        </div>
                    `;
                }
            },
            { 
                data: 'chief_complaint', 
                name: 'chief_complaint',
                render: function(data) {
                    if (!data) return '<em class="text-muted">Not specified</em>';
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { 
                data: 'diagnosis', 
                name: 'diagnosis',
                render: function(data) {
                    if (!data) return '<em class="text-muted">Not specified</em>';
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { 
                data: 'visit_date', 
                name: 'visit_date',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            { 
                data: 'doctor_name', 
                name: 'doctor_name', 
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div>
                            <strong>${data || 'N/A'}</strong><br>
                            <small class="text-muted">${row.doctor_code || ''}</small>
                        </div>
                    `;
                }
            },
            {
                data: 'attachment_count',
                orderable: false,
                render: function(data, type, row) {
                    if (data > 0) {
                        return `<span class="badge bg-info"><i class="bx bx-paperclip"></i> ${data}</span>`;
                    }
                    return '<span class="text-muted">-</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let html = `
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item view-record" href="#" data-id="${row.id}">
                                    <i class="bx bx-show me-1"></i>View Details
                                </a></li>
                    `;
                    
                    <?php if (in_array($user_type, ['admin','doctor'])): ?>
                    html += `
                                <li><a class="dropdown-item edit-record" href="#" data-id="${row.id}">
                                    <i class="bx bx-edit me-1"></i>Edit Record
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger delete-record" href="#" data-id="${row.id}">
                                    <i class="bx bx-trash me-1"></i>Delete
                                </a></li>
                    `;
                    <?php endif; ?>
                    
                    html += `
                            </ul>
                        </div>
                    `;
                    return html;
                }
            }
        ],
        order: [[4, 'desc']], // Default sort by visit date descending
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading medical records...",
            emptyTable: "No medical records found"
        }
    });
}

function loadStatistics() {
    <?php if (in_array($user_type, ['admin','doctor'])): ?>
    $.ajax({
        url: '../ajax/get_medical_records_statistics.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#totalRecords').text(stats.total_records || 0);
                $('#recordsThisMonth').text(stats.records_this_month || 0);
                $('#recordsToday').text(stats.records_today || 0);
            }
        },
        error: function() {
            console.error('Failed to load medical records statistics');
        }
    });
    <?php endif; ?>
}

function loadPatientVitalSigns(patientId) {
    // Clear all vital signs first
    $('#vital_bp, #vital_temp, #vital_hr, #vital_rr, #vital_weight, #vital_height, #vital_spo2, #vital_pain_level, #vital_bmi').val('');
    
    $.ajax({
        url: '../ajax/get_patient_vital_signs.php',
        method: 'GET',
        data: { patient_id: patientId },
        success: function(response) {
            if (response.success && response.vital_signs) {
                const vs = response.vital_signs;
                
                // Populate vital signs fields
                if (vs.blood_pressure) $('#vital_bp').val(vs.blood_pressure);
                if (vs.temperature) $('#vital_temp').val(vs.temperature);
                if (vs.pulse) $('#vital_hr').val(vs.pulse);
                if (vs.respiratory_rate) $('#vital_rr').val(vs.respiratory_rate);
                if (vs.weight) $('#vital_weight').val(vs.weight);
                if (vs.height) $('#vital_height').val(vs.height);
                if (vs.oxygen_saturation) $('#vital_spo2').val(vs.oxygen_saturation);
                if (vs.pain_level) $('#vital_pain_level').val(vs.pain_level);
                
                // Calculate BMI
                calculateBMI();
                
                // Show success message
                const queuedTime = new Date(response.queued_at).toLocaleTimeString();
                toastr.success(`Vital signs loaded from today's queue entry (${queuedTime})`, 'Vital Signs Loaded');
            } else {
                // No vital signs found - show info message
                toastr.info('No vital signs found for today. Please enter manually.', 'No Vital Signs');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load vital signs:', error);
            toastr.warning('Could not load vital signs. Please enter manually.', 'Warning');
        }
    });
}

function loadDropdownOptions() {
    // Load patients (DataTables format)
    $.ajax({
        url: '../ajax/get_patients.php',
        method: 'GET',
        data: { length: 1000 }, // Get all patients
        success: function(response) {
            if (response.data && Array.isArray(response.data)) {
                let options = '<option value="">Select Patient</option>';
                response.data.forEach(function(patient) {
                    options += `<option value="${patient.id}">${patient.first_name} ${patient.last_name} (${patient.patient_id})</option>`;
                });
                $('#mr_patient_id, #advanced_patient_id, #filter_patient_id').html(options);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load patients:', error);
        }
    });
    
    // Load doctors
    $.ajax({
        url: '../ajax/get_doctors.php',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data) {
                let options = '<option value="">Select Doctor</option>';
                response.data.forEach(function(doctor) {
                    options += `<option value="${doctor.id}">${doctor.name} (${doctor.doctor_id})</option>`;
                });
                $('#mr_doctor_id, #advanced_doctor_id, #filter_doctor_id').html(options);
            }
        }
    });
}

function setupEventHandlers() {
    // Auto-populate vital signs when patient is selected
    $('#mr_patient_id').on('change', function() {
        const patientId = $(this).val();
        if (patientId) {
            loadPatientVitalSigns(patientId);
        } else {
            // Clear vital signs if no patient selected
            $('#vital_bp, #vital_temp, #vital_hr, #vital_rr, #vital_weight, #vital_height, #vital_spo2, #vital_pain_level').val('');
            calculateBMI();
        }
    });
    
    // Search functionality
    $('#recordsSearch').on('keypress', function(e) {
        if (e.key === 'Enter') {
            recordsTable.ajax.reload();
        }
    });
    
    $('#searchBtn').on('click', function() {
        recordsTable.ajax.reload();
    });
    
    // View mode toggle
    $('input[name="viewMode"]').on('change', function() {
        currentViewMode = this.id === 'tableView' ? 'table' : 'card';
        if (currentViewMode === 'table') {
            $('#tableViewContainer').show();
            $('#cardViewContainer').hide();
        } else {
            $('#tableViewContainer').hide();
            $('#cardViewContainer').show();
            loadCardView();
        }
    });
    
    // Filter handlers
    $('.filter-date').on('click', function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        applyDateFilter(filter);
    });
    
    // Modal handlers
    $('#addRecordBtn').on('click', function() {
        resetRecordForm();
        $('#modalTitle').text('Add Medical Record');
        $('#editRecordModal').modal('show');
    });
    
    $('#advancedSearchBtn').on('click', function() {
        $('#advancedSearchModal').modal('show');
    });
    
    $('#customFilterBtn').on('click', function() {
        $('#customFilterModal').modal('show');
    });
    
    // Form submissions
    $('#mrForm').on('submit', handleSaveRecord);
    $('#advancedSearchForm').on('submit', handleAdvancedSearch);
    $('#customFilterForm').on('submit', handleCustomFilter);
    
    // Table event handlers
    $('#recordsTable').on('click', '.view-record', handleViewRecord);
    $('#recordsTable').on('click', '.edit-record', handleEditRecord);
    $('#recordsTable').on('click', '.delete-record', handleDeleteRecord);
    
    // Clear buttons
    $('#clearSearchBtn').on('click', function() {
        $('#advancedSearchForm')[0].reset();
        $('#advancedSearchModal').modal('hide');
        recordsTable.ajax.reload();
    });
    
    $('#clearFilterBtn').on('click', function() {
        currentFilters = {};
        $('#customFilterForm')[0].reset();
        $('#customFilterModal').modal('hide');
        recordsTable.ajax.reload();
    });
}

function calculateBMI() {
    const weight = parseFloat($('#vital_weight').val());
    const height = parseFloat($('#vital_height').val());
    
    if (weight && height) {
        const heightInMeters = height / 100;
        const bmi = weight / (heightInMeters * heightInMeters);
        $('#vital_bmi').val(bmi.toFixed(1));
    } else {
        $('#vital_bmi').val('');
    }
}

function resetRecordForm() {
    $('#mrForm')[0].reset();
    $('#mr_id').val('');
    
    // Clear vital signs
    $('#vital_bp, #vital_hr, #vital_temp, #vital_weight, #vital_height, #vital_rr, #vital_spo2, #vital_pain_level, #vital_bmi').val('');
    
    // Set default date
    const today = new Date().toISOString().split('T')[0];
    $('#mr_visit_date').val(today);
    
    $('#existingAttachments').empty();
}

function collectVitalSigns() {
    const vitals = {
        blood_pressure: $('#vital_bp').val(),
        heart_rate: $('#vital_hr').val(),
        temperature: $('#vital_temp').val(),
        weight: $('#vital_weight').val(),
        height: $('#vital_height').val(),
        respiratory_rate: $('#vital_rr').val(),
        oxygen_saturation: $('#vital_spo2').val(),
        pain_level: $('#vital_pain_level').val(),
        bmi: $('#vital_bmi').val()
    };
    
    // Remove empty values
    Object.keys(vitals).forEach(key => {
        if (!vitals[key]) delete vitals[key];
    });
    
    return Object.keys(vitals).length > 0 ? vitals : null;
}

function populateVitalSigns(vitals) {
    if (!vitals) return;
    
    $('#vital_bp').val(vitals.blood_pressure || '');
    $('#vital_hr').val(vitals.heart_rate || '');
    $('#vital_temp').val(vitals.temperature || '');
    $('#vital_weight').val(vitals.weight || '');
    $('#vital_height').val(vitals.height || '');
    $('#vital_rr').val(vitals.respiratory_rate || '');
    $('#vital_spo2').val(vitals.oxygen_saturation || '');
    $('#vital_pain_level').val(vitals.pain_level || '');
    $('#vital_bmi').val(vitals.bmi || '');
}

function handleSaveRecord(e) {
    e.preventDefault();
    
    const recordId = $('#mr_id').val();
    const url = recordId ? '../ajax/update_medical_record.php' : '../ajax/create_medical_record.php';
    
    const formData = new FormData();
    
    // Add form fields
    $('#mrForm').serializeArray().forEach(function(field) {
        formData.append(field.name, field.value);
    });
    
    // Add vital signs as JSON
    const vitalSigns = collectVitalSigns();
    if (vitalSigns) {
        formData.append('vital_signs', JSON.stringify(vitalSigns));
    }
    
    // Add file attachment if present
    const fileInput = document.getElementById('mr_attachment');
    if (fileInput && fileInput.files[0]) {
        formData.append('attachment', fileInput.files[0]);
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#editRecordModal').modal('hide');
                recordsTable.ajax.reload();
                loadStatistics();
                showAlert('success', recordId ? 'Medical record updated successfully' : 'Medical record created successfully');
            } else {
                showAlert('error', response.message || 'Failed to save medical record');
            }
        },
        error: function() {
            showAlert('error', 'Server error occurred while saving medical record');
        }
    });
}

function handleViewRecord() {
    const recordId = $(this).data('id');
    
    $('#viewRecordBody').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading medical record...</p>
        </div>
    `);
    
    $('#viewRecordModal').modal('show');
    
    $.ajax({
        url: '../ajax/get_medical_record.php',
        method: 'GET',
        data: { id: recordId },
        success: function(response) {
            if (response.success && response.data) {
                displayRecordDetails(response.data);
            } else {
                $('#viewRecordBody').html('<div class="alert alert-danger">Failed to load medical record</div>');
            }
        },
        error: function() {
            $('#viewRecordBody').html('<div class="alert alert-danger">Server error occurred</div>');
        }
    });
}

function displayRecordDetails(record) {
    let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bx bx-user me-2"></i>Patient Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> ${record.patient_name || 'N/A'}</p>
                        <p><strong>Patient ID:</strong> ${record.patient_code || 'N/A'}</p>
                        <p><strong>Visit Date:</strong> ${new Date(record.visit_date).toLocaleDateString()}</p>
                        <p><strong>Record ID:</strong> <span class="badge bg-primary">${record.record_id || 'N/A'}</span></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bx bx-user-check me-2"></i>Doctor Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Doctor:</strong> ${record.doctor_name || 'N/A'}</p>
                        <p><strong>Doctor ID:</strong> ${record.doctor_code || 'N/A'}</p>
                        <p><strong>Created:</strong> ${new Date(record.created_at).toLocaleString()}</p>
                        <p><strong>Updated:</strong> ${new Date(record.updated_at).toLocaleString()}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Vital Signs
    if (record.vital_signs && Object.keys(record.vital_signs).length > 0) {
        html += `
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bx bx-pulse me-2"></i>Vital Signs</h6>
                </div>
                <div class="card-body">
                    <div class="row">
        `;
        
        const vitals = record.vital_signs;
        if (vitals.blood_pressure) html += `<div class="col-md-3"><strong>Blood Pressure:</strong><br>${vitals.blood_pressure}</div>`;
        if (vitals.heart_rate) html += `<div class="col-md-3"><strong>Heart Rate:</strong><br>${vitals.heart_rate} bpm</div>`;
        if (vitals.temperature) html += `<div class="col-md-3"><strong>Temperature:</strong><br>${vitals.temperature}°C</div>`;
        if (vitals.weight) html += `<div class="col-md-3"><strong>Weight:</strong><br>${vitals.weight} kg</div>`;
        if (vitals.height) html += `<div class="col-md-3"><strong>Height:</strong><br>${vitals.height} cm</div>`;
        if (vitals.respiratory_rate) html += `<div class="col-md-3"><strong>Respiratory Rate:</strong><br>${vitals.respiratory_rate}</div>`;
        if (vitals.oxygen_saturation) html += `<div class="col-md-3"><strong>Oxygen Saturation:</strong><br>${vitals.oxygen_saturation}%</div>`;
        if (vitals.bmi) html += `<div class="col-md-3"><strong>BMI:</strong><br>${vitals.bmi}</div>`;
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }
    
    // Clinical Information
    html += `
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bx bx-clipboard me-2"></i>Chief Complaint</h6>
                    </div>
                    <div class="card-body">
                        <p>${record.chief_complaint || '<em class="text-muted">Not specified</em>'}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bx bx-health me-2"></i>Diagnosis</h6>
                    </div>
                    <div class="card-body">
                        <p>${record.diagnosis || '<em class="text-muted">Not specified</em>'}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bx bx-first-aid me-2"></i>Treatment</h6>
                    </div>
                    <div class="card-body">
                        <p>${record.treatment || '<em class="text-muted">Not specified</em>'}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bx bx-pill me-2"></i>Prescription</h6>
                    </div>
                    <div class="card-body">
                        <p>${record.prescription || '<em class="text-muted">Not specified</em>'}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Notes
    if (record.notes) {
        html += `
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bx bx-note me-2"></i>Additional Notes</h6>
                </div>
                <div class="card-body">
                    <p>${record.notes}</p>
                </div>
            </div>
        `;
    }
    
    // Attachments
    if (record.attachments && record.attachments.length > 0) {
        html += `
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
        `;
        
        record.attachments.forEach(function(attachment) {
            html += `
                <li class="mb-2">
                    <a href="../ajax/download_medical_record_attachment.php?id=${attachment.id}" class="text-decoration-none">
                        <i class="bx bx-file me-2"></i>${attachment.original_name || attachment.file_path}
                    </a>
                    <small class="text-muted d-block">Uploaded: ${new Date(attachment.uploaded_at).toLocaleString()}</small>
                </li>
            `;
        });
        
        html += `
                    </ul>
                </div>
            </div>
        `;
    }
    
    $('#viewRecordBody').html(html);
}

function handleEditRecord() {
    const recordId = $(this).data('id');
    
    resetRecordForm();
    $('#modalTitle').text('Edit Medical Record');
    
    $.ajax({
        url: '../ajax/get_medical_record.php',
        method: 'GET',
        data: { id: recordId },
        success: function(response) {
            if (response.success && response.data) {
                const record = response.data;
                
                // Populate form fields
                $('#mr_id').val(record.id);
                $('#mr_patient_id').val(record.patient_id);
                $('#mr_doctor_id').val(record.doctor_id);
                $('#mr_visit_date').val(record.visit_date);
                $('#mr_chief_complaint').val(record.chief_complaint || '');
                $('#mr_diagnosis').val(record.diagnosis || '');
                $('#mr_treatment').val(record.treatment || '');
                $('#mr_prescription').val(record.prescription || '');
                $('#mr_notes').val(record.notes || '');
                
                // Populate vital signs
                if (record.vital_signs) {
                    populateVitalSigns(record.vital_signs);
                }
                
                // Show existing attachments
                if (record.attachments && record.attachments.length > 0) {
                    let attachmentHtml = '<div class="mb-2"><strong>Existing Attachments:</strong></div>';
                    record.attachments.forEach(function(attachment) {
                        attachmentHtml += `
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                <span><i class="bx bx-file me-2"></i>${attachment.original_name}</span>
                                <a href="../ajax/download_medical_record_attachment.php?id=${attachment.id}" class="btn btn-sm btn-outline-primary">Download</a>
                            </div>
                        `;
                    });
                    $('#existingAttachments').html(attachmentHtml);
                }
                
                $('#editRecordModal').modal('show');
            } else {
                showAlert('error', 'Failed to load medical record for editing');
            }
        },
        error: function() {
            showAlert('error', 'Server error occurred while loading record');
        }
    });
}

function handleDeleteRecord() {
    const recordId = $(this).data('id');
    
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the medical record and all associated data!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/delete_medical_record.php',
                method: 'POST',
                data: { id: recordId },
                success: function(response) {
                    if (response.success) {
                        recordsTable.ajax.reload();
                        loadStatistics();
                        showAlert('success', 'Medical record deleted successfully');
                    } else {
                        showAlert('error', response.message || 'Failed to delete medical record');
                    }
                },
                error: function() {
                    showAlert('error', 'Server error occurred while deleting record');
                }
            });
        }
    });
}

function handleAdvancedSearch(e) {
    e.preventDefault();
    
    const searchTerm = $('#advanced_search_term').val();
    const patientId = $('#advanced_patient_id').val();
    const doctorId = $('#advanced_doctor_id').val();
    const dateFrom = $('#advanced_date_from').val();
    const dateTo = $('#advanced_date_to').val();
    
    if (!searchTerm) {
        showAlert('warning', 'Please enter search terms');
        return;
    }
    
    const filters = { q: searchTerm };
    if (patientId) filters.patient_id = patientId;
    if (doctorId) filters.doctor_id = doctorId;
    if (dateFrom) filters.date_from = dateFrom;
    if (dateTo) filters.date_to = dateTo;
    
    $.ajax({
        url: '../ajax/search_medical_records.php',
        method: 'GET',
        data: filters,
        success: function(response) {
            if (response.success) {
                displaySearchResults(response.data);
                $('#advancedSearchModal').modal('hide');
            } else {
                showAlert('error', response.message || 'Search failed');
            }
        },
        error: function() {
            showAlert('error', 'Server error occurred during search');
        }
    });
}

function handleCustomFilter(e) {
    e.preventDefault();
    
    const patientId = $('#filter_patient_id').val();
    const doctorId = $('#filter_doctor_id').val();
    const dateFrom = $('#filter_date_from').val();
    const dateTo = $('#filter_date_to').val();
    
    currentFilters = {};
    if (patientId) currentFilters.patient_filter = patientId;
    if (doctorId) currentFilters.doctor_filter = doctorId;
    if (dateFrom) currentFilters.date_from = dateFrom;
    if (dateTo) currentFilters.date_to = dateTo;
    
    recordsTable.ajax.reload();
    $('#customFilterModal').modal('hide');
    showAlert('success', 'Filter applied successfully');
}

function applyDateFilter(filter) {
    const today = new Date();
    let dateFrom, dateTo;
    
    switch (filter) {
        case 'today':
            dateFrom = dateTo = today.toISOString().split('T')[0];
            break;
        case 'week':
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            dateFrom = weekStart.toISOString().split('T')[0];
            dateTo = today.toISOString().split('T')[0];
            break;
        case 'month':
            dateFrom = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            dateTo = today.toISOString().split('T')[0];
            break;
        case 'all':
        default:
            currentFilters = {};
            recordsTable.ajax.reload();
            return;
    }
    
    currentFilters = { date_from: dateFrom, date_to: dateTo };
    recordsTable.ajax.reload();
}

function displaySearchResults(results) {
    // This would show search results in a modal or replace table content
    // For now, we'll use the existing table structure
    showAlert('info', `Found ${results.length} matching records`);
}

function loadCardView() {
    // Implement card view loading if needed
    $('#recordsCardContainer').html('<div class="col-12 text-center py-4"><em>Card view coming soon...</em></div>');
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'error' ? 'alert-danger' : 
                     type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the page
    $('.container-xxl').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
