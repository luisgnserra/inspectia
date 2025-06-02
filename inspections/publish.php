<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

// Get inspection ID from URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId)) {
    addError("Inspection ID is required.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to this company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspection not found or you don't have permission to publish it.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Check if the inspection is already published
if ($inspection['status'] === 'published') {
    addError("This inspection is already published.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Get the questions for this inspection to validate
$questions = getQuestionsByInspectionId($inspectionId);

// Check if there are any questions
if (empty($questions)) {
    addError("Cannot publish an inspection without questions. Please add at least one question.");
    redirect(url: "/inspectia/inspections/edit.php?id=" . $inspectionId);
}

// Validate choice questions have options
$hasError = false;
foreach ($questions as $question) {
    if (($question['type'] === 'single_choice' || $question['type'] === 'multiple_choice')) {
        $options = getQuestionOptions($question['id']);
        if (empty($options)) {
            $hasError = true;
            addError("Question '" . htmlspecialchars($question['text']) . "' needs at least one option. Please add options.");
        }
    }
}

if ($hasError) {
    redirect(url: "/inspectia/inspections/edit.php?id=" . $inspectionId);
}

// Publish the inspection
if (publishInspection($inspectionId)) {
    addSuccessMessage("Inspection published successfully! It's now available for responses.");
} else {
    addError("Failed to publish inspection. Please try again.");
}

redirect(url: "/inspectia/inspections/index.php");
?>
