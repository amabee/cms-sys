/**
 * Login Page - Pure AJAX Implementation
 * Handles user authentication without page reload
 */

$(document).ready(function() {
    // Toggle password visibility
    $('.input-group-text').on('click', function() {
        const passwordInput = $(this).siblings('input');
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('bx-hide').addClass('bx-show');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('bx-show').addClass('bx-hide');
        }
    });

    // Handle login form submission
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const remember = $('#remember-me').is(':checked');
        
        // Validate inputs
        if (!username || !password) {
            showAlert('Please enter both username and password', 'danger');
            return;
        }
        
        // Show loading state
        setLoadingState(true);
        hideAlert();
        
        // Send AJAX request
        $.ajax({
            url: 'ajax/login.php',
            method: 'POST',
            data: {
                username: username,
                password: password,
                remember: remember ? 1 : 0
            },
            dataType: 'json',
            success: function(response) {
                setLoadingState(false);
                
                if (response.success) {
                    // Show success message
                    showAlert(response.message || 'Login successful! Redirecting...', 'success');
                    
                    // Redirect to dashboard or specified page
                    setTimeout(function() {
                        window.location.href = response.redirect || 'dashboard.php';
                    }, 1000);
                } else {
                    // Show error message
                    showAlert(response.message || 'Invalid username or password', 'danger');
                    
                    // Clear password field on error
                    $('#password').val('').focus();
                }
            },
            error: function(xhr, status, error) {
                setLoadingState(false);
                console.error('Login error:', status, error);
                showAlert('An error occurred during login. Please try again.', 'danger');
            }
        });
    });
    
    /**
     * Show alert message
     */
    function showAlert(message, type) {
        const alertBox = $('#loginAlert');
        alertBox.removeClass('d-none alert-success alert-danger alert-warning alert-info')
                .addClass('alert-' + type)
                .html('<i class="bx bx-' + getAlertIcon(type) + ' me-2"></i>' + message)
                .fadeIn();
    }
    
    /**
     * Hide alert message
     */
    function hideAlert() {
        $('#loginAlert').fadeOut().addClass('d-none');
    }
    
    /**
     * Get icon for alert type
     */
    function getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'error-circle',
            'warning': 'error',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    /**
     * Set loading state for login button
     */
    function setLoadingState(isLoading) {
        const btn = $('#loginBtn');
        const btnText = $('#loginBtnText');
        const btnSpinner = $('#loginBtnSpinner');
        
        if (isLoading) {
            btn.prop('disabled', true);
            btnText.addClass('d-none');
            btnSpinner.removeClass('d-none');
        } else {
            btn.prop('disabled', false);
            btnText.removeClass('d-none');
            btnSpinner.addClass('d-none');
        }
    }
    
    // Auto-focus username field
    $('#username').focus();
});
