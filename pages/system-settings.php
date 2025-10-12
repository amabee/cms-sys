<?php
$page_title = 'System Settings';
$additional_css = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'
];
$additional_js = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js'
];

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
            <h5 class="card-title text-primary mb-3">System Settings ⚙️</h5>
            <p class="mb-4">
              Manage your company information and system settings from this centralized dashboard.
            </p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-4">
            <img src="../assets/img/illustrations/security.jpg" height="170" alt="Settings">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- System Statistics -->
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="m-0"><i class="bx bx-stats me-2"></i>System Overview</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-md-3 mb-3">
            <div class="d-flex flex-column">
              <div class="avatar mx-auto mb-2">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="bx bx-user fs-4"></i>
                </span>
              </div>
              <span class="fw-medium" id="totalUsers">-</span>
              <small class="text-muted">Total Users</small>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="d-flex flex-column">
              <div class="avatar mx-auto mb-2">
                <span class="avatar-initial rounded-circle bg-label-success">
                  <i class="bx bx-group fs-4"></i>
                </span>
              </div>
              <span class="fw-medium" id="totalEmployees">-</span>
              <small class="text-muted">Active Employees</small>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="d-flex flex-column">
              <div class="avatar mx-auto mb-2">
                <span class="avatar-initial rounded-circle bg-label-info">
                  <i class="bx bx-buildings fs-4"></i>
                </span>
              </div>
              <span class="fw-medium" id="totalDepartments">-</span>
              <small class="text-muted">Departments</small>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="d-flex flex-column">
              <div class="avatar mx-auto mb-2">
                <span class="avatar-initial rounded-circle bg-label-warning">
                  <i class="bx bx-data fs-4"></i>
                </span>
              </div>
              <span class="fw-medium" id="dbSize">-</span>
              <small class="text-muted">Database Size (MB)</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Company Information -->
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="m-0"><i class="bx bx-building me-2"></i>Company Information</h5>
        <button class="btn btn-sm btn-primary" onclick="loadCompanyInfo()">
          <i class="bx bx-refresh me-1"></i>Refresh
        </button>
      </div>
      <div class="card-body">
        <form id="settingsForm" enctype="multipart/form-data">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><span class="text-danger">*</span> Company Name</label>
              <input type="text" class="form-control" name="company_name" id="company_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" name="email" id="email">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Contact Number</label>
              <input type="text" class="form-control" name="contact_number" id="contact_number">
            </div>
            <div class="col-md-6">
              <label class="form-label">Website</label>
              <input type="url" class="form-control" name="website" id="website" placeholder="https://example.com">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="address" id="address" rows="3"
              placeholder="Enter company address"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Company Logo</label>
            <input type="file" class="form-control" name="logo" id="logo" accept="image/*">
            <div class="form-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</div>
            <div id="currentLogo" class="mt-2"></div>
          </div>
          <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <i class="bx bx-save me-1"></i>Save Changes
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Database Management -->
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="m-0"><i class="bx bx-data me-2"></i>Database Management</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-warning" role="alert">
          <i class="bx bx-info-circle me-1"></i>
          <strong>Important:</strong> Always create a backup before performing any database operations. Database
          restoration will overwrite all existing data.
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="card border">
              <div class="card-body text-center">
                <i class="bx bx-download display-4 text-primary mb-3"></i>
                <h5>Backup Database</h5>
                <p class="text-muted mb-3">Create a complete backup of your current database including all tables and
                  data.</p>
                <button id="backupDb" class="btn btn-primary">
                  <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                  <i class="bx bx-download me-1"></i>Create Backup
                </button>
                <div id="backupStatus" class="mt-2"></div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border">
              <div class="card-body text-center">
                <i class="bx bx-upload display-4 text-warning mb-3"></i>
                <h5>Restore Database</h5>
                <p class="text-muted mb-3">Restore your database from a previous backup file. This will replace all
                  current data.</p>
                <form id="restoreForm">
                  <div class="mb-3">
                    <input type="file" class="form-control" name="backupFile" accept=".sql" required>
                    <div class="form-text">Select a SQL backup file</div>
                  </div>
                  <button type="submit" class="btn btn-warning">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <i class="bx bx-upload me-1"></i>Restore Database
                  </button>
                </form>
                <div id="restoreStatus" class="mt-2"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- System Information -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="m-0"><i class="bx bx-info-circle me-2"></i>System Information</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <table class="table table-borderless">
              <tr>
                <td><strong>Application Name:</strong></td>
                <td><?php echo APP_NAME; ?></td>
              </tr>
              <tr>
                <td><strong>Version:</strong></td>
                <td><?php echo APP_VERSION; ?></td>
              </tr>
              <tr>
                <td><strong>Database:</strong></td>
                <td><?php echo DB_NAME; ?></td>
              </tr>
              <tr>
                <td><strong>PHP Version:</strong></td>
                <td><?php echo phpversion(); ?></td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-borderless">
              <tr>
                <td><strong>Server:</strong></td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
              </tr>
              <tr>
                <td><strong>Timezone:</strong></td>
                <td><?php echo TIMEZONE; ?></td>
              </tr>
              <tr>
                <td><strong>Current Time:</strong></td>
                <td><?php echo date('Y-m-d H:i:s'); ?></td>
              </tr>
              <tr>
                <td><strong>Max Upload Size:</strong></td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>



