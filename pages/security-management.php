<?php
$page_title = 'Security Management';
$additional_css = [];
$additional_js = [];

include __DIR__ . '/../shared/secure_session_handler.php';
requireRoleSecure(['admin']); // Only administrators can access

// Initialize security controller
require_once __DIR__ . '/../controllers/SecurityController.php';
$securityController = new SecurityController();

ob_start();
?>
<!-- Security Dashboard -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Management Dashboard</h5>
      </div>
      <div class="card-body">
        <div class="row" id="securityStatsContainer">
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                <h4 class="mb-1" id="failedLogins24h">0</h4>
                <small class="text-muted">Failed Logins (24h)</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-lock text-danger fa-2x mb-2"></i>
                <h4 class="mb-1" id="activeLockouts">0</h4>
                <small class="text-muted">Active Lockouts</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-users text-info fa-2x mb-2"></i>
                <h4 class="mb-1" id="activeSessions">0</h4>
                <small class="text-muted">Active Sessions</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-light border-0 h-100">
              <div class="card-body text-center">
                <i class="fas fa-exclamation-circle text-danger fa-2x mb-2"></i>
                <h4 class="mb-1" id="highRiskEvents">0</h4>
                <small class="text-muted">High Risk Events (7d)</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Security Management Sections -->
<div class="row">
  <!-- Security Logs -->
  <div class="col-12 mb-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="mb-0">Security Logs</h5>
            <small class="text-muted">Recent security events and audit trail</small>
          </div>
          <div class="col-auto">
            <div class="btn-group">
              <button class="btn btn-outline-primary btn-sm" id="refreshSecurityLogs">
                <i class="fas fa-sync-alt me-1"></i>Refresh
              </button>
              <button class="btn btn-outline-info btn-sm" id="exportSecurityLogs">
                <i class="fas fa-download me-1"></i>Export
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <!-- Security Logs Filters -->
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Event Type</label>
            <select id="eventTypeFilter" class="form-select form-select-sm">
              <option value="">All Events</option>
              <option value="LOGIN">Login</option>
              <option value="LOGOUT">Logout</option>
              <option value="LOGIN_FAILED">Failed Login</option>
              <option value="ACCOUNT_LOCKED">Account Locked</option>
              <option value="PERMISSION_DENIED">Permission Denied</option>
              <option value="SUSPICIOUS_ACTIVITY">Suspicious Activity</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold">Risk Level</label>
            <select id="riskLevelFilter" class="form-select form-select-sm">
              <option value="">All Levels</option>
              <option value="LOW">Low</option>
              <option value="MEDIUM">Medium</option>
              <option value="HIGH">High</option>
              <option value="CRITICAL">Critical</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold">Date From</label>
            <input type="date" id="dateFromFilter" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold">Date To</label>
            <input type="date" id="dateToFilter" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Actions</label>
            <div class="d-flex gap-1">
              <button id="filterSecurityLogs" class="btn btn-primary btn-sm flex-fill">
                <i class="fas fa-filter me-1"></i>Filter
              </button>
              <button id="clearFilters" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
        
        <div class="table-responsive">
          <table class="table table-hover table-sm" id="securityLogsTable">
            <thead class="table-light">
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Event Type</th>
                <th>Risk Level</th>
                <th>IP Address</th>
                <th>Details</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="securityLogsBody">
              <!-- Logs will be populated here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Account Lockouts Management -->
  <div class="col-md-6 mb-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class="fas fa-user-lock me-2"></i>Account Lockouts</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>User</th>
                <th>Reason</th>
                <th>Locked</th>
                <th>Unlock</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="lockoutsTableBody">
              <!-- Lockouts will be populated here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Active Sessions -->
  <div class="col-md-6 mb-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class="fas fa-desktop me-2"></i>Active Sessions</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>User</th>
                <th>IP Address</th>
                <th>Login Time</th>
                <th>Last Activity</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="sessionsTableBody">
              <!-- Sessions will be populated here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Security Settings -->
