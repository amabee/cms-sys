<?php
$page_title = 'Receptionists Management';
$additional_css = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'
];
$additional_js = [
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
  '../assets/js/receptionists.js'
];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin']);

ob_start();
?>

<style>
  #receptionistsTable {
    width: 100%;
    font-size: 0.875rem;
  }

  #receptionistsTable td {
    vertical-align: middle;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f5;
  }

  #receptionistsTable th {
    background-color: #f8fafc;
    border-bottom: 2px solid #e8ecf1;
    font-weight: 600;
    color: #5a6f7d;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    padding: 0.75rem 1rem;
  }

  .user-cell {
    display: flex;
    align-items: center;
    gap: 0.65rem;
  }

  .user-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    font-weight: 600;
    font-size: 0.75rem;
    box-shadow: 0 2px 6px rgba(67, 89, 113, 0.1);
    color: white;
    background: linear-gradient(135deg, #696cff, #5f61e6);
  }

  .user-info h6 {
    margin: 0;
    font-weight: 600;
    color: #1a1a2e;
    font-size: 0.875rem;
  }

  .user-info p {
    margin: 0.15rem 0 0 0;
    color: #8b96aa;
    font-size: 0.75rem;
  }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Receptionists Management</h4>
    <p class="text-muted mb-0">Manage receptionist accounts and access</p>
  </div>
  <div>
    <button type="button" class="btn btn-primary" id="addReceptionistBtn">
      <i class="bx bx-plus me-1"></i>Add Receptionist
    </button>
  </div>
</div>

<!-- Receptionists Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom">
    <h5 class="card-title mb-0">
      <i class="bx bx-user me-2 text-primary"></i>Receptionist Accounts
    </h5>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="receptionistsTable">
        <thead>
          <tr>
            <th style="width: 60px; text-align: center;">#</th>
            <th>Name</th>
            <th>Username</th>
            <th>Contact</th>
            <th style="text-align: center; width: 100px;">Status</th>
            <th style="text-align: center; width: 80px;">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Receptionist Modal -->
<div class="modal fade" id="receptionistModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="receptionistModalLabel">
          <i class="bx bx-plus-circle me-2"></i>Add Receptionist
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="receptionistForm">
          <input type="hidden" id="receptionist-id" name="id">
          
          <div class="mb-3">
            <label for="first-name" class="form-label">First Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="first-name" name="first_name" required>
          </div>

          <div class="mb-3">
            <label for="last-name" class="form-label">Last Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="last-name" name="last_name" required>
          </div>

          <div class="mb-3">
            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>

          <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="tel" class="form-control" id="phone" name="phone">
          </div>

          <div class="mb-3" id="password-field">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password" name="password">
          </div>

          <div class="mb-3">
            <label for="is-active" class="form-label">Status</label>
            <select class="form-select" id="is-active" name="is_active">
              <option value="1" selected>Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveReceptionistBtn">Save</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
