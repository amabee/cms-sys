<?php
$page_title = 'Patient Portal';
$additional_css = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css',
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css'
];
$additional_js = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js',
    '../assets/js/patient-portal.js'
];

// Check if user is a patient or has patient access
include __DIR__ . '/../shared/session_handler.php';
requireRole(['patient', 'admin']);

// If admin, they can view as patient for testing
$is_admin_view = ($_SESSION['role'] === 'admin' && isset($_GET['patient_id']));
$current_patient_id = $is_admin_view ? $_GET['patient_id'] : $_SESSION['patient_id'];

if (!$current_patient_id) {
    header('Location: ../login.php');
    exit();
}

ob_start();
?>

<style>
    .portal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0.5rem;
    }
    
    .dashboard-card {
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-radius: 0.75rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2);
    }
    
    .stat-icon {
        font-size: 3rem;
        opacity: 0.8;
    }
    
    .quick-action-card {
        background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
        color: white;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .quick-action-card:hover {
        transform: scale(1.05);
        color: white;
    }
    
    .appointment-card {
        border-left: 4px solid #28a745;
        background: #f8f9fa;
        margin-bottom: 1rem;
    }
    
    .appointment-card.upcoming {
        border-left-color: #007bff;
        background: #e7f3ff;
    }
    
    .appointment-card.pending {
        border-left-color: #ffc107;
        background: #fff8e1;
    }
    
    .message-preview {
        border-left: 3px solid #6c757d;
        padding: 1rem;
        margin-bottom: 1rem;
        background: #f8f9fa;
        border-radius: 0 0.5rem 0.5rem 0;
    }
    
    .message-preview.unread {
        border-left-color: #dc3545;
        background: #fff5f5;
        font-weight: 600;
    }
    
    .health-goal-progress {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .health-goal-progress .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        transition: width 0.3s ease;
    }
    
    .portal-nav-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 2rem;
    }
    
    .portal-nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 1rem 1.5rem;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }
    
    .portal-nav-tabs .nav-link:hover {
        color: #495057;
        border-bottom-color: #dee2e6;
    }
    
    .portal-nav-tabs .nav-link.active {
        color: #007bff;
        border-bottom-color: #007bff;
        background: none;
    }
    
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .btn-portal-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-portal-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }
</style>

