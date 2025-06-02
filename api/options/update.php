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
$optionId = sanitizeInput($_POST['option_id'] ?? '');
$text = sanitizeInput($_POST['text'] ?? '');

// Validate input
if (empty($optionId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Option ID is required.']);
    exit;
}

if (empty($text)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Option text is required.']);
    exit;
}

// Get the option (we need to implement this function)
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM question_options WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $optionId);
$stmt->execute();
$option = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$option) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Option not found.']);
    exit;
}

// Get the question for this option
$question = getQuestionById($option['question_id']);

if (!$question) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Question not found.']);
    exit;
}

// Get the inspection for this question
$inspection = getInspectionById($question['inspection_id']);

// Check if inspection exists and belongs to the current user's company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You do not have permission to update this option.']);
    exit;
}

// Check if inspection is in draft status
if ($inspection['status'] !== 'draft') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Cannot update options in a published inspection. Please unpublish it first.']);
    exit;
}

// Update the option
if (!updateQuestionOption($optionId, $text)) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to update option. Please try again.']);
    exit;
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Option updated successfully.',
    'option' => [
        'id' => $optionId,
        'text' => $text
    ]
]);
?>
