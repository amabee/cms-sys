<?php
$page_title = 'Billing';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','secretary','receptionist']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">Billing</div>
      <div class="card-body">
        <p class="text-muted">Create and manage bills and payments.</p>
        <table class="table" id="billingTable">
          <thead><tr><th>Bill ID</th><th>Patient</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
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
