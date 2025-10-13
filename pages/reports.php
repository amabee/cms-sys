<?php
$page_title = 'Comprehensive Reports';
$additional_css = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css'
];
$additional_js = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
    '../assets/js/reports.js'
];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin', 'doctor']);

ob_start();
?>
<style>
    .stats-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
    }
    .stats-card h3 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .filter-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
    .chart-container {
        position: relative;
        height: 400px;
    }
    .btn-export {
        background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
        border: none;
        color: white;
    }
    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        color: white;
    }
    .loading-spinner {
        display: none;
        text-align: center;
        padding: 2rem;
    }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bx bx-bar-chart-alt-2 me-2"></i>Comprehensive Reports
    </h1>
    <div class="d-flex gap-2">
        <button class="btn btn-export text-white" onclick="exportCurrentReport()">
            <i class="bx bx-download me-2"></i>Export CSV
        </button>
        <button class="btn btn-secondary" onclick="printReport()">
            <i class="bx bx-printer me-2"></i>Print Report
        </button>
    </div>
</div>


<!-- Dashboard Statistics -->
<div class="row mb-4" id="dashboard-stats">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-user display-4 mb-3"></i>
                <h3 id="total-patients">-</h3>
                <p class="mb-0">Total Patients</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-calendar-check display-4 mb-3"></i>
                <h3 id="total-appointments">-</h3>
                <p class="mb-0">Appointments (30d)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-dollar display-4 mb-3"></i>
                <h3 id="total-revenue">-</h3>
                <p class="mb-0">Revenue (30d)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-clipboard display-4 mb-3"></i>
                <h3 id="total-lab-tests">-</h3>
                <p class="mb-0">Lab Tests (30d)</p>
            </div>
        </div>
    </div>
</div>

                    <!-- Report Tabs -->
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="reportTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="patient-demographics-tab" data-bs-toggle="tab" data-bs-target="#patient-demographics" type="button" role="tab">
                                        <i class="bx bx-user me-2"></i>Patient Demographics
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="appointment-analytics-tab" data-bs-toggle="tab" data-bs-target="#appointment-analytics" type="button" role="tab">
                                        <i class="bx bx-calendar me-2"></i>Appointment Analytics
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="financial-report-tab" data-bs-toggle="tab" data-bs-target="#financial-report" type="button" role="tab">
                                        <i class="bx bx-dollar-circle me-2"></i>Financial Report
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="lab-tests-report-tab" data-bs-toggle="tab" data-bs-target="#lab-tests-report" type="button" role="tab">
                                        <i class="bx bx-test-tube me-2"></i>Lab Tests Report
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="medical-records-report-tab" data-bs-toggle="tab" data-bs-target="#medical-records-report" type="button" role="tab">
                                        <i class="bx bx-notepad me-2"></i>Medical Records Report
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content" id="reportTabContent">
                                <!-- Patient Demographics Tab -->
                                <div class="tab-pane fade show active" id="patient-demographics" role="tabpanel">
                                    <div class="filter-section">
                                        <h5 class="mb-3"><i class="bx bx-filter me-2"></i>Filters</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="demographics-start-date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="demographics-start-date">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="demographics-end-date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="demographics-end-date">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button class="btn btn-primary" onclick="loadPatientDemographics()">
                                                    <i class="bx bx-search me-2"></i>Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="loading-spinner" id="demographics-loading">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2">Loading demographics data...</p>
                                    </div>

                                    <div id="demographics-content">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-gender-ambiguous me-2"></i>Gender Distribution</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="genderChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Age Distribution</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="ageChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Registration Trends (Last 12 Months)</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="registrationTrendsChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appointment Analytics Tab -->
                                <div class="tab-pane fade" id="appointment-analytics" role="tabpanel">
                                    <div class="filter-section">
                                        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="appointments-start-date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="appointments-start-date">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="appointments-end-date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="appointments-end-date">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="appointments-doctor" class="form-label">Doctor</label>
                                                <select class="form-control" id="appointments-doctor">
                                                    <option value="">All Doctors</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button class="btn btn-primary" onclick="loadAppointmentAnalytics()">
                                                    <i class="bx bx-search me-2"></i>Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="loading-spinner" id="appointments-loading">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2">Loading appointment analytics...</p>
                                    </div>

                                    <div id="appointments-content">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Status Distribution</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="appointmentStatusChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Peak Hours</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="peakHoursChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Doctor Performance</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped" id="doctorPerformanceTable">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Doctor Name</th>
                                                                        <th>Specialization</th>
                                                                        <th>Total Appointments</th>
                                                                        <th>Completed</th>
                                                                        <th>Completion Rate</th>
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
                                    <div class="filter-section">
                                        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="financial-start-date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="financial-start-date">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="financial-end-date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="financial-end-date">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button class="btn btn-primary" onclick="loadFinancialReport()">
                                                    <i class="bi bi-search me-2"></i>Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="loading-spinner" id="financial-loading">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2">Loading financial data...</p>
                                    </div>

                                    <div id="financial-content">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Revenue Trends</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="revenueChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Revenue Summary</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="revenue-summary"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i>Payment Methods</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="paymentMethodsChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Outstanding Bills (Aging)</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="agingChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lab Tests Report Tab -->
                                <div class="tab-pane fade" id="lab-tests-report" role="tabpanel">
                                    <div class="filter-section">
                                        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="lab-start-date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="lab-start-date">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="lab-end-date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="lab-end-date">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="lab-category" class="form-label">Category</label>
                                                <select class="form-control" id="lab-category">
                                                    <option value="">All Categories</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button class="btn btn-primary" onclick="loadLabTestsReport()">
                                                    <i class="bi bi-search me-2"></i>Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="loading-spinner" id="lab-loading">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2">Loading lab tests data...</p>
                                    </div>

                                    <div id="lab-content">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Test Status</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="testStatusChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-tags me-2"></i>Category Distribution</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="categoryChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-trophy me-2"></i>Most Ordered Tests</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped" id="popularTestsTable">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Test Name</th>
                                                                        <th>Category</th>
                                                                        <th>Total Orders</th>
                                                                        <th>Completed</th>
                                                                        <th>Completion Rate</th>
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
                                    <div class="filter-section">
                                        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="records-start-date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="records-start-date">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="records-end-date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="records-end-date">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="records-doctor" class="form-label">Doctor</label>
                                                <select class="form-control" id="records-doctor">
                                                    <option value="">All Doctors</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button class="btn btn-primary" onclick="loadMedicalRecordsReport()">
                                                    <i class="bi bi-search me-2"></i>Generate Report
                                                </button>
                            </div>
                                        </div>
                                    </div>

                                    <div class="loading-spinner" id="records-loading">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2">Loading medical records data...</p>
                                    </div>

                                    <div id="records-content">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Daily Records Volume</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="recordsVolumeChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Records Summary</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="records-summary"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-clipboard-pulse me-2"></i>Common Diagnoses</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container">
                                                            <canvas id="diagnosesChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Doctor Activity</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped" id="doctorActivityTable">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Doctor</th>
                                                                        <th>Specialization</th>
                                                                        <th>Records</th>
                                                                        <th>Patients</th>
                                                                        <th>Vital Signs %</th>
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