<div class="row">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Security Settings</h5>
      </div>
      <div class="card-body">
        <form id="securitySettingsForm">
          <?= csrfField() ?>
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-primary mb-3">Authentication Settings</h6>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Maximum Login Attempts</label>
                <input type="number" name="max_login_attempts" class="form-control" min="1" max="20" 
                       value="<?= htmlspecialchars($securityController->getSetting('max_login_attempts', 5)) ?>">
                <small class="text-muted">Number of failed attempts before account lockout</small>
              </div>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Lockout Duration (minutes)</label>
                <input type="number" name="lockout_duration" class="form-control" min="5" max="1440" 
                       value="<?= (int)($securityController->getSetting('lockout_duration', 900) / 60) ?>">
                <small class="text-muted">How long accounts remain locked</small>
              </div>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Session Timeout (minutes)</label>
                <input type="number" name="session_timeout" class="form-control" min="5" max="480" 
                       value="<?= (int)($securityController->getSetting('session_timeout', 3600) / 60) ?>">
                <small class="text-muted">Automatic logout after inactivity</small>
              </div>
            </div>
            
            <div class="col-md-6">
              <h6 class="text-primary mb-3">Password Policy</h6>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Minimum Password Length</label>
                <input type="number" name="password_min_length" class="form-control" min="6" max="50" 
                       value="<?= htmlspecialchars($securityController->getSetting('password_min_length', 8)) ?>">
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input type="checkbox" name="password_require_uppercase" class="form-check-input" 
                         <?= $securityController->getSetting('password_require_uppercase', 1) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold">Require Uppercase Letters</label>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input type="checkbox" name="password_require_lowercase" class="form-check-input" 
                         <?= $securityController->getSetting('password_require_lowercase', 1) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold">Require Lowercase Letters</label>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input type="checkbox" name="password_require_numbers" class="form-check-input" 
                         <?= $securityController->getSetting('password_require_numbers', 1) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold">Require Numbers</label>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input type="checkbox" name="password_require_symbols" class="form-check-input" 
                         <?= $securityController->getSetting('password_require_symbols', 1) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold">Require Special Characters</label>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Password History Count</label>
                <input type="number" name="password_history_count" class="form-control" min="0" max="25" 
                       value="<?= htmlspecialchars($securityController->getSetting('password_history_count', 5)) ?>">
                <small class="text-muted">Prevent reuse of recent passwords (0 to disable)</small>
              </div>
            </div>
          </div>
          
          <div class="text-end">
            <button type="button" class="btn btn-secondary me-2" id="resetSecuritySettings">
              <i class="fas fa-undo me-1"></i>Reset
            </button>
            <button type="submit" class="btn btn-danger">
              <i class="fas fa-save me-1"></i>Save Security Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>

