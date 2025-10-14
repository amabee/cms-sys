/**
 * Reports Management System
 * Handles comprehensive reporting functionality for clinic management
 */

let currentReportData = null;
let currentReportType = 'patient_demographics';
let chartInstances = {};

// Initialize the reports system
document.addEventListener('DOMContentLoaded', function() {
    console.log('Reports system initializing...');
    
    // Load initial dashboard statistics
    loadDashboardStatistics();
    
    // Load dropdown options
    loadDoctors();
    loadLabCategories();
    
    // Set default date ranges
    setDefaultDateRanges();
    
    // Load first report by default
    loadPatientDemographics();
    
    // Setup tab change handlers
    setupTabHandlers();
    
    console.log('Reports system initialized');
});

/**
 * Load dashboard statistics
 */
function loadDashboardStatistics() {
    fetch('../ajax/get_reports_dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                
                // Update patient stats
                document.getElementById('total-patients').textContent = stats.patients.total_patients || '0';
                
                // Update appointment stats
                document.getElementById('total-appointments').textContent = stats.appointments.total_appointments || '0';
                
                // Update revenue stats
                const revenue = parseFloat(stats.revenue.total_revenue || 0);
                document.getElementById('total-revenue').textContent = '$' + revenue.toLocaleString();
                
                // Update lab tests stats
                document.getElementById('total-lab-tests').textContent = stats.lab_tests.total_tests || '0';
            }
        })
        .catch(error => {
            console.error('Error loading dashboard statistics:', error);
        });
}

/**
 * Load doctors for dropdowns
 */
function loadDoctors() {
    fetch('../ajax/get_doctors.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const doctors = data.data;
                const appointmentSelect = document.getElementById('appointments-doctor');
                const recordsSelect = document.getElementById('records-doctor');
                
                // Clear existing options (keep "All Doctors")
                appointmentSelect.innerHTML = '<option value="">All Doctors</option>';
                recordsSelect.innerHTML = '<option value="">All Doctors</option>';
                
                // Add doctor options
                doctors.forEach(doctor => {
                    const option1 = new Option(`Dr. ${doctor.first_name} ${doctor.last_name} (${doctor.specialization})`, doctor.id);
                    const option2 = new Option(`Dr. ${doctor.first_name} ${doctor.last_name} (${doctor.specialization})`, doctor.id);
                    
                    appointmentSelect.appendChild(option1);
                    recordsSelect.appendChild(option2);
                });
            }
        })
        .catch(error => {
            console.error('Error loading doctors:', error);
        });
}

/**
 * Load lab test categories
 */
function loadLabCategories() {
    fetch('../ajax/get_lab_test_categories.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const categories = data.data;
                const categorySelect = document.getElementById('lab-category');
                
                // Clear existing options (keep "All Categories")
                categorySelect.innerHTML = '<option value="">All Categories</option>';
                
                // Add category options
                categories.forEach(category => {
                    const option = new Option(category.test_category, category.test_category);
                    categorySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading lab categories:', error);
        });
}

/**
 * Set default date ranges (last 30 days)
 */
function setDefaultDateRanges() {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    
    const endDateStr = endDate.toISOString().split('T')[0];
    const startDateStr = startDate.toISOString().split('T')[0];
    
    // Set all date inputs
    document.getElementById('demographics-start-date').value = startDateStr;
    document.getElementById('demographics-end-date').value = endDateStr;
    document.getElementById('appointments-start-date').value = startDateStr;
    document.getElementById('appointments-end-date').value = endDateStr;
    document.getElementById('financial-start-date').value = startDateStr;
    document.getElementById('financial-end-date').value = endDateStr;
    document.getElementById('lab-start-date').value = startDateStr;
    document.getElementById('lab-end-date').value = endDateStr;
    document.getElementById('records-start-date').value = startDateStr;
    document.getElementById('records-end-date').value = endDateStr;
}

/**
 * Setup tab change handlers
 */
function setupTabHandlers() {
    const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    console.log('Found tabs:', tabs.length); // Debug log
    
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const targetId = this.getAttribute('data-bs-target');
            console.log('Tab switched to:', targetId); // Debug log
            
            // Update current report type and load data
            switch (targetId) {
                case '#patient-demographics':
                    currentReportType = 'patient_demographics';
                    loadPatientDemographics();
                    break;
                case '#appointment-analytics':
                    currentReportType = 'appointment_analytics';
                    loadAppointmentAnalytics();
                    break;
                case '#financial-report':
                    currentReportType = 'financial';
                    loadFinancialReport();
                    break;
                case '#lab-tests-report':
                    currentReportType = 'lab_tests';
                    loadLabTestsReport();
                    break;
                case '#medical-records-report':
                    currentReportType = 'medical_records';
                    loadMedicalRecordsReport();
                    break;
            }
        });
    });
}

