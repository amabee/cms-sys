<?php
$page_title = 'Billing Management';
$additional_css = [];
$additional_js = ['https://cdn.jsdelivr.net/npm/sweetalert2@11'];

include __DIR__ . '/../shared/session_handler.php';
requireRole(['admin','secretary','receptionist']);

ob_start();
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-credit-card bx-sm text-primary"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">Total Revenue</span>
                <h3 class="card-title text-nowrap mb-1" id="totalRevenue">₱0.00</h3>
                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> This Month</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-wallet bx-sm text-success"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">Paid Amount</span>
                <h3 class="card-title text-nowrap mb-1" id="paidAmount">₱0.00</h3>
                <small class="text-success fw-semibold"><i class="bx bx-check"></i> Collected</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-time bx-sm text-warning"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">Pending Amount</span>
                <h3 class="card-title text-nowrap mb-1" id="pendingAmount">₱0.00</h3>
                <small class="text-warning fw-semibold"><i class="bx bx-time-five"></i> Outstanding</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <i class="bx bx-file bx-sm text-info"></i>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">Total Bills</span>
                <h3 class="card-title text-nowrap mb-1" id="totalBills">0</h3>
                <small class="text-info fw-semibold"><i class="bx bx-file"></i> Records</small>
            </div>
        </div>
    </div>
</div>

<!-- Billing Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Billing Management</h4>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newBillModal">
                        <i class="bx bx-plus me-1"></i> New Bill
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bx bx-filter me-1"></i> Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item filter-status" href="#" data-status="">All Bills</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="paid">Paid</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="partial">Partial</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="pending">Pending</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="overdue">Overdue</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="billingTable">
                        <thead>
                            <tr>
                                <th>Bill ID</th>
                                <th>Patient Name</th>
                                <th>Date</th>
                                <th>Consultation</th>
                                <th>Lab Charges</th>
                                <th>Medication</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Content loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Bill Modal -->
<div class="modal fade" id="newBillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newBillForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                            <select class="form-select" id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                                <!-- Populated via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="appointment_id" class="form-label">Appointment (Optional)</label>
                            <select class="form-select" id="appointment_id" name="appointment_id">
                                <option value="">No Associated Appointment</option>
                                <!-- Populated via AJAX -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="billing_date" class="form-label">Billing Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="billing_date" name="billing_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="consultation_fee" class="form-label">Consultation Fee</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" min="0" step="0.01" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="lab_charges" class="form-label">Lab Charges</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="lab_charges" name="lab_charges" min="0" step="0.01" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="medication_charges" class="form-label">Medication Charges</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="medication_charges" name="medication_charges" min="0" step="0.01" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="discount" class="form-label">Discount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="discount" name="discount" min="0" step="0.01" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tax_amount" class="form-label">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="tax_amount" name="tax_amount" min="0" step="0.01" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="total_amount" class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="total_amount" name="total_amount" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paid_amount" class="form-label">Paid Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="paid_amount" name="paid_amount" min="0" step="0.01" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="insurance">Insurance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes or comments"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Bill Modal -->
<div class="modal fade" id="editBillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBillForm">
                <input type="hidden" id="edit_billing_id" name="billing_id">
                <div class="modal-body">
                    <!-- Same form fields as new bill modal -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_appointment_id" class="form-label">Appointment (Optional)</label>
                            <select class="form-select" id="edit_appointment_id" name="appointment_id">
                                <option value="">No Associated Appointment</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_billing_date" class="form-label">Billing Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_billing_date" name="billing_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="edit_due_date" name="due_date">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_consultation_fee" class="form-label">Consultation Fee</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_consultation_fee" name="consultation_fee" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_lab_charges" class="form-label">Lab Charges</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_lab_charges" name="lab_charges" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_medication_charges" class="form-label">Medication Charges</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_medication_charges" name="medication_charges" min="0" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_discount" class="form-label">Discount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_discount" name="discount" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_tax_amount" class="form-label">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_tax_amount" name="tax_amount" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_total_amount" class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_total_amount" name="total_amount" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_paid_amount" class="form-label">Paid Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_paid_amount" name="paid_amount" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="edit_payment_method" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="insurance">Insurance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let billingTable;
let currentFilter = '';

