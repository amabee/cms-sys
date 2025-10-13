<?php
$page_title = 'Lab Tests Management';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor','secretary','receptionist']);

ob_start();
?>
<!-- Statistics Dashboard -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Lab Tests Dashboard</h5>
      </div>
      <div class="card-body">
        <div class="row" id="labStatsContainer">
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-vial text-primary fa-2x mb-2"></i>
                <h4 class="mb-1" id="totalTests">0</h4>
                <small class="text-muted">Total Tests</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                <h4 class="mb-1" id="pendingResults">0</h4>
                <small class="text-muted">Pending Results</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                <h4 class="mb-1" id="completedToday">0</h4>
                <small class="text-muted">Completed Today</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                <h4 class="mb-1" id="criticalResults">0</h4>
                <small class="text-muted">Critical Results</small>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Status Distribution -->
        <div class="row mt-4">
          <div class="col-md-8">
            <h6 class="text-muted mb-3">Test Status Distribution</h6>
            <div id="statusDistribution" class="d-flex flex-wrap gap-2">
              <!-- Status badges will be populated here -->
            </div>
          </div>
          <div class="col-md-4">
            <h6 class="text-muted mb-3">Performance Metrics</h6>
            <div class="small">
              <div class="d-flex justify-content-between mb-1">
                <span>Avg Turnaround:</span>
                <span id="avgTurnaround">0 hours</span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span>Recent Activity (30d):</span>
                <span id="recentTests">0 tests</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Lab Tests Section -->
