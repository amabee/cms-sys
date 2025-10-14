<?php
$page_title = 'Prescriptions Management';
$additional_css = [];
$additional_js = [
  "https://cdn.jsdelivr.net/npm/sweetalert2@11"
];

include __DIR__ . '/../shared/session_handler.php';

requireRole(['admin', 'doctor', 'nurse', 'receptionist', 'secretary']);
$user_role = $_SESSION['user_type'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

ob_start();
?>

<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <!-- Statistics Overview (Sneat-style) -->
      <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-file bx-sm text-primary"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Total Prescriptions</span>
              <h3 class="card-title text-nowrap mb-1" id="totalPrescriptions">0</h3>
              <small class="text-primary fw-semibold"><i class="bx bx-time"></i> All Time</small>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-check-circle bx-sm text-success"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Active</span>
              <h3 class="card-title text-nowrap mb-1" id="activePrescriptions">0</h3>
              <small class="text-success fw-semibold"><i class="bx bx-check"></i> Currently Active</small>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-check-double bx-sm text-info"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Completed</span>
              <h3 class="card-title text-nowrap mb-1" id="completedPrescriptions">0</h3>
              <small class="text-info fw-semibold"><i class="bx bx-check-double"></i> Finished</small>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <i class="bx bx-x-circle bx-sm text-warning"></i>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Discontinued</span>
              <h3 class="card-title text-nowrap mb-1" id="discontinuedPrescriptions">0</h3>
              <small class="text-warning fw-semibold"><i class="bx bx-x"></i> Stopped</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Prescriptions Management -->
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Prescriptions</h3>
              <div class="card-tools">
                <?php if (in_array($user_role, ['admin', 'doctor'])): ?>
                <button type="button" class="btn btn-primary btn-sm" id="newPrescriptionBtn">
                  <i class="bx bx-plus"></i> New Prescription
                </button>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-body">
              <!-- Quick Search -->
              <div class="row mb-3">
                <div class="col-md-12">
                  <div class="input-group">
                    <input type="text" class="form-control" id="searchFilter"
                      placeholder="Search by patient name, prescription number, or medication..."
                      aria-label="Search prescriptions">
                    <div class="input-group-append">
                      <button class="btn btn-outline-secondary" type="button" id="searchBtn" title="Search">
                        <i class="bx bx-search"></i> <span>Search</span>
                      </button>
                      <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear">
                        <i class="bx bx-x"></i> <span>Clear</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Prescriptions Table -->
              <div class="table-responsive">
                <table class="table table-striped table-hover" id="prescriptionsTable">
                  <thead>
                    <tr>
                      <th>Rx Number</th>
                      <th>Patient</th>
                      <th>Doctor</th>
                      <th>Medication</th>
                      <th>Dosage</th>
                      <th>Date</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="prescriptionsTableBody">
                    <tr>
                      <td colspan="8" class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading prescriptions...
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Prescription Modal -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="prescriptionModalTitle">
          <i class="bx bx-file-blank me-2 text-primary"></i>New Prescription
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="prescriptionForm">
        <div class="modal-body">
          <!-- Patient & Doctor -->
          <div class="mb-4">
            <h6 class="text-primary mb-3">Patient & Doctor Information</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="patientSelect" name="patient_id" required>
                    <option value="">Choose patient...</option>
                  </select>
                  <label for="patientSelect">Patient *</label>
                </div>
              </div>
              <?php if ($user_role === 'admin' || $user_role === 'secretary' || $user_role === 'receptionist'): ?>
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="doctorSelect" name="doctor_id" required>
                    <option value="">Choose doctor...</option>
                  </select>
                  <label for="doctorSelect">Prescribing Doctor *</label>
                </div>
              </div>
              <?php elseif ($user_role === 'doctor'): 
                // Get doctor's ID from doctors table
                $db = getDBConnection();
                $stmt = $db->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
                $stmt->execute([$user_id]);
                $doctor_row = $stmt->fetch(PDO::FETCH_ASSOC);
                $doctor_id = $doctor_row ? $doctor_row['id'] : null;
              ?>
              <!-- Hidden field for doctor's own ID -->
              <input type="hidden" id="doctorSelect" name="doctor_id" value="<?= $doctor_id ?>">
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" value="Dr. <?= htmlspecialchars($_SESSION['first_name'] ?? '') ?> <?= htmlspecialchars($_SESSION['last_name'] ?? '') ?>" readonly>
                  <label>Prescribing Doctor</label>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <hr class="my-4">

          <!-- Medication Details -->
          <div class="mb-4">
            <h6 class="text-primary mb-3">Medication Details</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="medicationSelect" name="medication_id" required>
                    <option value="">Choose medication...</option>
                  </select>
                  <label for="medicationSelect">Medication *</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" id="dosageInput" name="dosage" placeholder="e.g., 500mg" required>
                  <label for="dosageInput">Dosage *</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="text" class="form-control" id="frequencyInput" name="frequency" placeholder="e.g., Twice daily" required>
                  <label for="frequencyInput">Frequency *</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="text" class="form-control" id="durationInput" name="duration" placeholder="e.g., 7 days">
                  <label for="durationInput">Duration</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating">
                  <input type="number" class="form-control" id="quantityInput" name="quantity" min="1" value="1" required>
                  <label for="quantityInput">Quantity *</label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-floating">
                  <textarea class="form-control" id="instructionsTextarea" name="instructions" style="height: 100px" placeholder="Enter special instructions..."></textarea>
                  <label for="instructionsTextarea">Special Instructions</label>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Status & Appointment -->
          <div class="mb-3">
            <h6 class="text-primary mb-3">Additional Information</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="statusSelect" name="status">
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="discontinued">Discontinued</option>
                  </select>
                  <label for="statusSelect">Status</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <select class="form-select" id="appointmentSelect" name="appointment_id">
                    <option value="">No associated appointment</option>
                  </select>
                  <label for="appointmentSelect">Related Appointment</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="bx bx-x me-1"></i>Cancel
          </button>
          <button type="button" class="btn btn-primary" id="savePrescriptionBtn">
            <i class="bx bx-check me-1"></i>Save Prescription
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Prescription Details Modal -->
<div class="modal fade" id="prescriptionDetailsModal" tabindex="-1" aria-labelledby="prescriptionDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="prescriptionDetailsLabel">Prescription Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="prescriptionDetailsContent">
        <!-- Prescription details will be loaded here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i>Close
        </button>
        <button type="button" class="btn btn-outline-info" id="printPrescriptionBtn">
          <i class="bx bx-printer me-1"></i>Print
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Load statistics
  (function loadPrescriptionStats() {
    fetch('../ajax/get_prescription_statistics.php')
      .then(r => {
        if (!r.ok) throw new Error('HTTP error ' + r.status);
        return r.json();
      })
      .then(res => {
        if (!res || !res.success || !res.data) {
          console.warn('Invalid statistics response:', res);
          return;
        }
        const s = res.data;
        document.getElementById('totalPrescriptions').textContent = s.total || 0;
        document.getElementById('activePrescriptions').textContent = s.active || 0;
        document.getElementById('completedPrescriptions').textContent = s.completed || 0;
        document.getElementById('discontinuedPrescriptions').textContent = s.discontinued || 0;
      })
      .catch((error) => {
        console.error('Failed to load prescription statistics:', error);
        document.getElementById('totalPrescriptions').textContent = '0';
        document.getElementById('activePrescriptions').textContent = '0';
        document.getElementById('completedPrescriptions').textContent = '0';
        document.getElementById('discontinuedPrescriptions').textContent = '0';
      });
  })();

  // Load dropdown options
  function loadDropdownOptions() {
    // Load patients
    $.ajax({
      url: '../ajax/get_patients.php',
      method: 'GET',
      data: { length: 1000 },
      success: function(response) {
        if (response.data && Array.isArray(response.data)) {
          let options = '<option value="">Choose patient...</option>';
          response.data.forEach(function(patient) {
            options += `<option value="${patient.id}">${patient.first_name} ${patient.last_name} (${patient.patient_id})</option>`;
          });
          $('#patientSelect').html(options);
        }
      },
      error: function(xhr, status, error) {
        console.error('Failed to load patients:', error);
      }
    });
    
    // Load doctors
    $.ajax({
      url: '../ajax/get_doctors.php',
      method: 'GET',
      success: function(response) {
        if (response.success && response.data) {
          let options = '<option value="">Choose doctor...</option>';
          response.data.forEach(function(doctor) {
            options += `<option value="${doctor.id}">${doctor.name} - ${doctor.specialization || 'No specialization'}</option>`;
          });
          $('#doctorSelect').html(options);
        }
      },
      error: function(xhr, status, error) {
        console.error('Failed to load doctors:', error);
      }
    });

    // Load medications
    $.ajax({
      url: '../ajax/get_medications.php',
      method: 'GET',
      success: function(response) {
        if (response.success && response.data) {
          let options = '<option value="">Choose medication...</option>';
          
          // Handle different response formats
          let medications = response.data;
          if (!Array.isArray(medications)) {
            // If data is an object with medications property
            medications = medications.medications || medications.data || [];
          }
          
          if (Array.isArray(medications)) {
            medications.forEach(function(med) {
              options += `<option value="${med.id}">${med.name} (${med.generic_name || 'Generic'})</option>`;
            });
          }
          
          $('#medicationSelect').html(options);
        }
      },
      error: function(xhr, status, error) {
        console.error('Failed to load medications:', error);
      }
    });
  }

  // Initialize DataTable
  let prescriptionsTable;
  
  function initializeDataTable() {
    prescriptionsTable = $('#prescriptionsTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '../ajax/get_prescriptions_dt.php',
        type: 'GET',
        error: function(xhr, error, code) {
          console.error('DataTable AJAX error:', error, code);
          console.error('Response:', xhr.responseText);
          console.error('Status:', xhr.status);
        }
      },
      columns: [
        { data: 'prescription_id' },
        { 
          data: 'patient_name',
          render: function(data) {
            return data || '-';
          }
        },
        { 
          data: 'doctor_name',
          render: function(data) {
            return data || '-';
          }
        },
        { 
          data: 'medication_name',
          render: function(data) {
            return data || '-';
          }
        },
        { data: 'dosage' },
        { 
          data: 'created_at',
          render: function(data) {
            return data ? new Date(data).toLocaleDateString() : '-';
          }
        },
        { 
          data: 'status',
          render: function(data) {
            const badges = {
              'active': 'badge bg-success',
              'completed': 'badge bg-info',
              'discontinued': 'badge bg-warning'
            };
            return `<span class="${badges[data] || 'badge bg-secondary'}">${data || 'N/A'}</span>`;
          }
        },
        {
          data: null,
          orderable: false,
          render: function(data) {
            return `
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary view-prescription" data-id="${data.id}" title="View">
                  <i class="bx bx-show"></i>
                </button>
                <button class="btn btn-outline-info edit-prescription" data-id="${data.id}" title="Edit">
                  <i class="bx bx-edit"></i>
                </button>
                <button class="btn btn-outline-danger delete-prescription" data-id="${data.id}" title="Delete">
                  <i class="bx bx-trash"></i>
                </button>
              </div>
            `;
          }
        }
      ],
      order: [[5, 'desc']], // Sort by date descending
      pageLength: 25,
      language: {
        emptyTable: "No prescriptions found",
        zeroRecords: "No matching prescriptions found"
      }
    });
  }

  // Initialize UI
  (function initPrescriptionsUI($) {
    if (!$) return;

    // Initialize DataTable
    initializeDataTable();
    
    // Load dropdowns on page load
    loadDropdownOptions();

    // Quick Search actions
    $('#searchBtn').on('click', function () {
      const q = $('#searchFilter').val() || '';
      prescriptionsTable.search(q).draw();
    });
    
    $('#clearSearchBtn').on('click', function () {
      $('#searchFilter').val('');
      prescriptionsTable.search('').draw();
    });
    
    // Search on Enter key
    $('#searchFilter').on('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        prescriptionsTable.search($(this).val()).draw();
      }
    });

    // New Prescription button
    $('#newPrescriptionBtn').on('click', function () {
      try { 
        document.getElementById('prescriptionForm').reset();
        $('#prescriptionForm input[name="id"]').remove();
      } catch (e) { }
      $('#prescriptionModalTitle').text('New Prescription');
      $('#statusSelect').val('active');
      $('#prescriptionModal').modal('show');
      setTimeout(() => $('#patientSelect').focus(), 200);
    });

    // Save prescription
    $('#savePrescriptionBtn').on('click', function() {
      const form = document.getElementById('prescriptionForm');
      
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const formData = new FormData(form);
      const isEdit = formData.has('id') && formData.get('id');
      
      const btn = $(this);
      const originalHtml = btn.html();
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

      $.ajax({
        url: isEdit ? '../ajax/update_prescription.php' : '../ajax/create_prescription.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          btn.prop('disabled', false).html(originalHtml);
          
          if (response.success) {
            $('#prescriptionModal').modal('hide');
            prescriptionsTable.ajax.reload(null, false);
            
            Swal.fire({
              icon: 'success',
              title: isEdit ? 'Prescription Updated' : 'Prescription Created',
              text: `Prescription has been successfully ${isEdit ? 'updated' : 'created'}.`,
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed',
              text: response.message || `Failed to ${isEdit ? 'update' : 'create'} prescription`
            });
          }
        },
        error: function(xhr, status, error) {
          btn.prop('disabled', false).html(originalHtml);
          console.error('Save prescription error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Connection error. Please try again.'
          });
        }
      });
    });

    // View Prescription Details
    $(document).on('click', '.view-prescription', function() {
      const prescriptionId = $(this).data('id');
      
      $('#prescriptionDetailsContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading prescription details...</p></div>');
      $('#prescriptionDetailsModal').modal('show');
      
      $.ajax({
        url: '../ajax/get_prescription_details.php',
        method: 'GET',
        data: { id: prescriptionId },
        success: function(response) {
          if (response.success && response.data) {
            const rx = response.data;
            
            const statusBadges = {
              'active': 'success',
              'completed': 'info',
              'discontinued': 'warning'
            };
            const statusClass = statusBadges[rx.status] || 'secondary';
            
            const html = `
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0"><i class="bx bx-file me-2"></i>Prescription Information</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <table class="table table-sm table-borderless">
                        <tr>
                          <th width="40%">Rx Number:</th>
                          <td><strong>${rx.prescription_id || '-'}</strong></td>
                        </tr>
                        <tr>
                          <th>Patient:</th>
                          <td>${rx.patient_name || '-'}</td>
                        </tr>
                        <tr>
                          <th>Doctor:</th>
                          <td>${rx.doctor_name || '-'}</td>
                        </tr>
                        <tr>
                          <th>Date:</th>
                          <td>${rx.created_at ? new Date(rx.created_at).toLocaleDateString() : '-'}</td>
                        </tr>
                        <tr>
                          <th>Status:</th>
                          <td><span class="badge bg-${statusClass}">${rx.status || '-'}</span></td>
                        </tr>
                      </table>
                    </div>
                    <div class="col-md-6">
                      <table class="table table-sm table-borderless">
                        <tr>
                          <th width="40%">Medication:</th>
                          <td>${rx.medication_name || '-'}</td>
                        </tr>
                        <tr>
                          <th>Dosage:</th>
                          <td>${rx.dosage || '-'}</td>
                        </tr>
                        <tr>
                          <th>Frequency:</th>
                          <td>${rx.frequency || '-'}</td>
                        </tr>
                        <tr>
                          <th>Duration:</th>
                          <td>${rx.duration || '-'}</td>
                        </tr>
                        <tr>
                          <th>Quantity:</th>
                          <td>${rx.quantity || '-'}</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              ${rx.instructions ? `
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bx bx-note me-2"></i>Special Instructions</h6>
                  </div>
                  <div class="card-body">
                    <p class="mb-0">${rx.instructions.replace(/\n/g, '<br>')}</p>
                  </div>
                </div>
              ` : ''}
              
              <div class="row">
                <div class="col-md-6">
                  <small class="text-muted">Created: ${rx.created_at ? new Date(rx.created_at).toLocaleString() : '-'}</small>
                </div>
                <div class="col-md-6 text-end">
                  <small class="text-muted">Last Updated: ${rx.updated_at ? new Date(rx.updated_at).toLocaleString() : '-'}</small>
                </div>
              </div>
            `;
            $('#prescriptionDetailsContent').html(html);
          } else {
            $('#prescriptionDetailsContent').html('<div class="alert alert-danger"><i class="bx bx-error me-2"></i>Failed to load prescription details</div>');
          }
        },
        error: function(xhr, status, error) {
          console.error('View prescription error:', error);
          console.error('Response:', xhr.responseText);
          $('#prescriptionDetailsContent').html('<div class="alert alert-danger"><i class="bx bx-error me-2"></i>Error loading prescription details. Please try again.</div>');
        }
      });
    });

    // Edit Prescription
    $(document).on('click', '.edit-prescription', function() {
      const prescriptionId = $(this).data('id');
      
      $.ajax({
        url: '../ajax/get_prescription_details.php',
        method: 'GET',
        data: { id: prescriptionId },
        success: function(response) {
          if (response.success && response.data) {
            const rx = response.data;
            
            document.getElementById('prescriptionForm').reset();
            
            $('#prescriptionForm input[name="id"]').remove();
            $('#prescriptionForm').append(`<input type="hidden" name="id" value="${rx.id}">`);
            
            $('#patientSelect').val(rx.patient_id);
            $('#doctorSelect').val(rx.doctor_id || '');
            $('#medicationSelect').val(rx.medication_id || '');
            $('#dosageInput').val(rx.dosage || '');
            $('#frequencyInput').val(rx.frequency || '');
            $('#durationInput').val(rx.duration || '');
            $('#quantityInput').val(rx.quantity || 1);
            $('#instructionsTextarea').val(rx.instructions || '');
            $('#statusSelect').val(rx.status || 'active');
            $('#appointmentSelect').val(rx.appointment_id || '');
            
            $('#prescriptionModalTitle').text('Edit Prescription');
            $('#prescriptionModal').modal('show');
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed',
              text: 'Failed to load prescription details'
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Edit prescription error:', error);
          console.error('Response:', xhr.responseText);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load prescription details'
          });
        }
      });
    });

    // Delete Prescription
    $(document).on('click', '.delete-prescription', function() {
      const prescriptionId = $(this).data('id');
      
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Deleting...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          $.ajax({
            url: '../ajax/delete_prescription.php',
            method: 'POST',
            data: { id: prescriptionId },
            success: function(response) {
              if (response.success) {
                prescriptionsTable.ajax.reload(null, false);
                Swal.fire({
                  icon: 'success',
                  title: 'Deleted!',
                  text: 'Prescription has been deleted.',
                  timer: 2000,
                  showConfirmButton: false
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Failed',
                  text: response.message || 'Failed to delete prescription'
                });
              }
            },
            error: function() {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Connection error. Please try again.'
              });
            }
          });
        }
      });
    });
  })(window.jQuery);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
