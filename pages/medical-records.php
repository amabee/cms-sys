<?php
$page_title = 'Medical Records';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor','patient']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Medical Records</div>
      <div class="card-body">
        <p class="text-muted">Clinical notes, diagnoses and visit history.</p>
        <div class="d-flex mb-2 justify-content-between">
          <div>
            <input id="recordsSearch" class="form-control d-inline-block" style="width:320px;" placeholder="Search records" />
          </div>
          <div>
            <?php if (in_array($user_type, ['admin','doctor'])): ?>
              <button id="addRecordBtn" class="btn btn-success">Add Record</button>
            <?php endif; ?>
          </div>
        </div>
        <table class="table table-striped" id="recordsTable" style="width:100%">
          <thead>
            <tr>
              <th>Patient</th>
              <th>Diagnosis</th>
              <th>Treatment</th>
              <th>Doctor</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>
  <!-- View Record Modal -->
        <div class="modal fade" id="viewRecordModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Medical Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="viewRecordBody">
                <div class="text-center py-4">Loading...</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit/Create Record Modal -->
        <div class="modal fade" id="editRecordModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Edit Medical Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="mrForm">
                  <input type="hidden" id="mr_id" name="id" />
                  <div class="row">
                    <div class="col-md-6 mb-3"><label>Patient</label><select id="mr_patient_id" name="patient_id" class="form-select"></select></div>
                    <div class="col-md-6 mb-3"><label>Doctor</label><select id="mr_doctor_id" name="doctor_id" class="form-select"></select></div>
                  </div>
                  <div class="row">
                    <div class="col-md-4 mb-3"><label>Visit Date</label><input type="date" id="mr_visit_date" name="visit_date" class="form-control" /></div>
                    <div class="col-md-8 mb-3"><label>Chief Complaint</label><input type="text" id="mr_chief_complaint" name="chief_complaint" class="form-control" /></div>
                  </div>
                  <div class="mb-3"><label>Diagnosis</label><textarea id="mr_diagnosis" name="diagnosis" class="form-control" rows="3"></textarea></div>
                  <div class="mb-3"><label>Treatment</label><textarea id="mr_treatment" name="treatment" class="form-control" rows="2"></textarea></div>
                  <div class="mb-3"><label>Prescription</label><textarea id="mr_prescription" name="prescription" class="form-control" rows="2"></textarea></div>
                  <div class="mb-3"><label>Vital Signs (JSON)</label><textarea id="mr_vitals" name="vital_signs" class="form-control" rows="2"></textarea></div>
                  <div class="mb-3"><label>Notes</label><textarea id="mr_notes" name="notes" class="form-control" rows="2"></textarea></div>
                  <div class="mb-3"><label>Attachment (optional)</label><input type="file" id="mr_attachment" name="attachment" class="form-control" /></div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="mrSaveBtn">Save</button>
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
    const table = $('#recordsTable').DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: '../ajax/get_medical_records.php',
        type: 'GET',
        data: function(d){ d.search = d.search || {}; d.search.value = $('#recordsSearch').val(); }
      },
      columns: [
        { data: 'patient_name' },
        { data: 'diagnosis', render: function(d){ return d ? (d.length>100? d.substring(0,100) + '...': d) : ''; } },
        { data: 'treatment', render: function(d){ return d ? (d.length>100? d.substring(0,100) + '...': d) : ''; } },
        { data: 'doctor_name' },
        { data: 'visit_date' },
        { data: null, orderable: false, render: function(data){ return `<button class="btn btn-sm btn-outline-primary view-record" data-id="${data.id}">View</button>`; } }
      ]
    });

    $('#recordsSearch').on('keypress', function(e){ if (e.key === 'Enter') table.ajax.reload(); });

    $('#recordsSearch').next().on('click', function(){ table.ajax.reload(); });

    $('#recordsTable').on('click', '.view-record', function(){
      var id = $(this).data('id');
      $('#viewRecordBody').html('<div class="text-center py-4">Loading...</div>');
      var modal = new bootstrap.Modal(document.getElementById('viewRecordModal'));
      modal.show();
      $.get('../ajax/get_medical_record.php', { id: id }, function(resp){
        if (resp && resp.success && resp.data) {
          var r = resp.data;
          var html = `
            <div class="row">
              <div class="col-md-6">
                <p><strong>Patient:</strong> ${r.patient_name || ''} (${r.patient_code||''})</p>
                <p><strong>Doctor:</strong> ${r.doctor_name || ''} (${r.doctor_code||''})</p>
                <p><strong>Date:</strong> ${r.visit_date || ''}</p>
                <p><strong>Chief complaint:</strong><br>${r.chief_complaint || ''}</p>
              </div>
              <div class="col-md-6">
                <p><strong>Diagnosis:</strong><br>${r.diagnosis || ''}</p>
                <p><strong>Treatment:</strong><br>${r.treatment || ''}</p>
                <p><strong>Prescription:</strong><br>${r.prescription || ''}</p>
              </div>
            </div>
            <div class="row mt-3"><div class="col-12"><strong>Notes:</strong><div class="border p-2">${r.notes || ''}</div></div></div>
          `;
          if (r.attachments && r.attachments.length) {
            html += '<div class="row mt-3"><div class="col-12"><strong>Attachments</strong><ul>';
            r.attachments.forEach(function(a){ html += `<li><a href="../ajax/download_medical_record_attachment.php?id=${a.id}">${a.original_name || a.file_path}</a> <small class="text-muted">(${a.uploaded_at})</small></li>`; });
            html += '</ul></div></div>';
          }
          $('#viewRecordBody').html(html);
        } else {
          $('#viewRecordBody').html('<div class="text-danger">Failed to load record</div>');
        }
      }, 'json').fail(function(){ $('#viewRecordBody').html('<div class="text-danger">Failed to load record</div>'); });
    });

    // Add record button - simple redirect to appointment page or open a modal (future)
    $('#addRecordBtn').on('click', function(){
      // open create modal
      $('#mr_id').val(''); $('#mrForm')[0].reset(); loadMRSelectors(); var m = new bootstrap.Modal(document.getElementById('editRecordModal')); m.show();
    });
    
    function loadMRSelectors(){
      $.getJSON('../ajax/get_patients.php', function(resp){ if(resp && resp.data){ var s=''; resp.data.forEach(function(p){ s+='<option value="'+p.id+'">'+p.first_name+' '+p.last_name+' ('+p.patient_id+')</option>'; }); $('#mr_patient_id').html(s); }});
      $.getJSON('../ajax/get_doctors.php', function(resp){ if(resp && resp.data){ var s=''; resp.data.forEach(function(d){ s+='<option value="'+d.id+'">'+d.first_name+' '+d.last_name+'</option>'; }); $('#mr_doctor_id').html(s); }});
    }

    $('#recordsTable').on('click', '.view-record', function(){ var id = $(this).data('id'); var modal = new bootstrap.Modal(document.getElementById('viewRecordModal')); modal.show(); $('#viewRecordBody').html('<div class="text-center py-4">Loading...</div>'); $.get('../ajax/get_medical_record.php',{id:id}, function(resp){ if(resp && resp.success){ var r=resp.data; var html = `<div class="row"><div class="col-md-6"><p><strong>Patient:</strong> ${r.patient_name || ''} (${r.patient_code||''})</p><p><strong>Doctor:</strong> ${r.doctor_name || ''} (${r.doctor_code||''})</p><p><strong>Date:</strong> ${r.visit_date || ''}</p><p><strong>Chief complaint:</strong><br>${r.chief_complaint || ''}</p></div><div class="col-md-6"><p><strong>Diagnosis:</strong><br>${r.diagnosis || ''}</p><p><strong>Treatment:</strong><br>${r.treatment || ''}</p><p><strong>Prescription:</strong><br>${r.prescription || ''}</p></div></div><div class="row mt-3"><div class="col-12"><strong>Notes:</strong><div class="border p-2">${r.notes || ''}</div></div></div>`; $('#viewRecordBody').html(html); } else { $('#viewRecordBody').html('<div class="text-danger">Failed to load record</div>'); } }, 'json'); });

    $('#recordsTable').on('click', '.edit-record', function(){ var id=$(this).data('id'); $('#mrForm')[0].reset(); loadMRSelectors(); $.getJSON('../ajax/get_medical_record.php',{id:id}, function(resp){ if(resp && resp.success && resp.data){ var r=resp.data; $('#mr_id').val(r.id); setTimeout(function(){ $('#mr_patient_id').val(r.patient_id); $('#mr_doctor_id').val(r.doctor_id); $('#mr_visit_date').val(r.visit_date); $('#mr_chief_complaint').val(r.chief_complaint); $('#mr_diagnosis').val(r.diagnosis); $('#mr_treatment').val(r.treatment); $('#mr_prescription').val(r.prescription); $('#mr_vitals').val(JSON.stringify(r.vital_signs||{})); $('#mr_notes').val(r.notes); },200); var m = new bootstrap.Modal(document.getElementById('editRecordModal')); m.show(); } else Swal.fire('Error','Failed to load record','error'); }); });

    $('#mrSaveBtn').on('click', function(){ var id=$('#mr_id').val(); var url = id ? '../ajax/update_medical_record.php' : '../ajax/create_medical_record.php'; var fd = new FormData(); var fields = $('#mrForm').serializeArray(); fields.forEach(function(f){ fd.append(f.name, f.value); }); var fileEl = document.getElementById('mr_attachment'); if(fileEl && fileEl.files && fileEl.files[0]) fd.append('attachment', fileEl.files[0]); $.ajax({ url: url, data: fd, type: 'POST', processData: false, contentType: false, dataType: 'json', success:function(resp){ if(resp && resp.success){ $('#editRecordModal').modal('hide'); table.ajax.reload(); Swal.fire('Saved','Record saved','success'); } else Swal.fire('Error', resp.message||'Save failed','error'); }, error:function(){ Swal.fire('Error','Server error','error'); } }); });
  });
</script>
