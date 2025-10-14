<?php
$page_title = 'Lab Tests Management';
$additional_css = [];
$additional_js = [
  "https://cdn.jsdelivr.net/npm/sweetalert2@11"
];

include __DIR__ . '/../shared/session_handler.php';

requireRole(['admin', 'doctor', 'nurse', 'receptionist']);
$user_role = $_SESSION['role'] ?? null;


ob_start();
?>

<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <!-- Statistics Overview (Sneat-style, AJAX-populated) -->
      <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-test-tube bx-sm text-primary"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Total Tests</span>
              <h3 class="card-title text-nowrap mb-1" id="totalTests">0</h3>
              <small class="text-primary fw-semibold"><i class="bx bx-time"></i> All Time</small>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-time-five bx-sm text-warning"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Pending Results</span>
              <h3 class="card-title text-nowrap mb-1" id="pendingResults">0</h3>
              <small class="text-warning fw-semibold"><i class="bx bx-hourglass"></i> Awaiting</small>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-check-circle bx-sm text-success"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Completed Tests</span>
              <h3 class="card-title text-nowrap mb-1" id="completedTests">0</h3>
              <small class="text-success fw-semibold"><i class="bx bx-check"></i> Results Available</small>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-calendar bx-sm text-info"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Recent Activity (30d)</span>
              <h3 class="card-title text-nowrap mb-1" id="recentActivity">0</h3>
              <small class="text-info fw-semibold"><i class="bx bx-calendar-event"></i> Last 30 days</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Lab Tests Management -->
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Lab Tests</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" id="newLabTestBtn">
                  <i class="fas fa-plus"></i> New Lab Test
                </button>
              </div>
            </div>
            <div class="card-body">
              <!-- Quick Search -->
              <div class="row mb-3">
                <div class="col-md-12">
                  <div class="input-group">
                    <input type="text" class="form-control" id="searchFilter"
                      placeholder="Search by patient name, test name, or reference number..."
                      aria-label="Search lab tests">
                    <div class="input-group-append">
                      <button class="btn btn-outline-secondary" type="button" id="searchBtn" title="Search">
                        <i class="fas fa-search"></i> <span>Search</span>
                      </button>
                      <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear">
                        <i class="fas fa-times"></i> <span>Clear</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Lab Tests Table -->
              <div class="table-responsive">
                <table class="table table-striped table-hover" id="labTestsTable">
                  <thead>
                    <tr>
                      <th>Reference #</th>
                      <th>Patient</th>
                      <th>Test Name</th>
                      <th>Category</th>
                      <th>Date Ordered</th>
                      <th>Status</th>
                      <th>Doctor</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="labTestsTableBody">
                    <tr>
                      <td colspan="8" class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading lab tests...
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <nav aria-label="Lab tests pagination">
                <ul class="pagination" id="labTestsPagination">
                  <!-- Pagination will be generated dynamically -->
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
  </section>
</div>

