<?php
$page_title = 'Lab Tests Management';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';

requireRole(['admin','doctor','nurse','receptionist']);
$user_role = $_SESSION['role'] ?? null;


ob_start();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Lab Tests Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Lab Tests</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

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
                                <button type="button" class="btn btn-info btn-sm" id="quickLabTestBtn">
                                    <i class="fas fa-bolt"></i> Quick Order
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="searchLabTestsBtn">
                                    <i class="fas fa-search"></i> Advanced Search
                                </button>
                                <button type="button" class="btn btn-success btn-sm" id="templatesBtn">
                                    <i class="fas fa-clipboard-list"></i> Templates
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
                                        <input type="text" class="form-control" id="searchFilter" placeholder="Search by patient name, test name, or reference number..." aria-label="Search lab tests">
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

    <!-- Lab Test Modal -->
    <div class="modal fade" id="labTestModal" tabindex="-1" role="dialog" aria-labelledby="labTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="labTestModalTitle">New Lab Test</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="labTestForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="patientSelect">Patient *</label>
                                    <select class="form-control" id="patientSelect" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                    </select>
                                </div>
                            </div>
                            <?php if ($user_role === 'admin' || $user_role === 'nurse'): ?>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doctorSelect">Ordering Doctor *</label>
                                    <select class="form-control" id="doctorSelect" name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="testCategorySelect">Test Category *</label>
                                    <select class="form-control" id="testCategorySelect" name="category_id" required>
                                        <option value="">Select Category</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="testNameSelect">Test Name *</label>
                                    <select class="form-control" id="testNameSelect" name="test_name" required>
                                        <option value="">Select Test</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="testDate">Test Date *</label>
                                    <input type="date" class="form-control" id="testDate" name="test_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="prioritySelect">Priority</label>
                                    <select class="form-control" id="prioritySelect" name="priority">
                                        <option value="normal">Normal</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="stat">STAT</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="expectedDate">Expected Results Date</label>
                                    <input type="date" class="form-control" id="expectedDate" name="expected_date">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="clinicalNotes">Clinical Notes</label>
                                    <textarea class="form-control" id="clinicalNotes" name="clinical_notes" rows="3" placeholder="Clinical indications, special instructions..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specimenType">Specimen Type</label>
                                    <select class="form-control" id="specimenType" name="specimen_type">
                                        <option value="blood">Blood</option>
                                        <option value="urine">Urine</option>
                                        <option value="stool">Stool</option>
                                        <option value="saliva">Saliva</option>
                                        <option value="tissue">Tissue</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fastingRequired">Fasting Required</label>
                                    <select class="form-control" id="fastingRequired" name="fasting_required">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-info" id="previewLabTestBtn">Preview</button>
                        <button type="button" class="btn btn-primary" id="saveLabTestBtn">Save Lab Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lab Test Details Modal -->
    <div class="modal fade" id="labTestDetailsModal" tabindex="-1" role="dialog" aria-labelledby="labTestDetailsLabel" aria-hidden="true">
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

    <!-- Include SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Include Lab Tests Management JavaScript -->
    <script src="../assets/js/lab-tests.js"></script>

    <!-- Templates Modal -->
    <div class="modal fade" id="templatesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-list me-2"></i>Lab Test Templates</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="input-group" style="max-width: 360px;">
                            <input type="text" class="form-control" id="templateSearch" placeholder="Search templates">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="templateSearchBtn"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="createTemplateBtn"><i class="fas fa-plus"></i> New Template</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Test</th>
                                    <th>Priority</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="templatesTableBody">
                                <tr><td colspan="5" class="text-center text-muted py-4">Loading templates...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Load statistics via AJAX to populate Sneat cards
    (function loadLabStats(){
        fetch('../ajax/get_lab_tests_statistics.php')
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(res => {
                if (!res || !res.success || !res.data) return;
                const s = res.data;
                const total = s.total_tests || 0;
                const ordered = (s.by_status && (s.by_status.ordered||0)) || 0;
                const inProgress = (s.by_status && (s.by_status.in_progress||0)) || 0;
                const completed = (s.by_status && (s.by_status.completed||0)) || 0;
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
    (function initLabTestsUI($){
        if (!$) return;

        // Quick Search actions
        $('#searchBtn').on('click', function(){
            const q = $('#searchFilter').val() || '';
            // Emit generic events other scripts can hook into
            $(document).trigger('labtests:search', [q]);
            // Also trigger change on the input for existing listeners
            $('#searchFilter').trigger('change');
        });
        $('#clearSearchBtn').on('click', function(){
            $('#searchFilter').val('');
            $(document).trigger('labtests:search', ['']);
            $('#searchFilter').trigger('change');
        });

        // Buttons in header
        $('#newLabTestBtn').on('click', function(){
            try { document.getElementById('labTestForm').reset(); } catch(e){}
            $('#labTestModalTitle').text('New Lab Test');
            // Set sensible defaults
            const today = new Date().toISOString().split('T')[0];
            $('#testDate').val(today);
            $('#prioritySelect').val('normal');
            $('#labTestModal').modal('show');
            setTimeout(()=>$('#patientSelect').focus(), 200);
        });

        $('#quickLabTestBtn').on('click', function(){
            try { document.getElementById('labTestForm').reset(); } catch(e){}
            const today = new Date().toISOString().split('T')[0];
            $('#testDate').val(today);
            // Quick defaults
            $('#prioritySelect').val('urgent');
            $('#labTestModal').modal('show');
            setTimeout(()=>$('#patientSelect').focus(), 200);
        });

        $('#searchLabTestsBtn').on('click', function(){
            $('#labTestFilters').slideToggle(150);
        });

        $('#templatesBtn').on('click', function(){
            $('#templatesModal').modal('show');
            loadTemplates();
        });

        // Template search
        $('#templateSearchBtn').on('click', function(){
            loadTemplates($('#templateSearch').val());
        });
        $('#templateSearch').on('keypress', function(e){ if (e.key === 'Enter') { loadTemplates($('#templateSearch').val()); } });
    })(window.jQuery);

    // Load templates list
    function loadTemplates(q=''){
        const $tbody = $('#templatesTableBody');
        $tbody.html('<tr><td colspan="5" class="text-center text-muted py-4">Loading templates...</td></tr>');
        $.ajax({
            url: '../ajax/get_lab_test_templates.php',
            method: 'GET',
            data: q ? { q } : {},
            success: function(res){
                if (!res || !res.success || !Array.isArray(res.data)){
                    $tbody.html('<tr><td colspan="5" class="text-center text-danger py-4">Failed to load templates</td></tr>');
                    return;
                }
                if (res.data.length === 0){
                    $tbody.html('<tr><td colspan="5" class="text-center text-muted py-4">No templates found</td></tr>');
                    return;
                }
                const rows = res.data.map(t => `
                    <tr>
                        <td>${escapeHtml(t.name)}</td>
                        <td>${escapeHtml(t.category || '')}</td>
                        <td>${escapeHtml(t.test_name || '')}</td>
                        <td><span class="badge ${priorityClass(t.priority)}">${(t.priority||'normal').toUpperCase()}</span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-primary" data-template='${JSON.stringify(t).replace(/'/g, "&#39;")}' onclick="applyTemplateFromBtn(this)"><i class="fas fa-check"></i> Apply</button>
                        </td>
                    </tr>
                `).join('');
                $tbody.html(rows);
            },
            error: function(){
                $tbody.html('<tr><td colspan="5" class="text-center text-danger py-4">Server error</td></tr>');
            }
        });
    }

    // Helpers for templates
    function priorityClass(p){
        switch((p||'normal').toLowerCase()){
            case 'urgent': return 'badge-warning';
            case 'stat': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }
    function escapeHtml(s){
        if (s === undefined || s === null) return '';
        return String(s).replace(/[&<>"]/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]); });
    }
    window.applyTemplateFromBtn = function(btn){
        try{
            const t = JSON.parse($(btn).data('template'));
            if (!t) return;
            // Fill modal form fields if present
            if (t.category_id) $('#testCategorySelect').val(String(t.category_id)).trigger('change');
            if (t.test_name) $('#testNameSelect').val(String(t.test_name)).trigger('change');
            if (t.priority) $('#prioritySelect').val(String(t.priority));
            if (t.specimen_type) $('#specimenType').val(String(t.specimen_type));
            if (t.fasting_required) $('#fastingRequired').val(String(t.fasting_required));
            if (t.clinical_notes) $('#clinicalNotes').val(String(t.clinical_notes));
            // Open modal if not visible
            if (!$('#labTestModal').hasClass('show')) $('#labTestModal').modal('show');
            // Focus next field
            setTimeout(()=>$('#patientSelect').focus(), 150);
            // Close templates modal
            $('#templatesModal').modal('hide');
        }catch(e){ /* ignore */ }
    }
    </script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
