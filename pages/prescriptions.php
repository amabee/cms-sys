<?php
$page_title = 'Prescription Management';
$additional_css = [];
$additional_js = [
  "https://cdn.jsdelivr.net/npm/sweetalert2@11"
];

include __DIR__ . '/../shared/session_handler.php';

requireRole(['admin', 'doctor', 'nurse', 'receptionist']);
$user_role = $_SESSION['user_type'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

ob_start();
?>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <!-- Statistics Overview -->
            <div class="row mb-4">
                <div class="col-lg-3 col-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <i class="bx bx-file bx-lg text-info"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Total Prescriptions</small>
                                    <h3 class="mb-0" id="totalPrescriptions">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <i class="bx bx-check-circle bx-lg text-success"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Active</small>
                                    <h3 class="mb-0" id="activePrescriptions">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <i class="bx bx-check-double bx-lg text-warning"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Completed</small>
                                    <h3 class="mb-0" id="completedPrescriptions">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <i class="bx bx-time-five bx-lg text-danger"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Pending</small>
                                    <h3 class="mb-0" id="pendingPrescriptions">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row">
                <!-- Prescriptions List -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Prescriptions</h3>
                            <div class="card-tools">
                                <?php if (in_array($user_role, ['admin', 'doctor'])): ?>
                                <button type="button" class="btn btn-primary btn-sm" id="newPrescriptionBtn">
                                    <i class="fas fa-plus"></i> New Prescription
                                </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-info btn-sm" id="searchPrescriptionsBtn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3" id="prescriptionFilters" style="display: none;">
                                <div class="col-md-3">
                                    <label for="statusFilter">Status</label>
                                    <select class="form-control" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="dateFromFilter">Date From</label>
                                    <input type="date" class="form-control" id="dateFromFilter">
                                </div>
                                <div class="col-md-3">
                                    <label for="dateToFilter">Date To</label>
                                    <input type="date" class="form-control" id="dateToFilter">
                                </div>
                                <div class="col-md-3">
                                    <label for="searchFilter">Search</label>
                                    <input type="text" class="form-control" id="searchFilter" placeholder="Patient name or Rx#">
                                </div>
                            </div>

                            <!-- Prescriptions Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="prescriptionsTable">
                                    <thead>
                                        <tr>
                                            <th>Rx Number</th>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Status</th>
                                            <th>Cost</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prescriptionsTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading prescriptions...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav aria-label="Prescriptions pagination">
                                <ul class="pagination justify-content-center" id="prescriptionsPagination">
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Side Panel -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <?php if (in_array($user_role, ['admin', 'doctor'])): ?>
                            <button type="button" class="btn btn-primary btn-block mb-2" id="quickPrescriptionBtn">
                                <i class="fas fa-prescription-bottle"></i> Quick Prescription
                            </button>
                            <button type="button" class="btn btn-info btn-block mb-2" id="templatesBtn">
                                <i class="fas fa-clipboard-list"></i> Templates
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-success btn-block mb-2" id="medicationsBtn">
                                <i class="fas fa-pills"></i> Medications Database
                            </button>
                            <button type="button" class="btn btn-warning btn-block mb-2" id="interactionsBtn">
                                <i class="fas fa-exclamation-triangle"></i> Drug Interactions
                            </button>
                            <button type="button" class="btn btn-danger btn-block mb-2" id="allergiesBtn">
                                <i class="fas fa-allergies"></i> Patient Allergies
                            </button>
                        </div>
                    </div>

                    <!-- Top Medications -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Most Prescribed Medications</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (isset($stats['data']['top_medications'])): ?>
                                    <?php foreach (array_slice($stats['data']['top_medications'], 0, 5) as $med): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($med['medication_name']) ?>
                                            <span class="badge badge-primary badge-pill"><?= $med['prescription_count'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-muted">No data available</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- New Prescription Modal -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="prescriptionModalTitle">New Prescription</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="prescriptionForm">
                    <div class="row">
                        <!-- Patient Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="patientSelect">Patient *</label>
                                <select class="form-control select2" id="patientSelect" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                </select>
                            </div>
                        </div>

                        <!-- Doctor Selection (for admin) -->
                        <?php if ($user_role === 'admin'): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="doctorSelect">Doctor *</label>
                                <select class="form-control select2" id="doctorSelect" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                </select>
                            </div>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="doctor_id" value="<?= $user_id ?>">
                        <?php endif; ?>

                        <!-- Prescription Date -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="prescriptionDate">Prescription Date *</label>
                                <input type="date" class="form-control" id="prescriptionDate" name="prescription_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Pharmacy -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pharmacySelect">Pharmacy</label>
                                <select class="form-control select2" id="pharmacySelect" name="pharmacy_id">
                                    <option value="">Select Pharmacy</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Safety Checks -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="checkAllergies" name="check_allergies" checked>
                                <label class="custom-control-label" for="checkAllergies">Check for allergies</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="checkInteractions" name="check_interactions" checked>
                                <label class="custom-control-label" for="checkInteractions">Check drug interactions</label>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription Items -->
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Prescription Items</h5>
                        <button type="button" class="btn btn-success btn-sm" id="addMedicationBtn">
                            <i class="fas fa-plus"></i> Add Medication
                        </button>
                    </div>

                    <div id="medicationItems">
                        <!-- Medication items will be added here dynamically -->
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="prescriptionNotes">Notes</label>
                        <textarea class="form-control" id="prescriptionNotes" name="notes" rows="3" 
                                  placeholder="Additional instructions or notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePrescriptionBtn">
                    <i class="fas fa-save"></i> Save Prescription
                </button>
                <button type="button" class="btn btn-info" id="previewPrescriptionBtn">
                    <i class="fas fa-eye"></i> Preview
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Prescription Details Modal -->
<div class="modal fade" id="prescriptionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Prescription Details</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="prescriptionDetailsContent">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" id="printPrescriptionBtn">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn btn-success" id="sendToPharmacyBtn">
                    <i class="fas fa-paper-plane"></i> Send to Pharmacy
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Medication Item Template -->
<template id="medicationItemTemplate">
    <div class="card medication-item mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Medication *</label>
                        <select class="form-control medication-select" name="medication_id" required>
                            <option value="">Select Medication</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Quantity *</label>
                        <input type="number" class="form-control" name="quantity" required min="1">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Unit</label>
                        <select class="form-control" name="unit">
                            <option value="tablets">Tablets</option>
                            <option value="capsules">Capsules</option>
                            <option value="ml">ML</option>
                            <option value="mg">MG</option>
                            <option value="units">Units</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Frequency *</label>
                        <select class="form-control" name="frequency" required>
                            <option value="">Select Frequency</option>
                            <option value="QD">Once Daily (QD)</option>
                            <option value="BID">Twice Daily (BID)</option>
                            <option value="TID">Three Times Daily (TID)</option>
                            <option value="QID">Four Times Daily (QID)</option>
                            <option value="Q4H">Every 4 Hours</option>
                            <option value="Q6H">Every 6 Hours</option>
                            <option value="Q8H">Every 8 Hours</option>
                            <option value="Q12H">Every 12 Hours</option>
                            <option value="PRN">As Needed (PRN)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm d-block remove-medication">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Dosage Instructions *</label>
                        <input type="text" class="form-control" name="dosage_instruction" 
                               placeholder="e.g., Take 1 tablet by mouth" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Duration (Days)</label>
                        <input type="number" class="form-control" name="duration_days" min="1">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Refills</label>
                        <select class="form-control" name="refills_allowed">
                            <option value="0">0 (No Refills)</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Special Instructions</label>
                <textarea class="form-control" name="special_instructions" rows="2" 
                          placeholder="Special instructions, warnings, or notes..."></textarea>
            </div>
        </div>
    </div>
</template>

<script>
// Prescriptions page JavaScript
$(document).ready(function() {
  // Initialize DataTable for prescriptions
  const table = $('#prescriptionsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '../ajax/get_prescriptions.php',
      type: 'GET'
    },
    columns: [
      { data: 'prescription_id' },
      { data: 'patient_name' },
      { data: 'doctor_name' },
      { data: 'created_at' },
      { data: 'status' },
      { 
        data: null,
        orderable: false,
        render: function(data) {
          return `<button class="btn btn-sm btn-outline-primary view-prescription" data-id="${data.id}">
            <i class="bx bx-show"></i> View
          </button>`;
        }
      }
    ]
  });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
