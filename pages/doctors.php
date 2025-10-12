<?php
$page_title = 'Doctors';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Doctors</div>
      <div class="card-body">
        <p class="text-muted">Manage doctor profiles, specializations, and schedules.</p>
        <table class="table table-hover" id="doctorsTable">
          <thead><tr><th>Doctor ID</th><th>Name</th><th>Specialization</th><th>Contact</th><th>Actions</th></tr></thead>
          <tbody><tr><td>—</td><td>—</td><td>—</td><td>—</td><td>—</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
