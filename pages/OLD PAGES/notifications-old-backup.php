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

<style>
    .notification-card {
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
    }
    .notification-card.unread {
        background: #f8f9fa;
        border-left-color: #dc3545;
    }
    .notification-card.critical {
        border-left-color: #dc3545;
        background: #ffeaa7;
    }
    .notification-card.high {
        border-left-color: #fd7e14;
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    .notification-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .notification-time {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .loading-spinner {
        display: none;
        text-align: center;
        padding: 2rem;
    }
    .btn-send-notification {
        background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
        border: none;
        color: white;
    }
    .btn-send-notification:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        color: white;
    }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bx bx-bell me-2"></i>Notifications Management
    </h1>
    <div class="d-flex gap-2">
        <button class="btn btn-send-notification" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
            <i class="bx bx-plus me-2"></i>Send Notification
        </button>
        <button class="btn btn-secondary" onclick="processNotifications()">
            <i class="bx bx-cog me-2"></i>Process Queue
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4" id="notification-stats">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-bell-ring display-4 mb-3"></i>
                <h3 id="total-notifications">-</h3>
                <p class="mb-0">Total Notifications</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-bell-off display-4 mb-3"></i>
                <h3 id="unread-notifications">-</h3>
                <p class="mb-0">Unread</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-error display-4 mb-3"></i>
                <h3 id="critical-notifications">-</h3>
                <p class="mb-0">Critical</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="bx bx-x-circle display-4 mb-3"></i>
                <h3 id="failed-notifications">-</h3>
                <p class="mb-0">Failed</p>
            </div>
        </div>
    </div>
</div>

<!-- Tabs for different notification views -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="notificationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="recent-notifications-tab" data-bs-toggle="tab" data-bs-target="#recent-notifications" type="button" role="tab">
                    <i class="bx bx-time me-2"></i>Recent Notifications
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pending-notifications-tab" data-bs-toggle="tab" data-bs-target="#pending-notifications" type="button" role="tab">
                    <i class="bx bx-loader me-2"></i>Pending
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="failed-notifications-tab" data-bs-toggle="tab" data-bs-target="#failed-notifications" type="button" role="tab">
                    <i class="bx bx-error-circle me-2"></i>Failed
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                    <i class="bx bx-file-blank me-2"></i>Templates
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="bx bx-filter me-2"></i>Filters</h5>
            <div class="row">
                <div class="col-md-3">
                    <label for="filter-type" class="form-label">Type</label>
                    <select class="form-control" id="filter-type">
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
                    <label for="filter-priority" class="form-label">Priority</label>
                    <select class="form-control" id="filter-priority">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-status" class="form-label">Status</label>
                    <select class="form-control" id="filter-status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="sent">Sent</option>
                        <option value="delivered">Delivered</option>
                        <option value="read">Read</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="applyFilters()">
                        <i class="bx bx-search me-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="tab-content" id="notificationTabContent">
            <!-- Recent Notifications Tab -->
            <div class="tab-pane fade show active" id="recent-notifications" role="tabpanel">
                <div class="loading-spinner" id="recent-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading notifications...</p>
                </div>

                <div id="recent-notifications-content">
                    <div id="notifications-list"></div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <span id="notifications-info" class="text-muted"></span>
                        </div>
                        <nav>
                            <ul class="pagination" id="notifications-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Pending Notifications Tab -->
            <div class="tab-pane fade" id="pending-notifications" role="tabpanel">
                <div class="loading-spinner" id="pending-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading pending notifications...</p>
                </div>

                <div id="pending-notifications-content">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        Pending notifications are those that are scheduled to be sent or are in the processing queue.
                    </div>
                    <div id="pending-notifications-list"></div>
                </div>
            </div>

            <!-- Failed Notifications Tab -->
            <div class="tab-pane fade" id="failed-notifications" role="tabpanel">
                <div class="loading-spinner" id="failed-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading failed notifications...</p>
                </div>

                <div id="failed-notifications-content">
                    <div class="alert alert-warning">
                        <i class="bx bx-error me-2"></i>
                        Failed notifications may need manual review and reprocessing.
                    </div>
                    <div id="failed-notifications-list"></div>
                </div>
            </div>

            <!-- Templates Tab -->
            <div class="tab-pane fade" id="templates" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Notification Templates</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
                        <i class="bx bx-plus me-2"></i>Add Template
                    </button>
                </div>

                <div class="loading-spinner" id="templates-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading templates...</p>
                </div>

                <div id="templates-content">
                    <div class="table-responsive">
                        <table class="table table-striped" id="templates-table">
                            <thead>
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
                <h5 class="modal-title">Send New Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createNotificationForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="notification-type" class="form-label">Notification Type</label>
                            <select class="form-control" id="notification-type" required>
                                <option value="">Select Type</option>
                                <option value="appointment_reminder">Appointment Reminder</option>
                                <option value="lab_result_ready">Lab Results Ready</option>
                                <option value="bill_generated">Bill Generated</option>
                                <option value="system_alert">System Alert</option>
                                <option value="general_announcement">General Announcement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="notification-priority" class="form-label">Priority</label>
                            <select class="form-control" id="notification-priority" required>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="recipient-type" class="form-label">Recipient Type</label>
                            <select class="form-control" id="recipient-type" required>
                                <option value="">Select Recipient</option>
                                <option value="patient">Specific Patient</option>
                                <option value="user">Specific User</option>
                                <option value="all_patients">All Patients</option>
                                <option value="all_users">All Users</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recipient-select" class="form-label">Select Recipient</label>
                            <select class="form-control" id="recipient-select"></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notification-title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="notification-title" required>
                    </div>

                    <div class="mb-3">
                        <label for="notification-message" class="form-label">Message</label>
                        <textarea class="form-control" id="notification-message" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Delivery Methods</label>
                        <div class="form-check-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="method-email" value="email" checked>
                                <label class="form-check-label" for="method-email">Email</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="method-sms" value="sms">
                                <label class="form-check-label" for="method-sms">SMS</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="method-inapp" value="in_app" checked>
                                <label class="form-check-label" for="method-inapp">In-App</label>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createNotification()">Send Notification</button>
            </div>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="template-name" class="form-label">Template Name</label>
                            <input type="text" class="form-control" id="template-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="template-code" class="form-label">Template Code</label>
                            <input type="text" class="form-control" id="template-code" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="template-type" class="form-label">Notification Type</label>
                            <select class="form-control" id="template-type" required>
                                <option value="appointment_reminder">Appointment Reminder</option>
                                <option value="lab_result_ready">Lab Results Ready</option>
                                <option value="bill_generated">Bill Generated</option>
                                <option value="system_alert">System Alert</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="template-method" class="form-label">Delivery Method</label>
                            <select class="form-control" id="template-method" required>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                                <option value="in_app">In-App</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="template-subject" class="form-label">Subject Template (Email only)</label>
                        <input type="text" class="form-control" id="template-subject">
                        <div class="form-text">Use {variable_name} for dynamic content</div>
                    </div>

                    <div class="mb-3">
                        <label for="template-message" class="form-label">Message Template</label>
                        <textarea class="form-control" id="template-message" rows="6" required></textarea>
                        <div class="form-text">Use {variable_name} for dynamic content. Available variables: {patient_name}, {doctor_name}, {clinic_name}, {appointment_date}, etc.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">Save Template</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
