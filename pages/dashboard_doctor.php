<?php
$page_title = 'Doctor Dashboard';
$additional_css = [];
$additional_js = ['dashboards-doctor.js'];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['doctor']);

ob_start();
?>
<div class="row">
  <!-- Queue Status Cards -->
  <div class="col-md-4 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="menu-icon tf-icons bx bx-user-check text-primary fs-4"></i>
          </div>
          <div class="dropdown">
            <button class="btn p-0" id="refreshQueue" title="Refresh Queue">
              <i class="bx bx-refresh text-muted"></i>
            </button>
          </div>
        </div>
        <span>Waiting Patients</span>
        <h3 class="card-title text-nowrap mb-1" id="waitingCount">-</h3>
        <small class="text-success fw-semibold" id="queueStatus">Loading...</small>
      </div>
    </div>
  </div>

  <div class="col-md-4 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="menu-icon tf-icons bx bx-calendar text-warning fs-4"></i>
          </div>
        </div>
        <span>Today's Appointments</span>
        <h3 class="card-title text-nowrap mb-1" id="appointmentsCount">-</h3>
        <small class="text-warning fw-semibold" id="appointmentsStatus">Loading...</small>
      </div>
    </div>
  </div>

  <div class="col-md-4 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="menu-icon tf-icons bx bx-check-circle text-success fs-4"></i>
          </div>
        </div>
        <span>Patients Served</span>
        <h3 class="card-title text-nowrap mb-1" id="servedCount">-</h3>
        <small class="text-success fw-semibold" id="servedStatus">Today</small>
      </div>
    </div>
  </div>

  <!-- Patient Queue -->
  <div class="col-md-8 col-12 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Patient Queue</h5>
        <div>
          <button type="button" class="btn btn-primary btn-sm" id="callNextPatient">
            <i class="bx bx-phone-call me-1"></i>Call Next
          </button>
        </div>
      </div>
      <div class="card-body">
        <div id="queueContainer">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading queue...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-md-4 col-12 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <button type="button" class="btn btn-outline-primary" id="searchPatientBtn" onclick="window.location.href='doctor-patient-search.php'">
            <i class="bx bx-search me-1"></i>Search Patient
          </button>
          <button type="button" class="btn btn-outline-success" id="newMedicalRecordBtn">
            <i class="bx bx-file-plus me-1"></i>New Medical Record
          </button>
          <button type="button" class="btn btn-outline-info" id="newLabTestBtn">
            <i class="bx bx-test-tube me-1"></i>New Lab Test
          </button>
          <button type="button" class="btn btn-outline-warning" id="viewAppointmentsBtn">
            <i class="bx bx-calendar me-1"></i>My Appointments
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Today's Appointments -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Today's Appointments</h5>
        <small class="text-muted" id="appointmentsDate"></small>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover" id="appointmentsTable">
            <thead>
              <tr>
                <th>Time</th>
                <th>Patient</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="5" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2 text-muted">Loading appointments...</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Patient Search Modal -->
<div class="modal fade" id="searchPatientModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Search Patient</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" class="form-control" id="patientSearchInput" placeholder="Search by name, ID, or phone...">
        </div>
        <div id="patientSearchResults">
          <div class="text-center py-4 text-muted">
            Start typing to search for patients...
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
