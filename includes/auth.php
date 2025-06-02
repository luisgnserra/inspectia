<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Register a new user
function registerUser($email, $password, $confirmPassword) {
    global $errors;
    
    // Validate email
    if (!validateEmail($email)) {
        addError("Invalid email address.");
        return false;
    }
    
    // Check if email is already registered
    if (getUserByEmail($email)) {
        addError("Email is already registered.");
        return false;
    }
    
    // Validate password
    if (strlen($password) < 8) {
        addError("Password must be at least 8 characters.");
        return false;
    }
    
    // Confirm passwords match
    if ($password !== $confirmPassword) {
        addError("Passwords do not match.");
        return false;
    }
    
    // Create the user
    $userId = createUser($email, $password);
    
    if (!$userId) {
        addError("Registration failed. Please try again.");
        return false;
    }
    
    return $userId;
}

// Login a user
function loginUser($email, $password) {
    global $errors;
    
    // Get user by email
    $user = getUserByEmail($email);
    
    if (!$user) {
        addError("Invalid email or password.");
        return false;
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        addError("Invalid email or password.");
        return false;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_plan'] = $user['plan'];
    
    // Check if user has companies
    $companies = getUserCompanies($user['id']);
    
    if (count($companies) > 0) {
        // Set the first company as active
        setActiveCompany($companies[0]['id']);
    }
    
    return true;
}

// Logout the current user
function logoutUser() {
    session_unset();
    session_destroy();
    session_start();
    return true;
}

// Generate and store password reset token
function generatePasswordResetToken($email) {
    $user = getUserByEmail($email);
    
    if (!$user) {
        addError("Email not found.");
        return false;
    }
    
    $token = bin2hex(random_bytes(32));
    $expiry = time() + 3600; // 1 hour expiry
    
    $database = new Database();
    $db = $database->getConnection();
    
    // First, check if a token already exists
    $query = "SELECT user_id FROM password_reset_tokens WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Update existing token
        $query = "UPDATE password_reset_tokens SET token = :token, expiry = :expiry WHERE user_id = :user_id";
    } else {
        // Insert new token
        $query = "INSERT INTO password_reset_tokens (user_id, token, expiry) VALUES (:user_id, :token, :expiry)";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':expiry', $expiry);
    
    if ($stmt->execute()) {
        return $token;
    }
    
    addError("Failed to generate reset token. Please try again.");
    return false;
}

// Verify password reset token
function verifyPasswordResetToken($token) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT user_id FROM password_reset_tokens 
              WHERE token = :token AND expiry > :now";
    
    $now = time();
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':now', $now);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['user_id'];
    }
    
    addError("Invalid or expired reset token.");
    return false;
}

// Reset password using token
function resetPassword($token, $newPassword, $confirmPassword) {
    global $errors;
    
    if (strlen($newPassword) < 8) {
        addError("A senha deve conter no mínimo 8 caractéres.");
        return false;
    }
    
    if ($newPassword !== $confirmPassword) {
        addError("As senhas não são iguais.");
        return false;
    }
    
    $userId = verifyPasswordResetToken($token);
    
    if (!$userId) {
        return false;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':user_id', $userId);
    
    if ($stmt->execute()) {
        // Delete the used token
        $query = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return true;
    }
    
    addError("Failed to reset password. Please try again.");
    return false;
}

// Send password reset email
function sendPasswordResetEmail($email, $token) {
    $resetLink = BASE_URL . "/auth/reset-password.php?token=" . $token;
    $subject = "Password Reset Request";
    $message = "Hello,\n\nYou have requested to reset your password. Please click the link below to reset your password:\n\n";
    $message .= $resetLink . "\n\n";
    $message .= "This link will expire in 1 hour.\n\n";
    $message .= "If you did not request this, please ignore this email.\n\n";
    $message .= "Regards,\nThe Inspection System Team";
    
    $headers = "From: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($email, $subject, $message, $headers);
}

// Check if user is authenticated, redirect if not
function requireLogin() {
    if (!isLoggedIn()) {
        addError("Please login to access this page.");
        redirect(url: "/inspectia/auth/login.php");
    }
}

// Check if user has an active company, redirect if not
function requireActiveCompany() {
    if (!getActiveCompanyId()) {
        addError("Please create or select a company first.");
        redirect(url: "/inspectia/companies/index.php");
    }
}
?>
