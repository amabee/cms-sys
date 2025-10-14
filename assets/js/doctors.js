// Doctors Management JavaScript
let doctorsDataTable = null;
let currentFilters = {};

// Initialize page when document is ready
$(document).ready(function() {
    initializeDoctorsManagement();
    loadDoctorStatistics();
    loadSpecializations();
    
    // Add Doctor button handler
    $('#addDoctorBtn').on('click', function() {
        $('#addDoctorForm')[0].reset();
        $('#addDoctorModal').modal('show');
    });
    
    // Save Doctor button handler
    $('#saveDoctorBtn').on('click', function() {
        saveDoctor();
    });
    
    // Update Doctor button handler
    $('#updateDoctorBtn').on('click', function() {
        updateDoctor();
    });
    
    // Modal hidden event
    $('#addDoctorModal').on('hidden.bs.modal', function() {
        $('#addDoctorForm')[0].reset();
    });
    
    // Filter event handlers
    $('.filter-specialization').on('click', function(e) {
        e.preventDefault();
        const specialization = $(this).data('specialization');
        currentFilters.specialization = specialization;
        if (doctorsDataTable) {
            doctorsDataTable.ajax.reload();
        }
    });
    
    $('.filter-availability').on('click', function(e) {
        e.preventDefault();
        const availability = $(this).data('availability');
        currentFilters.is_available = availability;
        if (doctorsDataTable) {
            doctorsDataTable.ajax.reload();
        }
    });
    
    // Global search handler
    $('#globalSearch').on('keyup', debounce(function() {
        const search = $(this).val();
        currentFilters.search = search;
        if (doctorsDataTable) {
            doctorsDataTable.ajax.reload();
        }
    }, 300));
});

// Initialize doctors management DataTable
function initializeDoctorsManagement() {
    doctorsDataTable = $('#doctorsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../ajax/get_doctors_dt.php',
            type: 'GET',
            data: function(d) {
                // Add custom filters
                d.specialization = currentFilters.specialization || '';
                d.is_available = currentFilters.is_available || '';
                d.search = currentFilters.search || '';
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', error, thrown);
                showToast('Failed to load doctors data', 'error');
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
                        return `<span class="fw-semibold text-success">#${data}</span>`;
                    }
                    return data;
                }
            },
            { 
                data: null,
                title: 'Doctor',
                render: function(data, type, row) {
                    if (type === 'display') {
                        const name = row.name || 'Unknown Doctor';
                        const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                        
                        return `
                            <div class="doctor-cell">
                                <div class="avatar">
                                    ${initials}
                                </div>
                                <div class="doctor-info min-w-0">
                                    <h6 class="text-truncate mb-0">${escapeHtml(name)}</h6>
                                    <p class="text-muted text-truncate mb-0">ID: ${escapeHtml(row.doctor_id || 'N/A')}</p>
                                </div>
                            </div>
                        `;
                    }
                    return row.name || 'Unknown';
                }
            },
            { 
                data: 'specialization',
                title: 'Specialization',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<span class="specialization-badge">
                            <i class="bx bx-plus-medical"></i>
                            ${escapeHtml(data || 'General')}
                        </span>`;
                    }
                    return data || 'General';
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
                            <div class="contact-group">
                                ${email ? `<div>
                                    <i class="bx bx-envelope text-muted me-1"></i>
                                    <a href="mailto:${escapeHtml(email)}" class="text-truncate d-inline-block" style="max-width: 180px;" title="${escapeHtml(email)}">${escapeHtml(email)}</a>
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
                data: 'experience_years',
                title: 'Experience',
                render: function(data, type, row) {
                    if (type === 'display') {
                        const years = parseInt(data) || 0;
                        return `
                            <div class="text-center">
                                <span class="fw-semibold">${years}</span>
                                <span class="text-muted"> ${years === 1 ? 'year' : 'years'}</span>
                            </div>
                        `;
                    }
                    return data || 0;
                },
                className: 'text-center'
            },
            { 
                data: 'is_available',
                title: 'Availability',
                render: function(data, type, row) {
                    if (type === 'display') {
                        if (data == 1) {
                            return `<span class="availability-badge available">
                                <i class="bx bx-check-circle"></i>Available
                            </span>`;
                        } else {
                            return `<span class="availability-badge unavailable">
                                <i class="bx bx-x-circle"></i>Unavailable
                            </span>`;
                        }
                    }
                    return data == 1 ? 'Available' : 'Unavailable';
                },
                className: 'text-center'
            },
            { 
                data: null,
                title: 'Actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `
                            <div class="dropdown">
                                <button class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item view-doctor" href="#" data-id="${row.id}">
                                            <i class="bx bx-show me-2"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item edit-doctor" href="#" data-id="${row.id}">
                                            <i class="bx bx-edit me-2"></i>Edit Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item toggle-availability" href="#" data-id="${row.id}" data-available="${row.is_available}">
                                            <i class="bx ${row.is_available == 1 ? 'bx-x-circle' : 'bx-check-circle'} me-2"></i>
                                            ${row.is_available == 1 ? 'Mark Unavailable' : 'Mark Available'}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger delete-doctor" href="#" data-id="${row.id}">
                                            <i class="bx bx-trash me-2"></i>Delete
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
            emptyTable: "No doctors found",
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            loadingRecords: 'Loading...',
            zeroRecords: 'No matching doctors found',
            info: 'Showing _START_ to _END_ of _TOTAL_ doctors',
            infoEmpty: 'No doctors available',
            infoFiltered: '(filtered from _MAX_ total doctors)',
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
    
    // Event delegation for dynamically loaded buttons
    $('#doctorsTable').on('click', '.view-doctor', function(e) {
        e.preventDefault();
        const doctorId = $(this).data('id');
        viewDoctorDetails(doctorId);
    });
    
    $('#doctorsTable').on('click', '.edit-doctor', function(e) {
        e.preventDefault();
        const doctorId = $(this).data('id');
        editDoctor(doctorId);
    });
    
    $('#doctorsTable').on('click', '.toggle-availability', function(e) {
        e.preventDefault();
        const doctorId = $(this).data('id');
        const isAvailable = $(this).data('available');
        toggleDoctorAvailability(doctorId, isAvailable);
    });
    
    $('#doctorsTable').on('click', '.delete-doctor', function(e) {
        e.preventDefault();
        const doctorId = $(this).data('id');
        deleteDoctor(doctorId);
    });
}

