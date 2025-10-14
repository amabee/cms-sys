<?php
$page_title = 'Notifications Management';
$additional_css = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css'
];
$additional_js = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
    '../assets/js/notifications.js'
];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin', 'doctor', 'receptionist']);

ob_start();
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Notifications Management</h4>
        <p class="text-muted mb-0">Monitor and manage system notifications</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
            <i class="bx bx-plus me-2"></i>Send Notification
        </button>
        <button class="btn btn-label-secondary" onclick="processNotifications()">
            <i class="bx bx-cog me-2"></i>Process Queue
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4" id="notification-stats">
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text mb-1">Total Notifications</p>
                        <div class="d-flex align-items-center mb-1">
                            <h4 class="mb-0 me-2" id="total-notifications">-</h4>
                        </div>
                        <small class="text-muted">All time</small>
                    </div>
                    <div class="card-icon">
                        <span class="badge bg-label-primary rounded-pill p-2">
                            <i class="bx bx-bell bx-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text mb-1">Unread</p>
                        <div class="d-flex align-items-center mb-1">
                            <h4 class="mb-0 me-2" id="unread-notifications">-</h4>
                        </div>
                        <small class="text-muted">Pending attention</small>
                    </div>
                    <div class="card-icon">
                        <span class="badge bg-label-warning rounded-pill p-2">
                            <i class="bx bx-bell-off bx-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text mb-1">Critical</p>
                        <div class="d-flex align-items-center mb-1">
                            <h4 class="mb-0 me-2" id="critical-notifications">-</h4>
                        </div>
                        <small class="text-muted">High priority</small>
                    </div>
                    <div class="card-icon">
                        <span class="badge bg-label-danger rounded-pill p-2">
                            <i class="bx bx-error bx-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text mb-1">Failed</p>
                        <div class="d-flex align-items-center mb-1">
                            <h4 class="mb-0 me-2" id="failed-notifications">-</h4>
                        </div>
                        <small class="text-muted">Need review</small>
                    </div>
                    <div class="card-icon">
                        <span class="badge bg-label-secondary rounded-pill p-2">
                            <i class="bx bx-x-circle bx-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notifications Tabs -->
<div class="card">
    <div class="card-header">
        <div class="nav-align-top">
            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#recent-notifications" aria-controls="recent-notifications" aria-selected="true">
                        <i class="bx bx-time me-1"></i> Recent
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#pending-notifications" aria-controls="pending-notifications" aria-selected="false">
                        <i class="bx bx-loader me-1"></i> Pending
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#failed-notifications" aria-controls="failed-notifications" aria-selected="false">
                        <i class="bx bx-error-circle me-1"></i> Failed
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#templates" aria-controls="templates" aria-selected="false">
                        <i class="bx bx-file-blank me-1"></i> Templates
                    </button>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bx bx-filter me-2"></i>Filter Notifications
                </h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="filter-type" class="form-label small">Type</label>
                        <select class="form-select form-select-sm" id="filter-type">
                            <option value="">All Types</option>
                            <option value="appointment_reminder">Appointment Reminder</option>
                            <option value="lab_result_ready">Lab Results</option>
                            <option value="lab_critical_value">Critical Lab Values</option>
                            <option value="bill_generated">Bill Generated</option>
                            <option value="bill_overdue">Bill Overdue</option>
                            <option value="system_alert">System Alert</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-priority" class="form-label small">Priority</label>
                        <select class="form-select form-select-sm" id="filter-priority">
                            <option value="">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-status" class="form-label small">Status</label>
                        <select class="form-select form-select-sm" id="filter-status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="delivered">Delivered</option>
                            <option value="read">Read</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
                            <i class="bx bx-search me-1"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content p-0">
            <!-- Recent Notifications Tab -->
            <div class="tab-pane fade show active" id="recent-notifications" role="tabpanel">
                <div class="text-center py-5" id="recent-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading notifications...</p>
                </div>

                <div id="recent-notifications-content">
                    <div id="notifications-list"></div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <span id="notifications-info" class="text-muted small"></span>
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="notifications-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Pending Notifications Tab -->
            <div class="tab-pane fade" id="pending-notifications" role="tabpanel">
                <div class="text-center py-5" id="pending-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading pending notifications...</p>
                </div>

                <div id="pending-notifications-content">
                    <div class="alert alert-primary d-flex align-items-center" role="alert">
                        <i class="bx bx-info-circle me-2"></i>
                        <div>
                            Pending notifications are those that are scheduled to be sent or are in the processing queue.
                        </div>
                    </div>
                    <div id="pending-notifications-list"></div>
                </div>
            </div>

            <!-- Failed Notifications Tab -->
            <div class="tab-pane fade" id="failed-notifications" role="tabpanel">
                <div class="text-center py-5" id="failed-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading failed notifications...</p>
                </div>

                <div id="failed-notifications-content">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bx bx-error me-2"></i>
                        <div>
                            Failed notifications may need manual review and reprocessing.
                        </div>
                    </div>
                    <div id="failed-notifications-list"></div>
                </div>
            </div>

            <!-- Templates Tab -->
            <div class="tab-pane fade" id="templates" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Notification Templates</h6>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
                        <i class="bx bx-plus me-1"></i>Add Template
                    </button>
                </div>

                <div class="text-center py-5" id="templates-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading templates...</p>
                </div>

                <div id="templates-content">
                    <div class="table-responsive">
                        <table class="table table-hover" id="templates-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Template Name</th>
                                    <th>Type</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Actions</th>
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

