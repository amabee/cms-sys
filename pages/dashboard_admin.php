<?php
$page_title = 'Admin Dashboard';
$additional_css = ['../assets/vendor/libs/apex-charts/apex-charts.css'];
$additional_js = ['../assets/vendor/libs/apex-charts/apexcharts.js','../assets/js/dashboards-analytics.js'];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Admin Overview</h4>
        <p class="mb-0">Summary metrics and quick actions for clinic administrators.</p>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">Recent Activity</div>
      <div class="card-body">
        <table class="table table-striped">
          <thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
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
