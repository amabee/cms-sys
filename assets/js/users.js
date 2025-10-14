// User Management JavaScript
let usersDataTable = null;
let currentFilters = {};

// Initialize page when document is ready
$(document).ready(function() {
    initializeUserManagement();
    loadUserStatistics();
    
    // Filter event handlers
    $('.filter-role').on('click', function(e) {
        e.preventDefault();
        const role = $(this).data('role');
        currentFilters.role = role;
        if (usersDataTable) {
            usersDataTable.ajax.reload();
        }
        updateFilterDisplay('role', role, $(this).text());
    });
    
    $('.filter-status').on('click', function(e) {
        e.preventDefault();
        const status = $(this).data('status');
        currentFilters.is_active = status;
        if (usersDataTable) {
            usersDataTable.ajax.reload();
        }
        updateFilterDisplay('status', status, $(this).text());
    });
    
    // Global search handler
    $('#globalSearch').on('keyup', debounce(function() {
        const search = $(this).val();
        currentFilters.search = search;
        if (usersDataTable) {
            usersDataTable.ajax.reload();
        }
    }, 300));
    
    // Modal event handlers
    $('#editUserModal').on('hidden.bs.modal', function() {
        resetForm('editUserForm');
        $('#edit-user-id').val('');
    });
});

// Initialize user management DataTable
function initializeUserManagement() {
    usersDataTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../ajax/get_users_new.php',
            type: 'GET',
            data: function(d) {
                // Add custom filters
                d.role = currentFilters.role || '';
                d.is_active = currentFilters.is_active || '';
                d.search = currentFilters.search || '';
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', error, thrown);
                showToast('Failed to load users data', 'error');
            }
        },
        columns: [
            { 
                data: 'id', 
                title: '#',
                width: '60px',
                className: 'text-center',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<span class="fw-semibold text-primary">#${data}</span>`;
                    }
                    return data;
                }
            },
            { 
                data: null,
                title: 'User',
                render: function(data, type, row) {
                    if (type === 'display') {
                        const name = row.full_name || 'Unknown User';
                        const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                        const roleColors = {
                            'admin': { bg: 'danger', text: 'white' },
                            'doctor': { bg: 'success', text: 'white' }, 
                            'nurse': { bg: 'info', text: 'white' },
                            'receptionist': { bg: 'secondary', text: 'white' }
                        };
                        const colors = roleColors[row.role] || { bg: 'light', text: 'dark' };
                        
                        return `
                            <div class="d-flex align-items-center py-1">
                                <div class="avatar avatar-md rounded-circle bg-${colors.bg} text-${colors.text} me-3 d-flex align-items-center justify-content-center fw-bold">
                                    ${initials}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="mb-0 fw-semibold text-truncate">${escapeHtml(name)}</h6>
                                    <small class="text-muted text-truncate d-block">@${escapeHtml(row.username || 'unknown')}</small>
                                </div>
                            </div>
                        `;
                    }
                    return row.full_name || 'Unknown';
                }
            },
            { 
                data: null,
                title: 'Contact',
                render: function(data, type, row) {
                    if (type === 'display') {
                        const email = row.email || '';
                        const phone = row.phone || '';
                        return `
                            <div class="py-1">
                                ${email ? `<div class="mb-1">
                                    <i class="bx bx-envelope text-muted me-1"></i>
                                    <span class="text-truncate d-inline-block" style="max-width: 180px;" title="${escapeHtml(email)}">${escapeHtml(email)}</span>
                                </div>` : ''}
                                ${phone ? `<div>
                                    <i class="bx bx-phone text-muted me-1"></i>
                                    <span class="text-nowrap">${escapeHtml(phone)}</span>
                                </div>` : ''}
                                ${!email && !phone ? '<span class="text-muted">No contact info</span>' : ''}
                            </div>
                        `;
                    }
                    return (row.email || '') + ' ' + (row.phone || '');
                }
            },
            { 
                data: 'role',
                title: 'Role',
                render: function(data, type, row) {
                    if (type === 'display') {
                        const roleConfig = {
                            'admin': { 
                                class: 'bg-danger text-white', 
                                icon: 'bx-shield-alt-2', 
                                label: 'Administrator' 
                            },
                            'doctor': { 
                                class: 'bg-success text-white', 
                                icon: 'bx-plus-medical', 
                                label: 'Doctor' 
                            },
                            'nurse': { 
                                class: 'bg-info text-white', 
                                icon: 'bx-heart', 
                                label: 'Nurse' 
                            },
                            'receptionist': { 
                                class: 'bg-secondary text-white', 
                                icon: 'bx-user-voice', 
                                label: 'Receptionist' 
                            }
                        };
                        const config = roleConfig[data] || { 
                            class: 'bg-light text-dark', 
                            icon: 'bx-user', 
                            label: 'Unknown' 
                        };
                        
                        return `
                            <span class="badge ${config.class} px-3 py-2 fw-medium">
                                <i class="bx ${config.icon} me-1"></i>${config.label}
                            </span>
                        `;
                    }
                    return data || 'unknown';
                },
                className: 'text-center'
            },
            { 
                data: 'is_active',
                title: 'Status',
                render: function(data, type, row) {
                    if (type === 'display') {
                        if (data == 1) {
                            return `
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="badge bg-light-success text-success px-3 py-2 fw-medium">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success rounded-circle p-1 me-2"></span>
                                            Active
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="badge bg-light-secondary text-secondary px-3 py-2 fw-medium">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-secondary rounded-circle p-1 me-2"></span>
                                            Inactive
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }
                    return data == 1 ? 'Active' : 'Inactive';
                },
                className: 'text-center'
            },
            { 
                data: 'last_login',
                title: 'Last Active',
                render: function(data, type, row) {
                    if (type === 'display') {
                        if (data && data !== '0000-00-00 00:00:00') {
                            const date = new Date(data);
                            const now = new Date();
                            const diffTime = Math.abs(now - date);
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            
                            let timeAgo = '';
                            let iconClass = '';
                            let textClass = '';
                            
                            if (diffDays === 0) {
                                timeAgo = 'Today';
                                iconClass = 'bx-time text-success';
                                textClass = 'text-success';
                            } else if (diffDays === 1) {
                                timeAgo = 'Yesterday';
                                iconClass = 'bx-time text-warning';
                                textClass = 'text-warning';
                            } else if (diffDays <= 7) {
                                timeAgo = `${diffDays} days ago`;
                                iconClass = 'bx-time text-info';
                                textClass = 'text-info';
                            } else if (diffDays <= 30) {
                                timeAgo = `${Math.floor(diffDays/7)} weeks ago`;
                                iconClass = 'bx-time text-muted';
                                textClass = 'text-muted';
                            } else {
                                timeAgo = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                iconClass = 'bx-time text-muted';
                                textClass = 'text-muted';
                            }
                            
                            return `
                                <div class="text-center py-1">
                                    <div class="d-flex align-items-center justify-content-center mb-1">
                                        <i class="bx ${iconClass} me-1"></i>
                                        <small class="${textClass} fw-medium">${timeAgo}</small>
                                    </div>
                                    <small class="text-muted">${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                                </div>
                            `;
                        }
                        return `
                            <div class="text-center py-1">
                                <div class="d-flex align-items-center justify-content-center mb-1">
                                    <i class="bx bx-time text-muted me-1"></i>
                                    <small class="text-muted fw-medium">Never</small>
                                </div>
                                <small class="text-muted">No login</small>
                            </div>
                        `;
                    }
                    return data || 'Never';
                },
                className: 'text-center'
            },
            {
                data: null,
                title: 'Actions',
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        const statusAction = row.is_active == 1 ? 'Deactivate' : 'Activate';
                        const statusIcon = row.is_active == 1 ? 'user-x' : 'user-check';
                        const statusClass = row.is_active == 1 ? 'warning' : 'success';
                        
                        return `
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" onclick="viewUser(${row.id})">
                                            <i class="bx bx-show me-2"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" onclick="editUser(${row.id})">
                                            <i class="bx bx-edit me-2"></i>Edit User
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-${statusClass}" href="javascript:void(0);" 
                                            onclick="toggleUserStatus(${row.id}, ${row.is_active == 1 ? '0' : '1'})">
                                            <i class="bx bx-${statusIcon} me-2"></i>${statusAction}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteUser(${row.id})">
                                            <i class="bx bx-trash me-2"></i>Delete User
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        `;
                    }
                    return '';
                },
                className: 'text-center',
                width: '50px'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            emptyTable: "No users found",
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            loadingRecords: 'Loading...',
            zeroRecords: 'No matching users found',
            info: 'Showing _START_ to _END_ of _TOTAL_ users',
            infoEmpty: 'No users available',
            infoFiltered: '(filtered from _MAX_ total users)',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        dom: '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        drawCallback: function(settings) {
            // Initialize tooltips after each draw
            $('[title]').tooltip({
                container: 'body',
                trigger: 'hover'
            });
        }
    });
}

// Load user statistics
function loadUserStatistics() {
    $.ajax({
        url: '../ajax/get_user_statistics.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#total-users').text(stats.total_users || 0);
                $('#active-users').text(stats.active_users || 0);
                $('#inactive-users').text(stats.inactive_users || 0);
                $('#admin-users').text(stats.admin_users || 0);
            }
        },
        error: function() {
            console.error('Failed to load user statistics');
        }
    });
}

// Edit user
function editUser(id) {
    $.ajax({
        url: '../ajax/get_user.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const user = response.user;
                $('#edit-user-id').val(user.id);
                $('#edit-first-name').val(user.first_name);
                $('#edit-last-name').val(user.last_name);
                $('#edit-email').val(user.email);
                $('#edit-username').val(user.username);
                $('#edit-password').val(''); // Clear password field
                $('#edit-role').val(user.role);
                $('#edit-phone').val(user.phone);
                $('#edit-is-active').prop('checked', user.is_active == 1);
                $('#editUserModal').modal('show');
            } else {
                showToast('Error loading user: ' + response.message, 'error');
            }
        },
        error: function() {
            showToast('Failed to load user', 'error');
        }
    });
}

// Update user
function updateUser() {
    const form = document.getElementById('editUserForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        id: $('#edit-user-id').val(),
        first_name: $('#edit-first-name').val(),
        last_name: $('#edit-last-name').val(),
        email: $('#edit-email').val(),
        username: $('#edit-username').val(),
        role: $('#edit-role').val(),
        phone: $('#edit-phone').val(),
        is_active: $('#edit-is-active').is(':checked') ? 1 : 0
    };
    
    // Include password only if provided
    const password = $('#edit-password').val();
    if (password.trim()) {
        data.password = password;
    }
    
    $.ajax({
        url: '../ajax/update_user.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('User updated successfully', 'success');
                $('#editUserModal').modal('hide');
                if (usersDataTable) {
                    usersDataTable.ajax.reload();
                }
                loadUserStatistics();
            } else {
                showToast('Error updating user: ' + response.message, 'error');
            }
        },
        error: function() {
            showToast('Failed to update user', 'error');
        }
    });
}

// View user details
function viewUser(id) {
    $.ajax({
        url: '../ajax/get_user.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const user = response.user;
                $('#view-name').text(user.full_name);
                $('#view-email').text(user.email);
                $('#view-username').text(user.username);
                $('#view-role').html(`<span class="badge bg-primary">${user.role_label}</span>`);
                $('#view-phone').text(user.phone || '-');
                $('#view-status').html(user.is_active == 1 ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-secondary">Inactive</span>'
                );
                $('#view-created').text(user.created_at_formatted);
                $('#view-last-login').text(user.last_login_formatted);
                $('#viewUserModal').modal('show');
            } else {
                showToast('Error loading user: ' + response.message, 'error');
            }
        },
        error: function() {
            showToast('Failed to load user', 'error');
        }
    });
}

// Delete user
function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: '../ajax/delete_user.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('User deleted successfully', 'success');
                if (usersDataTable) {
                    usersDataTable.ajax.reload();
                }
                loadUserStatistics();
            } else {
                showToast('Error deleting user: ' + response.message, 'error');
            }
        },
        error: function() {
            showToast('Failed to delete user', 'error');
        }
    });
}

// Toggle user status (activate/deactivate)
function toggleUserStatus(id, isActive) {
    const action = isActive ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }
    
    $.ajax({
        url: '../ajax/toggle_user_status.php',
        type: 'POST',
        data: { 
            id: id,
            is_active: isActive
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                if (usersDataTable) {
                    usersDataTable.ajax.reload();
                }
                loadUserStatistics();
            } else {
                showToast('Error: ' + response.message, 'error');
            }
        },
        error: function() {
            showToast(`Failed to ${action} user`, 'error');
        }
    });
}

// Update filter display
function updateFilterDisplay(type, value, label) {
    const filterBtn = $(`.filter-${type}`).parent().prev('button');
    if (value === '') {
        filterBtn.html(`<i class="bx bx-filter me-1"></i>Filter by ${type === 'role' ? 'Role' : 'Status'}`);
    } else {
        filterBtn.html(`<i class="bx bx-filter me-1"></i>${label}`);
    }
}

// Reset form
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = $('#toast');
    const toastBody = $('#toast-message');
    
    // Set message and style
    toastBody.text(message);
    
    // Remove existing classes and add new ones
    toast.removeClass('bg-success bg-danger bg-warning bg-info text-white');
    
    switch (type) {
        case 'success':
            toast.addClass('bg-success text-white');
            break;
        case 'error':
        case 'danger':
            toast.addClass('bg-danger text-white');
            break;
        case 'warning':
            toast.addClass('bg-warning text-white');
            break;
        default:
            toast.addClass('bg-info text-white');
    }
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.toString().replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
