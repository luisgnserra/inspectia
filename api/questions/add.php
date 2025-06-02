<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in via API
if (!isLoggedIn()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed. Only POST requests are accepted.']);
    exit;
}

// Get the request data
$inspectionId = sanitizeInput($_POST['inspection_id'] ?? '');
$text = sanitizeInput($_POST['text'] ?? '');
$type = sanitizeInput($_POST['type'] ?? '');
$isRequired = isset($_POST['is_required']) && ($_POST['is_required'] === 'true' || $_POST['is_required'] === '1');

// Validate input
if (empty($inspectionId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Inspection ID is required.']);
    exit;
}

if (empty($text)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Question text is required.']);
    exit;
}

if (empty($type)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Question type is required.']);
    exit;
}

// Validate question type
$validTypes = ['short_text', 'long_text', 'single_choice', 'multiple_choice', 'date', 'time'];
if (!in_array($type, $validTypes)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid question type. Valid types are: ' . implode(', ', $validTypes)]);
    exit;
}

// Get the inspection
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to the current user's company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You do not have permission to add questions to this inspection.']);
    exit;
}

// Check if inspection is in draft status
if ($inspection['status'] !== 'draft') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Cannot add questions to a published inspection. Please unpublish it first.']);
    exit;
}

// Create the question
$questionId = createQuestion($inspectionId, $text, $type, $isRequired);

if (!$questionId) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to create question. Please try again.']);
    exit;
}

// Handle options for choice questions
$options = [];
if (($type === 'single_choice' || $type === 'multiple_choice') && isset($_POST['options']) && is_array($_POST['options'])) {
    foreach ($_POST['options'] as $optionText) {
        $optionText = sanitizeInput($optionText);
        
        if (!empty($optionText)) {
            $optionId = createQuestionOption($questionId, $optionText);
            if ($optionId) {
                $options[] = [
                    'id' => $optionId,
                    'text' => $optionText
                ];
            }
        }
    }
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Question created successfully.',
    'question' => [
        'id' => $questionId,
        'text' => $text,
        'type' => $type,
        'is_required' => $isRequired,
        'options' => $options
    ]
]);
?>
