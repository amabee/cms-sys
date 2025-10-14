<?php
$page_title = 'Doctors Management';
$additional_css = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'
];
$additional_js = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
  '../assets/js/doctors.js'
];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor']);

ob_start();
?>

<style>
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
    background: linear-gradient(135deg, #10b981, #059669);
  }

  #doctorsTable {
    width: 100%;
    font-size: 0.875rem;
  }

  #doctorsTable .dropdown-toggle::after {
    display: none;
  }

  #doctorsTable td {
    vertical-align: middle;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f5;
  }

  #doctorsTable th {
    background-color: #f8fafc;
    border-bottom: 2px solid #e8ecf1;
    font-weight: 600;
    color: #5a6f7d;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    padding: 0.75rem 1rem;
  }

  #doctorsTable tbody tr {
    /* No hover effects */
  }

  .card-body .table-responsive {
    margin: -0.5rem;
    padding: 0.5rem;
  }

  .doctor-cell {
    display: flex;
    align-items: center;
    gap: 0.65rem;
  }

  .doctor-info h6 {
    margin: 0;
    font-weight: 600;
    color: #1a1a2e;
    font-size: 0.875rem;
  }

  .doctor-info p {
    margin: 0.15rem 0 0 0;
    color: #8b96aa;
    font-size: 0.75rem;
  }

  .specialization-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    white-space: nowrap;
  }

  .availability-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
  }

  .availability-badge.available {
    background: rgba(34, 197, 94, 0.12);
    color: #22c55e;
  }

  .availability-badge.unavailable {
    background: rgba(107, 114, 128, 0.12);
    color: #6b7280;
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
  }

  .min-w-0 {
    min-width: 0;
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

  .dataTables_wrapper .dataTables_length,
  .dataTables_wrapper .dataTables_filter {
    display: none !important;
  }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Doctors Management</h4>
    <p class="text-muted mb-0">Manage doctor profiles, specializations, and availability</p>
  </div>
  <div>
    <button type="button" class="btn btn-primary" id="addDoctorBtn">
      <i class="bx bx-plus me-1"></i>Add Doctor
    </button>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4" id="doctor-stats">
  <div class="col-lg-3 col-sm-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text mb-1">Total Doctors</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="total-doctors">-</h4>
            </div>
            <small class="text-muted">All registered doctors</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-success rounded-pill p-2">
              <i class="bx bx-plus-medical bx-sm"></i>
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
            <p class="card-text mb-1">Available Today</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="available-doctors">-</h4>
            </div>
            <small class="text-muted">Currently available</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-primary rounded-pill p-2">
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
            <p class="card-text mb-1">Today's Appointments</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="today-appointments">-</h4>
            </div>
            <small class="text-muted">Scheduled today</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-info rounded-pill p-2">
              <i class="bx bx-calendar bx-sm"></i>
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
            <p class="card-text mb-1">Specializations</p>
            <div class="d-flex align-items-center mb-1">
              <h4 class="mb-0 me-2" id="specializations-count">-</h4>
            </div>
            <small class="text-muted">Different fields</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-warning rounded-pill p-2">
              <i class="bx bx-category bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Doctors Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom">
    <div class="row align-items-center">
      <div class="col">
        <h5 class="card-title mb-0">
          <i class="bx bx-user-plus me-2 text-success"></i>Medical Staff
        </h5>
        <p class="text-muted small mb-0">Manage doctor profiles and schedules</p>
      </div>
      <div class="col-auto">
        <div class="d-flex gap-2">
          <!-- Specialization Filter -->
          <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle border" type="button" data-bs-toggle="dropdown">
              <i class="bx bx-filter me-1"></i>Specialization
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item filter-specialization" href="#" data-specialization="">All Specializations</a></li>
            </ul>
          </div>

          <!-- Availability Filter -->
          <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle border" type="button" data-bs-toggle="dropdown">
              <i class="bx bx-check-circle me-1"></i>Status
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item filter-availability" href="#" data-availability="">All Status</a></li>
              <li><a class="dropdown-item filter-availability" href="#" data-availability="1">Available</a></li>
              <li><a class="dropdown-item filter-availability" href="#" data-availability="0">Unavailable</a></li>
            </ul>
          </div>

          <!-- Global Search -->
          <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-light border-end-0">
              <i class="bx bx-search"></i>
            </span>
            <input type="text" class="form-control border-start-0" id="globalSearch" placeholder="Search doctors...">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="doctorsTable">
        <thead>
          <tr>
            <th style="width: 60px; text-align: center;">#</th>
            <th>Doctor</th>
            <th>Specialization</th>
            <th>Contact</th>
            <th style="text-align: center; width: 120px;">Experience</th>
            <th style="text-align: center; width: 120px;">Availability</th>
            <th style="text-align: center; width: 80px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Data will be loaded via DataTables AJAX -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Doctor Modal -->
<div class="modal fade" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addDoctorModalLabel">
          <i class="bx bx-plus-circle me-2"></i>Add New Doctor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addDoctorForm">
          <div class="row g-3">
            <!-- Personal Information -->
            <div class="col-12">
              <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Personal Information</h6>
            </div>

            <!-- First Name -->
            <div class="col-md-6">
              <label for="add-first-name" class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="add-first-name" name="first_name" required placeholder="John">
            </div>

            <!-- Last Name -->
            <div class="col-md-6">
              <label for="add-last-name" class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="add-last-name" name="last_name" required placeholder="Doe">
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label for="add-email" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="add-email" name="email" required placeholder="doctor@clinic.com">
            </div>

            <!-- Phone -->
            <div class="col-md-6">
              <label for="add-phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" id="add-phone" name="phone" required placeholder="(555) 123-4567">
            </div>

            <!-- Username -->
            <div class="col-md-6">
              <label for="add-username" class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="add-username" name="username" required placeholder="dr.johndoe">
            </div>

            <!-- Password -->
            <div class="col-md-6">
              <label for="add-password" class="form-label">Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="add-password" name="password" required placeholder="Enter password">
            </div>

            <!-- Doctor Information -->
            <div class="col-12 mt-4">
              <h6 class="text-primary mb-3"><i class="bx bx-plus-medical me-2"></i>Doctor Information</h6>
            </div>

            <!-- Doctor ID -->
            <div class="col-md-6">
              <label for="add-doctor-id" class="form-label">Doctor ID <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="add-doctor-id" name="doctor_id" required placeholder="e.g., DOC-001">
            </div>

            <!-- Specialization -->
            <div class="col-md-6">
              <label for="add-specialization" class="form-label">Specialization <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="add-specialization" name="specialization" required placeholder="e.g., Cardiology">
            </div>

            <!-- License Number -->
            <div class="col-md-6">
              <label for="add-license-number" class="form-label">License Number</label>
              <input type="text" class="form-control" id="add-license-number" name="license_number" placeholder="e.g., LIC-123456">
            </div>

            <!-- Experience Years -->
            <div class="col-md-6">
              <label for="add-experience-years" class="form-label">Years of Experience</label>
              <input type="number" class="form-control" id="add-experience-years" name="experience_years" min="0" placeholder="e.g., 5">
            </div>

            <!-- Consultation Fee -->
            <div class="col-md-6">
              <label for="add-consultation-fee" class="form-label">Consultation Fee</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" id="add-consultation-fee" name="consultation_fee" min="0" step="0.01" value="0.00">
              </div>
            </div>

            <!-- Availability -->
            <div class="col-md-6">
              <label for="add-is-available" class="form-label">Availability Status</label>
              <select class="form-select" id="add-is-available" name="is_available">
                <option value="1" selected>Available</option>
                <option value="0">Unavailable</option>
              </select>
            </div>

            <!-- Qualification -->
            <div class="col-md-12">
              <label for="add-qualification" class="form-label">Qualifications</label>
              <textarea class="form-control" id="add-qualification" name="qualification" rows="2" placeholder="e.g., MD, MBBS, Specialist in Cardiology"></textarea>
            </div>

            <!-- Bio -->
            <div class="col-md-12">
              <label for="add-bio" class="form-label">Bio</label>
              <textarea class="form-control" id="add-bio" name="bio" rows="3" placeholder="Brief biography or description"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i>Cancel
        </button>
        <button type="button" class="btn btn-primary" id="saveDoctorBtn">
          <i class="bx bx-save me-1"></i>Save Doctor
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Doctor Modal -->
<div class="modal fade" id="editDoctorModal" tabindex="-1" aria-labelledby="editDoctorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editDoctorModalLabel">
          <i class="bx bx-edit me-2"></i>Edit Doctor Profile
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editDoctorForm">
          <input type="hidden" id="edit-doctor-db-id" name="id">
          <div class="row g-3">
            <!-- Personal Information -->
            <div class="col-12">
              <h6 class="text-primary mb-3"><i class="bx bx-user me-2"></i>Personal Information</h6>
            </div>

            <div class="col-md-6">
              <label for="edit-first-name" class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-first-name" name="first_name" required>
            </div>

            <div class="col-md-6">
              <label for="edit-last-name" class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-last-name" name="last_name" required>
            </div>

            <div class="col-md-6">
              <label for="edit-email" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="edit-email" name="email" required>
            </div>

            <div class="col-md-6">
              <label for="edit-phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" id="edit-phone" name="phone" required>
            </div>

            <!-- Doctor Information -->
            <div class="col-12 mt-4">
              <h6 class="text-primary mb-3"><i class="bx bx-plus-medical me-2"></i>Doctor Information</h6>
            </div>

            <div class="col-md-6">
              <label for="edit-specialization" class="form-label">Specialization <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-specialization" name="specialization" required>
            </div>

            <div class="col-md-6">
              <label for="edit-license-number" class="form-label">License Number</label>
              <input type="text" class="form-control" id="edit-license-number" name="license_number">
            </div>

            <div class="col-md-6">
              <label for="edit-experience-years" class="form-label">Years of Experience</label>
              <input type="number" class="form-control" id="edit-experience-years" name="experience_years" min="0">
            </div>

            <div class="col-md-6">
              <label for="edit-consultation-fee" class="form-label">Consultation Fee</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" id="edit-consultation-fee" name="consultation_fee" min="0" step="0.01">
              </div>
            </div>

            <div class="col-md-6">
              <label for="edit-is-available" class="form-label">Availability Status</label>
              <select class="form-select" id="edit-is-available" name="is_available">
                <option value="1">Available</option>
                <option value="0">Unavailable</option>
              </select>
            </div>

            <div class="col-md-12">
              <label for="edit-qualification" class="form-label">Qualifications</label>
              <textarea class="form-control" id="edit-qualification" name="qualification" rows="2"></textarea>
            </div>

            <div class="col-md-12">
              <label for="edit-bio" class="form-label">Bio</label>
              <textarea class="form-control" id="edit-bio" name="bio" rows="3"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i>Cancel
        </button>
        <button type="button" class="btn btn-primary" id="updateDoctorBtn">
          <i class="bx bx-save me-1"></i>Update Doctor
        </button>
      </div>
    </div>
  </div>
</div>

<!-- View Doctor Details Modal -->
<div class="modal fade" id="viewDoctorModal" tabindex="-1" aria-labelledby="viewDoctorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewDoctorModalLabel">
          <i class="bx bx-info-circle me-2"></i>Doctor Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-12 text-center mb-3">
            <div class="avatar mx-auto" style="width: 80px; height: 80px; font-size: 2rem;" id="view-doctor-avatar"></div>
            <h5 class="mt-3 mb-1" id="view-doctor-name"></h5>
            <p class="text-muted mb-0" id="view-doctor-id"></p>
          </div>

          <div class="col-12"><hr></div>

          <div class="col-md-6">
            <strong><i class="bx bx-envelope me-2"></i>Email:</strong>
            <p class="mb-0" id="view-doctor-email"></p>
          </div>

          <div class="col-md-6">
            <strong><i class="bx bx-phone me-2"></i>Phone:</strong>
            <p class="mb-0" id="view-doctor-phone"></p>
          </div>

          <div class="col-md-6">
            <strong><i class="bx bx-plus-medical me-2"></i>Specialization:</strong>
            <p class="mb-0" id="view-doctor-specialization"></p>
          </div>

          <div class="col-md-6">
            <strong><i class="bx bx-id-card me-2"></i>License Number:</strong>
            <p class="mb-0" id="view-doctor-license"></p>
          </div>

          <div class="col-md-6">
            <strong><i class="bx bx-time me-2"></i>Experience:</strong>
            <p class="mb-0" id="view-doctor-experience"></p>
          </div>

          <div class="col-md-6">
            <strong><i class="bx bx-money me-2"></i>Consultation Fee:</strong>
            <p class="mb-0" id="view-doctor-fee"></p>
          </div>

          <div class="col-md-6">
            <strong><i class="bx bx-check-circle me-2"></i>Availability:</strong>
            <p class="mb-0" id="view-doctor-availability"></p>
          </div>

          <div class="col-md-12">
            <strong><i class="bx bx-award me-2"></i>Qualifications:</strong>
            <p class="mb-0" id="view-doctor-qualification"></p>
          </div>

          <div class="col-md-12">
            <strong><i class="bx bx-file-blank me-2"></i>Bio:</strong>
            <p class="mb-0" id="view-doctor-bio"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
