<?php
$page_title = 'Appointments';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor','secretary','receptionist','patient']);

// Get user context for customization
$is_doctor = ($user_type === 'doctor');
$page_subtitle = $is_doctor ? 'My Appointments' : 'All Appointments';

ob_start();
?>
<div class="row">
  <!-- Statistics Cards for Doctors -->
  <?php if ($is_doctor): ?>
  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="menu-icon tf-icons bx bx-calendar text-primary fs-4"></i>
          </div>
        </div>
        <span>Today's Appointments</span>
        <h3 class="card-title text-nowrap mb-1" id="todayCount">-</h3>
        <small class="text-primary fw-semibold">Today</small>
      </div>
    </div>
  </div>
  
  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="menu-icon tf-icons bx bx-time text-warning fs-4"></i>
          </div>
        </div>
        <span>Upcoming</span>
        <h3 class="card-title text-nowrap mb-1" id="upcomingCount">-</h3>
        <small class="text-warning fw-semibold">This Week</small>
      </div>
    </div>
  </div>
  
  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="menu-icon tf-icons bx bx-check text-success fs-4"></i>
          </div>
        </div>
        <span>Completed</span>
        <h3 class="card-title text-nowrap mb-1" id="completedCount">-</h3>
        <small class="text-success fw-semibold">This Month</small>
      </div>
    </div>
  </div>
  
  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="menu-icon tf-icons bx bx-x text-danger fs-4"></i>
          </div>
        </div>
        <span>Cancelled</span>
        <h3 class="card-title text-nowrap mb-1" id="cancelledCount">-</h3>
        <small class="text-danger fw-semibold">This Month</small>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0"><?= $page_subtitle ?></h5>
          <small class="text-muted">Manage and view appointments</small>
        </div>
        <div>
          <?php if ($is_doctor): ?>
          <button type="button" class="btn btn-outline-primary btn-sm me-2" id="filterTodayBtn">
            <i class="bx bx-calendar-check me-1"></i>Today Only
          </button>
          <button type="button" class="btn btn-outline-info btn-sm" id="refreshAppointmentsBtn">
            <i class="bx bx-refresh me-1"></i>Refresh
          </button>
          <?php else: ?>
          <button type="button" class="btn btn-primary btn-sm" id="newAppointmentBtn">
            <i class="bx bx-plus me-1"></i>New Appointment
          </button>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped" id="appointmentsTable" style="width:100%">
            <thead>
              <tr>
                <th>Appointment ID</th>
                <th>Patient</th>
                <?php if (!$is_doctor): ?><th>Doctor</th><?php endif; ?>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>

