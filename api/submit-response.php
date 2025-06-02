<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método não permitido. Apenas requisições POST são aceitas.']);
    exit;
}

// Get the public link ID from the request
$publicLinkId = sanitizeInput($_POST['public_link_id'] ?? '');

if (empty($publicLinkId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Requisição inválida. ID do link público é obrigatório.']);
    exit;
}

// Get inspection by public link
$inspection = getInspectionByPublicLink($publicLinkId);

if (!$inspection) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Formulário de inspeção não encontrado.']);
    exit;
}

// Check if inspection is published
if ($inspection['status'] !== 'published') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Este formulário de inspeção não está aceitando respostas no momento.']);
    exit;
}

// Check if form can still accept responses
if (!canSubmitResponse($inspection['id'])) {
    http_response_code(403); // Forbidden
    
    if ($inspection['response_limit'] === 'single') {
        echo json_encode(['error' => 'Este formulário já recebeu uma resposta e não aceita múltiplos envios.']);
    } else {
        echo json_encode(['error' => 'Este formulário atingiu o número máximo de respostas permitidas.']);
    }
    exit;
}

// Get questions for this inspection
$questions = getQuestionsByInspectionId($inspection['id']);

// Validate required questions
$errors = [];
foreach ($questions as $question) {
    $questionId = $question['id'];
    
    // Skip non-required questions
    if (!$question['is_required']) {
        continue;
    }
    
    // Check if the question was answered
    if ($question['type'] === 'multiple_choice') {
        if (!isset($_POST['question_' . $questionId]) || !is_array($_POST['question_' . $questionId]) || empty($_POST['question_' . $questionId])) {
            $errors[] = 'A questão "' . $question['text'] . '" é obrigatória.';
        }
    } else {
        if (!isset($_POST['question_' . $questionId]) || trim($_POST['question_' . $questionId]) === '') {
            $errors[] = 'A questão "' . $question['text'] . '" é obrigatória.';
        }
    }
}

// If there are validation errors, return them
if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Falha na validação.', 'errors' => $errors]);
    exit;
}

// Create a new response
$responseId = createResponse($inspection['id']);

if (!$responseId) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to create response. Please try again.']);
    exit;
}

// Process each question's answer
$successfulAnswers = 0;
foreach ($questions as $question) {
    $questionId = $question['id'];
    $questionText = $question['text'];
    $answerText = '';
    
    // Handle different question types
    if ($question['type'] === 'single_choice') {
        $answer = $_POST['question_' . $questionId] ?? '';
        
        if (!empty($answer)) {
            // Get the option text
            $options = getQuestionOptions($questionId);
            foreach ($options as $option) {
                if ($option['id'] === $answer) {
                    $answerText = $option['text'];
                    break;
                }
            }
        }
    } elseif ($question['type'] === 'multiple_choice') {
        $selectedOptions = $_POST['question_' . $questionId] ?? [];
        
        if (!empty($selectedOptions) && is_array($selectedOptions)) {
            $options = getQuestionOptions($questionId);
            $selectedTexts = [];
            
            foreach ($options as $option) {
                if (in_array($option['id'], $selectedOptions)) {
                    $selectedTexts[] = $option['text'];
                }
            }
            
            $answerText = implode(', ', $selectedTexts);
        }
    } else {
        // For text, date, and time questions
        $answerText = sanitizeInput($_POST['question_' . $questionId] ?? '');
    }
    
    // Save the answer
    if (createResponseAnswer($responseId, $questionId, $questionText, $answerText)) {
        $successfulAnswers++;
    }
}

// Check if all answers were saved successfully
if ($successfulAnswers === count($questions)) {
    echo json_encode([
        'success' => true,
        'message' => 'Response submitted successfully.',
        'response_id' => $responseId
    ]);
} else {
    // If some answers failed to save, we should still consider it a partial success
    echo json_encode([
        'success' => true,
        'message' => 'Response submitted with some issues.',
        'response_id' => $responseId,
        'warning' => 'Not all answers were saved successfully.'
    ]);
}
?>
