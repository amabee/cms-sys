/**
 * System Settings Management JavaScript
 * Handles all client-side functionality for system configuration
 */

$(document).ready(function() {
    let currentSettings = {};
    let hasUnsavedChanges = false;

    // Initialize the page
    initializeSystemSettings();

    /**
     * Initialize system settings interface
     */
    function initializeSystemSettings() {
        // Set up event listeners
        setupEventListeners();
        
        // Load first category by default
        const firstCategory = $('.settings-category').first();
        if (firstCategory.length) {
            firstCategory.click();
        }
    }

    /**
     * Set up all event listeners
     */
    function setupEventListeners() {
        // Category navigation
        $(document).on('click', '.settings-category', function(e) {
            e.preventDefault();
            const category = $(this).data('category');
            loadSettingsCategory(category);
            
            // Update active state
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        });

        // Management tabs
        $('#backupManagementTab').click(function(e) {
            e.preventDefault();
            loadBackupManagement();
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        });

        $('#maintenanceTab').click(function(e) {
            e.preventDefault();
            loadMaintenanceManager();
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        });

        $('#systemActivityTab').click(function(e) {
            e.preventDefault();
            loadSystemActivity();
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        });

        $('#emailTemplatesTab').click(function(e) {
            e.preventDefault();
            loadEmailTemplates();
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        });

        // Save settings
        $('#saveAllSettings').click(function() {
            saveAllSettings();
        });

        // Test email configuration
        $('#testEmailConfig').click(function() {
            $('#emailTestModal').modal('show');
        });

        // Quick actions
        $('#createBackupBtn').click(function() {
            $('#backupModal').modal('show');
        });

        $('#clearLogsBtn').click(function() {
            clearOldLogs();
        });

        $('#optimizeDatabaseBtn').click(function() {
            optimizeDatabase();
        });

        $('#exportSettingsBtn').click(function() {
            exportSettings();
        });

        // Modal actions
        $('#startBackupBtn').click(function() {
            createBackup();
        });

        $('#sendTestEmailBtn').click(function() {
            sendTestEmail();
        });

        // Track changes
        $(document).on('input change', '.setting-input', function() {
            hasUnsavedChanges = true;
            $('#saveAllSettings').show();
        });

        // Warn about unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    /**
     * Load settings for a specific category
     */
    function loadSettingsCategory(category) {
        showLoading('Loading settings...');

        $.ajax({
            url: 'ajax/get_system_settings.php',
            method: 'GET',
            data: { category: category },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displaySettingsCategory(response.data[category]);
                    $('#testEmailConfig').toggle(category === 'email');
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to load settings', 'error');
            }
        });
    }

    /**
     * Display settings for a category
     */
    function displaySettingsCategory(categoryData) {
        if (!categoryData) return;

        let html = `
            <div class="settings-category-content">
                <h5><i class="${categoryData.icon}"></i> ${categoryData.display_name}</h5>
                <hr>
                <form id="settingsForm">
        `;

        categoryData.settings.forEach(function(setting) {
            html += generateSettingField(setting);
        });

        html += `
                </form>
            </div>
        `;

        $('#settingsContent').html(html);
        $('#settingsTitle').text(categoryData.display_name);
        $('#saveAllSettings').show();
    }

    /**
     * Generate HTML for a setting field
     */
    function generateSettingField(setting) {
        let fieldHtml = `
            <div class="form-group">
                <label for="${setting.setting_key}">${formatSettingKey(setting.setting_key)}</label>
        `;

        switch (setting.setting_type) {
            case 'boolean':
                fieldHtml += `
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input setting-input" 
                               id="${setting.setting_key}" 
                               data-key="${setting.setting_key}"
                               ${setting.setting_value === 'true' || setting.setting_value === '1' ? 'checked' : ''}>
                        <label class="custom-control-label" for="${setting.setting_key}"></label>
                    </div>
                `;
                break;

            case 'number':
                fieldHtml += `
                    <input type="number" class="form-control setting-input" 
                           id="${setting.setting_key}" 
                           data-key="${setting.setting_key}"
                           value="${setting.setting_value}">
                `;
                break;

            case 'file':
                fieldHtml += `
                    <input type="file" class="form-control-file setting-input" 
                           id="${setting.setting_key}" 
                           data-key="${setting.setting_key}">
                    <small class="form-text text-muted">Current: ${setting.setting_value || 'No file'}</small>
                `;
                break;

            default:
                fieldHtml += `
                    <input type="text" class="form-control setting-input" 
                           id="${setting.setting_key}" 
                           data-key="${setting.setting_key}"
                           value="${setting.setting_value}">
                `;
        }

        if (setting.description) {
            fieldHtml += `<small class="form-text text-muted">${setting.description}</small>`;
        }

        fieldHtml += `</div>`;
        return fieldHtml;
    }

    /**
     * Save all settings
     */
    function saveAllSettings() {
        const formData = {};
        $('.setting-input').each(function() {
            const key = $(this).data('key');
            let value;

            if ($(this).attr('type') === 'checkbox') {
                value = $(this).prop('checked') ? 'true' : 'false';
            } else {
                value = $(this).val();
            }

            formData[key] = value;
        });

        showLoading('Saving settings...');

        $.ajax({
            url: 'ajax/update_system_settings.php',
            method: 'POST',
            data: { settings: formData },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('Success', 'Settings saved successfully', 'success');
                    hasUnsavedChanges = false;
                    $('#saveAllSettings').hide();
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to save settings', 'error');
            }
        });
    }

    /**
     * Load backup management interface
     */
    function loadBackupManagement() {
        showLoading('Loading backup history...');

        $.ajax({
            url: 'ajax/get_backup_history.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayBackupManagement(response.data);
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to load backup history', 'error');
            }
        });
    }

    /**
     * Display backup management interface
     */
    function displayBackupManagement(backups) {
        let html = `
            <div class="backup-management">
                <h5><i class="fas fa-database"></i> Backup Management</h5>
                <hr>
                
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#backupModal">
                        <i class="fas fa-plus"></i> Create New Backup
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Backup Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        backups.forEach(function(backup) {
            const statusBadge = getStatusBadge(backup.status);
            html += `
                <tr>
                    <td>${backup.backup_name}</td>
                    <td><span class="badge badge-info">${backup.backup_type}</span></td>
                    <td>${backup.backup_size_formatted || 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td>${formatDateTime(backup.created_at)}</td>
                    <td>
                        ${backup.status === 'completed' ? 
                            `<button class="btn btn-sm btn-success" onclick="downloadBackup(${backup.id})">
                                <i class="fas fa-download"></i>
                            </button>` : ''}
                        <button class="btn btn-sm btn-danger" onclick="deleteBackup(${backup.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        $('#settingsContent').html(html);
        $('#settingsTitle').text('Backup Management');
        $('#saveAllSettings').hide();
        $('#testEmailConfig').hide();
    }

    /**
     * Load maintenance manager
     */
    function loadMaintenanceManager() {
        showLoading('Loading maintenance schedules...');

        $.ajax({
            url: 'ajax/get_maintenance_schedules.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayMaintenanceManager(response.data);
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to load maintenance schedules', 'error');
            }
        });
    }

    /**
     * Display maintenance manager interface
     */
    function displayMaintenanceManager(schedules) {
        let html = `
            <div class="maintenance-management">
                <h5><i class="fas fa-tools"></i> Maintenance Management</h5>
                <hr>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Task Name</th>
                                <th>Type</th>
                                <th>Schedule</th>
                                <th>Last Run</th>
                                <th>Next Run</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        schedules.forEach(function(schedule) {
            const statusBadge = schedule.is_active ? 
                '<span class="badge badge-success">Active</span>' : 
                '<span class="badge badge-secondary">Inactive</span>';

            html += `
                <tr>
                    <td>${schedule.task_name}</td>
                    <td><span class="badge badge-info">${schedule.task_type}</span></td>
                    <td>${schedule.schedule_type}</td>
                    <td>${schedule.last_run ? formatDateTime(schedule.last_run) : 'Never'}</td>
                    <td>${schedule.next_run ? formatDateTime(schedule.next_run) : 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="runMaintenanceTask(${schedule.id})">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="editMaintenanceTask(${schedule.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        $('#settingsContent').html(html);
        $('#settingsTitle').text('Maintenance Management');
        $('#saveAllSettings').hide();
        $('#testEmailConfig').hide();
    }

    /**
     * Load system activity logs
     */
    function loadSystemActivity() {
        showLoading('Loading system activity...');

        $.ajax({
            url: 'ajax/get_system_activity.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displaySystemActivity(response.data);
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to load system activity', 'error');
            }
        });
    }

    /**
     * Display system activity interface
     */
    function displaySystemActivity(activities) {
        let html = `
            <div class="system-activity">
                <h5><i class="fas fa-history"></i> System Activity</h5>
                <hr>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Entity</th>
                                <th>User</th>
                                <th>Date</th>
                                <th>Changes</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        activities.forEach(function(activity) {
            html += `
                <tr>
                    <td><span class="badge badge-primary">${activity.activity_type}</span></td>
                    <td>${activity.entity_type}: ${activity.entity_id}</td>
                    <td>${activity.username || 'System'}</td>
                    <td>${formatDateTime(activity.created_at)}</td>
                    <td>
                        ${activity.old_value && activity.new_value ? 
                            `<small>${truncateText(activity.old_value, 30)} → ${truncateText(activity.new_value, 30)}</small>` : 
                            'N/A'}
                    </td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        $('#settingsContent').html(html);
        $('#settingsTitle').text('System Activity');
        $('#saveAllSettings').hide();
        $('#testEmailConfig').hide();
    }

    /**
     * Load email templates
     */
    function loadEmailTemplates() {
        showLoading('Loading email templates...');

        $.ajax({
            url: 'ajax/get_email_templates.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayEmailTemplates(response.data);
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to load email templates', 'error');
            }
        });
    }

    /**
     * Display email templates interface
     */
    function displayEmailTemplates(templates) {
        let html = `
            <div class="email-templates">
                <h5><i class="fas fa-envelope-open-text"></i> Email Templates</h5>
                <hr>
        `;

        templates.forEach(function(template) {
            const statusBadge = template.is_active ? 
                '<span class="badge badge-success">Active</span>' : 
                '<span class="badge badge-secondary">Inactive</span>';

            html += `
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">${template.template_name}</h6>
                            <div>
                                ${statusBadge}
                                <span class="badge badge-info ml-2">${template.template_type}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>Subject:</strong> ${template.subject}</p>
                        <p><strong>Variables:</strong> ${template.variables ? template.variables.join(', ') : 'None'}</p>
                        <button class="btn btn-sm btn-primary" onclick="editEmailTemplate(${template.id})">
                            <i class="fas fa-edit"></i> Edit Template
                        </button>
                    </div>
                </div>
            `;
        });

        html += `</div>`;

        $('#settingsContent').html(html);
        $('#settingsTitle').text('Email Templates');
        $('#saveAllSettings').hide();
        $('#testEmailConfig').hide();
    }

    /**
     * Create system backup
     */
    function createBackup() {
        const backupType = $('#backupType').val();
        
        showLoading('Creating backup...');
        $('#backupModal').modal('hide');

        $.ajax({
            url: 'ajax/create_backup.php',
            method: 'POST',
            data: { 
                backup_type: backupType,
                compress: $('#compressBackup').prop('checked'),
                encrypt: $('#encryptBackup').prop('checked')
            },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('Success', 'Backup created successfully', 'success');
                    // Reload backup management if currently viewing
                    if ($('#settingsTitle').text() === 'Backup Management') {
                        loadBackupManagement();
                    }
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to create backup', 'error');
            }
        });
    }

    /**
     * Send test email
     */
    function sendTestEmail() {
        const email = $('#testEmailAddress').val();
        
        if (!email) {
            showAlert('Warning', 'Please enter an email address', 'warning');
            return;
        }

        showLoading('Sending test email...');
        $('#emailTestModal').modal('hide');

        $.ajax({
            url: 'ajax/test_email_config.php',
            method: 'POST',
            data: { email: email },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('Success', response.message, 'success');
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to send test email', 'error');
            }
        });
    }

    /**
     * Clear old logs
     */
    function clearOldLogs() {
        if (!confirm('Are you sure you want to clear old log files?')) {
            return;
        }

        showLoading('Clearing logs...');

        $.ajax({
            url: 'ajax/clear_old_logs.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('Success', 'Old logs cleared successfully', 'success');
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to clear logs', 'error');
            }
        });
    }

    /**
     * Optimize database
     */
    function optimizeDatabase() {
        if (!confirm('Are you sure you want to optimize the database? This may take some time.')) {
            return;
        }

        showLoading('Optimizing database...');

        $.ajax({
            url: 'ajax/optimize_database.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('Success', 'Database optimized successfully', 'success');
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to optimize database', 'error');
            }
        });
    }

    /**
     * Export settings
     */
    function exportSettings() {
        window.open('ajax/export_settings.php', '_blank');
    }

    // Utility Functions
    
    /**
     * Format setting key for display
     */
    function formatSettingKey(key) {
        return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Get status badge HTML
     */
    function getStatusBadge(status) {
        const badges = {
            'completed': '<span class="badge badge-success">Completed</span>',
            'failed': '<span class="badge badge-danger">Failed</span>',
            'in_progress': '<span class="badge badge-primary">In Progress</span>',
            'pending': '<span class="badge badge-warning">Pending</span>'
        };
        return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
    }

    /**
     * Format date time for display
     */
    function formatDateTime(datetime) {
        return new Date(datetime).toLocaleString();
    }

    /**
     * Truncate text with ellipsis
     */
    function truncateText(text, length) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    /**
     * Show loading modal
     */
    function showLoading(message = 'Loading...') {
        $('#loadingMessage').text(message);
        $('#loadingModal').modal('show');
    }

    /**
     * Hide loading modal
     */
    function hideLoading() {
        $('#loadingModal').modal('hide');
    }

    /**
     * Show alert message
     */
    function showAlert(title, message, type = 'info') {
        // Use Toast notification for better UX
        const toast = $(`
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
                <div class="toast-header">
                    <strong class="mr-auto text-${type === 'error' ? 'danger' : type}">${title}</strong>
                    <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        `);

        // Add toast container if it doesn't exist
        if (!$('.toast-container').length) {
            $('body').append('<div class="toast-container position-fixed top-0 right-0 p-3" style="z-index: 1060;"></div>');
        }

        $('.toast-container').append(toast);
        toast.toast('show');
    }

    // Global functions for onclick handlers
    window.downloadBackup = function(backupId) {
        window.open(`ajax/download_backup.php?id=${backupId}`, '_blank');
    };

    window.deleteBackup = function(backupId) {
        if (!confirm('Are you sure you want to delete this backup?')) {
            return;
        }

        $.ajax({
            url: 'ajax/delete_backup.php',
            method: 'POST',
            data: { backup_id: backupId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('Success', 'Backup deleted successfully', 'success');
                    loadBackupManagement();
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                showAlert('Error', 'Failed to delete backup', 'error');
            }
        });
    };

    window.runMaintenanceTask = function(scheduleId) {
        if (!confirm('Are you sure you want to run this maintenance task now?')) {
            return;
        }

        showLoading('Running maintenance task...');

        $.ajax({
            url: 'ajax/run_maintenance_task.php',
            method: 'POST',
            data: { schedule_id: scheduleId },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('Success', 'Maintenance task completed successfully', 'success');
                    loadMaintenanceManager();
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to run maintenance task', 'error');
            }
        });
    };

    window.editMaintenanceTask = function(scheduleId) {
        // Implementation for editing maintenance tasks
        showAlert('Info', 'Edit maintenance task functionality coming soon', 'info');
    };

    window.editEmailTemplate = function(templateId) {
        // Implementation for editing email templates
        showAlert('Info', 'Edit email template functionality coming soon', 'info');
    };
});
