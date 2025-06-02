<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';

// Log out the user
logoutUser();

// Redirect to login page
addSuccessMessage("You have been successfully logged out.");
redirect(url: "/index.php");
?>
