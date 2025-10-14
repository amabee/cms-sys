<?php
session_start();

// Check if user is already logged in

if (isset($_SESSION['user_id'])) {
  // Redirect to appropriate dashboard based on user type
  switch ($_SESSION['user_type']) {
    case 'admin':
      header('Location: admin/dashboard.php');
      break;
    case 'hr':
      header('Location: hr/dashboard.php');
      break;
    case 'supervisor':
      header('Location: supervisor/dashboard.php');
      break;
    case 'employee':
      header('Location: employee/dashboard.php');
      break;
    default:
      // If user type is not recognized, redirect to login
      session_destroy();
      header('Location: login.php');
      break;
  }
  exit();
}

// If user is not logged in, redirect to login page
header('Location: login.php');
exit();
?>