<!-- Patient Portal Header -->
<div class="portal-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-1">
                    <i class="bx bx-user-circle me-2"></i>
                    Welcome to Your Patient Portal
                </h1>
                <p class="mb-0 opacity-75" id="patient-welcome-message">
                    Manage your health information and stay connected with your care team
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex align-items-center justify-content-end">
                    <div class="me-3">
                        <small class="opacity-75">Last Login:</small><br>
                        <span id="last-login-time">Loading...</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bx bx-user me-1"></i>Account
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="showProfileSettings()">
                                <i class="bx bx-cog me-2"></i>Profile Settings
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="showPreferences()">
                                <i class="bx bx-slider me-2"></i>Preferences
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="bx bx-log-out me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs portal-nav-tabs" id="portalTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
            <i class="bx bx-home me-2"></i>Dashboard
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab">
            <i class="bx bx-calendar me-2"></i>Appointments
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button" role="tab">
            <i class="bx bx-file-blank me-2"></i>Medical Records
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="lab-results-tab" data-bs-toggle="tab" data-bs-target="#lab-results" type="button" role="tab">
            <i class="bx bx-test-tube me-2"></i>Lab Results
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button" role="tab">
            <i class="bx bx-wallet me-2"></i>Billing
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages" type="button" role="tab">
            <i class="bx bx-message me-2"></i>Messages
            <span class="badge bg-danger ms-1" id="unread-messages-count" style="display: none;">0</span>
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="portalTabContent">
    
    <!-- Dashboard Tab -->
    <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
        
        <!-- Quick Stats Row -->
        <div class="row mb-4" id="dashboard-stats">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="bx bx-calendar-check text-primary stat-icon"></i>
                        <h3 class="mt-3 mb-1" id="upcoming-appointments-count">-</h3>
                        <p class="text-muted mb-0">Upcoming Appointments</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="bx bx-message-dots text-info stat-icon"></i>
                        <h3 class="mt-3 mb-1" id="unread-messages-stat">-</h3>
                        <p class="text-muted mb-0">Unread Messages</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="bx bx-test-tube text-success stat-icon"></i>
                        <h3 class="mt-3 mb-1" id="recent-lab-results-count">-</h3>
                        <p class="text-muted mb-0">Recent Lab Results</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="bx bx-wallet text-warning stat-icon"></i>
                        <h3 class="mt-3 mb-1" id="outstanding-bills-amount">-</h3>
                        <p class="text-muted mb-0">Outstanding Bills</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3">Quick Actions</h5>
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card quick-action-card" onclick="requestAppointment()">
                            <div class="card-body text-center py-4">
                                <i class="bx bx-plus-circle display-4 mb-3"></i>
                                <h6>Request Appointment</h6>
                                <small>Schedule a new visit</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card quick-action-card" onclick="sendMessage()">
                            <div class="card-body text-center py-4">
                                <i class="bx bx-envelope display-4 mb-3"></i>
                                <h6>Send Message</h6>
                                <small>Contact your provider</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card quick-action-card" onclick="viewRecords()">
                            <div class="card-body text-center py-4">
                                <i class="bx bx-file-blank display-4 mb-3"></i>
                                <h6>View Records</h6>
                                <small>Access medical history</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card quick-action-card" onclick="payBills()">
                            <div class="card-body text-center py-4">
                                <i class="bx bx-credit-card display-4 mb-3"></i>
                                <h6>Pay Bills</h6>
                                <small>Make a payment</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="row">
            <!-- Upcoming Appointments -->
            <div class="col-lg-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Upcoming Appointments</h6>
                        <a href="#" class="btn btn-sm btn-outline-primary" onclick="showTab('appointments-tab')">View All</a>
                    </div>
                    <div class="card-body">
                        <div id="upcoming-appointments-list">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Loading appointments...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="col-lg-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-message me-2"></i>Recent Messages</h6>
                        <a href="#" class="btn btn-sm btn-outline-primary" onclick="showTab('messages-tab')">View All</a>
                    </div>
                    <div class="card-body">
                        <div id="recent-messages-list">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Loading messages...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Health Goals -->
            <div class="col-lg-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-target-lock me-2"></i>Health Goals</h6>
                        <button class="btn btn-sm btn-outline-success" onclick="addHealthGoal()">Add Goal</button>
                    </div>
                    <div class="card-body">
                        <div id="health-goals-list">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Loading health goals...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Lab Results -->
            <div class="col-lg-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-test-tube me-2"></i>Recent Lab Results</h6>
                        <a href="#" class="btn btn-sm btn-outline-primary" onclick="showTab('lab-results-tab')">View All</a>
                    </div>
                    <div class="card-body">
                        <div id="recent-lab-results-list">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Loading lab results...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Other tabs content will be loaded dynamically -->
    <div class="tab-pane fade" id="appointments" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bx bx-calendar me-2"></i>My Appointments</h4>
            <button class="btn btn-portal-primary" onclick="requestAppointment()">
                <i class="bx bx-plus me-2"></i>Request New Appointment
            </button>
        </div>
        <div id="appointments-content">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading appointments...</p>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="records" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bx bx-file-blank me-2"></i>Medical Records</h4>
        </div>
        <div id="records-content">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading medical records...</p>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="lab-results" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bx bx-test-tube me-2"></i>Lab Results</h4>
        </div>
        <div id="lab-results-content">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading lab results...</p>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="billing" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bx bx-wallet me-2"></i>Billing & Payments</h4>
        </div>
        <div id="billing-content">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading billing information...</p>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="messages" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bx bx-message me-2"></i>Messages</h4>
            <button class="btn btn-portal-primary" onclick="composeMessage()">
                <i class="bx bx-edit me-2"></i>Compose Message
            </button>
        </div>
        <div id="messages-content">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading messages...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modals will be dynamically loaded here -->
<div id="modal-container"></div>

<script>
// Pass patient ID to JavaScript
window.currentPatientId = <?php echo json_encode($current_patient_id); ?>;
window.isAdminView = <?php echo json_encode($is_admin_view); ?>;
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