// Load doctor statistics
function loadDoctorStatistics() {
    $.ajax({
        url: '../ajax/get_doctor_statistics.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#total-doctors').text(response.data.total || 0);
                $('#available-doctors').text(response.data.available || 0);
                $('#today-appointments').text(response.data.today_appointments || 0);
                $('#specializations-count').text(response.data.specializations || 0);
            }
        },
        error: function() {
            console.error('Failed to load doctor statistics');
        }
    });
}

// Load specializations for filter
function loadSpecializations() {
    $.ajax({
        url: '../ajax/get_specializations.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const dropdown = $('.filter-specialization').parent().parent();
                response.data.forEach(function(spec) {
                    dropdown.append(`
                        <li><a class="dropdown-item filter-specialization" href="#" data-specialization="${spec}">${spec}</a></li>
                    `);
                });
                
                // Re-bind click events
                $('.filter-specialization').on('click', function(e) {
                    e.preventDefault();
                    const specialization = $(this).data('specialization');
                    currentFilters.specialization = specialization;
                    if (doctorsDataTable) {
                        doctorsDataTable.ajax.reload();
                    }
                });
            }
        },
        error: function() {
            console.error('Failed to load specializations');
        }
    });
}

// Save new doctor
function saveDoctor() {
    const form = $('#addDoctorForm');
    
    // Validate form
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    // Get form data
    const formData = {
        first_name: $('#add-first-name').val(),
        last_name: $('#add-last-name').val(),
        email: $('#add-email').val(),
        phone: $('#add-phone').val(),
        username: $('#add-username').val(),
        password: $('#add-password').val(),
        doctor_id: $('#add-doctor-id').val(),
        specialization: $('#add-specialization').val(),
        license_number: $('#add-license-number').val(),
        experience_years: $('#add-experience-years').val(),
        consultation_fee: $('#add-consultation-fee').val(),
        is_available: $('#add-is-available').val(),
        qualification: $('#add-qualification').val(),
        bio: $('#add-bio').val()
    };
    
    // Disable save button
    const saveBtn = $('#saveDoctorBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');
    
    $.ajax({
        url: '../ajax/create_doctor.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('Doctor added successfully', 'success');
                $('#addDoctorModal').modal('hide');
                form[0].reset();
                
                // Reload data
                if (doctorsDataTable) {
                    doctorsDataTable.ajax.reload();
                }
                loadDoctorStatistics();
            } else {
                showToast(response.message || 'Failed to add doctor', 'error');
            }
        },
        error: function(xhr) {
            let errorMsg = 'An error occurred while adding doctor';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showToast(errorMsg, 'error');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html(originalText);
        }
    });
}

// View doctor details
function viewDoctorDetails(doctorId) {
    $.ajax({
        url: '../ajax/get_doctor_details.php',
        type: 'GET',
        data: { id: doctorId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const doctor = response.data;
                const initials = doctor.full_name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                
                $('#view-doctor-avatar').text(initials);
                $('#view-doctor-name').text(doctor.full_name || 'N/A');
                $('#view-doctor-id').text('ID: ' + (doctor.doctor_id || 'N/A'));
                $('#view-doctor-email').text(doctor.email || 'N/A');
                $('#view-doctor-phone').text(doctor.phone || 'N/A');
                $('#view-doctor-specialization').text(doctor.specialization || 'N/A');
                $('#view-doctor-license').text(doctor.license_number || 'N/A');
                $('#view-doctor-experience').text((doctor.experience_years || 0) + ' years');
                $('#view-doctor-fee').text('₱' + parseFloat(doctor.consultation_fee || 0).toFixed(2));
                $('#view-doctor-availability').html(doctor.is_available == 1 
                    ? '<span class="badge bg-success">Available</span>' 
                    : '<span class="badge bg-secondary">Unavailable</span>');
                $('#view-doctor-qualification').text(doctor.qualification || 'N/A');
                $('#view-doctor-bio').text(doctor.bio || 'No bio available');
                
                $('#viewDoctorModal').modal('show');
            } else {
                showToast(response.message || 'Failed to load doctor details', 'error');
            }
        },
        error: function() {
            showToast('An error occurred while loading doctor details', 'error');
        }
    });
}