$(document).ready(function() {
    // Initialize DataTable
    initializeBillingTable();
    
    // Load statistics
    loadBillingStatistics();
    
    // Load patients for dropdowns
    loadPatientsForDropdown();
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    $('#billing_date').val(today);
    $('#edit_billing_date').val(today);
    
    // Auto-calculate total amount
    $('#newBillForm input[name="consultation_fee"], #newBillForm input[name="lab_charges"], #newBillForm input[name="medication_charges"], #newBillForm input[name="discount"], #newBillForm input[name="tax_amount"]').on('input', function() {
        calculateTotal('#newBillForm');
    });
    
    $('#editBillForm input[name="consultation_fee"], #editBillForm input[name="lab_charges"], #editBillForm input[name="medication_charges"], #editBillForm input[name="discount"], #editBillForm input[name="tax_amount"]').on('input', function() {
        calculateTotal('#editBillForm');
    });
    
    // Form submissions
    $('#newBillForm').on('submit', handleCreateBill);
    $('#editBillForm').on('submit', handleUpdateBill);
    
    // Filter handlers
    $('.filter-status').on('click', function(e) {
        e.preventDefault();
        currentFilter = $(this).data('status');
        billingTable.ajax.reload();
    });
    
    // Patient selection handler for appointments
    $('#patient_id, #edit_patient_id').on('change', function() {
        const patientId = $(this).val();
        const isEdit = $(this).attr('id').startsWith('edit_');
        
        if (patientId) {
            loadAppointmentsForPatient(patientId, isEdit);
        } else {
            const appointmentSelect = isEdit ? '#edit_appointment_id' : '#appointment_id';
            $(appointmentSelect).html('<option value="">No Associated Appointment</option>');
        }
    });
});

