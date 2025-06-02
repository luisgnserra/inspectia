<?php
require_once  '../../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

// Get inspection ID and export format from URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');
$format = sanitizeInput($_GET['format'] ?? 'csv');

if (empty($inspectionId)) {
    addError("Inspection ID is required.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to this company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspection not found or you don't have permission to export responses.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Check if there are responses to export
if ($inspection['response_count'] === 0) {
    addError("There are no responses to export for this inspection.");
    redirect(url: "/inspectia/inspections/responses/index.php?id=" . $inspectionId);
}

// Export responses based on format
if ($format === 'csv') {
    exportResponsesAsCsv($inspectionId);
} else if ($format === 'json') {
    exportResponsesAsJson($inspectionId);
} else {
    addError("Invalid export format. Please choose CSV or JSON.");
    redirect(url: "/inspectia/inspections/responses/index.php?id=" . $inspectionId);
}

// The functions exportResponsesAsCsv and exportResponsesAsJson send headers and output the file directly,
// so no need for a redirect or to include header/footer files.
?>
