<?php
$page_title = 'User Management';
$additional_css = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'
];
$additional_js = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
  '../assets/js/users.js'
];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin']);

ob_start();
?>

<style>
  .bg-light-danger {
    background-color: rgba(255, 62, 29, 0.1) !important;
  }

  .bg-light-success {
    background-color: rgba(113, 221, 55, 0.1) !important;
  }

  .bg-light-info {
    background-color: rgba(22, 177, 255, 0.1) !important;
  }

  .bg-light-secondary {
    background-color: rgba(133, 146, 163, 0.1) !important;
  }

  .avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    font-weight: 600;
    font-size: 0.75rem;
    box-shadow: 0 2px 6px rgba(67, 89, 113, 0.1);
    color: white;
  }

  .avatar-admin {
    background: linear-gradient(135deg, #f97316, #ea580c);
  }

  .avatar-doctor {
    background: linear-gradient(135deg, #10b981, #059669);
  }

  .avatar-nurse {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
  }

  .avatar-receptionist {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  }

  #usersTable {
    width: 100%;
    font-size: 0.875rem;
  }

  #usersTable .dropdown-toggle::after {
    display: none;
  }

  #usersTable td {
    vertical-align: middle;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f5;
  }

  #usersTable th {
    background-color: #f8fafc;
    border-bottom: 2px solid #e8ecf1;
    font-weight: 600;
    color: #5a6f7d;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    padding: 0.75rem 1rem;
  }

  .card-body .table-responsive {
    margin: -0.5rem;
    padding: 0.5rem;
  }

  #usersTable tbody tr {
    transition: all 0.2s ease;
  }

  #usersTable tbody tr:hover {
    background-color: #f9fbfd;
    transform: translateX(2px);
  }

  .user-cell {
    display: flex;
    align-items: center;
    gap: 0.65rem;
  }

  .user-info h6 {
    margin: 0;
    font-weight: 600;
    color: #1a1a2e;
    font-size: 0.875rem;
  }

  .user-info p {
    margin: 0.15rem 0 0 0;
    color: #8b96aa;
    font-size: 0.75rem;
  }

  .contact-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .contact-group a,
  .contact-group p {
    margin: 0;
    font-size: 0.8125rem;
    font-size: 0.9rem;
    color: #4b5563;
    text-decoration: none;
  }

  .contact-group a:hover {
    color: #3b82f6;
    text-decoration: underline;
  }

  .role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
    white-space: nowrap;
  }

  .role-badge.admin {
    background: rgba(249, 115, 22, 0.12);
    color: #f97316;
  }

  .role-badge.doctor {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
  }

  .role-badge.nurse {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
  }

  .role-badge.receptionist {
    background: rgba(139, 92, 246, 0.12);
    color: #8b5cf6;
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
  }

  .status-badge.active {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
  }

  .status-badge.inactive {
    background: rgba(107, 114, 128, 0.12);
    color: #6b7280;
  }

  .last-active-cell {
    text-align: center;
    font-size: 0.8125rem;
    color: #4b5563;
  }

  .last-active-time {
    font-weight: 600;
    margin-bottom: 0.15rem;
  }

  .last-active-date {
    color: #8b96aa;
    font-size: 0.7rem;
  }

  .actions-cell {
    text-align: center;
  }

  .action-btn {
    background: transparent;
    border: none;
    color: #8b96aa;
    cursor: pointer;
    padding: 0.4rem;
    transition: all 0.2s ease;
    font-size: 1rem;
  }

  .action-btn:hover {
    color: #3b82f6;
    transform: scale(1.1);
  }

  .min-w-0 {
    min-width: 0;
  }

  .badge {
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 6px;
  }

  /* DataTables Pagination Styling */
  .dataTables_wrapper .dataTables_paginate {
    padding-top: 0.5rem;
  }

  .dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 0.125rem;
    border: 1px solid #d9dee3;
    border-radius: 0.375rem;
    background: white;
    color: #697a8d !important;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s ease;
  }

  .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #f8f9fa;
    border-color: #d9dee3;
    color: #566a7f !important;
  }

  .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #696cff;
    border-color: #696cff;
    color: white !important;
    font-weight: 600;
  }

  .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: #5f61e6;
    border-color: #5f61e6;
    color: white !important;
  }

  .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }

  .dataTables_wrapper .dataTables_info {
    padding-top: 0.85rem;
    font-size: 0.8125rem;
    color: #697a8d;
  }

  /* Remove default DataTables styles */
  .dataTables_wrapper .dataTables_length,
  .dataTables_wrapper .dataTables_filter {
    display: none !important;
  }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">User Management</h4>
    <p class="text-muted mb-0">Manage system users and their permissions</p>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4" id="user-stats">
  <div class="col-lg-3 col-sm-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text mb-1">Total Users</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="total-users">-</h4>
            </div>
            <small class="text-muted">All system users</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-primary rounded-pill p-2">
              <i class="bx bx-users bx-sm"></i>
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
            <p class="card-text mb-1">Active Users</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="active-users">-</h4>
            </div>
            <small class="text-muted">Currently active</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-success rounded-pill p-2">
              <i class="bx bx-user-check bx-sm"></i>
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
            <p class="card-text mb-1">Inactive Users</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="inactive-users">-</h4>
            </div>
            <small class="text-muted">Deactivated</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-secondary rounded-pill p-2">
              <i class="bx bx-user-x bx-sm"></i>
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
            <p class="card-text mb-1">Administrators</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="admin-users">-</h4>
            </div>
            <small class="text-muted">Admin role</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-danger rounded-pill p-2">
              <i class="bx bx-shield bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom">
    <div class="row align-items-center">
      <div class="col">
        <h5 class="card-title mb-0">
          <i class="bx bx-users me-2 text-primary"></i>System Users
        </h5>
        <p class="text-muted small mb-0">Manage user accounts and permissions</p>
      </div>
      <div class="col-auto">
        <div class="d-flex gap-2">
          <!-- Role Filter -->
          <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle border" type="button" data-bs-toggle="dropdown">
              <i class="bx bx-filter me-1"></i>Role
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item filter-role" href="#" data-role="">All Roles</a></li>
              <li><a class="dropdown-item filter-role" href="#" data-role="admin">Admin</a></li>
              <li><a class="dropdown-item filter-role" href="#" data-role="doctor">Doctor</a></li>
              <li><a class="dropdown-item filter-role" href="#" data-role="nurse">Nurse</a></li>
              <li><a class="dropdown-item filter-role" href="#" data-role="receptionist">Receptionist</a></li>
            </ul>
          </div>

          <!-- Status Filter -->
          <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle border" type="button" data-bs-toggle="dropdown">
              <i class="bx bx-check-circle me-1"></i>Status
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item filter-status" href="#" data-status="">All Status</a></li>
              <li><a class="dropdown-item filter-status" href="#" data-status="1">Active</a></li>
              <li><a class="dropdown-item filter-status" href="#" data-status="0">Inactive</a></li>
            </ul>
          </div>

          <!-- Global Search -->
          <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-light border-end-0">
              <i class="bx bx-search"></i>
            </span>
            <input type="text" class="form-control border-start-0" id="globalSearch" placeholder="Search users...">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="usersTable" style="max-width: 100%;">
        <thead>
          <tr>
            <th style="width: 60px; text-align: center;">#</th>
            <th>User</th>
            <th>Contact</th>
            <th style="text-align: center; width: 120px;">Role</th>
            <th style="text-align: center; width: 100px;">Status</th>
            <th style="text-align: center; width: 130px;">Last Active</th>
            <th style="text-align: center; width: 80px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Data will be loaded via DataTables AJAX -->
          <tr>
            <td style="text-align: center;"><strong>#1</strong></td>
            <td>
              <div class="user-cell">
                <div class="avatar avatar-admin">SA</div>
                <div class="user-info">
                  <h6>System Administrator</h6>
                  <p>@admin</p>
                </div>
              </div>
            </td>
            <td>
              <div class="contact-group">
                <a href="mailto:admin@clinic.com">admin@clinic.com</a>
              </div>
            </td>
            <td style="text-align: center;">
              <span class="role-badge admin">
                <i class="bx bx-shield"></i>Administrator
              </span>
            </td>
            <td style="text-align: center;">
              <span class="status-badge active">
                <i class="bx bx-check-circle"></i>Active
              </span>
            </td>
            <td>
              <div class="last-active-cell">
                <div class="last-active-time"><i class="bx bx-time"></i> Yesterday</div>
                <div class="last-active-date">07:04 PM</div>
              </div>
            </td>
            <td style="text-align: center;">
              <button class="action-btn" title="View">
                <i class="bx bx-show"></i>
              </button>
              <button class="action-btn" title="Edit">
                <i class="bx bx-pencil"></i>
              </button>
              <button class="action-btn" title="Delete">
                <i class="bx bx-trash"></i>
              </button>
            </td>
          </tr>
          <tr>
            <td style="text-align: center;"><strong>#2</strong></td>
            <td>
              <div class="user-cell">
                <div class="avatar avatar-doctor">JS</div>
                <div class="user-info">
                  <h6>John Smith</h6>
                  <p>@dr.smith</p>
                </div>
              </div>
            </td>
            <td>
              <div class="contact-group">
                <a href="mailto:dr.smith@clinic.com">dr.smith@clinic.com</a>
                <p><i class="bx bx-phone"></i> (555) 123-1001</p>
              </div>
            </td>
            <td style="text-align: center;">
              <span class="role-badge doctor">
                <i class="bx bx-plus-circle"></i>Doctor
              </span>
            </td>
            <td style="text-align: center;">
              <span class="status-badge active">
                <i class="bx bx-check-circle"></i>Active
              </span>
            </td>
            <td>
              <div class="last-active-cell">
                <div class="last-active-time"><i class="bx bx-time"></i> Never</div>
                <div class="last-active-date">No login</div>
              </div>
            </td>
            <td style="text-align: center;">
              <button class="action-btn" title="View">
                <i class="bx bx-show"></i>
              </button>
              <button class="action-btn" title="Edit">
                <i class="bx bx-pencil"></i>
              </button>
              <button class="action-btn" title="Delete">
                <i class="bx bx-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editUserForm" novalidate>
          <input type="hidden" id="edit-user-id" name="user_id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="edit-first-name">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-first-name" name="first_name" required>
              <div class="invalid-feedback">Please provide a first name.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="edit-last-name">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-last-name" name="last_name" required>
              <div class="invalid-feedback">Please provide a last name.</div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="edit-email">Email <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="edit-email" name="email" required>
              <div class="invalid-feedback">Please provide a valid email.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="edit-username">Username <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-username" name="username" required>
              <div class="invalid-feedback">Please provide a username.</div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="edit-password">New Password</label>
              <input type="password" class="form-control" id="edit-password" name="password" minlength="6">
              <small class="text-muted">Leave blank to keep current password</small>
              <div class="invalid-feedback">Password must be at least 6 characters.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="edit-role">Role <span class="text-danger">*</span></label>
              <select class="form-select" id="edit-role" name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="doctor">Doctor</option>
                <option value="nurse">Nurse</option>
                <option value="receptionist">Receptionist</option>
              </select>
              <div class="invalid-feedback">Please select a role.</div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="edit-phone">Phone Number</label>
              <input type="tel" class="form-control" id="edit-phone" name="phone">
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-center">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="edit-is-active" name="is_active">
                <label class="form-check-label" for="edit-is-active">
                  Active User
                </label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="updateUser()">Update User</button>
      </div>
    </div>
  </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">User Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 mb-3">
            <strong>Full Name:</strong>
            <p class="mb-0" id="view-name">-</p>
          </div>
          <div class="col-6 mb-3">
            <strong>Email:</strong>
            <p class="mb-0" id="view-email">-</p>
          </div>
          <div class="col-6 mb-3">
            <strong>Username:</strong>
            <p class="mb-0" id="view-username">-</p>
          </div>
          <div class="col-6 mb-3">
            <strong>Role:</strong>
            <p class="mb-0" id="view-role">-</p>
          </div>
          <div class="col-6 mb-3">
            <strong>Phone:</strong>
            <p class="mb-0" id="view-phone">-</p>
          </div>
          <div class="col-6 mb-3">
            <strong>Status:</strong>
            <p class="mb-0" id="view-status">-</p>
          </div>
          <div class="col-6 mb-3">
            <strong>Created:</strong>
            <p class="mb-0" id="view-created">-</p>
          </div>
          <div class="col-12 mb-3">
            <strong>Last Login:</strong>
            <p class="mb-0" id="view-last-login">-</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 11055">
  <div id="toast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-body" id="toast-message">
      Default message
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>

