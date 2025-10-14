<?php
$page_title = 'Search Patients';
$additional_css = [];
$additional_js = ['doctor-patient-search.js'];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['doctor', 'admin']);

ob_start();
?>

<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Patient Search</h5>
          <small class="text-muted">Search and manage patients</small>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.history.back()">
          <i class="bx bx-arrow-back me-1"></i>Back to Dashboard
        </button>
      </div>
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-lg-8 col-12">
            <div class="input-group input-group-lg">
              <span class="input-group-text">
                <i class="bx bx-search"></i>
              </span>
              <input type="text" class="form-control" id="patientSearchInput" 
                     placeholder="Search by name, ID, phone number, or email..." autofocus>
              <button class="btn btn-primary" type="button" id="advancedSearchBtn">
                <i class="bx bx-filter me-1"></i>Advanced
              </button>
            </div>
          </div>
          <div class="col-lg-4 col-12 mt-3 mt-lg-0">
            <div class="d-grid gap-2 d-lg-block">
              <button type="button" class="btn btn-success" id="quickAddToQueueBtn" disabled>
                <i class="bx bx-plus me-1"></i>Add Selected to Queue
              </button>
            </div>
          </div>
        </div>
        
        <!-- Advanced Search Panel (Hidden by default) -->
        <div class="card mb-4" id="advancedSearchPanel" style="display: none;">
          <div class="card-header">
            <h6 class="mb-0">Advanced Search Filters</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select class="form-select" id="filterGender">
                  <option value="">All</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Age Range</label>
                <select class="form-select" id="filterAgeRange">
                  <option value="">All Ages</option>
                  <option value="0-18">0-18 years</option>
                  <option value="19-35">19-35 years</option>
                  <option value="36-55">36-55 years</option>
                  <option value="56-120">56+ years</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Last Visit</label>
                <select class="form-select" id="filterLastVisit">
                  <option value="">Any time</option>
                  <option value="7">Last 7 days</option>
                  <option value="30">Last 30 days</option>
                  <option value="90">Last 3 months</option>
                  <option value="365">Last year</option>
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn">
                  <i class="bx bx-x me-1"></i>Clear Filters
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Search Results -->
        <div id="searchResultsContainer">
          <div class="text-center py-5 text-muted">
            <i class="bx bx-search fs-1"></i>
            <p class="mt-2">Enter a search term to find patients</p>
            <small>Search by name, patient ID, phone number, or email address</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Patient Details Modal -->
<div class="modal fade" id="patientDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Patient Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="patientDetailsBody">
        Loading...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <div class="btn-group">
          <button type="button" class="btn btn-success" id="addToQueueFromModal">
            <i class="bx bx-plus me-1"></i>Add to Queue
          </button>
          <button type="button" class="btn btn-primary" id="newMedicalRecordFromModal">
            <i class="bx bx-file-plus me-1"></i>New Medical Record
          </button>
          <button type="button" class="btn btn-info" id="newLabTestFromModal">
            <i class="bx bx-test-tube me-1"></i>New Lab Test
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
