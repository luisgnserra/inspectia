<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';

// Log out the user
logoutUser();

// Redirect to login page
addSuccessMessage("You have been successfully logged out.");
redirect(url: "/inspectia/auth/login.php");
?>
