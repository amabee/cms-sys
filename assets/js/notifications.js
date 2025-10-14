// Notifications Management JavaScript
let currentPage = 1;
let currentFilters = {};
let currentTab = 'recent-notifications';

// Initialize notifications page
$(document).ready(function() {
    loadNotificationStatistics();
    loadRecentNotifications();
    loadPatientsList();
    loadUsersList();
    
    // Tab switching event handlers - use more generic selector
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        currentTab = target ? target.substring(1) : '';
        
        console.log('Tab switched to:', currentTab); // Debug log
        
        switch(currentTab) {
            case 'recent-notifications':
                loadRecentNotifications();
                break;
            case 'pending-notifications':
                loadPendingNotifications();
                break;
            case 'failed-notifications':
                loadFailedNotifications();
                break;
            case 'templates':
                console.log('Loading templates...'); // Debug log
                loadTemplates();
                break;
        }
    });
    
    // Recipient type change handler
    $('#recipient-type').change(function() {
        const type = $(this).val();
        const select = $('#recipient-select');
        
        select.empty().append('<option value="">Select Recipient</option>');
        
        if (type === 'patient') {
            select.prop('disabled', false);
            loadPatientsList();
        } else if (type === 'user') {
            select.prop('disabled', false);
            loadUsersList();
        } else if (type === 'all_patients' || type === 'all_users') {
            select.prop('disabled', true);
        } else {
            select.prop('disabled', true);
        }
    });
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        if (currentTab === 'recent-notifications') {
            loadNotificationStatistics();
            loadRecentNotifications(true); // Silent refresh
        }
    }, 30000);
});

// Load notification statistics
function loadNotificationStatistics() {
    $.ajax({
        url: '../ajax/get_notification_statistics.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#total-notifications').text(stats.total || 0);
                $('#unread-notifications').text(stats.unread || 0);
                $('#critical-notifications').text(stats.critical || 0);
                $('#failed-notifications').text(stats.failed || 0);
            }
        },
        error: function() {
            console.error('Failed to load notification statistics');
        }
    });
}

// Load recent notifications
function loadRecentNotifications(silent = false) {
    if (!silent) {
        $('#recent-loading').show();
        $('#recent-notifications-content').hide();
    }
    
    const params = {
        page: currentPage,
        ...currentFilters
    };
    
    $.ajax({
        url: '../ajax/get_notifications.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayNotifications(response.data.notifications, '#notifications-list');
                updatePagination(response.data.pagination, '#notifications-pagination');
                updateInfoText(response.data.pagination, '#notifications-info');
            } else {
                showAlert('Error loading notifications: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to load notifications', 'danger');
        },
        complete: function() {
            $('#recent-loading').hide();
            $('#recent-notifications-content').show();
        }
    });
}

// Load pending notifications
function loadPendingNotifications() {
    $('#pending-loading').show();
    $('#pending-notifications-content').hide();
    
    $.ajax({
        url: '../ajax/get_notifications.php',
        type: 'GET',
        data: { status: 'pending', ...currentFilters },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayNotifications(response.data.notifications, '#pending-notifications-list');
            } else {
                showAlert('Error loading pending notifications: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to load pending notifications', 'danger');
        },
        complete: function() {
            $('#pending-loading').hide();
            $('#pending-notifications-content').show();
        }
    });
}

// Load failed notifications
function loadFailedNotifications() {
    $('#failed-loading').show();
    $('#failed-notifications-content').hide();
    
    $.ajax({
        url: '../ajax/get_notifications.php',
        type: 'GET',
        data: { status: 'failed', ...currentFilters },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayNotifications(response.data.notifications, '#failed-notifications-list');
            } else {
                showAlert('Error loading failed notifications: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to load failed notifications', 'danger');
        },
        complete: function() {
            $('#failed-loading').hide();
            $('#failed-notifications-content').show();
        }
    });
}

