<?php
$page_title = 'Patient Dashboard';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['patient']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Patient Portal</h4>
        <p class="mb-0">Access your appointments, records, and billing.</p>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
