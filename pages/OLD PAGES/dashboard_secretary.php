<?php
$page_title = 'Secretary Dashboard';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['secretary']);

ob_start();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Secretary Panel</h4>
        <p class="mb-0">Manage schedules, referrals, and administrative tasks.</p>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