// Display notifications in the specified container
function displayNotifications(notifications, container) {
    const $container = $(container);
    $container.empty();
    
    if (!notifications || notifications.length === 0) {
        $container.html(`
            <div class="text-center py-5 text-muted">
                <i class="bx bx-bell-off display-1"></i>
                <h4>No notifications found</h4>
                <p>There are no notifications matching your criteria.</p>
            </div>
        `);
        return;
    }
    
    notifications.forEach(notification => {
        const priorityClass = getPriorityClass(notification.priority);
        const statusBadge = getStatusBadge(notification.status);
        const typeBadge = getTypeBadge(notification.type);
        const unreadClass = notification.is_read ? '' : 'unread';
        
        const card = `
            <div class="card notification-card ${priorityClass} ${unreadClass} mb-3" data-notification-id="${notification.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="card-title mb-1">${escapeHtml(notification.title)}</h5>
                            <div class="d-flex gap-2 mb-2">
                                ${typeBadge}
                                ${statusBadge}
                                ${getPriorityBadge(notification.priority)}
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="notification-time">${formatDateTime(notification.created_at)}</small>
                            ${!notification.is_read ? '<div class="badge bg-danger">New</div>' : ''}
                        </div>
                    </div>
                    
                    <p class="card-text">${escapeHtml(notification.message)}</p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                <i class="bx bx-user me-1"></i>
                                To: ${notification.recipient_name || 'System'}
                            </small>
                            ${notification.scheduled_for ? `
                                <small class="text-muted ms-3">
                                    <i class="bx bx-time me-1"></i>
                                    Scheduled: ${formatDateTime(notification.scheduled_for)}
                                </small>
                            ` : ''}
                        </div>
                        <div class="btn-group btn-group-sm">
                            ${!notification.is_read ? `
                                <button class="btn btn-outline-primary" onclick="markAsRead(${notification.id})" title="Mark as Read">
                                    <i class="bx bx-check"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-outline-info" onclick="viewNotificationDetails(${notification.id})" title="View Details">
                                <i class="bx bx-show"></i>
                            </button>
                            ${notification.status === 'failed' ? `
                                <button class="btn btn-outline-warning" onclick="retryNotification(${notification.id})" title="Retry">
                                    <i class="bx bx-refresh"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-outline-danger" onclick="deleteNotification(${notification.id})" title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $container.append(card);
    });
}

// Get priority CSS class
function getPriorityClass(priority) {
    switch(priority) {
        case 'critical': return 'critical';
        case 'high': return 'high';
        default: return '';
    }
}

// Get status badge HTML
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'sent': '<span class="badge bg-info">Sent</span>',
        'delivered': '<span class="badge bg-success">Delivered</span>',
        'read': '<span class="badge bg-primary">Read</span>',
        'failed': '<span class="badge bg-danger">Failed</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

// Get type badge HTML
function getTypeBadge(type) {
    const badges = {
        'appointment_reminder': '<span class="badge bg-info notification-type-badge">Appointment</span>',
        'lab_result_ready': '<span class="badge bg-success notification-type-badge">Lab Results</span>',
        'lab_critical_value': '<span class="badge bg-danger notification-type-badge">Critical Lab</span>',
        'bill_generated': '<span class="badge bg-primary notification-type-badge">Billing</span>',
        'bill_overdue': '<span class="badge bg-warning notification-type-badge">Overdue Bill</span>',
        'system_alert': '<span class="badge bg-dark notification-type-badge">System Alert</span>'
    };
    return badges[type] || '<span class="badge bg-secondary notification-type-badge">General</span>';
}

// Get priority badge HTML
function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge bg-light text-dark notification-type-badge">Low</span>',
        'normal': '<span class="badge bg-secondary notification-type-badge">Normal</span>',
        'high': '<span class="badge bg-warning notification-type-badge">High</span>',
        'critical': '<span class="badge bg-danger notification-type-badge">Critical</span>'
    };
    return badges[priority] || '';
}

// Apply filters
function applyFilters() {
    currentFilters = {
        type: $('#filter-type').val(),
        priority: $('#filter-priority').val(),
        status: $('#filter-status').val()
    };
    
    currentPage = 1;
    
    switch(currentTab) {
        case 'recent-notifications':
            loadRecentNotifications();
            break;
        case 'pending-notifications':
            loadPendingNotifications();
            break;
        case 'failed-notifications':
            loadFailedNotifications();
            break;
    }
}

// Create new notification
function createNotification() {
    const form = document.getElementById('createNotificationForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const methods = [];
    if ($('#method-email').is(':checked')) methods.push('email');
    if ($('#method-sms').is(':checked')) methods.push('sms');
    if ($('#method-inapp').is(':checked')) methods.push('in_app');
    
    if (methods.length === 0) {
        showAlert('Please select at least one delivery method', 'warning');
        return;
    }
    
    const data = {
        type: $('#notification-type').val(),
        priority: $('#notification-priority').val(),
        recipient_type: $('#recipient-type').val(),
        recipient_id: $('#recipient-select').val(),
        title: $('#notification-title').val(),
        message: $('#notification-message').val(),
        methods: methods,
        scheduled_for: $('#scheduled-for').val() || null
    };
    
    $.ajax({
        url: '../ajax/create_notification.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Notification created successfully', 'success');
                $('#createNotificationModal').modal('hide');
                form.reset();
                loadNotificationStatistics();
                if (currentTab === 'recent-notifications') {
                    loadRecentNotifications();
                }
            } else {
                showAlert('Error creating notification: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to create notification', 'danger');
        }
    });
}

// Process notifications queue
function processNotifications() {
    showAlert('Processing notification queue...', 'info');
    
    $.ajax({
        url: '../ajax/process_notifications.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert(`Queue processed successfully. ${response.data.processed} notifications processed.`, 'success');
                loadNotificationStatistics();
                if (currentTab === 'recent-notifications') {
                    loadRecentNotifications();
                }
            } else {
                showAlert('Error processing queue: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to process notification queue', 'danger');
        }
    });
}

// Mark notification as read
function markAsRead(notificationId) {
    $.ajax({
        url: '../ajax/mark_notification_read.php',
        type: 'POST',
        data: { notification_id: notificationId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $(`[data-notification-id="${notificationId}"]`).removeClass('unread');
                $(`[data-notification-id="${notificationId}"] .badge:contains("New")`).remove();
                loadNotificationStatistics();
            } else {
                showAlert('Error marking as read: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to mark notification as read', 'danger');
        }
    });
}

// View notification details
function viewNotificationDetails(notificationId) {
    // Implementation for viewing notification details
    // This would open a modal with full notification information
    showAlert('View details functionality would be implemented here', 'info');
}

// Retry failed notification
function retryNotification(notificationId) {
    if (!confirm('Are you sure you want to retry this notification?')) {
        return;
    }
    
    $.ajax({
        url: '../ajax/retry_notification.php',
        type: 'POST',
        data: { notification_id: notificationId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Notification queued for retry', 'success');
                if (currentTab === 'failed-notifications') {
                    loadFailedNotifications();
                }
                loadNotificationStatistics();
            } else {
                showAlert('Error retrying notification: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to retry notification', 'danger');
        }
    });
}

// Delete notification
function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: '../ajax/delete_notification.php',
        type: 'POST',
        data: { notification_id: notificationId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Notification deleted successfully', 'success');
                $(`[data-notification-id="${notificationId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
                loadNotificationStatistics();
            } else {
                showAlert('Error deleting notification: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to delete notification', 'danger');
        }
    });
}

// Load templates
function loadTemplates() {
    $('#templates-loading').show();
    $('#templates-content').hide();
    
    $.ajax({
        url: '../ajax/get_notification_templates.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayTemplates(response.data);
            } else {
                showAlert('Error loading templates: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to load templates', 'danger');
        },
        complete: function() {
            $('#templates-loading').hide();
            $('#templates-content').show();
        }
    });
}

