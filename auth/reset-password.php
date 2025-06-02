<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url: "/inspectia/dashboard/index.php");
}

// Get token from URL
$token = sanitizeInput($_GET['token'] ?? '');

if (empty($token)) {
    addError("Invalid password reset link.");
    redirect(url: "/inspectia/auth/login.php");
}

// Verify the token
$userId = verifyPasswordResetToken($token);

if (!$userId) {
    addError("The password reset link is invalid or has expired.");
    redirect(url: "/inspectia/auth/login.php");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (resetPassword($token, $newPassword, $confirmPassword)) {
        addSuccessMessage("Your password has been successfully reset. You can now login with your new password.");
        redirect(url: "/inspectia/auth/login.php");
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card my-5">
            <div class="card-header">
                <h3 class="mb-0">Reset Password</h3>
            </div>
            <div class="card-body">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?token=<?= $token ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock me-2"></i>Reset Password
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

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; ?>