/**
 * Load Patient Demographics Report
 */
function loadPatientDemographics() {
    currentReportType = 'patient_demographics';
    
    const startDate = document.getElementById('demographics-start-date').value;
    const endDate = document.getElementById('demographics-end-date').value;
    
    showLoading('demographics-loading');
    hideContent('demographics-content');
    
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    fetch(`../ajax/get_patient_demographics.php?${params}`)
        .then(response => response.json())
        .then(data => {
            hideLoading('demographics-loading');
            
            if (data.success) {
                currentReportData = data.data;
                
                // Create charts
                createGenderChart(data.data.gender_distribution);
                createAgeChart(data.data.age_distribution);
                createRegistrationTrendsChart(data.data.registration_trends);
                
                showContent('demographics-content');
            } else {
                showError('Error loading patient demographics: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            hideLoading('demographics-loading');
            showError('Error loading patient demographics: ' + error.message);
        });
}

/**
 * Load Appointment Analytics Report
 */
function loadAppointmentAnalytics() {
    console.log('loadAppointmentAnalytics called'); // Debug log
    currentReportType = 'appointment_analytics';
    
    const startDate = document.getElementById('appointments-start-date').value;
    const endDate = document.getElementById('appointments-end-date').value;
    const doctorId = document.getElementById('appointments-doctor').value;
    
    console.log('Dates:', startDate, endDate, 'Doctor:', doctorId); // Debug log
    
    showLoading('appointments-loading');
    hideContent('appointments-content');
    
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (doctorId) params.append('doctor_id', doctorId);
    
    fetch(`../ajax/get_appointment_analytics.php?${params}`)
        .then(response => response.json())
        .then(data => {
            hideLoading('appointments-loading');
            
            if (data.success) {
                currentReportData = data.data;
                
                // Create charts
                createAppointmentStatusChart(data.data.status_distribution);
                createPeakHoursChart(data.data.peak_hours);
                
                // Populate doctor performance table
                populateDoctorPerformanceTable(data.data.doctor_performance);
                
                showContent('appointments-content');
            } else {
                showError('Error loading appointment analytics: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            hideLoading('appointments-loading');
            showError('Error loading appointment analytics: ' + error.message);
        });
}

/**
 * Load Financial Report
 */
function loadFinancialReport() {
    currentReportType = 'financial';
    
    const startDate = document.getElementById('financial-start-date').value;
    const endDate = document.getElementById('financial-end-date').value;
    
    showLoading('financial-loading');
    hideContent('financial-content');
    
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    fetch(`../ajax/get_financial_report.php?${params}`)
        .then(response => response.json())
        .then(data => {
            hideLoading('financial-loading');
            
            if (data.success) {
                currentReportData = data.data;
                
                // Create charts
                createRevenueChart(data.data.monthly_trends);
                createPaymentMethodsChart(data.data.payment_methods);
                createAgingChart(data.data.aging_analysis);
                
                // Populate revenue summary
                populateRevenueSummary(data.data.revenue_summary);
                
                showContent('financial-content');
            } else {
                showError('Error loading financial report: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            hideLoading('financial-loading');
            showError('Error loading financial report: ' + error.message);
        });
}

/**
 * Load Lab Tests Report
 */
function loadLabTestsReport() {
    currentReportType = 'lab_tests';
    
    const startDate = document.getElementById('lab-start-date').value;
    const endDate = document.getElementById('lab-end-date').value;
    const category = document.getElementById('lab-category').value;
    
    showLoading('lab-loading');
    hideContent('lab-content');
    
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (category) params.append('category', category);
    
    fetch(`../ajax/get_lab_tests_report.php?${params}`)
        .then(response => response.json())
        .then(data => {
            hideLoading('lab-loading');
            
            if (data.success) {
                currentReportData = data.data;
                
                // Create charts
                createTestStatusChart(data.data.status_summary);
                createCategoryChart(data.data.category_distribution);
                
                // Populate popular tests table
                populatePopularTestsTable(data.data.popular_tests);
                
                showContent('lab-content');
            } else {
                showError('Error loading lab tests report: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            hideLoading('lab-loading');
            showError('Error loading lab tests report: ' + error.message);
        });
}

/**
 * Load Medical Records Report
 */
function loadMedicalRecordsReport() {
    currentReportType = 'medical_records';
    
    const startDate = document.getElementById('records-start-date').value;
    const endDate = document.getElementById('records-end-date').value;
    const doctorId = document.getElementById('records-doctor').value;
    
    showLoading('records-loading');
    hideContent('records-content');
    
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (doctorId) params.append('doctor_id', doctorId);
    
    fetch(`../ajax/get_medical_records_report.php?${params}`)
        .then(response => response.json())
        .then(data => {
            hideLoading('records-loading');
            
            if (data.success) {
                currentReportData = data.data;
                
                // Create charts
                createRecordsVolumeChart(data.data.daily_volume);
                createDiagnosesChart(data.data.common_diagnoses);
                
                // Populate summaries and tables
                populateRecordsSummary(data.data.records_summary);
                populateDoctorActivityTable(data.data.doctor_activity);
                
                showContent('records-content');
            } else {
                showError('Error loading medical records report: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            hideLoading('records-loading');
            showError('Error loading medical records report: ' + error.message);
        });
}

/**
 * Chart Creation Functions
 */

function createGenderChart(data) {
    const ctx = document.getElementById('genderChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (chartInstances.genderChart) {
        chartInstances.genderChart.destroy();
    }
    
    chartInstances.genderChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.gender.charAt(0).toUpperCase() + item.gender.slice(1)),
            datasets: [{
                data: data.map(item => item.count),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createAgeChart(data) {
    const ctx = document.getElementById('ageChart').getContext('2d');
    
    if (chartInstances.ageChart) {
        chartInstances.ageChart.destroy();
    }
    
    chartInstances.ageChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.age_group),
            datasets: [{
                label: 'Patients',
                data: data.map(item => item.count),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createRegistrationTrendsChart(data) {
    const ctx = document.getElementById('registrationTrendsChart').getContext('2d');
    
    if (chartInstances.registrationTrendsChart) {
        chartInstances.registrationTrendsChart.destroy();
    }
    
    chartInstances.registrationTrendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.month),
            datasets: [{
                label: 'New Patients',
                data: data.map(item => item.new_patients),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createAppointmentStatusChart(data) {
    const ctx = document.getElementById('appointmentStatusChart').getContext('2d');
    
    if (chartInstances.appointmentStatusChart) {
        chartInstances.appointmentStatusChart.destroy();
    }
    
    chartInstances.appointmentStatusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(item => item.status.replace('_', ' ').toUpperCase()),
            datasets: [{
                data: data.map(item => item.count),
                backgroundColor: [
                    '#36A2EB',
                    '#FF6384',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createPeakHoursChart(data) {
    const ctx = document.getElementById('peakHoursChart').getContext('2d');
    
    if (chartInstances.peakHoursChart) {
        chartInstances.peakHoursChart.destroy();
    }
    
    chartInstances.peakHoursChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => `${item.hour}:00`),
            datasets: [{
                label: 'Appointments',
                data: data.map(item => item.appointment_count),
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createRevenueChart(data) {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    if (chartInstances.revenueChart) {
        chartInstances.revenueChart.destroy();
    }
    
    chartInstances.revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.month),
            datasets: [{
                label: 'Total Revenue',
                data: data.map(item => parseFloat(item.total_revenue)),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4
            }, {
                label: 'Collected Revenue',
                data: data.map(item => parseFloat(item.collected_revenue)),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createPaymentMethodsChart(data) {
    const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
    
    if (chartInstances.paymentMethodsChart) {
        chartInstances.paymentMethodsChart.destroy();
    }
    
    chartInstances.paymentMethodsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.payment_method.toUpperCase()),
            datasets: [{
                data: data.map(item => parseFloat(item.total_amount)),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createAgingChart(data) {
    const ctx = document.getElementById('agingChart').getContext('2d');
    
    if (chartInstances.agingChart) {
        chartInstances.agingChart.destroy();
    }
    
    chartInstances.agingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.aging_period),
            datasets: [{
                label: 'Outstanding Amount',
                data: data.map(item => parseFloat(item.total_outstanding)),
                backgroundColor: 'rgba(255, 159, 64, 0.8)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Outstanding: $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createTestStatusChart(data) {
    const ctx = document.getElementById('testStatusChart').getContext('2d');
    
    if (chartInstances.testStatusChart) {
        chartInstances.testStatusChart.destroy();
    }
    
    chartInstances.testStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.status.replace('_', ' ').toUpperCase()),
            datasets: [{
                data: data.map(item => item.count),
                backgroundColor: [
                    '#36A2EB',
                    '#FF6384',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createCategoryChart(data) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    if (chartInstances.categoryChart) {
        chartInstances.categoryChart.destroy();
    }
    
    chartInstances.categoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.test_category),
            datasets: [{
                label: 'Total Tests',
                data: data.map(item => item.count),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createRecordsVolumeChart(data) {
    const ctx = document.getElementById('recordsVolumeChart').getContext('2d');
    
    if (chartInstances.recordsVolumeChart) {
        chartInstances.recordsVolumeChart.destroy();
    }
    
    chartInstances.recordsVolumeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.visit_date),
            datasets: [{
                label: 'Total Records',
                data: data.map(item => item.total_records),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4
            }, {
                label: 'Unique Patients',
                data: data.map(item => item.unique_patients),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createDiagnosesChart(data) {
    const ctx = document.getElementById('diagnosesChart').getContext('2d');
    
    if (chartInstances.diagnosesChart) {
        chartInstances.diagnosesChart.destroy();
    }
    
    // Take top 10 diagnoses
    const topDiagnoses = data.slice(0, 10);
    
    chartInstances.diagnosesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topDiagnoses.map(item => item.diagnosis),
            datasets: [{
                label: 'Frequency',
                data: topDiagnoses.map(item => item.frequency),
                backgroundColor: 'rgba(255, 206, 86, 0.8)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Table Population Functions
 */

function populateDoctorPerformanceTable(data) {
    const tbody = document.querySelector('#doctorPerformanceTable tbody');
    tbody.innerHTML = '';
    
    data.forEach(doctor => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${doctor.doctor_name}</td>
            <td>${doctor.specialization}</td>
            <td>${doctor.total_appointments}</td>
            <td>${doctor.completed_appointments}</td>
            <td><span class="badge bg-${doctor.completion_rate >= 90 ? 'success' : doctor.completion_rate >= 75 ? 'warning' : 'danger'}">${doctor.completion_rate}%</span></td>
            <td>${doctor.no_shows}</td>
        `;
    });
}

function populatePopularTestsTable(data) {
    const tbody = document.querySelector('#popularTestsTable tbody');
    tbody.innerHTML = '';
    
    data.forEach(test => {
        const completionRate = test.total_orders > 0 ? ((test.completed_orders / test.total_orders) * 100).toFixed(1) : 0;
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${test.test_name}</td>
            <td>${test.test_category}</td>
            <td>${test.total_orders}</td>
            <td>${test.completed_orders}</td>
            <td><span class="badge bg-${completionRate >= 90 ? 'success' : completionRate >= 75 ? 'warning' : 'danger'}">${completionRate}%</span></td>
        `;
    });
}

function populateDoctorActivityTable(data) {
    const tbody = document.querySelector('#doctorActivityTable tbody');
    tbody.innerHTML = '';
    
    data.forEach(doctor => {
        const vitalSignsRate = parseFloat(doctor.vital_signs_rate || 0).toFixed(1);
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${doctor.doctor_name}</td>
            <td>${doctor.specialization}</td>
            <td>${doctor.total_records}</td>
            <td>${doctor.unique_patients}</td>
            <td><span class="badge bg-${vitalSignsRate >= 90 ? 'success' : vitalSignsRate >= 75 ? 'warning' : 'danger'}">${vitalSignsRate}%</span></td>
        `;
    });
}

/**
 * Summary Population Functions
 */

function populateRevenueSummary(data) {
    const container = document.getElementById('revenue-summary');
    
    container.innerHTML = `
        <div class="mb-3">
            <h6 class="text-muted mb-1">Total Revenue</h6>
            <h4 class="text-primary mb-0">$${parseFloat(data.total_revenue || 0).toLocaleString()}</h4>
        </div>
        <div class="mb-3">
            <h6 class="text-muted mb-1">Collected</h6>
            <h5 class="text-success mb-0">$${parseFloat(data.total_collected || 0).toLocaleString()}</h5>
        </div>
        <div class="mb-3">
            <h6 class="text-muted mb-1">Outstanding</h6>
            <h5 class="text-warning mb-0">$${parseFloat(data.total_outstanding || 0).toLocaleString()}</h5>
        </div>
        <div class="mb-3">
            <h6 class="text-muted mb-1">Total Bills</h6>
            <h5 class="mb-0">${data.total_bills || 0}</h5>
        </div>
        <hr>
        <div class="row text-center">
            <div class="col-6">
                <small class="text-muted">Collection Rate</small>
                <div class="text-primary">${data.total_revenue > 0 ? ((data.total_collected / data.total_revenue) * 100).toFixed(1) : 0}%</div>
            </div>
            <div class="col-6">
                <small class="text-muted">Discounts</small>
                <div class="text-info">$${parseFloat(data.total_discounts || 0).toLocaleString()}</div>
            </div>
        </div>
    `;
}

function populateRecordsSummary(data) {
    const container = document.getElementById('records-summary');
    
    container.innerHTML = `
        <div class="mb-3">
            <h6 class="text-muted mb-1">Total Records</h6>
            <h4 class="text-primary mb-0">${data.total_records || 0}</h4>
        </div>
        <div class="mb-3">
            <h6 class="text-muted mb-1">Unique Patients</h6>
            <h5 class="text-success mb-0">${data.unique_patients || 0}</h5>
        </div>
        <div class="mb-3">
            <h6 class="text-muted mb-1">Active Doctors</h6>
            <h5 class="text-info mb-0">${data.active_doctors || 0}</h5>
        </div>
        <hr>
        <div class="text-center">
            <small class="text-muted">Vital Signs Completion</small>
            <div class="text-primary">${parseFloat(data.vital_signs_completion_rate || 0).toFixed(1)}%</div>
            <div class="progress mt-2">
                <div class="progress-bar" role="progressbar" style="width: ${data.vital_signs_completion_rate || 0}%"></div>
            </div>
        </div>
    `;
}

/**
 * Utility Functions
 */

function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('d-none');
        element.style.display = 'block';
    }
}

function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('d-none');
        element.style.display = 'none';
    }
}

function showContent(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('d-none');
        element.style.display = 'block';
    }
}

function hideContent(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('d-none');
        element.style.display = 'none';
    }
}

function showError(message) {
    console.error(message);
    // You could add a toast notification here
    alert(message);
}

/**
 * Export Functions
 */

function exportCurrentReport() {
    if (!currentReportData) {
        alert('No report data to export. Please generate a report first.');
        return;
    }
    
    // Create form and submit to export endpoint
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../ajax/export_report.php';
    
    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'report_type';
    typeInput.value = currentReportType;
    
    const dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'report_data';
    dataInput.value = JSON.stringify(currentReportData);
    
    form.appendChild(typeInput);
    form.appendChild(dataInput);
    document.body.appendChild(form);
    
    form.submit();
    document.body.removeChild(form);
}

function printReport() {
    window.print();
}
