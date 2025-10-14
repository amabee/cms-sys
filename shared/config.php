<?php
// Database configuration

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'clinic_cms');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Application configuration
define('APP_NAME', 'Clinic Management System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Manila');

// hCaptcha configuration
define('HCAPTCHA_SITE_KEY', '0d9658da-7e3e-4208-a160-e39c5f0e66ce');
define('HCAPTCHA_SECRET_KEY', 'ES_f97ecd1d57764475b12f852ba6847111'); 

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Database connection function
if (!function_exists('getDBConnection')) {
  function getDBConnection()
  {
    try {
      $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      return $pdo;
    } catch (PDOException $e) {
      return null;
    }
  }
}

// Authentication helper functions
if (!function_exists('isLoggedIn')) {
  function isLoggedIn()
  {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
  }
}

if (!function_exists('requireAuth')) {
  function requireAuth()
  {
    if (!isLoggedIn()) {
      header('Location: ../index.php');
      exit();
    }
  }
}

if (!function_exists('requireRole')) {
  function requireRole($allowedRoles)
  {
    requireAuth();
    $userType = $_SESSION['user_type'] ?? '';
    if (!in_array($userType, (array) $allowedRoles)) {
      header('Location: ../unauthorized.php');
      exit();
    }
  }
}

// Utility functions
if (!function_exists('sanitize')) {
  function sanitize($data)
  {
    return htmlspecialchars(strip_tags(trim($data)));
  }
}

if (!function_exists('formatDate')) {
  function formatDate($date, $format = 'Y-m-d H:i:s')
  {
    return date($format, strtotime($date));
  }
}

if (!function_exists('redirect')) {
  function redirect($url, $message = '', $type = 'info')
  {
    if ($message) {
      $_SESSION['flash_message'] = $message;
      $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
  }
}

if (!function_exists('getFlashMessage')) {
  function getFlashMessage()
  {
    if (isset($_SESSION['flash_message'])) {
      $message = $_SESSION['flash_message'];
      $type = $_SESSION['flash_type'] ?? 'info';
      unset($_SESSION['flash_message'], $_SESSION['flash_type']);
      return ['message' => $message, 'type' => $type];
    }
    return null;
  }
}

if (!function_exists('getPageTitle')) {
  function getPageTitle($page = '')
  {
    $baseTitle = APP_NAME;
    return $page ? "$page - $baseTitle" : $baseTitle;
  }
}

if (!function_exists('isActivePage')) {
  function isActivePage($page)
  {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page ? 'active' : '';
  }
}


?>

