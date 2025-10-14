<?php
$page_title = 'Lab Tests Management';
$additional_css = [];
$additional_js = [
  "https://cdn.jsdelivr.net/npm/sweetalert2@11",
  "../assets/js/lab-tests.js"
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
                <button type="button" class="btn btn-secondary btn-sm" id="searchLabTestsBtn">
                  <i class="fas fa-search"></i> Advanced Search
                </button>

              </div>
            </div>
            <div class="card-body">
              <!-- Advanced Filters (Initially Hidden) -->
              <div class="row mb-3" id="labTestFilters" style="display: none;">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="statusFilter">Status</label>
                    <select class="form-control" id="statusFilter">
                      <option value="">All Status</option>
                      <option value="ordered">Ordered</option>
                      <option value="sample_collected">Sample Collected</option>
                      <option value="in_progress">In Progress</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="categoryFilter">Category</label>
                    <select class="form-control" id="categoryFilter">
                      <option value="">All Categories</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="dateFromFilter">Date From</label>
                    <input type="date" class="form-control" id="dateFromFilter">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="dateToFilter">Date To</label>
                    <input type="date" class="form-control" id="dateToFilter">
                  </div>
                </div>
              </div>

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
                      <th>Priority</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="labTestsTableBody">
                    <tr>
                      <td colspan="9" class="text-center">
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
              <?php if ($user_role === 'admin' || $user_role === 'nurse'): ?>
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="doctorSelect" name="doctor_id" required>
                    <option value="">Choose doctor...</option>
                  </select>
                  <label for="doctorSelect">Ordering Doctor *</label>
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
                  <select class="form-select" id="testCategorySelect" name="category_id" required>
                    <option value="">Choose category...</option>
                  </select>
                  <label for="testCategorySelect">Test Category *</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="testNameSelect" name="test_name" required>
                    <option value="">Choose test...</option>
                  </select>
                  <label for="testNameSelect">Test Name *</label>
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
                  <select class="form-select" id="prioritySelect" name="priority">
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                    <option value="stat">STAT</option>
                  </select>
                  <label for="prioritySelect">Priority</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="date" class="form-control" id="expectedDate" name="expected_date">
                  <label for="expectedDate">Expected Results Date</label>
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
                  <textarea class="form-control" id="clinicalNotes" name="clinical_notes" style="height: 100px" placeholder="Enter clinical notes..."></textarea>
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
<div class="modal fade" id="labTestDetailsModal" tabindex="-1" role="dialog" aria-labelledby="labTestDetailsLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Lab Test Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="labTestDetailsContent">
        <!-- Lab test details will be loaded here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-info" id="printLabTestBtn">
          <i class="fas fa-print"></i> Print
        </button>
        <button type="button" class="btn btn-success" id="downloadResultsBtn">
          <i class="fas fa-download"></i> Download Results
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Load statistics via AJAX to populate Sneat cards
  (function loadLabStats() {
    fetch('../ajax/get_lab_tests_statistics.php')
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(res => {
        if (!res || !res.success || !res.data) return;
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
      .catch(() => {
        // Fail silently to avoid UI break
      });
  })();

  // Minimal, non-invasive handlers for search and page buttons
  (function initLabTestsUI($) {
    if (!$) return;

    // Quick Search actions
    $('#searchBtn').on('click', function () {
      const q = $('#searchFilter').val() || '';
      // Emit generic events other scripts can hook into
      $(document).trigger('labtests:search', [q]);
      // Also trigger change on the input for existing listeners
      $('#searchFilter').trigger('change');
    });
    $('#clearSearchBtn').on('click', function () {
      $('#searchFilter').val('');
      $(document).trigger('labtests:search', ['']);
      $('#searchFilter').trigger('change');
    });

    // Buttons in header
    $('#newLabTestBtn').on('click', function () {
      try { document.getElementById('labTestForm').reset(); } catch (e) { }
      $('#labTestModalTitle').text('New Lab Test');
      // Set sensible defaults
      const today = new Date().toISOString().split('T')[0];
      $('#testDate').val(today);
      $('#prioritySelect').val('normal');
      $('#labTestModal').modal('show');
      setTimeout(() => $('#patientSelect').focus(), 200);
    });

    $('#searchLabTestsBtn').on('click', function () {
      $('#labTestFilters').slideToggle(150);
    });
  })(window.jQuery);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>

