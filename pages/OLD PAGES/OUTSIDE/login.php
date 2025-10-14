<?php
include 'shared/config.php';
include 'controllers/auth/login.php';
include 'controllers/SystemController.php';

// Ensure session is started before checking session variables
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (isset($_SESSION['user_id'])) {
  redirect('index.php');
}

$systemController = new SystemController();
$system_details = $systemController->getSystemDetails();

$error_message = '';
$success_message = '';

// Check for logout confirmation
if (isset($_GET['logged_out']) && $_GET['logged_out'] == '1') {
  $success_message = 'You have been logged out successfully.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $hcaptcha_response = $_POST['h-captcha-response'] ?? '';

  if (empty($username) || empty($password)) {
    $error_message = 'Please enter both username/email and password.';
  } elseif (empty($hcaptcha_response)) {
    $error_message = 'Please complete the CAPTCHA verification.';
  } else {
    // Verify hCaptcha
    $hcaptcha_secret = HCAPTCHA_SECRET_KEY;
    $hcaptcha_url = 'https://hcaptcha.com/siteverify';
    $hcaptcha_data = [
      'secret' => $hcaptcha_secret,
      'response' => $hcaptcha_response,
      'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $hcaptcha_options = [
      'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($hcaptcha_data)
      ]
    ];

    $hcaptcha_context = stream_context_create($hcaptcha_options);
    $hcaptcha_result = file_get_contents($hcaptcha_url, false, $hcaptcha_context);
    $hcaptcha_json = json_decode($hcaptcha_result, true);

    if (!$hcaptcha_json['success']) {
      $error_message = 'CAPTCHA verification failed. Please try again.';
    } else {
      try {
        $loginController = new Login();
        if ($loginController->authenticate($username, $password)) {
        // Redirect based on user type (clinic roles)
        $userType = $_SESSION['user_type'] ?? '';
        switch ($userType) {
          case 'admin':
            redirect('pages/dashboard_admin.php', 'Login successful!', 'success');
            break;
          case 'doctor':
            redirect('pages/dashboard_doctor.php', 'Login successful!', 'success');
            break;
          case 'secretary':
            redirect('pages/dashboard_secretary.php', 'Login successful!', 'success');
            break;
          case 'receptionist':
            redirect('pages/dashboard_receptionist.php', 'Login successful!', 'success');
            break;
          case 'patient':
            redirect('pages/dashboard_patient.php', 'Login successful!', 'success');
            break;
          default:
            redirect('index.php', 'Login successful!', 'success');
            break;
        }
        } else {
          $error_message = 'Invalid username/email or password.';
        }
      } catch (Exception $e) {
        $error_message = "Login failed. Please try again later. {$e->getMessage()}";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>Login - <?= htmlspecialchars($system_details['name'] ?? 'EMP System') ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="assets/vendor/css/pages/page-auth.css" />

  <!-- hCaptcha -->
  <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
</head>

<body>
  <!-- Content -->
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <!-- Login Card -->
        <div class="card">
          <div class="card-body">
            <!-- Logo -->
            <div class="app-brand justify-content-center mb-4">
              <span class="app-brand-text demo text-primary fw-bold">
                <?= htmlspecialchars($system_details['name'] ?? 'EMP System') ?>
              </span>
            </div>
            <!-- /Logo -->
            <h4 class="mb-2 text-center">Welcome Back! 👋</h4>
            <p class="mb-4 text-center">Please sign-in to your account</p>

            <?php if ($error_message): ?>
              <div class="alert alert-danger alert-dismissible" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <form id="formAuthentication" class="mb-3" method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">Username or Email</label>
                <input type="text" class="form-control" id="email" name="email"
                  placeholder="Enter your username or email" value="<?= htmlspecialchars($username ?? '') ?>" autofocus
                  required />
              </div>
              <div class="mb-3 form-password-toggle">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="password">Password</label>
                  <a href="#">
                    <small>Forgot Password?</small>
                  </a>
                </div>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control" name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                  <span class="input-group-text cursor-pointer"><i class="bi bi-eye"></i></span>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember-me" />
                  <label class="form-check-label" for="remember-me"> Remember Me </label>
                </div>
              </div>
              <!-- hCaptcha -->
              <div class="mb-3">
                <div class="h-captcha" data-sitekey="<?= HCAPTCHA_SITE_KEY ?>"></div>
              </div>
              <div class="mb-3">
                <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
              </div>
            </form>

            <!-- <p class="text-center">
              <span>New here?</span>
              <a href="#">
                <span>Create an account</span>
              </a>
            </p> -->
          </div>
        </div>
        <!-- /Login Card -->
      </div>
    </div>
  </div>

  <!-- Core JS -->
  <script src="assets/vendor/libs/jquery/jquery.js"></script>
  <script src="assets/vendor/libs/popper/popper.js"></script>
  <script src="assets/vendor/js/bootstrap.js"></script>
  <script src="assets/vendor/js/menu.js"></script>

  <!-- Page JS -->
  <script>
    // Password show/hide functionality
    document.querySelectorAll('.form-password-toggle i').forEach(icon => {
      icon.addEventListener('click', e => {
        const input = e.target.closest('.input-group-merge').querySelector('input');
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('bi-eye');
          icon.classList.add('bi-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('bi-eye-slash');
          icon.classList.add('bi-eye');
        }
      });
    });
  </script>
</body>

</html>
