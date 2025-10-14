// Receptionists Management JavaScript
let receptionistsTable = null;
let isEditMode = false;

$(document).ready(function() {
    initializeTable();
    
    $('#addReceptionistBtn').on('click', function() {
        isEditMode = false;
        $('#receptionistForm')[0].reset();
        $('#receptionist-id').val('');
        $('#receptionistModalLabel').html('<i class="bx bx-plus-circle me-2"></i>Add Receptionist');
        $('#password-field').show();
        $('#password').prop('required', true);
        $('#receptionistModal').modal('show');
    });
    
    $('#saveReceptionistBtn').on('click', function() {
        saveReceptionist();
    });
});

function initializeTable() {
    receptionistsTable = $('#receptionistsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../ajax/get_users_by_role.php',
            type: 'GET',
            data: function(d) {
                d.role = 'receptionist';
            }
        },
        columns: [
            { 
                data: 'id',
                className: 'text-center',
                render: function(data) {
                    return `<span class="fw-semibold text-primary">#${data}</span>`;
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    const name = `${row.first_name} ${row.last_name}`;
                    const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
                    return `
                        <div class="user-cell">
                            <div class="user-avatar">${initials}</div>
                            <div class="user-info">
                                <h6>${escapeHtml(name)}</h6>
                            </div>
                        </div>
                    `;
                }
            },
            { data: 'username' },
            { 
                data: null,
                render: function(data, type, row) {
                    return `
                        <div>
                            ${row.email ? `<div><i class="bx bx-envelope text-muted me-1"></i>${escapeHtml(row.email)}</div>` : ''}
                            ${row.phone ? `<div><i class="bx bx-phone text-muted me-1"></i>${escapeHtml(row.phone)}</div>` : ''}
                        </div>
                    `;
                }
            },
            { 
                data: 'is_active',
                className: 'text-center',
                render: function(data) {
                    return data == 1 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-secondary">Inactive</span>';
                }
            },
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" 
                                    data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item edit-receptionist" href="#" data-id="${row.id}">
                                    <i class="bx bx-edit me-2"></i>Edit
                                </a></li>
                                <li><a class="dropdown-item reset-password" href="#" data-id="${row.id}" data-username="${row.username}">
                                    <i class="bx bx-key me-2"></i>Reset Password
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item delete-receptionist text-danger" href="#" data-id="${row.id}">
                                    <i class="bx bx-trash me-2"></i>Delete
                                </a></li>
                            </ul>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        dom: '<"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });
    
    $('#receptionistsTable').on('click', '.edit-receptionist', function(e) {
        e.preventDefault();
        editReceptionist($(this).data('id'));
    });
    
    $('#receptionistsTable').on('click', '.reset-password', function(e) {
        e.preventDefault();
        resetPassword($(this).data('id'), $(this).data('username'));
    });
    
    $('#receptionistsTable').on('click', '.delete-receptionist', function(e) {
        e.preventDefault();
        deleteReceptionist($(this).data('id'));
    });
}

function saveReceptionist() {
    const form = $('#receptionistForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    const formData = {
        id: $('#receptionist-id').val(),
        first_name: $('#first-name').val(),
        last_name: $('#last-name').val(),
        username: $('#username').val(),
        email: $('#email').val(),
        phone: $('#phone').val(),
        password: $('#password').val(),
        is_active: $('#is-active').val(),
        role: 'receptionist'
    };
    
    const url = isEditMode ? '../ajax/update_user.php' : '../ajax/create_user.php';
    const action = isEditMode ? 'updated' : 'created';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message || `Receptionist ${action} successfully`, 'success');
                $('#receptionistModal').modal('hide');
                receptionistsTable.ajax.reload();
            } else {
                showToast(response.message || `Failed to ${action.slice(0, -1)} receptionist`, 'error');
            }
        },
        error: function() {
            showToast('An error occurred', 'error');
        }
    });
}

function editReceptionist(id) {
    $.ajax({
        url: '../ajax/get_user_details.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                isEditMode = true;
                const user = response.data;
                $('#receptionist-id').val(user.id);
                $('#first-name').val(user.first_name);
                $('#last-name').val(user.last_name);
                $('#username').val(user.username);
                $('#email').val(user.email);
                $('#phone').val(user.phone);
                $('#is-active').val(user.is_active);
                $('#password-field').hide();
                $('#password').prop('required', false);
                $('#receptionistModalLabel').html('<i class="bx bx-edit me-2"></i>Edit Receptionist');
                $('#receptionistModal').modal('show');
            } else {
                showToast(response.message || 'Failed to load receptionist', 'error');
            }
        }
    });
}

function resetPassword(id, username) {
    Swal.fire({
        title: 'Reset Password',
        html: `
            <p>Reset password for <strong>${escapeHtml(username)}</strong></p>
            <input type="password" id="swal-new-password" class="swal2-input" placeholder="Enter new password" minlength="6">
            <input type="password" id="swal-confirm-password" class="swal2-input" placeholder="Confirm new password" minlength="6">
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reset Password',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const newPassword = document.getElementById('swal-new-password').value;
            const confirmPassword = document.getElementById('swal-confirm-password').value;
            
            if (!newPassword || !confirmPassword) {
                Swal.showValidationMessage('Please fill in both password fields');
                return false;
            }
            
            if (newPassword.length < 6) {
                Swal.showValidationMessage('Password must be at least 6 characters');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                Swal.showValidationMessage('Passwords do not match');
                return false;
            }
            
            return newPassword;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/reset_user_password.php',
                type: 'POST',
                data: { 
                    user_id: id,
                    new_password: result.value
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Password Reset Successfully!',
                            html: `
                                <p>Password has been reset for <strong>${escapeHtml(username)}</strong></p>
                                <div class="alert alert-info mt-3">
                                    <i class="bx bx-key me-2"></i>
                                    <strong>New Password:</strong> 
                                    <code class="text-dark" style="font-size: 1.1em; user-select: all;">${escapeHtml(result.value)}</code>
                                </div>
                                <small class="text-muted">Please save this password securely and share it with the user.</small>
                            `,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        showToast(response.message || 'Failed to reset password', 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred while resetting password', 'error');
                }
            });
        }
    });
}

function deleteReceptionist(id) {
    Swal.fire({
        title: 'Delete Receptionist',
        text: 'Are you sure you want to delete this receptionist? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/delete_user.php',
                type: 'POST',
                data: { user_id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message || 'Receptionist deleted successfully', 'success');
                        receptionistsTable.ajax.reload();
                    } else {
                        showToast(response.message || 'Failed to delete receptionist', 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred', 'error');
                }
            });
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

function showToast(message, type = 'info') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    
    Toast.fire({
        icon: type,
        title: message
    });
}
