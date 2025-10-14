$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Load billing data
    loadBillingStats();
    loadBills();
    
    // Payment form submit
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        recordPayment();
    });
});

/**
 * Check authentication
 */
function checkAuth() {
    $.ajax({
        url: '../ajax/check_session.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!response.success || response.user_type !== 'receptionist') {
                window.location.href = '../login-new.html';
            }
        },
        error: function() {
            window.location.href = '../login-new.html';
        }
    });
}

/**
 * Load billing statistics
 */
function loadBillingStats() {
    $.ajax({
        url: '../ajax/get_billing_statistics.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const stats = response.data;
                $('#totalRevenue').text(formatMoney(stats.total_revenue || 0));
                $('#pendingAmount').text(formatMoney(stats.pending_amount || 0));
                $('#paidToday').text(formatMoney(stats.paid_today || 0));
                $('#totalBills').text(stats.total_bills || 0);
            }
        }
    });
}

/**
 * Load bills
 */
function loadBills() {
    const params = {};
    
    const filterStatus = $('#filterStatus').val();
    const filterDateFrom = $('#filterDateFrom').val();
    const filterDateTo = $('#filterDateTo').val();
    
    if (filterStatus) params.status = filterStatus;
    if (filterDateFrom) params.date_from = filterDateFrom;
    if (filterDateTo) params.date_to = filterDateTo;
    
    $('#billsContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    $.ajax({
        url: '../ajax/get_billing.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayBills(response.data);
            } else {
                showNoBills();
            }
        },
        error: function() {
            showErrorMessage();
        }
    });
}

/**
 * Display bills
 */
function displayBills(bills) {
    if (!bills || bills.length === 0) {
        showNoBills();
        return;
    }
    
    $('#billCount').text(bills.length);
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    bills.forEach(function(bill) {
        const statusClass = getStatusClass(bill.payment_status);
        const statusText = capitalize(bill.payment_status || 'pending');
        const balance = (parseFloat(bill.total_amount) - parseFloat(bill.amount_paid || 0)).toFixed(2);
        
        html += `
            <tr>
                <td><strong>${escapeHtml(bill.bill_id || bill.id)}</strong></td>
                <td>
                    <div>${escapeHtml(bill.patient_name || 'N/A')}</div>
                    <small class="text-muted">${escapeHtml(bill.patient_id || '')}</small>
                </td>
                <td>${formatDate(bill.created_at || bill.bill_date)}</td>
                <td>₱${formatMoney(bill.total_amount)}</td>
                <td>₱${formatMoney(bill.amount_paid || 0)}</td>
                <td class="${balance > 0 ? 'text-danger' : 'text-success'}">
                    ₱${formatMoney(balance)}
                </td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewBillDetails(${bill.id})">
                                <i class="bx bx-show me-1"></i> View Details
                            </a>
                            ${bill.payment_status !== 'paid' ? `
                                <a class="dropdown-item" href="javascript:void(0);" onclick="showPaymentModal(${bill.id}, ${bill.total_amount}, ${bill.amount_paid || 0})">
                                    <i class="bx bx-money me-1"></i> Record Payment
                                </a>
                            ` : ''}
                            <a class="dropdown-item" href="javascript:void(0);" onclick="printBill(${bill.id})">
                                <i class="bx bx-printer me-1"></i> Print
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#billsContainer').html(html);
}

/**
 * Show create bill modal (placeholder)
 */
function showCreateBillModal() {
    alert('Create bill feature - would redirect to billing creation page');
}

/**
 * Show payment modal
 */
function showPaymentModal(billId, totalAmount, amountPaid) {
    const balance = totalAmount - amountPaid;
    
    $('#billId').val(billId);
    $('#totalAmount').val('₱' + formatMoney(totalAmount));
    $('#amountPaid').val(balance.toFixed(2));
    $('#amountPaid').attr('max', balance);
    $('#paymentMethod').val('');
    $('#paymentNotes').val('');
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

/**
 * Record payment
 */
function recordPayment() {
    const formData = {
        bill_id: $('#billId').val(),
        amount_paid: $('#amountPaid').val(),
        payment_method: $('#paymentMethod').val(),
        notes: $('#paymentNotes').val()
    };
    
    $.ajax({
        url: '../ajax/record_payment.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Payment recorded successfully');
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                loadBillingStats();
                loadBills();
            } else {
                alert(response.message || 'Failed to record payment');
            }
        },
        error: function() {
            alert('Error recording payment. Please try again.');
        }
    });
}

/**
 * View bill details
 */
function viewBillDetails(billId) {
    $.ajax({
        url: '../ajax/get_billing_details.php',
        type: 'GET',
        data: { bill_id: billId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                // Could display in a modal or redirect to details page
                alert('Bill Details:\n' + JSON.stringify(response.data, null, 2));
            } else {
                alert('Failed to load bill details');
            }
        }
    });
}

/**
 * Print bill
 */
function printBill(billId) {
    window.open(`../reports/bill_print.php?id=${billId}`, '_blank');
}

/**
 * Apply filters
 */
function applyFilters() {
    loadBills();
}

/**
 * Show no bills message
 */
function showNoBills() {
    $('#billCount').text('0');
    $('#billsContainer').html(`
        <div class="text-center py-5">
            <i class="bx bx-receipt" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">No bills found</p>
        </div>
    `);
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#billsContainer').html(`
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error me-2"></i>
            Failed to load bills. Please try refreshing the page.
        </div>
    `);
}

/**
 * Get status badge class
 */
function getStatusClass(status) {
    const classes = {
        'pending': 'bg-label-warning',
        'partial': 'bg-label-info',
        'paid': 'bg-label-success',
        'cancelled': 'bg-label-danger'
    };
    return classes[status] || 'bg-label-secondary';
}

// ===== Helper Functions =====

function formatMoney(amount) {
    if (!amount && amount !== 0) return '0.00';
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(date) {
    if (!date) return 'N/A';
    const d = new Date(date);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return d.toLocaleDateString('en-US', options);
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
