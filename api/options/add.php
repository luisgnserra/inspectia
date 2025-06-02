<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
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
$questionId = sanitizeInput($_POST['question_id'] ?? '');
$text = sanitizeInput($_POST['text'] ?? '');

// Validate input
if (empty($questionId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Question ID is required.']);
    exit;
}

if (empty($text)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Option text is required.']);
    exit;
}

// Get the question
$question = getQuestionById($questionId);

if (!$question) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Question not found.']);
    exit;
}

// Check if question type supports options
if ($question['type'] !== 'single_choice' && $question['type'] !== 'multiple_choice') {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Only choice questions can have options.']);
    exit;
}

// Get the inspection for this question
$inspection = getInspectionById($question['inspection_id']);

// Check if inspection exists and belongs to the current user's company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You do not have permission to add options to this question.']);
    exit;
}

// Check if inspection is in draft status
if ($inspection['status'] !== 'draft') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Cannot add options to a published inspection. Please unpublish it first.']);
    exit;
}

// Create the option
$optionId = createQuestionOption($questionId, $text);

if (!$optionId) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to create option. Please try again.']);
    exit;
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Option created successfully.',
    'option' => [
        'id' => $optionId,
        'text' => $text
    ]
]);
?>