<div class="row">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">Lab Tests Management</h5>
            <small class="text-muted">Comprehensive lab test ordering and results tracking</small>
          </div>
          <div class="col-auto">
            <button id="addLabTestBtn" class="btn btn-success">
              <i class="fas fa-plus me-1"></i>Add Lab Test
            </button>
          </div>
        </div>
      </div>
      <div class="card-body">
        <!-- Advanced Search Panel -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card bg-light border-0">
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label small fw-bold">Quick Search</label>
                    <input id="labTestsSearch" class="form-control form-control-sm" placeholder="Search tests, patients..." />
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select id="statusFilter" class="form-select form-select-sm">
                      <option value="">All Status</option>
                      <option value="ordered">Ordered</option>
                      <option value="sample_collected">Sample Collected</option>
                      <option value="in_progress">In Progress</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small fw-bold">Category</label>
                    <select id="categoryFilter" class="form-select form-select-sm">
                      <option value="">All Categories</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small fw-bold">Doctor</label>
                    <select id="doctorFilter" class="form-select form-select-sm">
                      <option value="">All Doctors</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-bold">Actions</label>
                    <div class="d-flex gap-1">
                      <button id="searchBtn" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-search me-1"></i>Search
                      </button>
                      <button id="resetBtn" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i>
                      </button>
                      <button id="advancedSearchBtn" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-filter"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Results Table -->
        <div class="table-responsive">
          <table class="table table-hover" id="labTestsTable" style="width:100%">
            <thead class="table-light">
              <tr>
                <th>Patient</th>
                <th>Test Name</th>
                <th>Category</th>
                <th>Date</th>
                <th>Status</th>
                <th>Doctor</th>
                <th>Results</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>

        <!-- View Modal -->
        <div class="modal fade" id="viewLabModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-microscope me-2"></i>Lab Test Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-0">
                <div class="row g-0" style="min-height: 600px;">
                  <!-- Left Panel - Test Details -->
                  <div class="col-md-6 p-4 border-end">
                    <div id="viewLabBody">
                      <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Loading test details...</div>
                      </div>
                    </div>
                  </div>
                  <!-- Right Panel - Lab Report Viewer -->
                  <div class="col-md-6 p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="mb-0"><i class="fas fa-file-medical me-2"></i>Lab Report</h6>
                      <div id="fileControls" class="d-none">
                        <a id="downloadLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                          <i class="fas fa-download"></i> Download
                        </a>
                        <a id="openNewTabLink" href="#" target="_blank" class="btn btn-sm btn-outline-secondary">
                          <i class="fas fa-external-link-alt"></i> Open
                        </a>
                      </div>
                    </div>
                    <div id="fileViewerContainer" class="flex-grow-1 d-flex align-items-center justify-content-center border rounded bg-light" style="min-height: 500px;">
                      <div class="text-center text-muted">
                        <i class="fas fa-file-medical fa-3x mb-3 opacity-25"></i>
                        <div>No report file available</div>
                        <small>Upload a report file to view here</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="fas fa-times me-1"></i>Close
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" id="editLabModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                  <i class="fas fa-plus-circle me-2"></i><span id="modalTitle">Add Lab Test</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="editLabForm">
                  <input type="hidden" name="id" id="lt_id">
                  
                  <div class="row mb-4">
                    <div class="col-12">
                      <div class="card bg-light border-0">
                        <div class="card-body py-2">
                          <h6 class="mb-2 text-primary"><i class="fas fa-user-injured me-2"></i>Patient Information</h6>
                          <div class="row">
                            <div class="col-md-8">
                              <label class="form-label fw-bold">Patient *</label>
                              <select name="patient_id" id="lt_patient_id" class="form-select" required>
                                <option value="">Select patient...</option>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label fw-bold">Ordering Doctor</label>
                              <select name="doctor_id" id="lt_doctor_id" class="form-select">
                                <option value="">Select doctor...</option>
                              </select>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-12">
                      <div class="card bg-light border-0">
                        <div class="card-body py-2">
                          <h6 class="mb-2 text-primary"><i class="fas fa-vial me-2"></i>Test Details</h6>
                          <div class="row">
                            <div class="col-md-8">
                              <label class="form-label fw-bold">Test Name *</label>
                              <input name="test_name" id="lt_test_name" class="form-control" placeholder="Enter test name..." required>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label fw-bold">Category</label>
                              <input name="test_category" id="lt_test_category" class="form-control" placeholder="e.g. Hematology" list="categoryList">
                              <datalist id="categoryList"></datalist>
                            </div>
                          </div>
                          <div class="row mt-3">
                            <div class="col-md-6">
                              <label class="form-label fw-bold">Test Date *</label>
                              <input name="test_date" id="lt_test_date" type="date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label fw-bold">Priority</label>
                              <select name="priority" id="lt_priority" class="form-select">
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT</option>
                              </select>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-12">
                      <label class="form-label fw-bold">Clinical Notes</label>
                      <textarea name="notes" id="lt_notes" class="form-control" rows="3" placeholder="Enter clinical notes, symptoms, or special instructions..."></textarea>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <label class="form-label fw-bold">Lab Report File</label>
                      <input type="file" name="report_file" id="lt_report_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx">
                      <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>
                        Supported formats: PDF, Images (PNG, JPG), Word documents. Max size: 10MB
                      </div>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button id="saveLabTest" type="button" class="btn btn-success">
                  <i class="fas fa-save me-1"></i>Save Lab Test
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced Search Modal -->
        <div class="modal fade" id="advancedSearchModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-search-plus me-2"></i>Advanced Search</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="advancedSearchForm">
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-bold">Patient Search</label>
                      <input name="patient_search" id="as_patient_search" class="form-control" placeholder="Patient name or ID...">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-bold">Test Search</label>
                      <input name="test_search" id="as_test_search" class="form-control" placeholder="Test name or category...">
                    </div>
                  </div>
                  
                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label class="form-label fw-bold">Status</label>
                      <select name="status" id="as_status" class="form-select">
                        <option value="">All Status</option>
                        <option value="ordered">Ordered</option>
                        <option value="sample_collected">Sample Collected</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label fw-bold">Category</label>
                      <select name="category" id="as_category" class="form-select">
                        <option value="">All Categories</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label fw-bold">Doctor</label>
                      <select name="doctor_id" id="as_doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-bold">Date From</label>
                      <input name="date_from" id="as_date_from" type="date" class="form-control">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-bold">Date To</label>
                      <input name="date_to" id="as_date_to" type="date" class="form-control">
                    </div>
                  </div>
                  
                  <div class="row mb-3">
                    <div class="col-12">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="critical_only" id="as_critical_only" value="1">
                        <label class="form-check-label fw-bold" for="as_critical_only">
                          <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                          Show only tests with critical/abnormal results
                        </label>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-outline-warning" id="resetAdvancedSearch">
                  <i class="fas fa-undo me-1"></i>Reset
                </button>
                <button type="button" class="btn btn-info" id="executeAdvancedSearch">
                  <i class="fas fa-search me-1"></i>Search
                </button>
              </div>
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
  $(function(){
  const USER_TYPE = '<?php echo $user_type ?? ''; ?>';
  let currentSearchType = 'standard'; // 'standard' or 'advanced'

  // Load Dashboard Statistics
  function loadDashboardStats() {
    $.get('../ajax/get_lab_tests_statistics.php', function(resp) {
      if (resp && resp.success && resp.data) {
        const stats = resp.data;
        
        // Update summary cards
        $('#totalTests').text(stats.total_tests || 0);
        $('#pendingResults').text(stats.pending_results || 0);
        $('#completedToday').text(stats.completed_today || 0);
        $('#criticalResults').text(stats.critical_results || 0);
        
        // Update performance metrics
        $('#avgTurnaround').text((stats.avg_turnaround_hours || 0) + ' hours');
        $('#recentTests').text((stats.recent_tests || 0) + ' tests');
        
        // Update status distribution
        const statusContainer = $('#statusDistribution');
        statusContainer.empty();
        
        if (stats.by_status) {
          const statusConfig = {
            'ordered': { label: 'Ordered', class: 'bg-warning text-dark' },
            'sample_collected': { label: 'Sample Collected', class: 'bg-info text-white' },
            'in_progress': { label: 'In Progress', class: 'bg-primary text-white' },
            'completed': { label: 'Completed', class: 'bg-success text-white' },
            'cancelled': { label: 'Cancelled', class: 'bg-danger text-white' }
          };
          
          Object.entries(stats.by_status).forEach(([status, count]) => {
            const config = statusConfig[status] || { label: status, class: 'bg-secondary text-white' };
            if (count > 0) {
              statusContainer.append(`
                <span class="badge ${config.class} px-3 py-2">
                  ${config.label}: ${count}
                </span>
              `);
            }
          });
        }
      }
    }, 'json').fail(function() {
      console.warn('Failed to load lab test statistics');
    });
  }

  // Load categories for filters
  function loadCategories() {
    $.get('../ajax/get_lab_test_categories.php', function(resp) {
      if (resp && resp.success && resp.data) {
        const categories = resp.data;
        
        // Update category filters
        const categorySelects = ['#categoryFilter', '#as_category'];
        categorySelects.forEach(selector => {
          const $select = $(selector);
          const currentVal = $select.val();
          $select.find('option:not(:first)').remove();
          
          categories.forEach(category => {
            $select.append(`<option value="${category}">${category}</option>`);
          });
          
          if (currentVal) $select.val(currentVal);
        });
        
        // Update category datalist for form
        const $datalist = $('#categoryList');
        $datalist.empty();
        categories.forEach(category => {
          $datalist.append(`<option value="${category}">`);
        });
      }
    }, 'json');
  }

  // Initialize DataTable
  const table = $('#labTestsTable').DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: '../ajax/get_lab_tests.php',
        type: 'GET',
        data: function(d) { 
          d.search = d.search || {};
          
          if (currentSearchType === 'standard') {
            d.search.value = $('#labTestsSearch').val();
          } else {
            // Advanced search parameters will be handled separately
            d.search.value = '';
          }
        }
      },
      columns: [
        { 
          data: 'patient_name',
          render: function(data, type, row) {
            return `
              <div class="fw-bold">${data || 'N/A'}</div>
              <small class="text-muted">${row.patient_code || ''}</small>
            `;
          }
        },
        { 
          data: 'test_name',
          render: function(data, type, row) {
            return `
              <div class="fw-bold">${data || 'N/A'}</div>
              ${row.test_category ? `<small class="text-muted">${row.test_category}</small>` : ''}
            `;
          }
        },
        { 
          data: 'test_category',
          render: function(data) {
            return data ? `<span class="badge bg-light text-dark border">${data}</span>` : '';
          }
        },
        { 
          data: 'test_date',
          render: function(data) {
            if (!data) return '';
            const date = new Date(data);
            return date.toLocaleDateString('en-US', { 
              year: 'numeric', 
              month: 'short', 
              day: 'numeric' 
            });
          }
        },
        { 
          data: 'status',
          render: function(data) {
            const statusConfig = {
              'ordered': { label: 'Ordered', class: 'bg-warning text-dark' },
              'sample_collected': { label: 'Sample Collected', class: 'bg-info text-white' },
              'in_progress': { label: 'In Progress', class: 'bg-primary text-white' },
              'completed': { label: 'Completed', class: 'bg-success text-white' },
              'cancelled': { label: 'Cancelled', class: 'bg-danger text-white' }
            };
            
            const config = statusConfig[data] || { label: data || 'Unknown', class: 'bg-secondary text-white' };
            return `<span class="badge ${config.class}">${config.label}</span>`;
          }
        },
        { 
          data: 'doctor_name',
          render: function(data, type, row) {
            return data ? `
              <div class="small">${data}</div>
              ${row.doctor_code ? `<small class="text-muted">${row.doctor_code}</small>` : ''}
            ` : '<span class="text-muted">Not assigned</span>';
          }
        },
        { 
          data: 'results',
          render: function(data, type, row) {
            if (!data) return '<span class="text-muted">Pending</span>';
            
            const isAbnormal = /critical|urgent|abnormal|high|low/i.test(data);
            const badgeClass = isAbnormal ? 'bg-warning text-dark' : 'bg-success text-white';
            const icon = isAbnormal ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
            
            return `
              <span class="badge ${badgeClass}">
                <i class="${icon} me-1"></i>
                ${isAbnormal ? 'Abnormal' : 'Normal'}
              </span>
            `;
          }
        },
        { data: null, orderable: false, render: function(data){
            const status = (data.status || '').toLowerCase();
            let actions = [];
            
            // View details - always available
            actions.push(`
              <button class="btn btn-outline-primary btn-sm view-lab" data-id="${data.id}" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
            `);

            // Status workflow actions based on role and current status
            if (['receptionist','secretary','admin','doctor'].includes(USER_TYPE)) {
              if (status === 'ordered') {
                actions.push(`
                  <button class="btn btn-outline-info btn-sm mark-sample" data-id="${data.id}" title="Mark Sample Collected">
                    <i class="fas fa-vial"></i>
                  </button>
                `);
              }
              
              if (status === 'sample_collected') {
                actions.push(`
                  <button class="btn btn-outline-warning btn-sm mark-inprogress" data-id="${data.id}" title="Mark In Progress">
                    <i class="fas fa-clock"></i>
                  </button>
                `);
              }
            }

            // Complete test - only admin/doctor
            if (['admin','doctor'].includes(USER_TYPE) && !['completed', 'cancelled'].includes(status)) {
              actions.push(`
                <button class="btn btn-outline-success btn-sm mark-completed" data-id="${data.id}" title="Mark Completed">
                  <i class="fas fa-check"></i>
                </button>
              `);
            }

            // Edit/Delete - admin & doctor only
            if (['admin','doctor'].includes(USER_TYPE)) {
              actions.push(`
                <button class="btn btn-outline-secondary btn-sm edit-lab" data-id="${data.id}" title="Edit Test">
                  <i class="fas fa-edit"></i>
                </button>
              `);
              actions.push(`
                <button class="btn btn-outline-danger btn-sm delete-lab" data-id="${data.id}" title="Delete Test">
                  <i class="fas fa-trash"></i>
                </button>
              `);
            }

            return `<div class="btn-group btn-group-sm" role="group">${actions.join('')}</div>`;
        } }
      ],
      language: {
        processing: '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Processing lab tests...</div></div>',
        emptyTable: '<div class="text-center py-4"><i class="fas fa-microscope fa-3x text-muted mb-3"></i><div>No lab tests found</div><small class="text-muted">Add your first lab test to get started</small></div>'
      },
      order: [[3, 'desc']], // Sort by date descending
      pageLength: 25,
      responsive: true
    });

    // Search functionality
    $('#labTestsSearch').on('keypress', function(e) { 
      if (e.key === 'Enter') {
        currentSearchType = 'standard';
        table.ajax.reload(); 
      }
    });

    $('#searchBtn').on('click', function() {
      currentSearchType = 'standard';
      table.ajax.reload();
    });

    $('#resetBtn').on('click', function() {
      $('#labTestsSearch, #statusFilter, #categoryFilter, #doctorFilter').val('');
      currentSearchType = 'standard';
      table.ajax.reload();
    });

    // Advanced Search Modal
    $('#advancedSearchBtn').on('click', function() {
      loadDoctors($('#as_doctor_id'));
      loadCategories();
      const modal = new bootstrap.Modal(document.getElementById('advancedSearchModal'));
      modal.show();
    });

    $('#resetAdvancedSearch').on('click', function() {
      $('#advancedSearchForm')[0].reset();
    });

    $('#executeAdvancedSearch').on('click', function() {
      const formData = new FormData(document.getElementById('advancedSearchForm'));
      const criteria = {};
      
      for (let [key, value] of formData.entries()) {
        if (value.trim()) {
          criteria[key] = value.trim();
        }
      }
      
      // Execute search
      $.post('../ajax/search_lab_tests.php', criteria, function(resp) {
        if (resp && resp.success) {
          // Update table with search results
          displaySearchResults(resp.data);
          $('#advancedSearchModal').modal('hide');
          
          Swal.fire({
            icon: 'success',
            title: 'Search Complete',
            text: `Found ${resp.data.length} matching tests`,
            timer: 2000,
            showConfirmButton: false
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Search Failed',
            text: resp.message || 'Search operation failed'
          });
        }
      }, 'json').fail(function() {
        Swal.fire({
          icon: 'error',
          title: 'Search Error',
          text: 'Unable to perform search. Please try again.'
        });
      });
    });

    function displaySearchResults(results) {
      // Clear and populate table with search results
      table.clear();
      
      results.forEach(function(row) {
        // Format the data to match DataTable structure
        const formattedRow = {
          id: row.id,
          patient_name: row.patient_name,
          patient_code: row.patient_code,
          test_name: row.test_name,
          test_category: row.test_category,
          test_date: row.test_date,
          status: row.status,
          doctor_name: row.doctor_name,
          doctor_code: row.doctor_code,
          results: row.results
        };
        
        table.row.add(formattedRow);
      });
      
      table.draw();
      currentSearchType = 'advanced';
    }

    // Load patients for select elements
    function loadPatients(selectEl, selected) {
      $.get('../ajax/get_patients.php', { length: 100 }, function(resp) {
        if (resp && resp.data) {
          selectEl.empty();
          selectEl.append('<option value="">Select patient...</option>');
          
          resp.data.forEach(function(p) {
            const displayName = `${p.first_name} ${p.last_name} (${p.patient_id})`;
            selectEl.append(`<option value="${p.id}">${displayName}</option>`);
          });
          
          if (selected) selectEl.val(selected);
        }
      }, 'json').fail(function() {
        selectEl.empty();
        selectEl.append('<option value="">Error loading patients</option>');
      });
    }

    // Load doctors for select elements
    function loadDoctors(selectEl, selected) {
      $.get('../ajax/get_doctors.php', function(resp) {
        selectEl.empty();
        selectEl.append('<option value="">Select doctor...</option>');
        
        if (resp && resp.success && resp.data) {
          resp.data.forEach(function(d) {
            const displayName = `Dr. ${d.name} (${d.doctor_id})`;
            selectEl.append(`<option value="${d.id}">${displayName}</option>`);
          });
          
          if (selected) selectEl.val(selected);
        }
      }, 'json').fail(function() {
        selectEl.empty();
        selectEl.append('<option value="">Error loading doctors</option>');
      });
      
      // Also populate filter dropdown
      if (selectEl.is('#doctorFilter')) {
        return; // Skip for filter dropdown to avoid duplication
      }
      
      // Update doctor filter if this is not the filter itself
      if (!selectEl.is('#doctorFilter, #as_doctor_id')) {
        $.get('../ajax/get_doctors.php', function(resp) {
          if (resp && resp.success && resp.data) {
            const filterSelects = ['#doctorFilter', '#as_doctor_id'];
            filterSelects.forEach(selector => {
              const $filterSelect = $(selector);
              const currentVal = $filterSelect.val();
              $filterSelect.find('option:not(:first)').remove();
              
              resp.data.forEach(function(d) {
                const displayName = `Dr. ${d.name}`;
                $filterSelect.append(`<option value="${d.id}">${displayName}</option>`);
              });
              
              if (currentVal) $filterSelect.val(currentVal);
            });
          }
        }, 'json');
      }
    }

    // Add new lab test
    $('#addLabTestBtn').on('click', function() {
      $('#editLabForm')[0].reset();
      $('#lt_id').val('');
      $('#modalTitle').text('Add Lab Test');
      
      // Set default date to today
      $('#lt_test_date').val(new Date().toISOString().split('T')[0]);
      
      // Load dropdown data
      loadPatients($('#lt_patient_id'));
      loadDoctors($('#lt_doctor_id'));
      loadCategories();
      
      const modal = new bootstrap.Modal(document.getElementById('editLabModal'));
      modal.show();
    });

    // View lab test details
    $('#labTestsTable').on('click', '.view-lab', function() {
      const id = $(this).data('id');
      
      // Reset modal content
      $('#viewLabBody').html(`
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <div class="mt-2 text-muted">Loading test details...</div>
        </div>
      `);
      $('#fileViewerContainer').html(`
        <div class="text-center text-muted">
          <i class="fas fa-file-medical fa-3x mb-3 opacity-25"></i>
          <div>Loading report...</div>
        </div>
      `);
      $('#fileControls').addClass('d-none');
      
      const modal = new bootstrap.Modal(document.getElementById('viewLabModal'));
      modal.show();
      
      $.get('../ajax/get_lab_test.php', { id: id }, function(resp) {
        if (resp && resp.success && resp.data) {
          const r = resp.data;
          
          // Enhanced left panel - test details
          const html = `
            <div class="card border-0 mb-3">
              <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="fas fa-user-injured me-2"></i>Patient Information</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-6">
                    <strong>Patient:</strong><br>
                    <span class="text-primary">${r.patient_name || 'N/A'}</span>
                  </div>
                  <div class="col-6">
                    <strong>Patient ID:</strong><br>
                    <span class="text-muted">${r.patient_code || 'N/A'}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 mb-3">
              <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0"><i class="fas fa-vial me-2"></i>Test Information</h6>
              </div>
              <div class="card-body">
                <div class="row mb-2">
                  <div class="col-6">
                    <strong>Test Name:</strong><br>
                    <span class="fw-bold">${r.test_name || 'N/A'}</span>
                  </div>
                  <div class="col-6">
                    <strong>Category:</strong><br>
                    ${r.test_category ? 
                      `<span class="badge bg-light text-dark border">${r.test_category}</span>` : 
                      '<span class="text-muted">Not specified</span>'
                    }
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-6">
                    <strong>Test Date:</strong><br>
                    <span class="text-muted">${r.test_date ? new Date(r.test_date).toLocaleDateString() : 'N/A'}</span>
                  </div>
                  <div class="col-6">
                    <strong>Status:</strong><br>
                    <span class="badge bg-${getStatusColor(r.status)}">${r.status || 'Unknown'}</span>
                  </div>
                </div>
                ${r.doctor_name ? `
                <div class="row">
                  <div class="col-12">
                    <strong>Ordering Doctor:</strong><br>
                    <span class="text-primary">Dr. ${r.doctor_name}</span>
                    ${r.doctor_code ? `<small class="text-muted">(${r.doctor_code})</small>` : ''}
                  </div>
                </div>
                ` : ''}
              </div>
            </div>

            ${r.results || r.normal_range || r.lab_technician || r.recorded_at ? `
            <div class="card border-0 mb-3">
              <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Lab Results</h6>
              </div>
              <div class="card-body">
                ${r.results ? `
                <div class="mb-2">
                  <strong>Results:</strong><br>
                  <div class="p-2 bg-light border rounded">
                    ${r.results.replace(/\n/g, '<br>')}
                  </div>
                </div>
                ` : ''}
                ${r.normal_range ? `
                <div class="mb-2">
                  <strong>Normal Range:</strong><br>
                  <span class="text-muted">${r.normal_range}</span>
                </div>
                ` : ''}
                ${r.lab_technician ? `
                <div class="mb-2">
                  <strong>Lab Technician:</strong><br>
                  <span class="text-info">${r.lab_technician}</span>
                </div>
                ` : ''}
                ${r.recorded_at ? `
                <div class="mb-2">
                  <strong>Recorded:</strong><br>
                  <span class="text-muted">${new Date(r.recorded_at).toLocaleString()}</span>
                </div>
                ` : ''}
                ${r.recorded_by_name ? `
                <div class="mb-0">
                  <strong>Recorded by:</strong><br>
                  <span class="text-primary">${r.recorded_by_name}</span>
                </div>
                ` : ''}
              </div>
            </div>
            ` : `
            <div class="card border-0 mb-3">
              <div class="card-body text-center text-muted">
                <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                <div>Results pending</div>
                <small>Lab results will appear here once available</small>
              </div>
            </div>
            `}

            ${r.notes ? `
            <div class="card border-0">
              <div class="card-header bg-warning text-dark py-2">
                <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Clinical Notes</h6>
              </div>
              <div class="card-body">
                <div class="p-2 bg-light border rounded">
                  ${r.notes.replace(/\n/g, '<br>')}
                </div>
              </div>
            </div>
            ` : ''}
          `;
          
          $('#viewLabBody').html(html);
          
          // Right panel - enhanced file viewer
          if (r.report_file && r.latest_result && r.latest_result.id) {
            displayLabReport(r.latest_result.id, r.report_file);
          } else {
            $('#fileViewerContainer').html(`
              <div class="text-center text-muted d-flex flex-column align-items-center justify-content-center h-100">
                <i class="fas fa-file-medical fa-4x mb-3 opacity-25"></i>
                <div class="h5">No Report File</div>
                <small>Lab report will appear here once uploaded</small>
              </div>
            `);
            $('#fileControls').addClass('d-none');
          }
          
        } else {
          $('#viewLabBody').html(`
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Failed to load lab test details
            </div>
          `);
          $('#fileViewerContainer').html(`
            <div class="text-center text-danger">
              <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
              <div>Failed to load report</div>
            </div>
          `);
        }
      }, 'json').fail(function() {
        $('#viewLabBody').html(`
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Network error. Please try again.
          </div>
        `);
      });
    });
    
    function getStatusColor(status) {
      const colors = {
        'ordered': 'warning',
        'sample_collected': 'info', 
        'in_progress': 'primary',
        'completed': 'success',
        'cancelled': 'danger'
      };
      return colors[status] || 'secondary';
    }
    
    function displayLabReport(labResultId, filename) {
      const fileExt = filename.split('.').pop().toLowerCase();
      const downloadUrl = `../ajax/download_lab_report.php?id=${labResultId}`;
      
      $('#downloadLink').attr('href', downloadUrl);
      $('#openNewTabLink').attr('href', downloadUrl);
      $('#fileControls').removeClass('d-none');
      
      if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
        // Display image
        $('#fileViewerContainer').html(`
          <img src="${downloadUrl}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;" alt="Lab Report">
        `);
      } else if (fileExt === 'pdf') {
        // Display PDF
        $('#fileViewerContainer').html(`
          <iframe src="${downloadUrl}" width="100%" height="100%" frameborder="0" style="border-radius: 4px;">
            <p>Your browser doesn't support PDF viewing. <a href="${downloadUrl}" target="_blank">Download the PDF</a></p>
          </iframe>
        `);
      } else {
        // Unsupported file type
        $('#fileViewerContainer').html(`
          <div class="text-center text-muted">
            <i class="fas fa-file fa-3x mb-2"></i>
            <p class="mb-1">File: ${filename}</p>
            <p class="small">Preview not available for this file type</p>
            <a href="${downloadUrl}" class="btn btn-sm btn-primary" target="_blank">
              <i class="fas fa-download"></i> Download File
            </a>
          </div>
        `);
      }
    }

    $('#labTestsTable').on('click', '.edit-lab', function(){
      var id = $(this).data('id');
      $('#editLabForm')[0].reset(); $('#lt_id').val(id); $('#editLabModal .modal-title').text('Edit Lab Test');
      $.get('../ajax/get_lab_test.php', { id: id }, function(resp){
        if (resp && resp.success && resp.data) {
          var r = resp.data;
          $('#lt_test_name').val(r.test_name||'');
          $('#lt_test_category').val(r.test_category||'');
          $('#lt_test_date').val(r.test_date||'');
          $('#lt_notes').val(r.notes||'');
          loadPatients($('#lt_patient_id'), r.patient_id);
          loadDoctors($('#lt_doctor_id'), r.doctor_id);
          var modal = new bootstrap.Modal(document.getElementById('editLabModal'));
          modal.show();
        } else {
          Swal.fire({ icon: 'error', title: 'Load failed' });
        }
      }, 'json');
    });

    // status actions
    function postStatus(id, status, extra){
      var payload = { id: id, status: status };
      if (extra) Object.assign(payload, extra);
      $.post('../ajax/update_lab_test.php', payload, function(resp){ if (resp && resp.success) { table.ajax.reload(); Swal.fire({ icon: 'success', title: 'Updated' }); } else Swal.fire({ icon: 'error', title: 'Update failed', text: resp.message||'' }); }, 'json');
    }

    $('#labTestsTable').on('click', '.mark-sample', function(){
      var id = $(this).data('id');
      var now = new Date();
      var fmt = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0') + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
      postStatus(id, 'sample_collected', { sample_collected_date: fmt });
    });

    $('#labTestsTable').on('click', '.mark-inprogress', function(){
      var id = $(this).data('id'); postStatus(id, 'in_progress');
    });

    $('#labTestsTable').on('click', '.mark-completed', function(){
      var id = $(this).data('id');
      // prompt for confirmation and optional results
      Swal.fire({ title: 'Mark completed?', text: 'You should attach results before completing', icon: 'question', showCancelButton:true, input:'textarea', inputPlaceholder:'Add short results (optional)'}).then(function(res){
        if (res.isConfirmed) { postStatus(id, 'completed', { results: res.value || '' }); }
      });
    });

    $('#labTestsTable').on('click', '.delete-lab', function(){
      var id = $(this).data('id');
      Swal.fire({ title: 'Confirm delete', text: 'This will remove the lab test', icon: 'warning', showCancelButton:true }).then(function(res){
        if (res.isConfirmed) {
          $.post('../ajax/delete_lab_test.php', { id: id }, function(resp){ if (resp && resp.success) { table.ajax.reload(); Swal.fire({ icon: 'success', title: 'Deleted' }); } else Swal.fire({ icon: 'error', title: 'Delete failed', text: resp.message||'' }); }, 'json');
        }
      });
    });

    $('#saveLabTest').on('click', function(){
      var form = $('#editLabForm');
      var id = $('#lt_id').val();
      var url = id ? '../ajax/update_lab_test.php' : '../ajax/create_lab_test.php';
      var fd = new FormData();
      // append form fields
      var fields = form.serializeArray();
      fields.forEach(function(f){ fd.append(f.name, f.value); });
      var fileEl = document.getElementById('lt_report_file');
      if (fileEl && fileEl.files && fileEl.files[0]) fd.append('report_file', fileEl.files[0]);
      $.ajax({ url: url, data: fd, type: 'POST', processData: false, contentType: false, dataType: 'json', success: function(resp){ if (resp && resp.success) { $('#editLabModal').modal('hide'); table.ajax.reload(); Swal.fire({ icon: 'success', title: 'Saved' }); } else Swal.fire({ icon: 'error', title: 'Save failed', text: resp.message||'' }); }, error: function(){ Swal.fire({ icon: 'error', title: 'Save failed', text: 'Server error' }); } });
    });
  // Initialize dashboard and filters on page load
  loadDashboardStats();
  loadCategories();
  loadDoctors($('#doctorFilter'));

  // Refresh dashboard stats every 5 minutes
  setInterval(loadDashboardStats, 300000);

  // Initialize date fields with default values
  const today = new Date().toISOString().split('T')[0];
  $('#lt_test_date').val(today);

  });
</script>
