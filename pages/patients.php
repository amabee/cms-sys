<?php
$page_title = 'Patients';
$additional_css = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'
];
$additional_js = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js'
];
// add Select2 for searchable dropdown
$additional_js[] = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
$additional_css[] = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin', 'doctor', 'secretary', 'receptionist']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Patients</div>
      <div class="card-body">
        <p class="text-muted">List of registered patients. Use the buttons to add or import patient data.</p>
        <div class="d-flex mb-3 justify-content-between">
          <div>
            <input id="patientsSearch" class="form-control me-2 d-inline-block" style="width:320px;"
              placeholder="Search patients by name, phone or ID" />
            <button id="patientsSearchBtn" class="btn btn-primary">Search</button>
          </div>
          <div>
            <button id="addPatientBtn" class="btn btn-success" data-bs-toggle="modal"
              data-bs-target="#addPatientModal">Add Patient</button>
          </div>
        </div>
        <table class="table table-hover" id="patientsTable" style="width:100%">
          <thead>
            <tr>
              <th>Patient ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Date of Birth</th>
              <th>Last Visit</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>

        <!-- Add Patient Modal -->
        <div class="modal fade" id="addPatientModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Add Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="addPatientForm">
                  <div class="mb-3"><label class="form-label">First name</label><input name="first_name"
                      class="form-control" required></div>
                  <div class="mb-3"><label class="form-label">Last name</label><input name="last_name"
                      class="form-control" required></div>
                  <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email"
                      class="form-control"></div>
                  <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control">
                  </div>
                  <div class="mb-3"><label class="form-label">Date of birth</label><input name="date_of_birth"
                      type="date" class="form-control"></div>
                  <div class="mb-3"><label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                      <option value="">Select</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                  <div class="mb-3"><label class="form-label">Address</label><textarea name="address"
                      class="form-control" rows="2"></textarea></div>
                  <div class="mb-3"><label class="form-label">Emergency contact name</label><input
                      name="emergency_contact_name" class="form-control"></div>
                  <div class="mb-3"><label class="form-label">Emergency contact phone</label><input
                      name="emergency_contact_phone" class="form-control"></div>
                  <div class="mb-3"><label class="form-label">Blood group</label><input name="blood_group"
                      class="form-control"></div>
                  <div class="mb-3"><label class="form-label">Allergies</label><input name="allergies"
                      class="form-control"></div>
                  <div class="mb-3"><label class="form-label">Insurance info</label><input name="insurance_info"
                      class="form-control"></div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="submitAddPatient" type="button" class="btn btn-primary">Save</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    // initialize DataTable with server-side processing
    const table = $('#patientsTable').DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: '../ajax/get_patients.php',
        type: 'GET',
        data: function (d) {
          // include our custom search input
          d.search = d.search || {};
          d.search.value = $('#patientsSearch').val();
        }
      },
      columns: [
        { data: 'patient_id' },
        { data: 'first_name', render: function (data, type, row) { return (row.first_name || '') + ' ' + (row.last_name || ''); } },
        { data: 'email' },
        { data: 'phone' },
        { data: 'date_of_birth' },
        { 
          data: 'last_visit_date', 
          render: function (data, type, row) { 
            if (!data) return '<span class="text-muted">Never</span>';
            const date = new Date(data);
            const today = new Date();
            const diffTime = Math.abs(today - date);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let timeAgo = '';
            if (diffDays === 0) {
              timeAgo = 'Today';
            } else if (diffDays === 1) {
              timeAgo = 'Yesterday';
            } else if (diffDays <= 7) {
              timeAgo = `${diffDays} days ago`;
            } else if (diffDays <= 30) {
              timeAgo = `${Math.floor(diffDays/7)} weeks ago`;
            } else if (diffDays <= 365) {
              timeAgo = `${Math.floor(diffDays/30)} months ago`;
            } else {
              timeAgo = `${Math.floor(diffDays/365)} years ago`;
            }
            
            return `<div>
              <div class="fw-medium">${date.toLocaleDateString()}</div>
              <small class="text-muted">${timeAgo}</small>
            </div>`;
          }
        },
        { 
          data: null, 
          orderable: false, 
          render: function (data) { 
            return `<div class="btn-group">
              <button class="btn btn-sm btn-outline-primary view-patient" data-id="${data.id}" title="View Details">
                <i class="bx bx-show"></i>
              </button>
              <button class="btn btn-sm btn-outline-success add-to-queue-btn" data-id="${data.id}" title="Add to Queue">
                <i class="bx bx-plus"></i>
              </button>
            </div>`; 
          } 
        }
      ]
    });

    $('#patientsSearchBtn').on('click', function () { table.ajax.reload(); });
    $('#patientsSearch').on('keypress', function (e) { if (e.key === 'Enter') { table.ajax.reload(); } });
    
    // Add to queue functionality for receptionist
    $('#patientsTable').on('click', '.add-to-queue-btn', function () {
      const patientId = $(this).data('id');
      
      $.post('../ajax/add_to_queue.php', {
        patient_id: patientId
      })
      .done(function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Added to Queue',
            text: 'Patient has been added to the queue successfully.',
            timer: 2000,
            showConfirmButton: false
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Failed',
            text: response.message || 'Failed to add patient to queue'
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

    // submit new patient
    $('#submitAddPatient').on('click', function () {
      const form = $('#addPatientForm');
      $.post('../ajax/create_patient.php', form.serialize(), function (resp) {
        if (resp && resp.success) {
          $('#addPatientModal').modal('hide');
          table.ajax.reload();
          Swal.fire({
            icon: 'success',
            title: 'Patient added',
            text: 'The patient was successfully created.'
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Patient creation failed',
            text: resp.message || 'Failed to create patient.'
          });
        }
      }, 'json');
    });

    // Patient view modal
    $('body').append(`
      <div class="modal fade" id="viewPatientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Patient details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
              <div class="modal-body" id="viewPatientBody">
                <div class="text-center py-4">Loading...</div>
              </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-outline-primary" id="editPatientBtn">Edit</button>
              <button type="button" class="btn btn-primary d-none" id="savePatientBtn">Save</button>
              <button type="button" class="btn btn-secondary d-none" id="cancelEditPatient">Cancel</button>
              <button type="button" class="btn btn-primary" id="newAppointmentFromPatient">New Appointment</button>
            </div>
          </div>
        </div>
      </div>
    `);

    // open view modal and load data
    $('#patientsTable').on('click', '.view-patient', function () {
      var id = $(this).data('id');
      var modal = new bootstrap.Modal(document.getElementById('viewPatientModal'));
      $('#viewPatientBody').html('<div class="text-center py-4">Loading...</div>');
      modal.show();
      $.get('../ajax/get_patient.php', { id: id }, function (res) {
          if (res && res.success && res.data) {
          var p = res.data;
          var html = `
            <div class="row">
            <div class="col-md-6">
              <div class="card mb-3">
                <div class="card-body">
                  <h6 class="card-title">Basic</h6>
                  <p class="mb-1"><strong>Patient ID:</strong> ${p.patient_id||''}</p>
                  <p class="mb-1"><strong>Name:</strong> ${p.first_name||''} ${p.last_name||''}</p>
                  <p class="mb-1"><strong>Email:</strong> ${p.email||''}</p>
                  <p class="mb-1"><strong>Phone:</strong> ${p.phone||''}</p>
                  <p class="mb-0"><strong>DOB:</strong> ${p.date_of_birth||''} <span class="text-muted">${p.gender?(' | ' + p.gender):''}</span></p>
                </div>
              </div>
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Contact</h6>
                  <p class="mb-1"><strong>Address:</strong> ${p.address||''}</p>
                  <p class="mb-0"><strong>Emergency:</strong> ${p.emergency_contact_name||''} ${p.emergency_contact_phone?('(' + p.emergency_contact_phone + ')'):''}</p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card mb-3">
                <div class="card-body">
                  <h6 class="card-title">Medical</h6>
                  <p class="mb-1"><strong>Blood group:</strong> ${p.blood_group||''}</p>
                  <p class="mb-1"><strong>Allergies:</strong> ${p.allergies||''}</p>
                  <p class="mb-0"><strong>Insurance:</strong> ${p.insurance_info||''}</p>
                </div>
              </div>
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Visit History</h6>
                  <div id="visitHistoryContainer">
                    <div class="text-center py-2 text-muted">
                      <small>Loading visit history...</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Medical Notes</h6>
                  <p class="mb-0 small text-muted">${p.medical_history || 'No medical history recorded'}</p>
                </div>
              </div>
            </div>
          </div>
          `;
          $('#viewPatientBody').html(html);
          
          // Load visit history
          loadPatientVisitHistory(p.id);
          
          // store current patient id on modal for new appointment
          $('#viewPatientModal').data('patient-id', p.id);
          // keep current patient in JS for editing
          window.currentPatient = p;
          // ensure footer state
          $('#editPatientBtn').removeClass('d-none');
          $('#savePatientBtn').addClass('d-none');
          $('#cancelEditPatient').addClass('d-none');
        } else {
          $('#viewPatientBody').html('<div class="text-danger">Failed to load patient</div>');
        }
      }, 'json').fail(function(){
        $('#viewPatientBody').html('<div class="text-danger">Failed to load patient</div>');
      });
    });

    // New Appointment modal (basic)
    $('body').append(`
      <div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">New Appointment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="newAppointmentForm">
                <input type="hidden" name="patient_id" id="na_patient_id">
                <div class="mb-3"><label class="form-label">Patient</label><input id="na_patient_name" class="form-control" readonly></div>
                <div class="mb-3"><label class="form-label">Date</label><input name="appointment_date" type="date" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Time</label><input name="appointment_time" type="time" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Doctor</label>
                  <select name="doctor_id" id="na_doctor_id" class="form-select" required>
                    <option value="">Select doctor</option>
                  </select>
                </div>
                <div class="mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button id="submitNewAppointment" type="button" class="btn btn-primary">Create</button>
            </div>
          </div>
        </div>
      </div>
    `);

    // wire New Appointment button from view modal
    $(document).on('click', '#newAppointmentFromPatient', function(){
      var pid = $('#viewPatientModal').data('patient-id');
      if (!pid) return;
      // load patient name into appointment modal
      var name = $('#viewPatientBody').find('p:contains("Name:")').text().replace('Name:','').trim();
      $('#na_patient_id').val(pid);
      $('#na_patient_name').val(name);
      var modal = new bootstrap.Modal(document.getElementById('newAppointmentModal'));
      // populate doctors into select (Select2)
      $.get('../ajax/get_doctors.php', function(r){
        var s = $('#na_doctor_id');
        s.empty(); s.append('<option value="">Select doctor</option>');
        if (r && r.success) {
          r.data.forEach(function(d){ s.append(`<option value="${d.id}" data-specialization="${d.specialization||''}">${d.name} (${d.doctor_id}) - ${d.specialization||''}</option>`); });
        }
        // init select2 if not already
        if (!s.hasClass('select2-hidden-accessible')) s.select2({ dropdownParent: $('#newAppointmentModal') });
        modal.show();
      }, 'json').fail(function(){ modal.show(); });
    });

    // Edit patient flow
    $(document).on('click', '#editPatientBtn', function(){
      var p = window.currentPatient || {};
      var form = `
        <form id="editPatientForm">
          <input type="hidden" name="id" value="${p.id||''}">
          <div class="mb-3"><label class="form-label">First name</label><input name="first_name" class="form-control" required value="${p.first_name||''}"></div>
          <div class="mb-3"><label class="form-label">Last name</label><input name="last_name" class="form-control" required value="${p.last_name||''}"></div>
          <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="${p.email||''}"></div>
          <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="${p.phone||''}"></div>
          <div class="mb-3"><label class="form-label">Date of birth</label><input name="date_of_birth" type="date" class="form-control" value="${p.date_of_birth||''}"></div>
          <div class="mb-3"><label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">Select</option>
              <option value="male" ${p.gender==='male'?'selected':''}>Male</option>
              <option value="female" ${p.gender==='female'?'selected':''}>Female</option>
              <option value="other" ${p.gender==='other'?'selected':''}>Other</option>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2">${p.address||''}</textarea></div>
          <div class="mb-3"><label class="form-label">Emergency contact name</label><input name="emergency_contact_name" class="form-control" value="${p.emergency_contact_name||''}"></div>
          <div class="mb-3"><label class="form-label">Emergency contact phone</label><input name="emergency_contact_phone" class="form-control" value="${p.emergency_contact_phone||''}"></div>
          <div class="mb-3"><label class="form-label">Blood group</label><input name="blood_group" class="form-control" value="${p.blood_group||''}"></div>
          <div class="mb-3"><label class="form-label">Allergies</label><input name="allergies" class="form-control" value="${p.allergies||''}"></div>
          <div class="mb-3"><label class="form-label">Insurance info</label><input name="insurance_info" class="form-control" value="${p.insurance_info||''}"></div>
        </form>
      `;
      $('#viewPatientBody').html(form);
      $('#editPatientBtn').addClass('d-none');
      $('#savePatientBtn').removeClass('d-none');
      $('#cancelEditPatient').removeClass('d-none');
    });

    $(document).on('click', '#cancelEditPatient', function(){
      // reload the patient view
      var id = $('#viewPatientModal').data('patient-id');
      if (!id) return;
      $.get('../ajax/get_patient.php', { id: id }, function(res){
        if (res && res.success) {
          window.currentPatient = res.data;
          // re-render view
          $('#viewPatientBody').html('');
          // reuse the click handler by triggering it to reload
          $('#patientsTable').find(`.view-patient[data-id="${id}"]`).trigger('click');
        }
      }, 'json');
      $('#editPatientBtn').removeClass('d-none');
      $('#savePatientBtn').addClass('d-none');
      $('#cancelEditPatient').addClass('d-none');
    });

    $(document).on('click', '#savePatientBtn', function(){
      var form = $('#editPatientForm');
      $.post('../ajax/update_patient.php', form.serialize(), function(resp){
        if (resp && resp.success) {
          // refresh patient and table
          var id = resp.id;
          $.get('../ajax/get_patient.php', { id: id }, function(r2){
            if (r2 && r2.success) {
              window.currentPatient = r2.data;
              // re-render view by triggering click on the view button
              $('#patientsTable').find(`.view-patient[data-id="${id}"]`).trigger('click');
              table.ajax.reload(null, false);
              Swal.fire({ icon: 'success', title: 'Patient updated' });
            }
          }, 'json');
        } else {
          Swal.fire({ icon: 'error', title: 'Update failed', text: resp.message || 'Failed to update patient' });
        }
      }, 'json').fail(function(){ Swal.fire({ icon: 'error', title: 'Update failed', text: 'Server error' }); });
    });

    // submit new appointment
    $(document).on('click', '#submitNewAppointment', function(){
      var form = $('#newAppointmentForm');
      $.post('../ajax/create_appointment.php', form.serialize(), function(resp){
        if (resp && resp.success) {
          // hide modals first to remove backdrop and restore clickability
          $('#newAppointmentModal').modal('hide');
          $('#viewPatientModal').modal('hide');
          // ensure Select2 dropdown is closed
          try { $('#na_doctor_id').select2('close'); } catch(e){}
          Swal.fire({ icon: 'success', title: 'Appointment created' });
        } else {
          // keep modal open; show error
          Swal.fire({ icon: 'error', title: 'Create failed', text: resp.message || 'Failed to create appointment' });
        }
      }, 'json').fail(function(){
        Swal.fire({ icon: 'error', title: 'Create failed', text: 'Server error' });
      });
    });

    // Function to load patient visit history
    function loadPatientVisitHistory(patientId) {
      const container = $('#visitHistoryContainer');
      
      $.get('../ajax/get_patient_visits.php', { patient_id: patientId })
        .done(function(response) {
          if (response.success && response.data && response.data.length > 0) {
            let html = '';
            response.data.forEach(function(visit) {
              const date = new Date(visit.appointment_date);
              const timeAgo = getTimeAgo(date);
              
              html += `
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                  <div>
                    <div class="fw-medium">${date.toLocaleDateString()}</div>
                    <small class="text-muted">${visit.doctor_name || 'Unknown Doctor'}</small>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-${getStatusColor(visit.status)}">${visit.status}</span>
                    <div><small class="text-muted">${timeAgo}</small></div>
                  </div>
                </div>
              `;
            });
            container.html(html);
          } else {
            container.html('<div class="text-center py-2 text-muted"><small>No visit history</small></div>');
          }
        })
        .fail(function() {
          container.html('<div class="text-center py-2 text-danger"><small>Failed to load visit history</small></div>');
        });
    }

    function getTimeAgo(date) {
      const now = new Date();
      const diffTime = Math.abs(now - date);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays === 0) return 'Today';
      if (diffDays === 1) return 'Yesterday';
      if (diffDays <= 7) return `${diffDays} days ago`;
      if (diffDays <= 30) return `${Math.floor(diffDays/7)} weeks ago`;
      if (diffDays <= 365) return `${Math.floor(diffDays/30)} months ago`;
      return `${Math.floor(diffDays/365)} years ago`;
    }

    function getStatusColor(status) {
      const colors = {
        'completed': 'success',
        'in_progress': 'warning',
        'scheduled': 'primary',
        'cancelled': 'danger',
        'no_show': 'secondary'
      };
      return colors[status] || 'secondary';
    }
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>

