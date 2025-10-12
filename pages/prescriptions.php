<?php
$page_title = 'Prescriptions';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','doctor','secretary','receptionist','patient']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Prescriptions</div>
      <div class="card-body">
        <p class="text-muted">Patient prescriptions and dispensing records.</p>
        <table class="table" id="prescriptionsTable">
          <thead><tr><th>Rx ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Medication</th></tr></thead>
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
