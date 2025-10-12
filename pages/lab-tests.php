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
        <table class="table" id="labTestsTable">
          <thead><tr><th>Test ID</th><th>Patient</th><th>Test Name</th><th>Date</th><th>Status</th></tr></thead>
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