<!-- Lab Test Modal (Simple & Modern) -->
<div class="modal fade" id="labTestModal" tabindex="-1" aria-labelledby="labTestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="labTestModalTitle">
          <i class="bx bx-test-tube me-2 text-primary"></i>New Laboratory Test Order
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="labTestForm">
        <div class="modal-body">
          <!-- Patient & Doctor -->
          <div class="mb-4">
            <h6 class="text-primary mb-3">Patient & Doctor Information</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="patientSelect" name="patient_id" required>
                    <option value="">Choose patient...</option>
                  </select>
                  <label for="patientSelect">Patient *</label>
                </div>
              </div>
              <?php if ($user_type === 'admin' || $user_type === 'secretary' || $user_type === 'receptionist'): ?>
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="doctorSelect" name="doctor_id" required>
                    <option value="">Choose doctor...</option>
                  </select>
                  <label for="doctorSelect">Ordering Doctor *</label>
                </div>
              </div>
              <?php elseif ($user_type === 'doctor'): 
                // Get doctor's ID from doctors table
                $db = getDBConnection();
                $stmt = $db->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
                $stmt->execute([$user_id]);
                $doctor_row = $stmt->fetch(PDO::FETCH_ASSOC);
                $doctor_id = $doctor_row ? $doctor_row['id'] : null;
              ?>
              <!-- Hidden field for doctor's own ID -->
              <input type="hidden" id="doctorSelect" name="doctor_id" value="<?= $doctor_id ?>">
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" value="Dr. <?= htmlspecialchars($_SESSION['first_name'] ?? '') ?> <?= htmlspecialchars($_SESSION['last_name'] ?? '') ?> (Ordering Doctor)" readonly>
                  <label>Ordering Doctor</label>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <hr class="my-4">

          <!-- Test Details -->
          <div class="mb-4">
            <h6 class="text-primary mb-3">Test Details</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" id="testCategoryInput" name="test_category" placeholder="e.g., Hematology, Chemistry" required>
                  <label for="testCategoryInput">Test Category *</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" id="testNameInput" name="test_name" placeholder="e.g., Complete Blood Count (CBC)" required>
                  <label for="testNameInput">Test Name *</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="date" class="form-control" id="testDate" name="test_date" value="<?= date('Y-m-d') ?>" required>
                  <label for="testDate">Test Date *</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="date" class="form-control" id="expectedDate" name="expected_date">
                  <label for="expectedDate">Expected Results Date</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <select class="form-select" id="statusSelect" name="status">
                    <option value="ordered">Ordered</option>
                    <option value="sample_collected">Sample Collected</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                  <label for="statusSelect">Status</label>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Specimen & Notes -->
          <div class="mb-3">
            <h6 class="text-primary mb-3">Specimen & Clinical Notes</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="specimenType" name="specimen_type">
                    <option value="blood">Blood</option>
                    <option value="urine">Urine</option>
                    <option value="stool">Stool</option>
                    <option value="saliva">Saliva</option>
                    <option value="tissue">Tissue</option>
                    <option value="other">Other</option>
                  </select>
                  <label for="specimenType">Specimen Type</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="fastingRequired" name="fasting_required">
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                  </select>
                  <label for="fastingRequired">Fasting Required</label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-floating">
                  <textarea class="form-control" id="clinicalNotes" name="notes" style="height: 100px" placeholder="Enter clinical notes..."></textarea>
                  <label for="clinicalNotes">Clinical Notes & Special Instructions</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="bx bx-x me-1"></i>Cancel
          </button>
          <button type="button" class="btn btn-outline-info" id="previewLabTestBtn">
            <i class="bx bx-show me-1"></i>Preview
          </button>
          <button type="button" class="btn btn-primary" id="saveLabTestBtn">
            <i class="bx bx-check me-1"></i>Save Lab Test
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Lab Test Details Modal -->
<div class="modal fade" id="labTestDetailsModal" tabindex="-1" aria-labelledby="labTestDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="labTestDetailsLabel">Lab Test Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="labTestDetailsContent">
        <!-- Lab test details will be loaded here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i>Close
        </button>
        <!-- <button type="button" class="btn btn-outline-info" id="printLabTestBtn">
          <i class="bx bx-printer me-1"></i>Print
        </button>
        <button type="button" class="btn btn-outline-success" id="downloadResultsBtn">
          <i class="bx bx-download me-1"></i>Download Results
        </button> -->
      </div>
    </div>
  </div>
</div>

