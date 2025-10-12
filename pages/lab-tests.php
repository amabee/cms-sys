<?php
$page_title = 'Lab Tests';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor','secretary','receptionist']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Lab Tests</div>
      <div class="card-body">
        <p class="text-muted">Ordered lab tests and results management.</p>
        <div class="d-flex mb-2 justify-content-between">
          <div>
            <input id="labTestsSearch" class="form-control d-inline-block" style="width:320px;" placeholder="Search lab tests" />
          </div>
          <div>
            <button id="addLabTestBtn" class="btn btn-success">Add Lab Test</button>
          </div>
        </div>
        <table class="table table-striped" id="labTestsTable" style="width:100%">
          <thead>
            <tr>
              <th>Patient</th>
              <th>Test Name</th>
              <th>Category</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>

        <!-- View Modal -->
        <div class="modal fade" id="viewLabModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Lab Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="viewLabBody">Loading...</div>
              <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
            </div>
          </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" id="editLabModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Add Lab Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="editLabForm">
                  <input type="hidden" name="id" id="lt_id">
                  <div class="mb-3"><label class="form-label">Patient</label><select name="patient_id" id="lt_patient_id" class="form-select" required></select></div>
                  <div class="mb-3"><label class="form-label">Doctor</label><select name="doctor_id" id="lt_doctor_id" class="form-select"></select></div>
                  <div class="mb-3"><label class="form-label">Test name</label><input name="test_name" id="lt_test_name" class="form-control" required></div>
                  <div class="mb-3"><label class="form-label">Category</label><input name="test_category" id="lt_test_category" class="form-control"></div>
                  <div class="mb-3"><label class="form-label">Date</label><input name="test_date" id="lt_test_date" type="date" class="form-control" required></div>
                  <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" id="lt_notes" class="form-control" rows="3"></textarea></div>
                  <div class="mb-3"><label class="form-label">Report file (optional)</label><input type="file" name="report_file" id="lt_report_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg"></div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="saveLabTest" type="button" class="btn btn-primary">Save</button>
              </div>
            </div>
          </div>
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
  const USER_TYPE = '<?php echo $user_type ?? ''; ?>';

  const table = $('#labTestsTable').DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: '../ajax/get_lab_tests.php',
        type: 'GET',
        data: function(d){ d.search = d.search || {}; d.search.value = $('#labTestsSearch').val(); }
      },
      columns: [
        { data: 'patient_name' },
        { data: 'test_name' },
        { data: 'test_category' },
        { data: 'test_date' },
        { data: 'status' },
        { data: null, orderable: false, render: function(data){
            var actions = `<button class="btn btn-sm btn-outline-primary view-lab" data-id="${data.id}">View</button> `;
            // sample_collected & in_progress allowed for receptionist/secretary/admin/doctor
            if (['receptionist','secretary','admin','doctor'].indexOf(USER_TYPE) !== -1) {
              actions += ` <button class="btn btn-sm btn-outline-info mark-sample" data-id="${data.id}">Sample Collected</button>`;
              actions += ` <button class="btn btn-sm btn-outline-warning mark-inprogress" data-id="${data.id}">In Progress</button>`;
            }
            // completed and edit/delete - admin & doctor only
            if (['admin','doctor'].indexOf(USER_TYPE) !== -1) {
              actions += ` <button class="btn btn-sm btn-outline-success mark-completed" data-id="${data.id}">Mark Completed</button>`;
              actions += ` <button class="btn btn-sm btn-outline-secondary edit-lab" data-id="${data.id}">Edit</button>`;
              actions += ` <button class="btn btn-sm btn-outline-danger delete-lab" data-id="${data.id}">Delete</button>`;
            }
            return actions;
        } }
      ]
    });

    $('#labTestsSearch').on('keypress', function(e){ if (e.key === 'Enter') table.ajax.reload(); });

    // populate patient select
    function loadPatients(selectEl, selected){
      $.get('../ajax/get_patients.php', { length: 100 }, function(resp){
        if (resp && resp.data) {
          selectEl.empty(); selectEl.append('<option value="">Select patient</option>');
          resp.data.forEach(function(p){ selectEl.append(`<option value="${p.id}">${p.first_name} ${p.last_name} (${p.patient_id})</option>`); });
          if (selected) selectEl.val(selected);
        }
      }, 'json');
    }

    // populate doctors select
    function loadDoctors(selectEl, selected){
      $.get('../ajax/get_doctors.php', function(r){
        selectEl.empty(); selectEl.append('<option value="">Select doctor</option>');
        if (r && r.success && r.data) {
          r.data.forEach(function(d){ selectEl.append(`<option value="${d.id}">${d.name} (${d.doctor_id})</option>`); });
          if (selected) selectEl.val(selected);
        }
      }, 'json');
    }

    $('#addLabTestBtn').on('click', function(){
      $('#editLabForm')[0].reset(); $('#lt_id').val(''); $('#editLabModal .modal-title').text('Add Lab Test');
      loadPatients($('#lt_patient_id'));
      loadDoctors($('#lt_doctor_id'));
      var modal = new bootstrap.Modal(document.getElementById('editLabModal'));
      modal.show();
    });

    $('#labTestsTable').on('click', '.view-lab', function(){
      var id = $(this).data('id'); $('#viewLabBody').html('Loading...');
      var modal = new bootstrap.Modal(document.getElementById('viewLabModal'));
      modal.show();
      $.get('../ajax/get_lab_test.php', { id: id }, function(resp){
        if (resp && resp.success && resp.data) {
          var r = resp.data;
          var html = `<p><strong>Patient:</strong> ${r.patient_name || ''} (${r.patient_code||''})</p><p><strong>Test:</strong> ${r.test_name||''}</p><p><strong>Category:</strong> ${r.test_category||''}</p><p><strong>Date:</strong> ${r.test_date||''}</p><p><strong>Status:</strong> ${r.status||''}</p><hr><p><strong>Results:</strong><br>${r.results||''}</p><p><strong>Notes:</strong><br>${r.notes||''}</p>`;
          $('#viewLabBody').html(html);
        } else $('#viewLabBody').html('<div class="text-danger">Failed to load</div>');
      }, 'json');
    });

    $('#labTestsTable').on('click', '.edit-lab', function(){
      var id = $(this).data('id');
      $('#editLabForm')[0].reset(); $('#lt_id').val(id); $('#editLabModal .modal-title').text('Edit Lab Test');
      $.get('../ajax/get_lab_test.php', { id: id }, function(resp){
        if (resp && resp.success && resp.data) {
          var r = resp.data;
          $('#lt_test_name').val(r.test_name||'');
          $('#lt_test_category').val(r.test_category||'');
          $('#lt_test_date').val(r.test_date||'');
          $('#lt_notes').val(r.notes||'');
          loadPatients($('#lt_patient_id'), r.patient_id);
          loadDoctors($('#lt_doctor_id'), r.doctor_id);
          var modal = new bootstrap.Modal(document.getElementById('editLabModal'));
          modal.show();
        } else {
          Swal.fire({ icon: 'error', title: 'Load failed' });
        }
      }, 'json');
    });

    // status actions
    function postStatus(id, status, extra){
      var payload = { id: id, status: status };
      if (extra) Object.assign(payload, extra);
      $.post('../ajax/update_lab_test.php', payload, function(resp){ if (resp && resp.success) { table.ajax.reload(); Swal.fire({ icon: 'success', title: 'Updated' }); } else Swal.fire({ icon: 'error', title: 'Update failed', text: resp.message||'' }); }, 'json');
    }

    $('#labTestsTable').on('click', '.mark-sample', function(){
      var id = $(this).data('id');
      var now = new Date();
      var fmt = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0') + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
      postStatus(id, 'sample_collected', { sample_collected_date: fmt });
    });

    $('#labTestsTable').on('click', '.mark-inprogress', function(){
      var id = $(this).data('id'); postStatus(id, 'in_progress');
    });

    $('#labTestsTable').on('click', '.mark-completed', function(){
      var id = $(this).data('id');
      // prompt for confirmation and optional results
      Swal.fire({ title: 'Mark completed?', text: 'You should attach results before completing', icon: 'question', showCancelButton:true, input:'textarea', inputPlaceholder:'Add short results (optional)'}).then(function(res){
        if (res.isConfirmed) { postStatus(id, 'completed', { results: res.value || '' }); }
      });
    });

    $('#labTestsTable').on('click', '.delete-lab', function(){
      var id = $(this).data('id');
      Swal.fire({ title: 'Confirm delete', text: 'This will remove the lab test', icon: 'warning', showCancelButton:true }).then(function(res){
        if (res.isConfirmed) {
          $.post('../ajax/delete_lab_test.php', { id: id }, function(resp){ if (resp && resp.success) { table.ajax.reload(); Swal.fire({ icon: 'success', title: 'Deleted' }); } else Swal.fire({ icon: 'error', title: 'Delete failed', text: resp.message||'' }); }, 'json');
        }
      });
    });

    $('#saveLabTest').on('click', function(){
      var form = $('#editLabForm');
      var id = $('#lt_id').val();
      var url = id ? '../ajax/update_lab_test.php' : '../ajax/create_lab_test.php';
      var fd = new FormData();
      // append form fields
      var fields = form.serializeArray();
      fields.forEach(function(f){ fd.append(f.name, f.value); });
      var fileEl = document.getElementById('lt_report_file');
      if (fileEl && fileEl.files && fileEl.files[0]) fd.append('report_file', fileEl.files[0]);
      $.ajax({ url: url, data: fd, type: 'POST', processData: false, contentType: false, dataType: 'json', success: function(resp){ if (resp && resp.success) { $('#editLabModal').modal('hide'); table.ajax.reload(); Swal.fire({ icon: 'success', title: 'Saved' }); } else Swal.fire({ icon: 'error', title: 'Save failed', text: resp.message||'' }); }, error: function(){ Swal.fire({ icon: 'error', title: 'Save failed', text: 'Server error' }); } });
    });
  });
</script>
