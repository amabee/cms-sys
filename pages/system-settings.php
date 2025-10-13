<?php
require_once 'shared/header.php';
require_once 'controllers/SystemSettingsController.php';

// Check if user has admin access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize controller
$controller = new SystemSettingsController($conn);
$categories = $controller->getConfigurationCategories();
$systemStats = $controller->getSystemStatistics();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">System Settings</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">System Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- System Overview -->
            <div class="row mb-4">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $systemStats['data']['active_users'] ?? 'N/A' ?></h3>
                            <p>Active Users</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $systemStats['data']['database_size'] ?? 'N/A' ?></h3>
                            <p>Database Size</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $systemStats['data']['recent_backups'] ?? 'N/A' ?></h3>
                            <p>Recent Backups</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-save"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $systemStats['data']['system_uptime'] ?? 'N/A' ?></h3>
                            <p>System Uptime</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-server"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Settings Interface -->
            <div class="row">
                <!-- Settings Categories -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Configuration Categories</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav nav-pills flex-column" id="settingsCategories">
                                <?php if ($categories['success']): ?>
                                    <?php foreach ($categories['data'] as $category): ?>
                                        <li class="nav-item">
                                            <a class="nav-link settings-category" 
                                               href="#" 
                                               data-category="<?= htmlspecialchars($category['category_name']) ?>">
                                                <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                                                <?= htmlspecialchars($category['display_name']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <!-- Additional management options -->
                                <li class="nav-item">
                                    <a class="nav-link" href="#" id="backupManagementTab">
                                        <i class="fas fa-database"></i>
                                        Backup Management
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" id="maintenanceTab">
                                        <i class="fas fa-tools"></i>
                                        Maintenance
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" id="systemActivityTab">
                                        <i class="fas fa-history"></i>
                                        System Activity
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" id="emailTemplatesTab">
                                        <i class="fas fa-envelope-open-text"></i>
                                        Email Templates
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title" id="settingsTitle">Select a Category</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-success btn-sm" id="saveAllSettings" style="display: none;">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-info btn-sm" id="testEmailConfig" style="display: none;">
                                    <i class="fas fa-paper-plane"></i> Test Email
                                </button>
                            </div>
                        </div>
                        <div class="card-body" id="settingsContent">
                            <div class="text-center text-muted">
                                <i class="fas fa-cogs fa-3x mb-3"></i>
                                <p>Please select a configuration category from the left sidebar to view and modify settings.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Storage Usage Card -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Storage Usage</h3>
                        </div>
                        <div class="card-body">
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= $systemStats['data']['storage_percentage'] ?? 0 ?>%">
                                    <?= $systemStats['data']['storage_percentage'] ?? 0 ?>%
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="description-block">
                                        <h5 class="description-header"><?= $systemStats['data']['storage_used'] ?? 'N/A' ?></h5>
                                        <span class="description-text">USED</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="description-block">
                                        <h5 class="description-header"><?= $systemStats['data']['storage_total'] ?? 'N/A' ?></h5>
                                        <span class="description-text">TOTAL</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary btn-block mb-2" id="createBackupBtn">
                                        <i class="fas fa-database"></i> Create Backup
                                    </button>
                                    <button type="button" class="btn btn-warning btn-block mb-2" id="clearLogsBtn">
                                        <i class="fas fa-trash"></i> Clear Old Logs
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-info btn-block mb-2" id="optimizeDatabaseBtn">
                                        <i class="fas fa-tachometer-alt"></i> Optimize Database
                                    </button>
                                    <button type="button" class="btn btn-success btn-block mb-2" id="exportSettingsBtn">
                                        <i class="fas fa-download"></i> Export Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Backup Creation Modal -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Create System Backup</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="backupForm">
                    <div class="form-group">
                        <label for="backupType">Backup Type</label>
                        <select class="form-control" id="backupType" name="backup_type" required>
                            <option value="full">Full Backup (Database + Files)</option>
                            <option value="database">Database Only</option>
                            <option value="files">Files Only</option>
                            <option value="configuration">Configuration Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="compressBackup" name="compress" checked>
                            <label class="custom-control-label" for="compressBackup">Compress backup</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="encryptBackup" name="encrypt">
                            <label class="custom-control-label" for="encryptBackup">Encrypt backup</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="startBackupBtn">
                    <i class="fas fa-database"></i> Start Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Email Test Modal -->
<div class="modal fade" id="emailTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Test Email Configuration</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="emailTestForm">
                    <div class="form-group">
                        <label for="testEmailAddress">Test Email Address</label>
                        <input type="email" class="form-control" id="testEmailAddress" name="email" 
                               placeholder="Enter email address to test" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        A test email will be sent to verify your email configuration settings.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendTestEmailBtn">
                    <i class="fas fa-paper-plane"></i> Send Test Email
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2" id="loadingMessage">Processing...</p>
            </div>
        </div>
    </div>
</div>

<!-- Include JavaScript -->
<script src="assets/js/system-settings.js"></script>

<?php require_once 'shared/footer.php'; ?>
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
