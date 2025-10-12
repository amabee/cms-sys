<?php
// For routing, we need config for session data
include_once 'shared/config.php';

// If no URL parameters, use dashboard router
if (empty($_GET['role']) && empty($_GET['page'])) {
  include 'dashboard_router.php';
  exit();
}

// Get URL parameters
$requested_role = $_GET['role'] ?? '';
$requested_page = $_GET['page'] ?? '';

// Load page permissions
$page_permissions = include 'shared/page_permissions.php';

// Validate page exists and user has permission
if (!isset($page_permissions[$requested_page])) {
  http_response_code(404);
  die('Page not found');
}

$allowed_roles = $page_permissions[$requested_page];

// Check if user is logged in and has proper role
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], $allowed_roles)) {
  http_response_code(403);
  header('Location: ../login.php');
  exit();
}

// Optional: Redirect if user is accessing wrong role URL
if ($_SESSION['user_type'] !== $requested_role) {
  header("Location: /{$_SESSION['user_type']}/{$requested_page}");
  exit();
}

// Include the actual page
$page_file = "pages/{$requested_page}";
if (file_exists($page_file)) {
  include $page_file;
} else {
  http_response_code(404);
  die('Page not found');
}
?>

