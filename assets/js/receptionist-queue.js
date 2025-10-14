$(document).ready(function() {
    // Check authentication
    checkAuth();
    
    // Load queue
    loadQueue();
    
    // Auto-refresh every 30 seconds
    setInterval(loadQueue, 30000);
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
 * Load queue
 */
function loadQueue() {
    $.ajax({
        url: '../ajax/get_queue.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateStats(response.stats || {});
                displayQueue(response.data);
            } else {
                showEmptyQueue();
            }
        },
        error: function() {
            showErrorMessage();
        }
    });
}

/**
 * Update stats cards
 */
function updateStats(stats) {
    $('#waitingCount').text(stats.waiting || 0);
    $('#inProgressCount').text(stats.in_progress || 0);
    $('#avgWaitTime').text(stats.avg_wait_time || 0);
}

/**
 * Display queue
 */
function displayQueue(queue) {
    if (!queue || queue.length === 0) {
        showEmptyQueue();
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Queue #</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Check-in Time</th>
                        <th>Wait Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    queue.forEach(function(item, index) {
        const queueNumber = index + 1;
        const statusClass = getStatusClass(item.status);
        const statusText = capitalize(item.status || 'waiting');
        const waitTime = calculateWaitTime(item.check_in_time);
        
        html += `
            <tr>
                <td><strong class="text-primary">#${queueNumber}</strong></td>
                <td>
                    <div>${escapeHtml(item.patient_name || 'N/A')}</div>
                    <small class="text-muted">${escapeHtml(item.patient_id || '')}</small>
                </td>
                <td>${escapeHtml(item.doctor_name || 'N/A')}</td>
                <td>${formatTime(item.check_in_time)}</td>
                <td>
                    <span class="${waitTime > 30 ? 'text-danger' : 'text-muted'}">
                        ${waitTime} mins
                    </span>
                </td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>
                    ${item.status === 'waiting' ? `
                        <button type="button" class="btn btn-sm btn-success" onclick="callNextPatient(${item.id})">
                            <i class="bx bx-phone-call me-1"></i>Call
                        </button>
                    ` : ''}
                    ${item.status === 'in-progress' ? `
                        <button type="button" class="btn btn-sm btn-info" disabled>
                            <i class="bx bx-loader bx-spin me-1"></i>In Progress
                        </button>
                    ` : ''}
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFromQueue(${item.id})">
                        <i class="bx bx-x"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#queueContainer').html(html);
}

/**
 * Call next patient
 */
function callNextPatient(queueId) {
    $.ajax({
        url: '../ajax/get_next_patient.php',
        type: 'POST',
        data: { queue_id: queueId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Patient called successfully');
                loadQueue();
            } else {
                alert(response.message || 'Failed to call patient');
            }
        },
        error: function() {
            alert('Error calling patient. Please try again.');
        }
    });
}

/**
 * Remove from queue
 */
function removeFromQueue(queueId) {
    if (!confirm('Remove this patient from the queue?')) return;
    
    $.ajax({
        url: '../ajax/remove_from_queue.php',
        type: 'POST',
        data: { queue_id: queueId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Patient removed from queue');
                loadQueue();
            } else {
                alert(response.message || 'Failed to remove from queue');
            }
        },
        error: function() {
            alert('Error removing from queue. Please try again.');
        }
    });
}

/**
 * Refresh queue
 */
function refreshQueue() {
    loadQueue();
}

/**
 * Show empty queue
 */
function showEmptyQueue() {
    updateStats({});
    $('#queueContainer').html(`
        <div class="text-center py-5">
            <i class="bx bx-list-ul" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">No patients in queue</p>
        </div>
    `);
}

/**
 * Show error message
 */
function showErrorMessage() {
    $('#queueContainer').html(`
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error me-2"></i>
            Failed to load queue. Please try refreshing the page.
        </div>
    `);
}

/**
 * Calculate wait time in minutes
 */
function calculateWaitTime(checkInTime) {
    if (!checkInTime) return 0;
    
    const now = new Date();
    const checkIn = new Date(checkInTime);
    const diffMs = now - checkIn;
    const diffMins = Math.floor(diffMs / 60000);
    
    return diffMins;
}

/**
 * Get status badge class
 */
function getStatusClass(status) {
    const classes = {
        'waiting': 'bg-label-warning',
        'in-progress': 'bg-label-info',
        'completed': 'bg-label-success',
        'cancelled': 'bg-label-danger'
    };
    return classes[status] || 'bg-label-secondary';
}

// ===== Helper Functions =====

function formatTime(datetime) {
    if (!datetime) return 'N/A';
    
    try {
        const d = new Date(datetime);
        let hours = d.getHours();
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    } catch (e) {
        return datetime;
    }
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
