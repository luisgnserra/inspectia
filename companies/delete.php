<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Check if user is logged in
requireLogin();

$userId = getCurrentUserId();
$companyId = sanitizeInput($_GET['id'] ?? '');

// Get company data
$company = getCompanyById($companyId);

// Check if company exists and belongs to this user
if (!$company || $company['user_id'] !== $userId) {
    addError("Company not found or you don't have permission to delete it.");
    redirect(url: "/inspectia/companies/index.php");
}

// Check if this is the active company
if ($companyId === getActiveCompanyId()) {
    // Get other companies to set as active
    $companies = getUserCompanies($userId);
    $otherCompanies = array_filter($companies, function($c) use ($companyId) {
        return $c['id'] !== $companyId;
    });
    
    // If there are other companies, set the first one as active
    if (!empty($otherCompanies)) {
        setActiveCompany(reset($otherCompanies)['id']);
    } else {
        // No other companies, clear active company
        setActiveCompany(null);
    }
}

// Delete the company
if (deleteCompany($companyId)) {
    addSuccessMessage("Company deleted successfully!");
} else {
    addError("Failed to delete company. Please try again.");
}

// Redirect based on whether there are remaining companies
$remainingCompanies = getUserCompanies($userId);
if (empty($remainingCompanies)) {
    redirect(url: "/inspectia/companies/create.php");
} else {
    redirect(url: "/inspectia/companies/index.php");
}
?>