<!-- Create Notification Modal -->
<div class="modal fade" id="createNotificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-bell me-2"></i>Send New Notification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createNotificationForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="notification-type" required>
                                    <option value="">Select Type</option>
                                    <option value="appointment_reminder">Appointment Reminder</option>
                                    <option value="lab_result_ready">Lab Results Ready</option>
                                    <option value="bill_generated">Bill Generated</option>
                                    <option value="system_alert">System Alert</option>
                                    <option value="general_announcement">General Announcement</option>
                                </select>
                                <label for="notification-type">Notification Type *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="notification-priority" required>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <label for="notification-priority">Priority *</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="recipient-type" required>
                                    <option value="">Select Recipient</option>
                                    <option value="patient">Specific Patient</option>
                                    <option value="user">Specific User</option>
                                    <option value="all_patients">All Patients</option>
                                    <option value="all_users">All Users</option>
                                </select>
                                <label for="recipient-type">Recipient Type *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="recipient-select"></select>
                                <label for="recipient-select">Select Recipient</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="notification-title" placeholder="Enter title" required>
                            <label for="notification-title">Title *</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-floating">
                            <textarea class="form-control" id="notification-message" placeholder="Enter message" style="height: 120px" required></textarea>
                            <label for="notification-message">Message *</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Delivery Methods</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="method-email" value="email" checked>
                                <label class="form-check-label" for="method-email">
                                    <i class="bx bx-envelope me-1"></i>Email
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="method-sms" value="sms">
                                <label class="form-check-label" for="method-sms">
                                    <i class="bx bx-message me-1"></i>SMS
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="method-inapp" value="in_app" checked>
                                <label class="form-check-label" for="method-inapp">
                                    <i class="bx bx-bell me-1"></i>In-App
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="scheduled-for" class="form-label">Schedule For (Optional)</label>
                        <input type="datetime-local" class="form-control" id="scheduled-for">
                        <div class="form-text">Leave empty to send immediately</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createNotification()">
                    <i class="bx bx-send me-1"></i>Send Notification
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-file-blank me-2"></i>Notification Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="template-name" placeholder="Enter template name" required>
                                <label for="template-name">Template Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="template-code" placeholder="Enter template code" required>
                                <label for="template-code">Template Code *</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="template-type" required>
                                    <option value="appointment_reminder">Appointment Reminder</option>
                                    <option value="lab_result_ready">Lab Results Ready</option>
                                    <option value="bill_generated">Bill Generated</option>
                                    <option value="system_alert">System Alert</option>
                                </select>
                                <label for="template-type">Notification Type *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="template-method" required>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="in_app">In-App</option>
                                </select>
                                <label for="template-method">Delivery Method *</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="template-subject" placeholder="Enter subject">
                            <label for="template-subject">Subject Template (Email only)</label>
                        </div>
                        <div class="form-text">Use {variable_name} for dynamic content</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-floating">
                            <textarea class="form-control" id="template-message" placeholder="Enter message template" style="height: 150px" required></textarea>
                            <label for="template-message">Message Template *</label>
                        </div>
                        <div class="form-text">
                            Use {variable_name} for dynamic content. Available variables: {patient_name}, {doctor_name}, {clinic_name}, {appointment_date}, etc.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">
                    <i class="bx bx-save me-1"></i>Save Template
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Notification Details Modal -->
<div class="modal fade" id="viewNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-detail me-2"></i>Notification Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="notification-details-content">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="resend-notification-btn" onclick="resendNotification()">
                    <i class="bx bx-send me-1"></i>Resend
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom notification card styles */
.notification-card {
    border-left: 3px solid #696cff;
    transition: all 0.3s ease;
    margin-bottom: 0.75rem;
    cursor: pointer;
}

.notification-card:hover {
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.notification-card.unread {
    background-color: #f8f9fa;
    border-left-color: #ff3e1d;
}

.notification-card.critical {
    border-left-color: #ff3e1d;
    background-color: #fff5f5;
}

.notification-card.high {
    border-left-color: #ffab00;
    background-color: #fffbf0;
}

.notification-card.low {
    border-left-color: #71dd37;
}

.notification-type-badge {
    font-size: 0.75rem;
}

.notification-time {
    font-size: 0.8125rem;
    color: #a1acb8;
}

.notification-priority-icon {
    font-size: 1.25rem;
}

/* Nav pills customization */
.nav-pills .nav-link {
    background-color: transparent;
    border-radius: 0.375rem;
    color: #697a8d;
}

.nav-pills .nav-link.active {
    background-color: #696cff;
    color: #fff;
}

.nav-pills .nav-link:hover:not(.active) {
    background-color: rgba(105, 108, 255, 0.1);
    color: #696cff;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
