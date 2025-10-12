<?php
$page_title = 'System Logs';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/session_handler.php';

// Check admin access
if (!isset($user_id)) {
  header('Location: login.php');
  exit();
}

ob_start();
?>

<div class="row">
  <!-- Welcome Card -->
  <div class="col-12 mb-4">
    <div class="card">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="card-title text-primary mb-3">System Activity Logs 📋</h5>
            <p class="mb-4">
              Monitor and track all system activities, user actions, and important events.
            </p>
            <button class="btn btn-sm btn-primary" onclick="exportLogs()">
              <i class="bx bx-export me-1"></i>Export Logs
            </button>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-4">
            <img src="../assets/img/illustrations/security.jpg" height="170" alt="System Logs">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Logs List -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="m-0"><i class="bx bx-history me-2"></i>Activity Logs</h5>
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm w-px-150" id="filterType">
            <option value="">All Activities</option>
            <option value="login">Login/Logout</option>
            <option value="user">User Management</option>
            <option value="employee">Employee Management</option>
            <option value="organization">Organization</option>
            <option value="settings">System Settings</option>
          </select>
          <input type="date" class="form-control form-control-sm w-px-150" id="filterDate">
          <button class="btn btn-sm btn-primary" onclick="refreshLogs()">
            <i class="bx bx-refresh me-1"></i>Refresh
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive text-nowrap">
          <table class="table table-borderless">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Activity Type</th>
                <th>Description</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody id="logsTableBody">
              <!-- Logs will be loaded here via AJAX -->
            </tbody>
          </table>
        </div>
        <!-- Logs Pagination -->
        <nav aria-label="Logs pagination" id="logsPaginationNav" class="d-none">
          <ul class="pagination pagination-sm justify-content-center" id="logsPagination">
            <!-- Will be populated via JavaScript -->
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php
// Include the export logs modal
include 'modals/export-logs-modal.php';
?>

<script>
  let currentLogsPage = 1;
  let logsData = [];

  function loadLogs(page = 1) {
    const filterType = $('#filterType').val();
    const filterDate = $('#filterDate').val();

    $.ajax({
      url: '../ajax/get_logs.php',
      type: 'GET',
      data: {
        page: page,
        type: filterType,
        date: filterDate
      },
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          logsData = response.logs;
          updateLogsTable(response.logs);
          updateLogsPagination(response.pagination);
          currentLogsPage = page;
        } else {
          console.error('Failed to load logs:', response.message);
          $('#logsTableBody').html('<tr><td colspan="5" class="text-center">Failed to load logs</td></tr>');
        }
      },
      error: function (xhr, status, error) {
        console.error('AJAX Error:', error);
        $('#logsTableBody').html('<tr><td colspan="5" class="text-center">Error loading logs</td></tr>');
      }
    });
  }

  function updateLogsTable(logs) {
    let html = '';
    
    if (logs.length === 0) {
      html = '<tr><td colspan="5" class="text-center">No logs found</td></tr>';
    } else {
      logs.forEach(log => {
        const avatar = log.display_name ? log.display_name.charAt(0).toUpperCase() : log.username.charAt(0).toUpperCase();
        const badgeColor = getActivityBadgeColor(log.action);
        
        html += `
          <tr>
            <td>
              <small class="text-muted">${log.timestamp}</small>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-xs me-2">
                  <span class="avatar-initial rounded-circle bg-label-${badgeColor}">
                    ${avatar}
                  </span>
                </div>
                <div>
                  <span class="fw-medium">${log.display_name}</span>
                  <small class="text-muted d-block">@${log.username}</small>
                </div>
              </div>
            </td>
            <td>
              <span class="badge bg-label-${badgeColor}">${log.action}</span>
            </td>
            <td>
              <span class="text-wrap">${log.description}</span>
            </td>
            <td>
              <small class="text-muted">${log.ip_address}</small>
            </td>
          </tr>
        `;
      });
    }
    
    $('#logsTableBody').html(html);
  }

  function getActivityBadgeColor(action) {
    const colors = {
      'LOGIN': 'success',
      'LOGOUT': 'warning',
      'CREATE': 'primary',
      'UPDATE': 'info',
      'DELETE': 'danger'
    };
    return colors[action] || 'secondary';
  }

  function updateLogsPagination(pagination) {
    if (pagination.total_pages <= 1) {
      $('#logsPaginationNav').addClass('d-none');
      return;
    }

    $('#logsPaginationNav').removeClass('d-none');
    let paginationHtml = '';

    // Previous button
    paginationHtml += `
      <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0);" onclick="changeLogsPage(${pagination.current_page - 1})">
          <i class="tf-icon bx bx-chevrons-left"></i>
        </a>
      </li>
    `;

    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
      if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 1 && i <= pagination.current_page + 1)) {
        paginationHtml += `
          <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="changeLogsPage(${i})">${i}</a>
          </li>
        `;
      } else if (i === pagination.current_page - 2 || i === pagination.current_page + 2) {
        paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
      }
    }

    // Next button
    paginationHtml += `
      <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0);" onclick="changeLogsPage(${pagination.current_page + 1})">
          <i class="tf-icon bx bx-chevrons-right"></i>
        </a>
      </li>
    `;

    // Add page info
    const startItem = (pagination.current_page - 1) * pagination.logs_per_page + 1;
    const endItem = Math.min(pagination.current_page * pagination.logs_per_page, pagination.total_logs);
    paginationHtml += `
      <li class="page-item disabled">
        <span class="page-link text-muted small">Showing ${startItem}-${endItem} of ${pagination.total_logs}</span>
      </li>
    `;

    $('#logsPagination').html(paginationHtml);
  }

  function changeLogsPage(page) {
    if (page >= 1) {
      loadLogs(page);
    }
  }

  function refreshLogs() {
    currentLogsPage = 1;
    loadLogs(currentLogsPage);
  }

  function exportLogs() {
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);

    $('#exportDateFrom').val(thirtyDaysAgo.toISOString().split('T')[0]);
    $('#exportDateTo').val(today.toISOString().split('T')[0]);
    $('#exportType').val($('#filterType').val());

    const modal = new bootstrap.Modal(document.getElementById('exportLogsModal'));
    modal.show();
  }

  function confirmExport() {
    const dateFrom = $('#exportDateFrom').val();
    const dateTo = $('#exportDateTo').val();
    const type = $('#exportType').val();

    if (!dateFrom || !dateTo) {
      Swal.fire('Error', 'Please select both start and end dates', 'error');
      return;
    }

    if (new Date(dateTo) < new Date(dateFrom)) {
      Swal.fire('Error', 'End date cannot be earlier than start date', 'error');
      return;
    }

    window.location.href = `../ajax/export_logs.php?type=${type}&date_from=${dateFrom}&date_to=${dateTo}`;

    const modal = bootstrap.Modal.getInstance(document.getElementById('exportLogsModal'));
    modal.hide();
  }

  $(document).ready(function () {
    // Load logs when page loads
    loadLogs();

    // Handle filter changes
    $('#filterType, #filterDate').on('change', function () {
      refreshLogs();
    });
  });
</script>

<?php
$content = ob_get_clean();
include './shared/layout.php';
?>