// Display templates
function displayTemplates(templates) {
    console.log('displayTemplates called with:', templates); // Debug log
    const tbody = $('#templates-table tbody');
    console.log('tbody element:', tbody.length); // Debug log
    tbody.empty();
    
    if (!templates || templates.length === 0) {
        tbody.html('<tr><td colspan="5" class="text-center text-muted">No templates found</td></tr>');
        return;
    }
    
    console.log('Processing', templates.length, 'templates'); // Debug log
    templates.forEach(template => {
        const row = `
            <tr>
                <td>${escapeHtml(template.template_name)}</td>
                <td>${getTypeBadge(template.notification_type)}</td>
                <td><span class="badge bg-secondary">${template.delivery_method.toUpperCase()}</span></td>
                <td>${template.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editTemplate(${template.id})" title="Edit">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="previewTemplate(${template.id})" title="Preview">
                            <i class="bx bx-show"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteTemplate(${template.id})" title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Save template
function saveTemplate() {
    const form = document.getElementById('templateForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        name: $('#template-name').val(),
        code: $('#template-code').val(),
        type: $('#template-type').val(),
        method: $('#template-method').val(),
        subject_template: $('#template-subject').val(),
        message_template: $('#template-message').val()
    };
    
    $.ajax({
        url: '../ajax/save_notification_template.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Template saved successfully', 'success');
                $('#templateModal').modal('hide');
                form.reset();
                if (currentTab === 'templates') {
                    loadTemplates();
                }
            } else {
                showAlert('Error saving template: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Failed to save template', 'danger');
        }
    });
}

// Load patients list for recipient selection
function loadPatientsList() {
    $.ajax({
        url: '../ajax/get_patients.php',
        type: 'GET',
        data: { limit: 1000, active_only: true },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#recipient-select');
                if ($('#recipient-type').val() === 'patient') {
                    select.empty().append('<option value="">Select Patient</option>');
                    response.data.patients.forEach(patient => {
                        select.append(`<option value="${patient.id}">${patient.first_name} ${patient.last_name} (${patient.patient_id})</option>`);
                    });
                }
            }
        }
    });
}

// Load users list for recipient selection
function loadUsersList() {
    $.ajax({
        url: '../ajax/get_users.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#recipient-select');
                if ($('#recipient-type').val() === 'user') {
                    select.empty().append('<option value="">Select User</option>');
                    response.data.forEach(user => {
                        select.append(`<option value="${user.id}">${user.first_name} ${user.last_name} (${user.username})</option>`);
                    });
                }
            }
        }
    });
}

// Pagination helper
function updatePagination(pagination, container) {
    const $container = $(container);
    $container.empty();
    
    if (pagination.total_pages <= 1) return;
    
    // Previous button
    const prevClass = pagination.current_page === 1 ? 'disabled' : '';
    $container.append(`
        <li class="page-item ${prevClass}">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">Previous</a>
        </li>
    `);
    
    // Page numbers
    for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
        const activeClass = i === pagination.current_page ? 'active' : '';
        $container.append(`
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
        `);
    }
    
    // Next button
    const nextClass = pagination.current_page === pagination.total_pages ? 'disabled' : '';
    $container.append(`
        <li class="page-item ${nextClass}">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">Next</a>
        </li>
    `);
}

// Update info text
function updateInfoText(pagination, container) {
    const start = (pagination.current_page - 1) * pagination.per_page + 1;
    const end = Math.min(start + pagination.per_page - 1, pagination.total_records);
    
    $(container).text(`Showing ${start}-${end} of ${pagination.total_records} notifications`);
}

// Change page
function changePage(page) {
    if (page < 1) return;
    
    currentPage = page;
    
    switch(currentTab) {
        case 'recent-notifications':
            loadRecentNotifications();
            break;
        case 'pending-notifications':
            loadPendingNotifications();
            break;
        case 'failed-notifications':
            loadFailedNotifications();
            break;
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showAlert(message, type) {
    // Create toast container if it doesn't exist
    if ($('#toast-container').length === 0) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
    }
    
    // Map alert types to toast styles
    const toastTypes = {
        'success': { icon: 'bx-check-circle', bgClass: 'bg-success' },
        'danger': { icon: 'bx-error-circle', bgClass: 'bg-danger' },
        'warning': { icon: 'bx-error', bgClass: 'bg-warning' },
        'info': { icon: 'bx-info-circle', bgClass: 'bg-info' }
    };
    
    const toastStyle = toastTypes[type] || toastTypes['info'];
    const toastId = 'toast-' + Date.now();
    
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white ${toastStyle.bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="bx ${toastStyle.icon} me-2 fs-5"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    // Add toast to container
    $('#toast-container').append(toastHtml);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        $(this).remove();
    });
}
