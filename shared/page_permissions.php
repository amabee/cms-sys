<?php
/*
  Page permissions map
  Key: page filename (relative)
  Value: array of allowed roles (admin, doctor, secretary, receptionist, patient)

  Notes:
  - 'admin' has full access by default for management pages
  - 'doctor' can access clinical resources (dashboard, patients, appointments, medical records)
  - 'secretary' and 'receptionist' can manage appointments, patients, billing
  - 'patient' has limited access to portal pages (patient-portal, appointments, profile)
*/

return [
  // Core dashboard/profile
  'dashboard.php' => ['admin', 'doctor', 'secretary', 'receptionist'],
  'dashboard_employee.php' => ['receptionist', 'secretary'],
  'profile.php' => ['admin', 'doctor', 'secretary', 'receptionist', 'patient'],

  // User & system management
  'user-management.php' => ['admin'],
  'system-settings.php' => ['admin'],
  'system-logs.php' => ['admin'],

  // Clinic resources
  'patients.php' => ['admin', 'doctor', 'secretary', 'receptionist'],
  'patient-portal.php' => ['patient'],
  'patient-profile.php' => ['admin', 'doctor', 'secretary', 'receptionist', 'patient'],

  'doctors.php' => ['admin'],
  'doctor-schedule.php' => ['admin', 'doctor', 'receptionist', 'secretary'],
  'doctor-patient-search.php' => ['admin', 'doctor', 'receptionist', 'secretary'],


  'appointments.php' => ['admin', 'doctor', 'secretary', 'receptionist', 'patient'],
  'appointment-details.php' => ['admin', 'doctor', 'secretary', 'receptionist', 'patient'],

  'medical-records.php' => ['admin', 'doctor', 'secretary', 'receptionist'],
  'medical-record-details.php' => ['admin', 'doctor'],

  'lab-tests.php' => ['admin', 'doctor', 'secretary', 'receptionist'],
  'lab-test-details.php' => ['admin', 'doctor'],

  'prescriptions.php' => ['admin', 'doctor', 'secretary', 'receptionist'],

  // Billing & payments
  'billing.php' => ['admin', 'receptionist', 'secretary'],
  'invoices.php' => ['admin', 'receptionist', 'secretary', 'patient'],

  // Administration/Human resources legacy pages (if present)
  // 'employee-management.php' => ['admin'], // legacy - ignored for now
  'organization-settings.php' => ['admin'],

  // Misc / utilities
  'working-days-calendar.php' => ['admin', 'doctor', 'receptionist', 'secretary'],
  'attendance.php' => ['admin', 'receptionist', 'secretary'],
  'leaves.php' => ['admin', 'receptionist', 'secretary'],

  // Patient-facing pages
  'login.php' => ['admin', 'doctor', 'secretary', 'receptionist', 'patient'],
  'logout.php' => ['admin', 'doctor', 'secretary', 'receptionist', 'patient'],

];