<script>
  $(function(){
    const isDoctor = <?= $is_doctor ? 'true' : 'false' ?>;
    
    // Define columns based on user role
    const columns = [
      { data: 'appointment_id' },
      { data: 'patient', render: function(data, type, row){ return `<strong>${row.patient_code||''}</strong><br/>${data||''}`; } }
    ];
    
    // Add doctor column only for non-doctors
    if (!isDoctor) {
      columns.push({ data: 'doctor', render: function(data, type, row){ return `<small>${row.doctor_code||''}</small><br/>${data||''}`; } });
    }
    
    // Add remaining columns
    columns.push(
      { data: 'appointment_date', render: function(data, type, row) { return formatDate(data); } },
      { data: 'appointment_time', render: function(data, type, row) { return formatTime(data); } },
      { data: 'status', render: function(data, type, row) { return getStatusBadge(data); } },
      { 
        data: null, 
        orderable: false, 
        render: function(data, type, row) {
          let actions = `<button class="btn btn-sm btn-outline-primary view-appointment" data-id="${row.id}" title="View Details">
            <i class="bx bx-show"></i>
          </button>`;
          
          if (isDoctor) {
            actions += ` <button class="btn btn-sm btn-outline-success add-to-queue" data-patient-id="${row.patient_id}" data-appointment-id="${row.id}" title="Add to Queue">
              <i class="bx bx-plus"></i>
            </button>`;
          }
          
          return `<div class="btn-group">${actions}</div>`;
        }
      }
    );

    const table = $('#appointmentsTable').DataTable({
      serverSide: true,
      processing: true,
      ajax: { url: '../ajax/get_appointments.php', type: 'GET' },
      columns: columns,
      order: [[isDoctor ? 2 : 3, 'desc']], // Sort by date column
      pageLength: 25,
      responsive: true
    });
    
    // Load statistics for doctors
    if (isDoctor) {
      loadAppointmentStats();
    }

    // Create new appointment modal
    $('body').append(`
      <div class="modal fade" id="newAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bx bx-plus-circle me-2"></i>New Appointment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <form id="newAppointmentForm">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Patient *</label>
                    <select class="form-select" name="patient_id" id="appointmentPatientSelect" required>
                      <option value="">Select patient...</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Doctor *</label>
                    <select class="form-select" name="doctor_id" id="appointmentDoctorSelect" required>
                      <option value="">Select doctor...</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Date *</label>
                    <input type="date" class="form-control" name="appointment_date" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Time *</label>
                    <input type="time" class="form-control" name="appointment_time" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Reason for Visit</label>
                    <textarea class="form-control" name="reason" rows="3" placeholder="Describe reason for appointment..."></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                      <option value="scheduled">Scheduled</option>
                      <option value="confirmed">Confirmed</option>
                    </select>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-primary" id="submitNewAppointment">Create Appointment</button>
            </div>
          </div>
        </div>
      </div>
    `);

    // View appointment modal
    $('body').append(`
      <div class="modal fade" id="viewAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="viewAppointmentBody">Loading...</div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button class="btn btn-primary" id="saveAppointmentChanges">Save</button>
            </div>
          </div>
        </div>
      </div>
    `);

    // New appointment button handler
    $('#newAppointmentBtn').on('click', function() {
      // Load patients
      $.get('../ajax/get_patients.php', { per_page: 1000 }, function(res) {
        if (res && res.data) {
          const options = res.data.map(p => 
            `<option value="${p.id}">${p.first_name} ${p.last_name} - ${p.patient_id}</option>`
          ).join('');
          $('#appointmentPatientSelect').html('<option value="">Select patient...</option>' + options);
        }
      });
      
      // Load doctors
      $.get('../ajax/get_doctors.php', function(res) {
        if (res && res.success && res.data) {
          const options = res.data.map(d => 
            `<option value="${d.id}">${d.first_name} ${d.last_name} - ${d.specialization || ''}</option>`
          ).join('');
          $('#appointmentDoctorSelect').html('<option value="">Select doctor...</option>' + options);
        }
      });
      
      // Set default date to today
      const today = new Date().toISOString().split('T')[0];
      $('input[name="appointment_date"]').val(today);
      
      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('newAppointmentModal'));
      modal.show();
    });

    // Submit new appointment
    $(document).on('click', '#submitNewAppointment', function() {
      const formData = $('#newAppointmentForm').serialize();
      
      $.post('../ajax/create_appointment.php', formData)
        .done(function(response) {
          if (response.success) {
            $('#newAppointmentModal').modal('hide');
            table.ajax.reload();
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: 'Appointment created successfully!',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed',
              text: response.message || 'Failed to create appointment'
            });
          }
        })
        .fail(function() {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Connection error. Please try again.'
          });
        });
    });

    // view appointment
    $('#appointmentsTable').on('click', '.view-appointment', function(){
      var id = $(this).data('id');
      var modal = new bootstrap.Modal(document.getElementById('viewAppointmentModal'));
      $('#viewAppointmentBody').html('Loading...'); modal.show();
      $.get('../ajax/get_appointment.php',{id:id}, function(res){
        if (res && res.success) {
          var a = res.data;
            var html = `
            <form id="editAppointmentForm">
              <input type="hidden" name="id" value="${a.id||''}">
              <div class="row mb-3">
                <div class="col-md-6">
                  <div class="mb-2"><strong>Appointment ID:</strong> <span class="text-primary">${a.appointment_id||''}</span></div>
                  <div class="mb-2"><strong>Patient:</strong> ${a.patient_code||''} - ${a.p_first||''} ${a.p_last||''}</div>
                  ${!isDoctor ? `<div class="mb-2"><strong>Doctor:</strong> ${a.doctor_code||''} - ${a.d_first||''} ${a.d_last||''}</div>` : ''}
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input name="appointment_date" type="date" class="form-control" value="${a.appointment_date||''}" ${isDoctor ? '' : ''}>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Time</label>
                    <input name="appointment_time" type="time" class="form-control" value="${a.appointment_time||''}" ${isDoctor ? '' : ''}>
                  </div>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Status</label>
                  <select name="status" class="form-select">
                    <option ${a.status==='scheduled'?'selected':''} value="scheduled">Scheduled</option>
                    <option ${a.status==='in_progress'?'selected':''} value="in_progress">In Progress</option>
                    <option ${a.status==='completed'?'selected':''} value="completed">Completed</option>
                    <option ${a.status==='cancelled'?'selected':''} value="cancelled">Cancelled</option>
                    <option ${a.status==='no_show'?'selected':''} value="no_show">No Show</option>
                  </select>
                </div>
                ${isDoctor ? `
                <div class="col-md-6">
                  <label class="form-label">Quick Actions</label>
                  <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="addPatientToQueue('${a.patient_id}', '${a.id}')">
                      <i class="bx bx-plus me-1"></i>Add to Queue
                    </button>
                  </div>
                </div>
                ` : ''}
              </div>
              
              <div class="mb-3">
                <label class="form-label">Reason/Notes</label>
                <textarea name="reason" class="form-control" rows="3">${a.reason||''}</textarea>
              </div>
              
              ${isDoctor ? `
              <div class="mb-3">
                <label class="form-label">Doctor Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Internal notes for this appointment...">${a.notes||''}</textarea>
              </div>
              ` : ''}
            </form>
          `;
          $('#viewAppointmentBody').html(html);
        } else {
          $('#viewAppointmentBody').html('<div class="text-danger">Failed to load</div>');
        }
      }, 'json');
    });

    // save appointment updates
    $(document).on('click', '#saveAppointmentChanges', function(){
      var form = $('#editAppointmentForm');
      $.post('../ajax/update_appointment.php', form.serialize(), function(res){
        if (res && res.success) {
          $('#viewAppointmentModal').modal('hide');
          table.ajax.reload();
          if (isDoctor) loadAppointmentStats();
          Swal.fire({ icon: 'success', title: 'Saved' });
        } else {
          Swal.fire({ icon: 'error', title: 'Save failed', text: res.message || 'Failed' });
        }
      }, 'json').fail(function(){ Swal.fire({ icon: 'error', title: 'Save failed' }); });
    });

    // Additional event handlers for doctor features
    if (isDoctor) {
      $('#filterTodayBtn').on('click', function() {
        const btn = $(this);
        if (btn.hasClass('active')) {
          // Remove filter
          table.search('').draw();
          btn.removeClass('active btn-primary').addClass('btn-outline-primary').html('<i class="bx bx-calendar-check me-1"></i>Today Only');
        } else {
          // Apply today filter
          const today = new Date().toISOString().split('T')[0];
          table.search(today).draw();
          btn.removeClass('btn-outline-primary').addClass('active btn-primary').html('<i class="bx bx-calendar-x me-1"></i>Show All');
        }
      });
      
      $('#refreshAppointmentsBtn').on('click', function() {
        table.ajax.reload();
        loadAppointmentStats();
      });

      // Add to queue functionality
      $(document).on('click', '.add-to-queue', function() {
        const patientId = $(this).data('patient-id');
        const appointmentId = $(this).data('appointment-id');
        
        $.post('../ajax/add_to_queue.php', {
          patient_id: patientId,
          appointment_id: appointmentId
        })
        .done(function(response) {
          if (response.success) {
            Swal.fire({ icon: 'success', title: 'Added to Queue', text: 'Patient has been added to your queue' });
          } else {
            Swal.fire({ icon: 'error', title: 'Failed', text: response.message || 'Failed to add to queue' });
          }
        })
        .fail(function() {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Connection error' });
        });
      });
    }

    // Utility functions
    function formatDate(dateStr) {
      if (!dateStr) return 'N/A';
      const date = new Date(dateStr);
      return date.toLocaleDateString();
    }

    function formatTime(timeStr) {
      if (!timeStr) return 'N/A';
      return timeStr;
    }

    function getStatusBadge(status) {
      const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'in_progress': '<span class="badge bg-warning">In Progress</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>',
        'no_show': '<span class="badge bg-secondary">No Show</span>'
      };
      return badges[status] || '<span class="badge bg-light text-dark">' + status + '</span>';
    }

    function loadAppointmentStats() {
      // Load statistics for doctor dashboard cards
      $.get('../ajax/get_doctor_appointments.php', { stats: true })
        .done(function(response) {
          if (response.success && response.stats) {
            $('#todayCount').text(response.stats.today || 0);
            $('#upcomingCount').text(response.stats.upcoming || 0);
            $('#completedCount').text(response.stats.completed || 0);
            $('#cancelledCount').text(response.stats.cancelled || 0);
          }
        });
    }
  });

  // Global function for adding patient to queue from modal
  function addPatientToQueue(patientId, appointmentId) {
    $.post('../ajax/add_to_queue.php', {
      patient_id: patientId,
      appointment_id: appointmentId
    })
    .done(function(response) {
      if (response.success) {
        $('#viewAppointmentModal').modal('hide');
        Swal.fire({ 
          icon: 'success', 
          title: 'Added to Queue', 
          text: 'Patient has been added to your queue',
          timer: 2000,
          showConfirmButton: false
        });
      } else {
        Swal.fire({ icon: 'error', title: 'Failed', text: response.message || 'Failed to add to queue' });
      }
    })
    .fail(function() {
      Swal.fire({ icon: 'error', title: 'Error', text: 'Connection error' });
    });
  }
</script>

