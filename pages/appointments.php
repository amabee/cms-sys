<?php
$page_title = 'Appointments';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor','secretary','receptionist','patient']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Appointments</div>
      <div class="card-body">
        <p class="text-muted">Manage and view appointments.</p>
        <table class="table table-striped" id="appointmentsTable" style="width:100%">
          <thead><tr><th>Appointment ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
        </table>
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
    const table = $('#appointmentsTable').DataTable({
      serverSide: true,
      processing: true,
      ajax: { url: '../ajax/get_appointments.php', type: 'GET' },
      columns: [
        { data: 'appointment_id' },
  { data: 'patient', render: function(data, type, row){ return `<strong>${row.patient_code||''}</strong><br/>${data||''}`; } },
  { data: 'doctor', render: function(data, type, row){ return `<small>${row.doctor_code||''}</small><br/>${data||''}`; } },
        { data: 'appointment_date' },
        { data: 'appointment_time' },
        { data: 'status' },
        { data: null, orderable:false, render: function(data, type, row){ return `<button class="btn btn-sm btn-outline-primary view-appointment" data-id="${row.id}">View</button>`; } }
      ]
    });

    // add appointment modal
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
              <div class="mb-2"><strong>Appointment ID:</strong> ${a.appointment_id||''}</div>
              <div class="mb-2"><strong>Patient:</strong> ${a.patient_code||''} - ${a.p_first||''} ${a.p_last||''}</div>
              <div class="mb-2"><strong>Doctor:</strong> ${a.doctor_code||''} - ${a.d_first||''} ${a.d_last||''}</div>
              <div class="mb-3"><label>Date</label><input name="appointment_date" type="date" class="form-control" value="${a.appointment_date||''}"></div>
              <div class="mb-3"><label>Time</label><input name="appointment_time" type="time" class="form-control" value="${a.appointment_time||''}"></div>
              <div class="mb-3"><label>Status</label>
                <select name="status" class="form-select">
                  <option ${a.status==='scheduled'?'selected':''} value="scheduled">Scheduled</option>
                  <option ${a.status==='in_progress'?'selected':''} value="in_progress">In Progress</option>
                  <option ${a.status==='completed'?'selected':''} value="completed">Completed</option>
                  <option ${a.status==='cancelled'?'selected':''} value="cancelled">Cancelled</option>
                </select>
              </div>
              <div class="mb-3"><label>Reason</label><textarea name="reason" class="form-control">${a.reason||''}</textarea></div>
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
          Swal.fire({ icon: 'success', title: 'Saved' });
        } else {
          Swal.fire({ icon: 'error', title: 'Save failed', text: res.message || 'Failed' });
        }
      }, 'json').fail(function(){ Swal.fire({ icon: 'error', title: 'Save failed' }); });
    });
  });
</script>

