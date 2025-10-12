<?php
$page_title = 'Medical Records';
$additional_css = [];
$additional_js = [];

include_once('/../shared/session_handler.php');
requireRole(['admin','doctor','patient']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Medical Records</div>
      <div class="card-body">
        <p class="text-muted">Clinical notes, diagnoses and visit history.</p>
        <table class="table table-bordered" id="recordsTable">
          <thead><tr><th>Record ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Summary</th></tr></thead>
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
