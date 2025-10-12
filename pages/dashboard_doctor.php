<?php
$page_title = 'Doctor Dashboard';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['doctor']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Doctor Overview</h4>
        <p class="mb-0">Your schedule, quick patient lookup, and recent notes.</p>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">Today's Appointments</div>
      <div class="card-body">
        <table class="table table-hover">
          <thead><tr><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th></tr></thead>
          <tbody>
            <tr><td>—</td><td>—</td><td>—</td><td>—</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
