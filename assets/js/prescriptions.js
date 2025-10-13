/**
 * Prescription Management JavaScript
 * Handles all client-side functionality for prescription management
 */

$(document).ready(function() {
    let currentPage = 1;
    let currentFilters = {};
    let medicationsList = [];
    let pharmaciesList = [];

    // Initialize the page
    initializePrescriptions();

    /**
     * Initialize prescription management
     */
    function initializePrescriptions() {
        setupEventListeners();
        loadPrescriptions();
        loadMedications();
        loadPharmacies();
        loadPatients();
        loadDoctors();
    }

    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Filter controls
        $('#searchPrescriptionsBtn').click(function() {
            $('#prescriptionFilters').toggle();
        });

        // Apply filters
        $('#statusFilter, #dateFromFilter, #dateToFilter, #searchFilter').on('change keyup', function() {
            currentFilters = {
                status: $('#statusFilter').val(),
                date_from: $('#dateFromFilter').val(),
                date_to: $('#dateToFilter').val(),
                search: $('#searchFilter').val()
            };
            currentPage = 1;
            loadPrescriptions();
        });

        // New prescription
        $('#newPrescriptionBtn, #quickPrescriptionBtn').click(function() {
            openPrescriptionModal();
        });

        // Add medication to prescription
        $('#addMedicationBtn').click(function() {
            addMedicationItem();
        });

        // Remove medication item
        $(document).on('click', '.remove-medication', function() {
            $(this).closest('.medication-item').remove();
            updatePrescriptionTotal();
        });

        // Save prescription
        $('#savePrescriptionBtn').click(function() {
            savePrescription();
        });

        // Preview prescription
        $('#previewPrescriptionBtn').click(function() {
            previewPrescription();
        });

        // View prescription details
        $(document).on('click', '.view-prescription', function() {
            const prescriptionId = $(this).data('id');
            viewPrescriptionDetails(prescriptionId);
        });

        // Update prescription status
        $(document).on('click', '.update-status', function() {
            const prescriptionId = $(this).data('id');
            const currentStatus = $(this).data('status');
            updatePrescriptionStatus(prescriptionId, currentStatus);
        });

        // Process refill
        $(document).on('click', '.process-refill', function() {
            const prescriptionId = $(this).data('id');
            processRefill(prescriptionId);
        });

        // Print prescription
        $('#printPrescriptionBtn').click(function() {
            printPrescription();
        });

        // Send to pharmacy
        $('#sendToPharmacyBtn').click(function() {
            sendToPharmacy();
        });

        // Medication selection change
        $(document).on('change', '.medication-select', function() {
            const medicationId = $(this).val();
            if (medicationId) {
                loadMedicationDetails(medicationId, $(this).closest('.medication-item'));
            }
        });

        // Quick actions
        $('#templatesBtn').click(function() {
            loadPrescriptionTemplates();
        });

        $('#medicationsBtn').click(function() {
            showMedicationsDatabase();
        });

        $('#interactionsBtn').click(function() {
            showDrugInteractions();
        });

        $('#allergiesBtn').click(function() {
            showPatientAllergies();
        });

        // Patient selection change
        $('#patientSelect').change(function() {
            const patientId = $(this).val();
            if (patientId) {
                checkPatientAllergies(patientId);
            }
        });
    }

    /**
     * Load prescriptions with filters and pagination
     */
    function loadPrescriptions() {
        showLoading('Loading prescriptions...');

        const params = {
            page: currentPage,
            ...currentFilters
        };

        $.ajax({
            url: 'ajax/get_prescriptions.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayPrescriptions(response.data);
                    displayPagination(response.pagination);
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to load prescriptions', 'error');
            }
        });
    }

    /**
     * Display prescriptions in table
     */
    function displayPrescriptions(prescriptions) {
        const tbody = $('#prescriptionsTableBody');
        tbody.empty();

        if (prescriptions.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center text-muted">No prescriptions found</td>
                </tr>
            `);
            return;
        }

        prescriptions.forEach(function(prescription) {
            const statusBadge = getStatusBadge(prescription.status);
            const row = `
                <tr>
                    <td>
                        <strong>${prescription.prescription_number}</strong>
                    </td>
                    <td>${prescription.patient_name}</td>
                    <td>${prescription.doctor_name}</td>
                    <td>${formatDate(prescription.prescription_date)}</td>
                    <td>
                        <span class="badge badge-info">${prescription.item_count} items</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>$${parseFloat(prescription.total_cost || 0).toFixed(2)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-info btn-sm view-prescription" 
                                    data-id="${prescription.id}" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-warning btn-sm update-status" 
                                    data-id="${prescription.id}" 
                                    data-status="${prescription.status}" title="Update Status">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${prescription.status === 'active' ? 
                                `<button class="btn btn-success btn-sm process-refill" 
                                         data-id="${prescription.id}" title="Process Refill">
                                    <i class="fas fa-redo"></i>
                                </button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    /**
     * Display pagination
     */
    function displayPagination(pagination) {
        const container = $('#prescriptionsPagination');
        container.empty();

        if (pagination.total_pages <= 1) return;

        // Previous button
        container.append(`
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
            </li>
        `);

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            container.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        // Next button
        container.append(`
            <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>
            </li>
        `);

        // Handle pagination clicks
        container.find('a.page-link').click(function(e) {
            e.preventDefault();
            if (!$(this).parent().hasClass('disabled')) {
                currentPage = parseInt($(this).data('page'));
                loadPrescriptions();
            }
        });
    }

    /**
     * Open prescription modal for new prescription
     */
    function openPrescriptionModal(prescriptionId = null) {
        $('#prescriptionModal').modal('show');
        $('#medicationItems').empty();
        
        if (prescriptionId) {
            // Load existing prescription for editing
            $('#prescriptionModalTitle').text('Edit Prescription');
            loadPrescriptionForEdit(prescriptionId);
        } else {
            // New prescription
            $('#prescriptionModalTitle').text('New Prescription');
            $('#prescriptionForm')[0].reset();
            addMedicationItem(); // Add first medication item
        }
    }

    /**
     * Add medication item to prescription
     */
    function addMedicationItem() {
        const template = document.getElementById('medicationItemTemplate');
        const clone = template.content.cloneNode(true);
        
        // Populate medication select
        const medicationSelect = clone.querySelector('.medication-select');
        medicationsList.forEach(function(med) {
            const option = document.createElement('option');
            option.value = med.id;
            option.textContent = `${med.medication_name} - ${med.strength}`;
            medicationSelect.appendChild(option);
        });

        $('#medicationItems').append(clone);
    }

    /**
     * Load medications list
     */
    function loadMedications() {
        $.ajax({
            url: 'ajax/get_medications.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    medicationsList = response.data;
                }
            }
        });
    }

    /**
     * Load pharmacies list
     */
    function loadPharmacies() {
        $.ajax({
            url: 'ajax/get_pharmacies.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    pharmaciesList = response.data;
                    const select = $('#pharmacySelect');
                    select.empty().append('<option value="">Select Pharmacy</option>');
                    response.data.forEach(function(pharmacy) {
                        select.append(`<option value="${pharmacy.id}">${pharmacy.pharmacy_name}</option>`);
                    });
                }
            }
        });
    }

    /**
     * Load patients for selection
     */
    function loadPatients() {
        $.ajax({
            url: 'ajax/get_patients.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const select = $('#patientSelect');
                    select.empty().append('<option value="">Select Patient</option>');
                    response.data.forEach(function(patient) {
                        select.append(`<option value="${patient.id}">${patient.first_name} ${patient.last_name} - DOB: ${patient.date_of_birth}</option>`);
                    });
                }
            }
        });
    }

    /**
     * Load doctors for selection (admin only)
     */
    function loadDoctors() {
        if ($('#doctorSelect').length) {
            $.ajax({
                url: 'ajax/get_doctors.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const select = $('#doctorSelect');
                        select.empty().append('<option value="">Select Doctor</option>');
                        response.data.forEach(function(doctor) {
                            select.append(`<option value="${doctor.id}">${doctor.first_name} ${doctor.last_name} - ${doctor.specialization}</option>`);
                        });
                    }
                }
            });
        }
    }

    /**
     * Save prescription
     */
    function savePrescription() {
        const formData = new FormData($('#prescriptionForm')[0]);
        
        // Collect medication items
        const items = [];
        $('.medication-item').each(function() {
            const item = {};
            $(this).find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name && $(this).val()) {
                    item[name] = $(this).val();
                }
            });
            if (item.medication_id && item.quantity && item.dosage_instruction && item.frequency) {
                items.push(item);
            }
        });

        if (items.length === 0) {
            showAlert('Warning', 'Please add at least one medication', 'warning');
            return;
        }

        const prescriptionData = {};
        for (let [key, value] of formData.entries()) {
            prescriptionData[key] = value;
        }
        prescriptionData.items = items;

        showLoading('Saving prescription...');

        $.ajax({
            url: 'ajax/create_prescription.php',
            method: 'POST',
            data: JSON.stringify(prescriptionData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('Success', 'Prescription saved successfully', 'success');
                    $('#prescriptionModal').modal('hide');
                    loadPrescriptions();
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to save prescription', 'error');
            }
        });
    }

    /**
     * View prescription details
     */
    function viewPrescriptionDetails(prescriptionId) {
        showLoading('Loading prescription details...');

        $.ajax({
            url: 'ajax/get_prescription_details.php',
            method: 'GET',
            data: { id: prescriptionId },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayPrescriptionDetails(response.data);
                } else {
                    showAlert('Error', response.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showAlert('Error', 'Failed to load prescription details', 'error');
            }
        });
    }

    /**
     * Display prescription details in modal
     */
    function displayPrescriptionDetails(prescription) {
        let html = `
            <div class="prescription-details">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Prescription Information</h5>
                        <table class="table table-sm">
                            <tr><td><strong>Rx Number:</strong></td><td>${prescription.prescription_number}</td></tr>
                            <tr><td><strong>Date:</strong></td><td>${formatDate(prescription.prescription_date)}</td></tr>
                            <tr><td><strong>Status:</strong></td><td>${getStatusBadge(prescription.status)}</td></tr>
                            <tr><td><strong>Total Cost:</strong></td><td>$${parseFloat(prescription.total_cost || 0).toFixed(2)}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Patient Information</h5>
                        <table class="table table-sm">
                            <tr><td><strong>Name:</strong></td><td>${prescription.patient_name}</td></tr>
                            <tr><td><strong>DOB:</strong></td><td>${formatDate(prescription.date_of_birth)}</td></tr>
                            <tr><td><strong>Phone:</strong></td><td>${prescription.phone_number || 'N/A'}</td></tr>
                            <tr><td><strong>Doctor:</strong></td><td>${prescription.doctor_name}</td></tr>
                        </table>
                    </div>
                </div>

                <hr>
                <h5>Prescribed Medications</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Strength</th>
                                <th>Quantity</th>
                                <th>Instructions</th>
                                <th>Refills</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        prescription.items.forEach(function(item) {
            html += `
                <tr>
                    <td>
                        <strong>${item.medication_name}</strong>
                        ${item.generic_name ? `<br><small class="text-muted">(${item.generic_name})</small>` : ''}
                    </td>
                    <td>${item.strength}</td>
                    <td>${item.quantity} ${item.unit}</td>
                    <td>
                        ${item.dosage_instruction}<br>
                        <small class="text-muted">Frequency: ${item.frequency}</small>
                        ${item.special_instructions ? `<br><small class="text-warning">${item.special_instructions}</small>` : ''}
                    </td>
                    <td>${item.refills_remaining}/${item.refills_allowed}</td>
                    <td>${getStatusBadge(item.status)}</td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
        `;

        // Add allergies section if available
        if (prescription.patient_allergies && prescription.patient_allergies.length > 0) {
            html += `
                <hr>
                <h5>Patient Allergies</h5>
                <div class="alert alert-warning">
            `;
            prescription.patient_allergies.forEach(function(allergy) {
                html += `
                    <div class="mb-1">
                        <strong>${allergy.allergen_name}</strong> 
                        <span class="badge badge-${getSeverityBadgeColor(allergy.severity)}">${allergy.severity}</span>
                        ${allergy.symptoms ? `- ${allergy.symptoms}` : ''}
                    </div>
                `;
            });
            html += `</div>`;
        }

        // Add refill history if available
        if (prescription.refills && prescription.refills.length > 0) {
            html += `
                <hr>
                <h5>Refill History</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Date</th><th>Medication</th><th>Quantity</th><th>Dispensed By</th></tr>
                        </thead>
                        <tbody>
            `;
            prescription.refills.forEach(function(refill) {
                html += `
                    <tr>
                        <td>${formatDate(refill.refill_date)}</td>
                        <td>${refill.medication_name}</td>
                        <td>${refill.quantity_dispensed}</td>
                        <td>${refill.dispensed_by || 'N/A'}</td>
                    </tr>
                `;
            });
            html += `</tbody></table></div>`;
        }

        // Add notes if available
        if (prescription.notes) {
            html += `
                <hr>
                <h5>Notes</h5>
                <div class="alert alert-info">${prescription.notes}</div>
            `;
        }

        html += `</div>`;

        $('#prescriptionDetailsContent').html(html);
        $('#prescriptionDetailsModal').modal('show');
        
        // Store prescription ID for actions
        $('#prescriptionDetailsModal').data('prescription-id', prescription.id);
    }

    /**
     * Update prescription status
     */
    function updatePrescriptionStatus(prescriptionId, currentStatus) {
        const statuses = ['active', 'completed', 'cancelled', 'expired', 'discontinued'];
        const statusOptions = statuses.map(status => 
            `<option value="${status}" ${status === currentStatus ? 'selected' : ''}>${capitalizeFirst(status)}</option>`
        ).join('');

        Swal.fire({
            title: 'Update Prescription Status',
            html: `
                <select id="statusSelect" class="form-control">
                    ${statusOptions}
                </select>
                <textarea id="reasonText" class="form-control mt-2" placeholder="Reason for change (optional)"></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update',
            preConfirm: () => {
                return {
                    status: document.getElementById('statusSelect').value,
                    reason: document.getElementById('reasonText').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const data = result.value;
                
                $.ajax({
                    url: 'ajax/update_prescription_status.php',
                    method: 'POST',
                    data: {
                        prescription_id: prescriptionId,
                        status: data.status,
                        reason: data.reason
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert('Success', 'Status updated successfully', 'success');
                            loadPrescriptions();
                        } else {
                            showAlert('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        showAlert('Error', 'Failed to update status', 'error');
                    }
                });
            }
        });
    }

    /**
     * Check patient allergies
     */
    function checkPatientAllergies(patientId) {
        $.ajax({
            url: 'ajax/get_patient_allergies.php',
            method: 'GET',
            data: { patient_id: patientId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let allergyList = response.data.map(allergy => 
                        `${allergy.allergen_name} (${allergy.severity})`
                    ).join(', ');
                    
                    showAlert('Patient Allergies', `Patient has known allergies: ${allergyList}`, 'warning');
                }
            }
        });
    }

    // Utility Functions

    /**
     * Get status badge HTML
     */
    function getStatusBadge(status) {
        const badges = {
            'active': '<span class="badge badge-success">Active</span>',
            'completed': '<span class="badge badge-primary">Completed</span>',
            'cancelled': '<span class="badge badge-danger">Cancelled</span>',
            'expired': '<span class="badge badge-warning">Expired</span>',
            'discontinued': '<span class="badge badge-secondary">Discontinued</span>'
        };
        return badges[status] || '<span class="badge badge-light">Unknown</span>';
    }

    /**
     * Get severity badge color
     */
    function getSeverityBadgeColor(severity) {
        const colors = {
            'mild': 'info',
            'moderate': 'warning',
            'severe': 'danger',
            'life_threatening': 'dark'
        };
        return colors[severity] || 'secondary';
    }

    /**
     * Format date for display
     */
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString();
    }

    /**
     * Capitalize first letter
     */
    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * Show loading modal
     */
    function showLoading(message = 'Loading...') {
        // Implementation depends on your loading modal setup
        console.log(message);
    }

    /**
     * Hide loading modal
     */
    function hideLoading() {
        // Implementation depends on your loading modal setup
    }

    /**
     * Show alert message
     */
    function showAlert(title, message, type = 'info') {
        // Use SweetAlert or your preferred notification system
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info'
            });
        } else {
            alert(`${title}: ${message}`);
        }
    }
});