function initializeBillingTable() {
    billingTable = $('#billingTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../ajax/get_billing.php',
            data: function(d) {
                d.status_filter = currentFilter;
                return d;
            }
        },
        columns: [
            { data: 'bill_id', name: 'bill_id' },
            { data: 'patient_name', name: 'patient_name', orderable: false },
            { 
                data: 'billing_date', 
                name: 'billing_date',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            { 
                data: 'consultation_fee', 
                name: 'consultation_fee',
                render: function(data) {
                    return '₱' + parseFloat(data || 0).toFixed(2);
                }
            },
            { 
                data: 'lab_charges', 
                name: 'lab_charges',
                render: function(data) {
                    return '₱' + parseFloat(data || 0).toFixed(2);
                }
            },
            { 
                data: 'medication_charges', 
                name: 'medication_charges',
                render: function(data) {
                    return '₱' + parseFloat(data || 0).toFixed(2);
                }
            },
            { 
                data: 'total_amount', 
                name: 'total_amount',
                render: function(data) {
                    return '₱' + parseFloat(data || 0).toFixed(2);
                }
            },
            { 
                data: 'paid_amount', 
                name: 'paid_amount',
                render: function(data) {
                    return '₱' + parseFloat(data || 0).toFixed(2);
                }
            },
            { 
                data: 'payment_status', 
                name: 'payment_status',
                render: function(data) {
                    const badges = {
                        'paid': '<span class="badge bg-success">Paid</span>',
                        'partial': '<span class="badge bg-warning">Partial</span>',
                        'pending': '<span class="badge bg-secondary">Pending</span>',
                        'overdue': '<span class="badge bg-danger">Overdue</span>'
                    };
                    return badges[data] || '<span class="badge bg-secondary">Pending</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="viewBill(${row.id})"><i class="bx bx-show me-1"></i>View</a></li>
                                <li><a class="dropdown-item" href="#" onclick="editBill(${row.id})"><i class="bx bx-edit me-1"></i>Edit</a></li>
                                <li><a class="dropdown-item" href="#" onclick="printBill(${row.id})"><i class="bx bx-printer me-1"></i>Print</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteBill(${row.id})"><i class="bx bx-trash me-1"></i>Delete</a></li>
                            </ul>
                        </div>
                    `;
                }
            }
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading billing records...",
            emptyTable: "No billing records found"
        }
    });
}

function loadBillingStatistics() {
    $.ajax({
        url: '../ajax/get_billing_statistics.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#totalRevenue').text('₱' + parseFloat(stats.total_revenue || 0).toFixed(2));
                $('#paidAmount').text('₱' + parseFloat(stats.paid_amount || 0).toFixed(2));
                $('#pendingAmount').text('₱' + parseFloat(stats.pending_amount || 0).toFixed(2));
                $('#totalBills').text(stats.total_bills || 0);
            }
        },
        error: function() {
            console.error('Failed to load billing statistics');
        }
    });
}

function loadPatientsForDropdown() {
    $.ajax({
        url: '../ajax/get_patients.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Select Patient</option>';
                response.data.forEach(function(patient) {
                    options += `<option value="${patient.id}">${patient.first_name} ${patient.last_name}</option>`;
                });
                $('#patient_id, #edit_patient_id').html(options);
            }
        },
        error: function() {
            showAlert('error', 'Failed to load patients');
        }
    });
}

function loadAppointmentsForPatient(patientId, isEdit = false) {
    const appointmentSelect = isEdit ? '#edit_appointment_id' : '#appointment_id';
    
    $.ajax({
        url: '../ajax/get_appointments.php',
        method: 'GET',
        data: { patient_id: patientId },
        success: function(response) {
            if (response.success) {
                let options = '<option value="">No Associated Appointment</option>';
                response.data.forEach(function(appointment) {
                    const date = new Date(appointment.appointment_date).toLocaleDateString();
                    options += `<option value="${appointment.id}">${date} - ${appointment.appointment_time}</option>`;
                });
                $(appointmentSelect).html(options);
            }
        },
        error: function() {
            console.error('Failed to load appointments for patient');
        }
    });
}

function calculateTotal(formSelector) {
    const consultationFee = parseFloat($(formSelector + ' input[name="consultation_fee"]').val() || 0);
    const labCharges = parseFloat($(formSelector + ' input[name="lab_charges"]').val() || 0);
    const medicationCharges = parseFloat($(formSelector + ' input[name="medication_charges"]').val() || 0);
    const discount = parseFloat($(formSelector + ' input[name="discount"]').val() || 0);
    const taxAmount = parseFloat($(formSelector + ' input[name="tax_amount"]').val() || 0);
    
    const subtotal = consultationFee + labCharges + medicationCharges;
    const total = subtotal - discount + taxAmount;
    
    $(formSelector + ' input[name="total_amount"]').val(total.toFixed(2));
}

function handleCreateBill(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    $.ajax({
        url: '../ajax/create_billing.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                $('#newBillModal').modal('hide');
                $('#newBillForm')[0].reset();
                billingTable.ajax.reload();
                loadBillingStatistics();
                showAlert('success', 'Billing record created successfully');
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Failed to create billing record');
        }
    });
}

function handleUpdateBill(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    $.ajax({
        url: '../ajax/update_billing.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                $('#editBillModal').modal('hide');
                billingTable.ajax.reload();
                loadBillingStatistics();
                showAlert('success', 'Billing record updated successfully');
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Failed to update billing record');
        }
    });
}

function editBill(billingId) {
    $.ajax({
        url: '../ajax/get_billing_details.php',
        method: 'GET',
        data: { id: billingId },
        success: function(response) {
            if (response.success) {
                const bill = response.data;
                
                // Populate edit form
                $('#edit_billing_id').val(bill.id);
                $('#edit_patient_id').val(bill.patient_id);
                $('#edit_appointment_id').val(bill.appointment_id || '');
                $('#edit_billing_date').val(bill.billing_date);
                $('#edit_due_date').val(bill.due_date || '');
                $('#edit_consultation_fee').val(bill.consultation_fee || 0);
                $('#edit_lab_charges').val(bill.lab_charges || 0);
                $('#edit_medication_charges').val(bill.medication_charges || 0);
                $('#edit_discount').val(bill.discount || 0);
                $('#edit_tax_amount').val(bill.tax_amount || 0);
                $('#edit_total_amount').val(bill.total_amount || 0);
                $('#edit_paid_amount').val(bill.paid_amount || 0);
                $('#edit_payment_method').val(bill.payment_method || 'cash');
                $('#edit_notes').val(bill.notes || '');
                
                // Load appointments for selected patient
                if (bill.patient_id) {
                    loadAppointmentsForPatient(bill.patient_id, true);
                }
                
                $('#editBillModal').modal('show');
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Failed to load billing record');
        }
    });
}

function viewBill(billingId) {
    // Implement view functionality - could open a detailed view modal or redirect
    window.open(`../reports/bill_details.php?id=${billingId}`, '_blank');
}

function printBill(billingId) {
    // Implement print functionality
    window.open(`../reports/print_bill.php?id=${billingId}`, '_blank');
}

function deleteBill(billingId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the billing record!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implement delete functionality
            showAlert('info', 'Delete functionality to be implemented');
        }
    });
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the container
    $('.container-xxl').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layout.php';
?>