<script>
  // Load statistics via AJAX to populate Sneat cards
  (function loadLabStats() {
    fetch('../ajax/get_lab_tests_statistics.php')
      .then(r => {
        if (!r.ok) throw new Error('HTTP error ' + r.status);
        return r.json();
      })
      .then(res => {
        if (!res || !res.success || !res.data) {
          console.warn('Invalid statistics response:', res);
          return;
        }
        const s = res.data;
        const total = s.total_tests || 0;
        const ordered = (s.by_status && (s.by_status.ordered || 0)) || 0;
        const inProgress = (s.by_status && (s.by_status.in_progress || 0)) || 0;
        const completed = (s.by_status && (s.by_status.completed || 0)) || 0;
        const recent = s.recent_tests || 0;

        document.getElementById('totalTests').textContent = total;
        document.getElementById('pendingResults').textContent = ordered + inProgress;
        document.getElementById('completedTests').textContent = completed;
        document.getElementById('recentActivity').textContent = recent;
      })
      .catch((error) => {
        console.error('Failed to load lab test statistics:', error);
        // Set to 0 on error
        document.getElementById('totalTests').textContent = '0';
        document.getElementById('pendingResults').textContent = '0';
        document.getElementById('completedTests').textContent = '0';
        document.getElementById('recentActivity').textContent = '0';
      });
  })();

  // Load dropdown options
  function loadDropdownOptions() {
    // Load patients (DataTables format)
    $.ajax({
      url: '../ajax/get_patients.php',
      method: 'GET',
      data: { length: 1000 },
      success: function(response) {
        if (response.data && Array.isArray(response.data)) {
          let options = '<option value="">Choose patient...</option>';
          response.data.forEach(function(patient) {
            options += `<option value="${patient.id}">${patient.first_name} ${patient.last_name} (${patient.patient_id})</option>`;
          });
          $('#patientSelect').html(options);
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
          let options = '<option value="">Choose doctor...</option>';
          response.data.forEach(function(doctor) {
            options += `<option value="${doctor.id}">${doctor.name} - ${doctor.specialization || 'No specialization'}</option>`;
          });
          $('#doctorSelect').html(options);
        }
      },
      error: function(xhr, status, error) {
        console.error('Failed to load doctors:', error);
      }
    });
  }

  // Initialize DataTable
  let labTestsTable;
  
  function initializeDataTable() {
    labTestsTable = $('#labTestsTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '../ajax/get_lab_tests.php',
        type: 'GET',
        error: function(xhr, error, code) {
          console.error('DataTable AJAX error:', error, code);
          console.error('Response:', xhr.responseText);
          console.error('Status:', xhr.status);
        }
      },
      columns: [
        { data: 'test_id' },
        { 
          data: 'patient_name',
          render: function(data) {
            return data || '-';
          }
        },
        { data: 'test_name' },
        { data: 'test_category' },
        { 
          data: 'test_date',
          render: function(data) {
            return data ? new Date(data).toLocaleDateString() : '-';
          }
        },
        { 
          data: 'status',
          render: function(data) {
            const badges = {
              'ordered': 'badge bg-warning',
              'sample_collected': 'badge bg-info',
              'in_progress': 'badge bg-info',
              'completed': 'badge bg-success',
              'cancelled': 'badge bg-danger'
            };
            return `<span class="${badges[data] || 'badge bg-secondary'}">${data || 'N/A'}</span>`;
          }
        },
        { 
          data: 'doctor_name',
          render: function(data) {
            return data || '-';
          }
        },
        {
          data: null,
          orderable: false,
          render: function(data) {
            return `
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary view-lab-test" data-id="${data.id}" title="View">
                  <i class="bx bx-show"></i>
                </button>
                <button class="btn btn-outline-info edit-lab-test" data-id="${data.id}" title="Edit">
                  <i class="bx bx-edit"></i>
                </button>
                <button class="btn btn-outline-danger delete-lab-test" data-id="${data.id}" title="Delete">
                  <i class="bx bx-trash"></i>
                </button>
              </div>
            `;
          }
        }
      ],
      order: [[4, 'desc']], // Sort by test_date descending
      pageLength: 25,
      language: {
        emptyTable: "No lab tests found",
        zeroRecords: "No matching lab tests found"
      }
    });
  }

  // Minimal, non-invasive handlers for search and page buttons
  (function initLabTestsUI($) {
    if (!$) return;

    // Initialize DataTable
    initializeDataTable();
    
    // Load dropdowns on page load
    loadDropdownOptions();

    // Quick Search actions
    $('#searchBtn').on('click', function () {
      const q = $('#searchFilter').val() || '';
      labTestsTable.search(q).draw();
    });
    
    $('#clearSearchBtn').on('click', function () {
      $('#searchFilter').val('');
      labTestsTable.search('').draw();
    });
    
    // Search on Enter key
    $('#searchFilter').on('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        labTestsTable.search($(this).val()).draw();
      }
    });

    // Buttons in header
    $('#newLabTestBtn').on('click', function () {
      try { 
        document.getElementById('labTestForm').reset();
        // Remove hidden id field if exists
        $('#labTestForm input[name="id"]').remove();
      } catch (e) { }
      $('#labTestModalTitle').text('New Lab Test');
      // Set sensible defaults
      const today = new Date().toISOString().split('T')[0];
      $('#testDate').val(today);
      $('#statusSelect').val('ordered');
      $('#labTestModal').modal('show');
      setTimeout(() => $('#patientSelect').focus(), 200);
    });

    // Save lab test
    $('#saveLabTestBtn').on('click', function() {
      const form = document.getElementById('labTestForm');
      
      // Validate form
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const formData = new FormData(form);
      const isEdit = formData.has('id') && formData.get('id');
      
      // Show loading state
      const btn = $(this);
      const originalHtml = btn.html();
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

      $.ajax({
        url: isEdit ? '../ajax/update_lab_test.php' : '../ajax/create_lab_test.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          btn.prop('disabled', false).html(originalHtml);
          
          if (response.success) {
            $('#labTestModal').modal('hide');
            
            // Reload DataTable
            labTestsTable.ajax.reload(null, false);
            
            // Show success message
            Swal.fire({
              icon: 'success',
              title: isEdit ? 'Lab Test Updated' : 'Lab Test Created',
              text: `Lab test has been successfully ${isEdit ? 'updated' : 'created'}.`,
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed',
              text: response.message || `Failed to ${isEdit ? 'update' : 'create'} lab test`
            });
          }
        },
        error: function(xhr, status, error) {
          btn.prop('disabled', false).html(originalHtml);
          console.error('Save lab test error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Connection error. Please try again.'
          });
        }
      });
    });

    // View Lab Test Details
    $(document).on('click', '.view-lab-test', function() {
      const testId = $(this).data('id');
      
      // Show loading
      $('#labTestDetailsContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading test details...</p></div>');
      $('#labTestDetailsModal').modal('show');
      
      // Fetch lab test details
      $.ajax({
        url: '../ajax/get_lab_test.php',
        method: 'GET',
        data: { id: testId },
        success: function(response) {
          if (response.success && response.data) {
            const test = response.data;
            
            // Format status badge
            const statusBadges = {
              'ordered': 'warning',
              'sample_collected': 'info',
              'in_progress': 'info',
              'completed': 'success',
              'cancelled': 'danger'
            };
            const statusClass = statusBadges[test.status] || 'secondary';
            
            const html = `
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0"><i class="bx bx-test-tube me-2"></i>Test Information</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <table class="table table-sm table-borderless">
                        <tr>
                          <th width="40%">Test ID:</th>
                          <td><strong>${test.test_id || '-'}</strong></td>
                        </tr>
                        <tr>
                          <th>Test Name:</th>
                          <td>${test.test_name || '-'}</td>
                        </tr>
                        <tr>
                          <th>Category:</th>
                          <td>${test.test_category || '-'}</td>
                        </tr>
                        <tr>
                          <th>Test Date:</th>
                          <td>${test.test_date ? new Date(test.test_date).toLocaleDateString() : '-'}</td>
                        </tr>
                        <tr>
                          <th>Status:</th>
                          <td><span class="badge bg-${statusClass}">${test.status || '-'}</span></td>
                        </tr>
                      </table>
                    </div>
                    <div class="col-md-6">
                      <table class="table table-sm table-borderless">
                        <tr>
                          <th width="40%">Patient:</th>
                          <td>${test.patient_name || '-'}</td>
                        </tr>
                        <tr>
                          <th>Patient ID:</th>
                          <td>${test.patient_code || '-'}</td>
                        </tr>
                        <tr>
                          <th>Ordering Doctor:</th>
                          <td>${test.doctor_name || '-'}</td>
                        </tr>
                        <tr>
                          <th>Fasting Required:</th>
                          <td>${test.fasting_required === 'yes' ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              ${test.notes ? `
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bx bx-note me-2"></i>Clinical Notes</h6>
                  </div>
                  <div class="card-body mt-5">
                    <p class="mb-0">${test.notes.replace(/\n/g, '<br>')}</p>
                  </div>
                </div>
              ` : ''}
              
              ${test.results || test.lab_technician || test.recorded_at ? `
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i>Lab Results</h6>
                  </div>
                  <div class="card-body mt-5">
                    ${test.results ? `
                      <div class="mb-3">
                        <strong>Results:</strong>
                        <p class="mt-2">${test.results.replace(/\n/g, '<br>')}</p>
                      </div>
                    ` : '<p class="text-muted">No results recorded yet.</p>'}
                    ${test.normal_range ? `
                      <div class="mb-3">
                        <strong>Normal Range:</strong>
                        <p class="mt-2">${test.normal_range}</p>
                      </div>
                    ` : ''}
                    ${test.lab_technician ? `<p><strong>Lab Technician:</strong> ${test.lab_technician}</p>` : ''}
                    ${test.recorded_at ? `<p><strong>Recorded At:</strong> ${new Date(test.recorded_at).toLocaleString()}</p>` : ''}
                    ${test.recorded_by_name ? `<p><strong>Recorded By:</strong> ${test.recorded_by_name}</p>` : ''}
                    ${test.report_file ? `
                      <p><strong>Report:</strong> <a href="../${test.report_file}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bx bx-download me-1"></i>Download Report</a></p>
                    ` : ''}
                  </div>
                </div>
              ` : ''}
              
              <div class="row">
                <div class="col-md-6">
                  <small class="text-muted">Created: ${test.created_at ? new Date(test.created_at).toLocaleString() : '-'}</small>
                </div>
                <div class="col-md-6 text-end">
                  <small class="text-muted">Last Updated: ${test.updated_at ? new Date(test.updated_at).toLocaleString() : '-'}</small>
                </div>
              </div>
            `;
            $('#labTestDetailsContent').html(html);
          } else {
            $('#labTestDetailsContent').html('<div class="alert alert-danger"><i class="bx bx-error me-2"></i>Failed to load lab test details</div>');
          }
        },
        error: function(xhr, status, error) {
          console.error('View lab test error:', error);
          console.error('Response:', xhr.responseText);
          $('#labTestDetailsContent').html('<div class="alert alert-danger"><i class="bx bx-error me-2"></i>Error loading lab test details. Please try again.</div>');
        }
      });
    });

    // Edit Lab Test
    $(document).on('click', '.edit-lab-test', function() {
      const testId = $(this).data('id');
      
      // Fetch lab test details
      $.ajax({
        url: '../ajax/get_lab_test.php',
        method: 'GET',
        data: { id: testId },
        success: function(response) {
          if (response.success && response.data) {
            const test = response.data;
            
            // Reset form first
            document.getElementById('labTestForm').reset();
            
            // Add hidden id field
            $('#labTestForm input[name="id"]').remove();
            $('#labTestForm').append(`<input type="hidden" name="id" value="${test.id}">`);
            
            // Populate form fields with correct IDs
            $('#patientSelect').val(test.patient_id);
            $('#doctorSelect').val(test.doctor_id || '');
            $('#testCategoryInput').val(test.test_category || '');
            $('#testNameInput').val(test.test_name || '');
            $('#testDate').val(test.test_date || '');
            $('#expectedDate').val(test.expected_date || '');
            $('#statusSelect').val(test.status || 'ordered');
            $('#specimenType').val(test.specimen_type || 'blood');
            $('#fastingRequired').val(test.fasting_required || 'no');
            $('#clinicalNotes').val(test.notes || '');
            
            $('#labTestModalTitle').text('Edit Lab Test');
            $('#labTestModal').modal('show');
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed',
              text: 'Failed to load lab test details'
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Edit lab test error:', error);
          console.error('Response:', xhr.responseText);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load lab test details'
          });
        }
      });
    });

    // Delete Lab Test
    $(document).on('click', '.delete-lab-test', function() {
      const testId = $(this).data('id');
      
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading
          Swal.fire({
            title: 'Deleting...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          $.ajax({
            url: '../ajax/delete_lab_test.php',
            method: 'POST',
            data: { id: testId },
            success: function(response) {
              if (response.success) {
                labTestsTable.ajax.reload(null, false);
                Swal.fire({
                  icon: 'success',
                  title: 'Deleted!',
                  text: 'Lab test has been deleted.',
                  timer: 2000,
                  showConfirmButton: false
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Failed',
                  text: response.message || 'Failed to delete lab test'
                });
              }
            },
            error: function() {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Connection error. Please try again.'
              });
            }
          });
        }
      });
    });
  })(window.jQuery);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>

