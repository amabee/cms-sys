<?php
include __DIR__ . '/../controllers/SystemController.php';
$systemController = new SystemController();
$system_details = $systemController->getSystemDetails();

// Get current page name for active menu item from URI (since there's routing)
$current_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', trim($current_uri, '/'));
$current_page = '';

// Extract page name from URI - handle dynamic admin path
foreach ($uri_parts as $part) {
  if (strpos($part, '.php') !== false) {
    $current_page = basename($part, '.php');
    break;
  }
}

// If no .php found, try the last part of the URI
if (empty($current_page) && !empty($uri_parts)) {
  $last_part = end($uri_parts);
  if (!empty($last_part) && $last_part !== 'admin') {
    $current_page = $last_part;
  }
}


// Function to check if menu item should be active
function isMenuActive($page_names, $current_page)
{
  // Handle both single page name and array of page names
  if (is_array($page_names)) {
    return in_array($current_page, $page_names) ? 'active' : '';
  }
  return $page_names === $current_page ? 'active' : '';
}

// Handle special cases for pages that might have different names but same section
$page_aliases = [
  'index' => 'dashboard',
  'home' => 'dashboard',
  'user-management' => 'user-management',
  'system-settings' => 'system-settings',
  'attendance' => 'attendance',
  'leaves' => 'leaves',
  'payroll' => 'payroll',
  'profile' => 'profile',
  'logout' => 'logout',
  'employee-management' => 'employee-management',
  'organization-settings' => 'organization-settings',
  'deductions' => 'deductions',
  'allowances' => 'allowances',
  'system-logs' => 'system-logs',
  'reports' => 'reports',
  'dtr' => 'dtr',
  'performance' => 'performance',
  'advanced_analytics' => 'advanced_analytics'
];

// Check if current page has an alias
if (isset($page_aliases[$current_page])) {
  $current_page = $page_aliases[$current_page];
}
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand mt-5">
    <a href="#" class="app-brand-link">
      <span class="app-brand-logo">
        <span class="text-primary">
          <img src="/uploads/company/<?= htmlspecialchars((string)($system_details['logo'] ?? 'default.png')) ?>" alt="Logo"
            style="max-width: 50px; max-height: 50px; width: auto; height: auto; object-fit: contain; border-radius: 50%;">
        </span>
      </span>
      <span
        class="app-brand-text menu-text fw-bold ms-2 fs-6"><?= htmlspecialchars((string)($system_details['name'] ?? 'Clinic Management System')) ?>
      </span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
    </a>
  </div>

  <div class="menu-divider mt-0"></div>
  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <!-- Dashboard - All users -->
    <li class="menu-item <?php echo isMenuActive('dashboard', $current_page); ?>">
      <a href="./dashboard.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-circle"></i>
        <div data-i18n="Dashboard">Dashboard</div>
      </a>
    </li>

    <!-- Clinic Core -->
    <?php if (in_array($user_type, ['admin','doctor','secretary','receptionist'])): ?>
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Clinic</span>
      </li>
      <li class="menu-item <?php echo isMenuActive('patients', $current_page); ?>">
        <a href="./patients.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-user-circle"></i>
          <div data-i18n="Patients">Patients</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('appointments', $current_page); ?>">
        <a href="./appointments.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-calendar"></i>
          <div data-i18n="Appointments">Appointments</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('medical-records', $current_page); ?>">
        <a href="./medical-records.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-notes"></i>
          <div data-i18n="Medical Records">Medical Records</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('lab-tests', $current_page); ?>">
        <a href="./lab-tests.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-flask"></i>
          <div data-i18n="Lab Tests">Lab Tests</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('prescriptions', $current_page); ?>">
        <a href="./prescriptions.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-prescription"></i>
          <div data-i18n="Prescriptions">Prescriptions</div>
        </a>
      </li>
    <?php endif; ?>

    <!-- Patient Portal -->
    <?php if ($user_type === 'patient'): ?>
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Patient</span>
      </li>
      <li class="menu-item <?php echo isMenuActive('patient-portal', $current_page); ?>">
        <a href="./patient-portal.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-log-in-circle"></i>
          <div data-i18n="Patient Portal">Patient Portal</div>
        </a>
      </li>
    <?php endif; ?>

    <!-- Administration -->
    <?php if ($user_type === 'admin'): ?>
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">Administration</span>
      </li>
      <li class="menu-item <?php echo isMenuActive('user-management', $current_page); ?>">
        <a href="./user-management.php" class="menu-link">
          <i class='menu-icon tf-icons bx  bx-user'  ></i>
          <div data-i18n="Users">User Management</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('doctors', $current_page); ?>">
        <a href="./doctors.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-user-check"></i>
          <div data-i18n="Doctors">Doctors</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('billing', $current_page); ?>">
        <a href="./billing.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-wallet"></i>
          <div data-i18n="Billing">Billing</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('invoices', $current_page); ?>">
        <a href="./invoices.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-receipt"></i>
          <div data-i18n="Invoices">Invoices</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('system-settings', $current_page); ?>">
        <a href="./system-settings.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-cog"></i>
          <div data-i18n="Settings">System Settings</div>
        </a>
      </li>
      <li class="menu-item <?php echo isMenuActive('system-logs', $current_page); ?>">
        <a href="./system-logs.php" class="menu-link">
          <i class="menu-icon tf-icons bx bx-list-ul"></i>
          <div data-i18n="Logs">System Logs</div>
        </a>
      </li>
    <?php endif; ?>

    <!-- Personal Section - All users -->
    <li class="menu-header small text-uppercase">
      <span class="menu-header-text">Personal</span>
    </li>
    <li class="menu-item <?php echo isMenuActive('profile', $current_page); ?>">
      <a href="./profile.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-user-circle"></i>
        <div data-i18n="Profile">My Profile</div>
      </a>
    </li>

    <!-- Logout -->
    <li class="menu-divider mt-3"></li>
    <li class="menu-item <?php echo isMenuActive('logout', $current_page); ?>">
      <a href="../logout.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-power-off text-danger"></i>
        <div data-i18n="Logout">Logout</div>
      </a>
    </li>

  </ul>
</aside>