// Edit doctor
function editDoctor(doctorId) {
    $.ajax({
        url: '../ajax/get_doctor_details.php',
        type: 'GET',
        data: { id: doctorId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const doctor = response.data;
                
                $('#edit-doctor-db-id').val(doctor.id);
                $('#edit-first-name').val(doctor.first_name);
                $('#edit-last-name').val(doctor.last_name);
                $('#edit-email').val(doctor.email);
                $('#edit-phone').val(doctor.phone);
                $('#edit-specialization').val(doctor.specialization);
                $('#edit-license-number').val(doctor.license_number);
                $('#edit-experience-years').val(doctor.experience_years);
                $('#edit-consultation-fee').val(doctor.consultation_fee);
                $('#edit-is-available').val(doctor.is_available);
                $('#edit-qualification').val(doctor.qualification);
                $('#edit-bio').val(doctor.bio);
                
                $('#editDoctorModal').modal('show');
            } else {
                showToast(response.message || 'Failed to load doctor details', 'error');
            }
        },
        error: function() {
            showToast('An error occurred while loading doctor details', 'error');
        }
    });
}

// Update doctor
function updateDoctor() {
    const form = $('#editDoctorForm');
    
    // Validate form
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    const updateBtn = $('#updateDoctorBtn');
    const originalText = updateBtn.html();
    
    // Show loading state
    updateBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');
    
    // Get form data
    const formData = {
        id: $('#edit-doctor-db-id').val(),
        first_name: $('#edit-first-name').val(),
        last_name: $('#edit-last-name').val(),
        email: $('#edit-email').val(),
        phone: $('#edit-phone').val(),
        specialization: $('#edit-specialization').val(),
        license_number: $('#edit-license-number').val(),
        experience_years: $('#edit-experience-years').val(),
        consultation_fee: $('#edit-consultation-fee').val(),
        is_available: $('#edit-is-available').val(),
        qualification: $('#edit-qualification').val(),
        bio: $('#edit-bio').val()
    };
    
    $.ajax({
        url: '../ajax/update_doctor.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message || 'Doctor updated successfully', 'success');
                $('#editDoctorModal').modal('hide');
                if (doctorsDataTable) {
                    doctorsDataTable.ajax.reload(null, false);
                }
                loadDoctorStatistics();
            } else {
                showToast(response.message || 'Failed to update doctor', 'error');
            }
            updateBtn.prop('disabled', false).html(originalText);
        },
        error: function(xhr) {
            showToast('An error occurred while updating doctor', 'error');
            updateBtn.prop('disabled', false).html(originalText);
        }
    });
}

// Toggle doctor availability
function toggleDoctorAvailability(doctorId, currentStatus) {
    const newStatus = currentStatus == 1 ? 0 : 1;
    const action = newStatus == 1 ? 'mark as available' : 'mark as unavailable';
    
    Swal.fire({
        title: 'Confirm Action',
        text: `Are you sure you want to ${action} this doctor?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/toggle_doctor_availability.php',
                type: 'POST',
                data: { doctor_id: doctorId, is_available: newStatus },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Doctor availability updated successfully', 'success');
                        if (doctorsDataTable) {
                            doctorsDataTable.ajax.reload(null, false);
                        }
                        loadDoctorStatistics();
                    } else {
                        showToast(response.message || 'Failed to update availability', 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred while updating availability', 'error');
                }
            });
        }
    });
}

// Delete doctor
function deleteDoctor(doctorId) {
    Swal.fire({
        title: 'Delete Doctor',
        text: 'Are you sure you want to delete this doctor? This action cannot be undone and will also delete the associated user account.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/delete_doctor.php',
                type: 'POST',
                data: { doctor_id: doctorId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message || 'Doctor deleted successfully', 'success');
                        if (doctorsDataTable) {
                            doctorsDataTable.ajax.reload(null, false);
                        }
                        loadDoctorStatistics();
                    } else {
                        showToast(response.message || 'Failed to delete doctor', 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred while deleting doctor', 'error');
                }
            });
        }
    });
}

// Utility function: Escape HTML
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

// Utility function: Debounce
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

// Utility function: Show toast notification
function showToast(message, type = 'info') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    
    Toast.fire({
        icon: type,
        title: message
    });
}
