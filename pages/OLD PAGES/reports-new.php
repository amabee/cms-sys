<?php
$page_title = 'Reports & Analytics';
$additional_css = [];
$additional_js = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
    '../assets/js/reports.js'
];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin', 'doctor']);

ob_start();
?>

<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="fw-bold mb-1">
            <i class="bx bx-bar-chart-alt-2 me-2 text-primary"></i>Reports & Analytics
          </h4>
          <p class="text-muted mb-0 small">Comprehensive insights and performance metrics</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-primary" onclick="exportCurrentReport()">
            <i class="bx bx-download me-1"></i>Export
          </button>
          <button class="btn btn-sm btn-outline-secondary" onclick="printReport()">
            <i class="bx bx-printer me-1"></i>Print
          </button>
        </div>
      </div>

      <!-- Statistics Overview -->
      <div class="row mb-4" id="dashboard-stats">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between mb-2">
                <div class="avatar flex-shrink-0">
                  <div class="avatar-initial bg-label-primary rounded">
                    <i class="bx bx-user"></i>
                  </div>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Total Patients</span>
              <h3 class="card-title mb-2" id="total-patients">-</h3>
              <small class="text-primary fw-semibold"><i class="bx bx-up-arrow-alt"></i> All Time</small>
            </div>
          </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between mb-2">
                <div class="avatar flex-shrink-0">
                  <div class="avatar-initial bg-label-success rounded">
                    <i class="bx bx-calendar-check"></i>
                  </div>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Appointments</span>
              <h3 class="card-title mb-2" id="total-appointments">-</h3>
              <small class="text-success fw-semibold"><i class="bx bx-time-five"></i> Last 30 Days</small>
            </div>
          </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between mb-2">
                <div class="avatar flex-shrink-0">
                  <div class="avatar-initial bg-label-info rounded">
                    <i class="bx bx-dollar"></i>
                  </div>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Revenue</span>
              <h3 class="card-title mb-2" id="total-revenue">-</h3>
              <small class="text-info fw-semibold"><i class="bx bx-wallet"></i> Last 30 Days</small>
            </div>
          </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between mb-2">
                <div class="avatar flex-shrink-0">
                  <div class="avatar-initial bg-label-warning rounded">
                    <i class="bx bx-test-tube"></i>
                  </div>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Lab Tests</span>
              <h3 class="card-title mb-2" id="total-lab-tests">-</h3>
              <small class="text-warning fw-semibold"><i class="bx bx-time-five"></i> Last 30 Days</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Report Tabs -->
      <div class="row">
        <div class="col-12">
          <div class="nav-align-top mb-4">
            <ul class="nav nav-pills mb-3" role="tablist">
              <li class="nav-item">
                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#patient-demographics">
                  <i class="tf-icons bx bx-user"></i> Patients
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#appointment-analytics">
                  <i class="tf-icons bx bx-calendar"></i> Appointments
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#financial-report">
                  <i class="tf-icons bx bx-dollar-circle"></i> Financial
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#lab-tests-report">
                  <i class="tf-icons bx bx-test-tube"></i> Lab Tests
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#medical-records-report">
                  <i class="tf-icons bx bx-notepad"></i> Records
                </button>
              </li>
            </ul>
            
            <div class="tab-content p-0">
              <!-- Patient Demographics Tab -->
              <div class="tab-pane fade show active" id="patient-demographics" role="tabpanel">
                <div class="card">
                  <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-filter-alt me-2"></i>Report Filters</h5>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-5">
                        <label for="demographics-start-date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="demographics-start-date">
                      </div>
                      <div class="col-md-5">
                        <label for="demographics-end-date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="demographics-end-date">
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="loadPatientDemographics()">
                          <i class="bx bx-search me-1"></i>Generate
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Loading State -->
                <div class="text-center py-5 d-none" id="demographics-loading">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-3 text-muted">Loading demographics data...</p>
                </div>

                <!-- Content -->
                <div id="demographics-content" class="mt-4">
                  <div class="row">
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Gender Distribution</h5>
                          <i class="bx bx-male-female text-primary"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="genderChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Age Distribution</h5>
                          <i class="bx bx-bar-chart text-info"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="ageChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Registration Trends (Last 12 Months)</h5>
                          <i class="bx bx-line-chart text-success"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="registrationTrendsChart" height="100"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Appointment Analytics Tab -->
              <div class="tab-pane fade" id="appointment-analytics" role="tabpanel">
                <div class="card">
                  <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-filter-alt me-2"></i>Report Filters</h5>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label for="appointments-start-date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="appointments-start-date">
                      </div>
                      <div class="col-md-4">
                        <label for="appointments-end-date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="appointments-end-date">
                      </div>
                      <div class="col-md-2">
                        <label for="appointments-doctor" class="form-label">Doctor</label>
                        <select class="form-select" id="appointments-doctor">
                          <option value="">All Doctors</option>
                        </select>
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="loadAppointmentAnalytics()">
                          <i class="bx bx-search me-1"></i>Generate
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="text-center py-5 d-none" id="appointments-loading">
                  <div class="spinner-border text-primary" role="status"></div>
                  <p class="mt-3 text-muted">Loading appointment analytics...</p>
                </div>

                <div id="appointments-content" class="mt-4">
                  <div class="row">
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Status Distribution</h5>
                          <i class="bx bx-pie-chart text-primary"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="appointmentStatusChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Peak Hours</h5>
                          <i class="bx bx-time text-warning"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="peakHoursChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0"><i class="bx bx-user-check me-2"></i>Doctor Performance</h5>
                        </div>
                        <div class="card-body">
                          <div class="table-responsive">
                            <table class="table table-hover" id="doctorPerformanceTable">
                              <thead>
                                <tr>
                                  <th>Doctor Name</th>
                                  <th>Specialization</th>
                                  <th>Total</th>
                                  <th>Completed</th>
                                  <th>Rate</th>
                                  <th>No Shows</th>
                                </tr>
                              </thead>
                              <tbody></tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Financial Report Tab -->
              <div class="tab-pane fade" id="financial-report" role="tabpanel">
                <div class="card">
                  <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-filter-alt me-2"></i>Report Filters</h5>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-5">
                        <label for="financial-start-date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="financial-start-date">
                      </div>
                      <div class="col-md-5">
                        <label for="financial-end-date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="financial-end-date">
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="loadFinancialReport()">
                          <i class="bx bx-search me-1"></i>Generate
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="text-center py-5 d-none" id="financial-loading">
                  <div class="spinner-border text-primary" role="status"></div>
                  <p class="mt-3 text-muted">Loading financial data...</p>
                </div>

                <div id="financial-content" class="mt-4">
                  <div class="row">
                    <div class="col-md-8 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Revenue Trends</h5>
                          <i class="bx bx-trending-up text-success"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="revenueChart" height="100"></canvas>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-4">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0"><i class="bx bx-money me-2"></i>Summary</h5>
                        </div>
                        <div class="card-body">
                          <div id="revenue-summary"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Payment Methods</h5>
                          <i class="bx bx-credit-card text-info"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="paymentMethodsChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Outstanding Bills</h5>
                          <i class="bx bx-time-five text-warning"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="agingChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Lab Tests Report Tab -->
              <div class="tab-pane fade" id="lab-tests-report" role="tabpanel">
                <div class="card">
                  <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-filter-alt me-2"></i>Report Filters</h5>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label for="lab-start-date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="lab-start-date">
                      </div>
                      <div class="col-md-4">
                        <label for="lab-end-date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="lab-end-date">
                      </div>
                      <div class="col-md-2">
                        <label for="lab-category" class="form-label">Category</label>
                        <select class="form-select" id="lab-category">
                          <option value="">All Categories</option>
                        </select>
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="loadLabTestsReport()">
                          <i class="bx bx-search me-1"></i>Generate
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="text-center py-5 d-none" id="lab-loading">
                  <div class="spinner-border text-primary" role="status"></div>
                  <p class="mt-3 text-muted">Loading lab tests data...</p>
                </div>

                <div id="lab-content" class="mt-4">
                  <div class="row">
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Test Status</h5>
                          <i class="bx bx-check-circle text-success"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="testStatusChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Category Distribution</h5>
                          <i class="bx bx-category text-primary"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="categoryChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0"><i class="bx bx-trophy me-2"></i>Most Ordered Tests</h5>
                        </div>
                        <div class="card-body">
                          <div class="table-responsive">
                            <table class="table table-hover" id="popularTestsTable">
                              <thead>
                                <tr>
                                  <th>Test Name</th>
                                  <th>Category</th>
                                  <th>Total Orders</th>
                                  <th>Completed</th>
                                  <th>Rate</th>
                                </tr>
                              </thead>
                              <tbody></tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Medical Records Report Tab -->
              <div class="tab-pane fade" id="medical-records-report" role="tabpanel">
                <div class="card">
                  <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-filter-alt me-2"></i>Report Filters</h5>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label for="records-start-date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="records-start-date">
                      </div>
                      <div class="col-md-4">
                        <label for="records-end-date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="records-end-date">
                      </div>
                      <div class="col-md-2">
                        <label for="records-doctor" class="form-label">Doctor</label>
                        <select class="form-select" id="records-doctor">
                          <option value="">All Doctors</option>
                        </select>
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="loadMedicalRecordsReport()">
                          <i class="bx bx-search me-1"></i>Generate
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="text-center py-5 d-none" id="records-loading">
                  <div class="spinner-border text-primary" role="status"></div>
                  <p class="mt-3 text-muted">Loading medical records data...</p>
                </div>

                <div id="records-content" class="mt-4">
                  <div class="row">
                    <div class="col-md-8 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Daily Records Volume</h5>
                          <i class="bx bx-line-chart text-success"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="recordsVolumeChart" height="100"></canvas>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-4">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0"><i class="bx bx-clipboard-data me-2"></i>Summary</h5>
                        </div>
                        <div class="card-body">
                          <div id="records-summary"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h5 class="card-title mb-0">Common Diagnoses</h5>
                          <i class="bx bx-health text-danger"></i>
                        </div>
                        <div class="card-body">
                          <canvas id="diagnosesChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0"><i class="bx bx-user-check me-2"></i>Doctor Activity</h5>
                        </div>
                        <div class="card-body">
                          <div class="table-responsive">
                            <table class="table table-hover" id="doctorActivityTable">
                              <thead>
                                <tr>
                                  <th>Doctor</th>
                                  <th>Specialization</th>
                                  <th>Records</th>
                                  <th>Patients</th>
                                  <th>Vitals %</th>
                                </tr>
                              </thead>
                              <tbody></tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