<script>
$(function() {
  const USER_TYPE = '<?php echo $user_type ?? ''; ?>';

  // Load dashboard statistics
  function loadSecurityStats() {
    $.get('../ajax/get_security_statistics.php', function(resp) {
      if (resp && resp.success && resp.data) {
        const stats = resp.data;
        $('#failedLogins24h').text(stats.failed_logins_24h || 0);
        $('#activeLockouts').text(stats.active_lockouts || 0);
        $('#activeSessions').text(stats.active_sessions || 0);
        $('#highRiskEvents').text(stats.high_risk_events || 0);
      }
    }, 'json').fail(function() {
      console.warn('Failed to load security statistics');
    });
  }

  // Load security logs
  function loadSecurityLogs(filters = {}) {
    const tbody = $('#securityLogsBody');
    tbody.html('<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>');
    
    $.post('../ajax/get_security_logs.php', filters, function(resp) {
      if (resp && resp.success && resp.data) {
        tbody.empty();
        
        if (resp.data.length === 0) {
          tbody.html('<tr><td colspan="7" class="text-center text-muted">No security logs found</td></tr>');
          return;
        }
        
        resp.data.forEach(function(log) {
          const riskBadge = getRiskBadge(log.risk_level);
          const eventBadge = getEventBadge(log.event_type);
          const userInfo = log.user_name ? `${log.user_name}<br><small class="text-muted">${log.username || ''}</small>` : '<span class="text-muted">System</span>';
          
          const row = `
            <tr>
              <td>
                <small>${new Date(log.created_at).toLocaleString()}</small>
              </td>
              <td>${userInfo}</td>
              <td>${eventBadge}</td>
              <td>${riskBadge}</td>
              <td><small class="font-monospace">${log.ip_address || ''}</small></td>
              <td>
                <small class="text-muted">${formatEventDetails(log.event_details)}</small>
              </td>
              <td>
                <button class="btn btn-outline-info btn-sm view-log-details" data-id="${log.id}">
                  <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
          `;
          tbody.append(row);
        });
      } else {
        tbody.html('<tr><td colspan="7" class="text-center text-danger">Failed to load security logs</td></tr>');
      }
    }, 'json').fail(function() {
      tbody.html('<tr><td colspan="7" class="text-center text-danger">Network error loading logs</td></tr>');
    });
  }

  // Load account lockouts
  function loadLockouts() {
    $.get('../ajax/get_account_lockouts.php', function(resp) {
      if (resp && resp.success && resp.data) {
        const tbody = $('#lockoutsTableBody');
        tbody.empty();
        
        if (resp.data.length === 0) {
          tbody.html('<tr><td colspan="5" class="text-center text-muted">No active lockouts</td></tr>');
          return;
        }
        
        resp.data.forEach(function(lockout) {
          const unlockTime = lockout.unlock_at ? new Date(lockout.unlock_at).toLocaleString() : 'Manual';
          const row = `
            <tr>
              <td>
                ${lockout.user_name || lockout.username || 'Unknown'}
                ${lockout.ip_address ? `<br><small class="text-muted">${lockout.ip_address}</small>` : ''}
              </td>
              <td><span class="badge bg-warning text-dark">${lockout.lockout_reason}</span></td>
              <td><small>${new Date(lockout.locked_at).toLocaleString()}</small></td>
              <td><small>${unlockTime}</small></td>
              <td>
                <button class="btn btn-success btn-sm unlock-account" data-id="${lockout.id}" data-user-id="${lockout.user_id}">
                  <i class="fas fa-unlock"></i>
                </button>
              </td>
            </tr>
          `;
          tbody.append(row);
        });
      }
    }, 'json');
  }

  // Load active sessions
  function loadActiveSessions() {
    $.get('../ajax/get_active_sessions.php', function(resp) {
      if (resp && resp.success && resp.data) {
        const tbody = $('#sessionsTableBody');
        tbody.empty();
        
        if (resp.data.length === 0) {
          tbody.html('<tr><td colspan="5" class="text-center text-muted">No active sessions</td></tr>');
          return;
        }
        
        resp.data.forEach(function(session) {
          const row = `
            <tr>
              <td>
                ${session.user_name || 'Unknown'}
                <br><small class="text-muted">${session.username || ''}</small>
              </td>
              <td><small class="font-monospace">${session.ip_address || ''}</small></td>
              <td><small>${new Date(session.login_time).toLocaleString()}</small></td>
              <td><small>${new Date(session.last_activity).toLocaleString()}</small></td>
              <td>
                <button class="btn btn-danger btn-sm terminate-session" data-session-id="${session.session_id}">
                  <i class="fas fa-sign-out-alt"></i>
                </button>
              </td>
            </tr>
          `;
          tbody.append(row);
        });
      }
    }, 'json');
  }

  // Helper functions
  function getRiskBadge(riskLevel) {
    const badges = {
      'LOW': '<span class="badge bg-success">Low</span>',
      'MEDIUM': '<span class="badge bg-warning text-dark">Medium</span>',
      'HIGH': '<span class="badge bg-danger">High</span>',
      'CRITICAL': '<span class="badge bg-dark">Critical</span>'
    };
    return badges[riskLevel] || '<span class="badge bg-secondary">Unknown</span>';
  }

  function getEventBadge(eventType) {
    const badges = {
      'LOGIN': '<span class="badge bg-success">Login</span>',
      'LOGOUT': '<span class="badge bg-info">Logout</span>',
      'LOGIN_FAILED': '<span class="badge bg-danger">Login Failed</span>',
      'ACCOUNT_LOCKED': '<span class="badge bg-warning text-dark">Account Locked</span>',
      'PERMISSION_DENIED': '<span class="badge bg-danger">Access Denied</span>',
      'SUSPICIOUS_ACTIVITY': '<span class="badge bg-dark">Suspicious Activity</span>'
    };
    return badges[eventType] || `<span class="badge bg-secondary">${eventType}</span>`;
  }

  function formatEventDetails(details) {
    if (!details) return '';
    
    try {
      const parsed = JSON.parse(details);
      let formatted = [];
      
      Object.entries(parsed).forEach(([key, value]) => {
        if (value && key !== 'user_agent') {
          formatted.push(`${key}: ${value}`);
        }
      });
      
      return formatted.join(', ').substring(0, 100) + (formatted.join(', ').length > 100 ? '...' : '');
    } catch (e) {
      return details.substring(0, 100) + (details.length > 100 ? '...' : '');
    }
  }

  // Event handlers
  $('#refreshSecurityLogs').on('click', function() {
    loadSecurityLogs();
  });

  $('#filterSecurityLogs').on('click', function() {
    const filters = {
      event_type: $('#eventTypeFilter').val(),
      risk_level: $('#riskLevelFilter').val(),
      date_from: $('#dateFromFilter').val(),
      date_to: $('#dateToFilter').val()
    };
    
    loadSecurityLogs(filters);
  });

  $('#clearFilters').on('click', function() {
    $('#eventTypeFilter, #riskLevelFilter, #dateFromFilter, #dateToFilter').val('');
    loadSecurityLogs();
  });

  // Unlock account
  $(document).on('click', '.unlock-account', function() {
    const lockoutId = $(this).data('id');
    const userId = $(this).data('user-id');
    
    Swal.fire({
      title: 'Unlock Account?',
      text: 'This will immediately unlock the user account.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      confirmButtonText: 'Yes, Unlock'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('../ajax/unlock_account.php', {
          lockout_id: lockoutId,
          user_id: userId
        }, function(resp) {
          if (resp && resp.success) {
            Swal.fire('Unlocked!', 'Account has been unlocked.', 'success');
            loadLockouts();
            loadSecurityStats();
          } else {
            Swal.fire('Error', resp.message || 'Failed to unlock account.', 'error');
          }
        }, 'json');
      }
    });
  });

  // Terminate session
  $(document).on('click', '.terminate-session', function() {
    const sessionId = $(this).data('session-id');
    
    Swal.fire({
      title: 'Terminate Session?',
      text: 'This will immediately log out the user.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Yes, Terminate'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('../ajax/terminate_session.php', {
          session_id: sessionId
        }, function(resp) {
          if (resp && resp.success) {
            Swal.fire('Terminated!', 'Session has been terminated.', 'success');
            loadActiveSessions();
            loadSecurityStats();
          } else {
            Swal.fire('Error', resp.message || 'Failed to terminate session.', 'error');
          }
        }, 'json');
      }
    });
  });

  // Save security settings
  $('#securitySettingsForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
      url: '../ajax/update_security_settings.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.success) {
          Swal.fire('Saved!', 'Security settings have been updated.', 'success');
        } else {
          Swal.fire('Error', resp.message || 'Failed to save settings.', 'error');
        }
      },
      error: function() {
        Swal.fire('Error', 'Network error occurred.', 'error');
      }
    });
  });

  // Initialize page
  loadSecurityStats();
  loadSecurityLogs();
  loadLockouts();
  loadActiveSessions();

  // Refresh stats every 30 seconds
  setInterval(function() {
    loadSecurityStats();
  }, 30000);

  // Set default date filters
  const today = new Date();
  const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
  $('#dateFromFilter').val(lastWeek.toISOString().split('T')[0]);
  $('#dateToFilter').val(today.toISOString().split('T')[0]);
});
</script>
