<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url: "/dashboard/index.php");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (validateEmail($email)) {
        $user = getUserByEmail($email);
        
        if ($user) {
            $token = generatePasswordResetToken($email);
            
            if ($token) {
                // Send password reset email
                if (sendPasswordResetEmail($email, $token)) {
                    addSuccessMessage("Password reset link has been sent to your email address.");
                } else {
                    addError("Failed to send password reset email. Please try again later.");
                }
            }
        } else {
            // Don't reveal that the email doesn't exist for security reasons
            addSuccessMessage("If this email is registered, a password reset link will be sent.");
        }
    } else {
        addError("Please enter a valid email address.");
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card my-5">
            <div class="card-header">
                <h3 class="mb-0">Forgot Password</h3>
            </div>
            <div class="card-body">
                <p class="card-text mb-4">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" novalidate>
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Remember your password? <a href="<?= BASE_URL ?>/auth/login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php'; ?>