<script>
  $(document).ready(function () {
    // Wait for jQuery to be loaded
    function waitForJQuery() {
      if (typeof $ !== 'undefined') {
        loadSystemStats();
        loadCompanyInfo();
      } else {
        setTimeout(waitForJQuery, 50);
      }
    }
    waitForJQuery();
  });

  // Load system statistics
  function loadSystemStats() {
    $.ajax({
      url: '../ajax/get_system_stats.php',
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          $('#totalUsers').text(response.stats.total_users);
          $('#totalEmployees').text(response.stats.total_employees);
          $('#totalDepartments').text(response.stats.total_departments);
          $('#dbSize').text(response.stats.db_size);
        }
      },
      error: function () {
        console.error('Failed to load system statistics');
      }
    });
  }

  // Load company information
  function loadCompanyInfo() {
    $.ajax({
      url: '../ajax/get_company_info.php',
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response.success && response.company) {
          const company = response.company;
          $('#company_name').val(company.name || '');
          $('#email').val(company.email || '');
          $('#contact_number').val(company.contact_number || '');
          $('#website').val(company.website || '');
          $('#address').val(company.address || '');

          // Show current logo if exists
          if (company.logo) {
            $('#currentLogo').html(`
                        <div class="mt-2">
                            <label class="form-label">Current Logo:</label><br>
                            <img src="../uploads/company/${company.logo}" alt="Company Logo" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                        </div>
                    `);
          } else {
            $('#currentLogo').html('<small class="text-muted">No logo uploaded</small>');
          }
        }
      },
      error: function () {
        Swal.fire('Error!', 'Failed to load clinic information', 'error');
      }
    });
  }

  // Company information form submission
  $('#settingsForm').on('submit', function (e) {
    e.preventDefault();

    const $btn = $(this).find('button[type="submit"]');
    const $spinner = $btn.find('.spinner-border');

    // Show loading state
    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // Create FormData object for file upload
    const formData = new FormData(this);

    $.ajax({
      url: '../ajax/update_company_info.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        const result = typeof response === 'string' ? JSON.parse(response) : response;
        if (result.success) {
          Swal.fire('Success!', result.message, 'success');
          loadCompanyInfo(); // Reload company info
        } else {
          Swal.fire('Error!', result.message, 'error');
        }
      },
      error: function () {
        Swal.fire('Error!', 'Failed to update company information', 'error');
      },
      complete: function () {
        $btn.prop('disabled', false);
        $spinner.addClass('d-none');
      }
    });
  });

  // Database backup
  $('#backupDb').on('click', function () {
    const $btn = $(this);
    const $spinner = $btn.find('.spinner-border');

    // Show loading state
    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');
    $('#backupStatus').html('');

    $.ajax({
      url: '../ajax/backup_database.php',
      type: 'POST',
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          Swal.fire({
            title: 'Backup Created!',
            html: `Database backup created successfully.<br><br>
                           <a href="${response.download_url}" class="btn btn-primary" download>
                               <i class="bx bx-download me-1"></i>Download Backup
                           </a>`,
            icon: 'success',
            showConfirmButton: true
          });
          $('#backupStatus').html(`
                    <div class="alert alert-success">
                        <small>Backup created: ${response.filename}</small>
                    </div>
                `);
        } else {
          Swal.fire('Error!', response.message, 'error');
          $('#backupStatus').html(`
                    <div class="alert alert-danger">
                        <small>${response.message}</small>
                    </div>
                `);
        }
      },
      error: function () {
        Swal.fire('Error!', 'Failed to create database backup', 'error');
      },
      complete: function () {
        $btn.prop('disabled', false);
        $spinner.addClass('d-none');
      }
    });
  });

  // Database restore
  $('#restoreForm').on('submit', function (e) {
    e.preventDefault();

    const fileInput = $(this).find('input[type="file"]')[0];
    if (!fileInput.files.length) {
      Swal.fire('Error!', 'Please select a backup file', 'error');
      return;
    }

    // Confirmation dialog
    Swal.fire({
      title: 'Restore Database?',
      text: "This will replace ALL current data with the backup data. This action cannot be undone!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, restore it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        performRestore();
      }
    });

    function performRestore() {
      const $btn = $('#restoreForm').find('button[type="submit"]');
      const $spinner = $btn.find('.spinner-border');

      // Show loading state
      $btn.prop('disabled', true);
      $spinner.removeClass('d-none');
      $('#restoreStatus').html('');

      const formData = new FormData(document.getElementById('restoreForm'));

      $.ajax({
        url: '../ajax/restore_database.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          const result = typeof response === 'string' ? JSON.parse(response) : response;
          if (result.success) {
            Swal.fire('Success!', result.message, 'success');
            $('#restoreStatus').html(`
                        <div class="alert alert-success">
                            <small>Database restored successfully</small>
                        </div>
                    `);
            // Refresh page after successful restore
            setTimeout(() => {
              window.location.reload();
            }, 2000);
          } else {
            Swal.fire('Error!', result.message, 'error');
            $('#restoreStatus').html(`
                        <div class="alert alert-danger">
                            <small>${result.message}</small>
                        </div>
                    `);
          }
        },
        error: function () {
          Swal.fire('Error!', 'Failed to restore database', 'error');
        },
        complete: function () {
          $btn.prop('disabled', false);
          $spinner.addClass('d-none');
        }
      });
    }
  });
</script>

<?php
$content = ob_get_clean();
include './shared/layout.php';
?>
