<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Certifique-se de que é apenas acessível para usuários logados
requireLogin();

// Verificar se tem companhia ativa
requireActiveCompany();

// Defina o cabeçalho de resposta como JSON
header('Content-Type: application/json');

// Função para garantir que a resposta seja um JSON válido
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Obter o ID da inspeção dos parâmetros da URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');
$limit = (int)($_GET['limit'] ?? 10); // Padrão para 10 respostas

// Validar se foi fornecido um ID de inspeção
if (empty($inspectionId)) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'ID da inspeção é obrigatório'
    ], 400);
}

// Obter dados da inspeção
$inspection = getInspectionById($inspectionId);

// Verificar se a inspeção existe e pertence a esta empresa
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Inspeção não encontrada ou você não tem permissão para visualizar suas respostas'
    ], 403);
}

// Obter as questões para esta inspeção
$questions = getQuestionsByInspectionId($inspectionId);

// Obter as respostas para esta inspeção
$responses = getResponsesByInspectionId($inspectionId);

// Limitar o número de respostas retornadas
if ($limit > 0 && count($responses) > $limit) {
    $responses = array_slice($responses, 0, $limit);
}

// Formatar as respostas para retorno no JSON
$formattedResponses = [];

foreach ($responses as $response) {
    $responseAnswers = getResponseAnswers($response['id']);
    $answersArray = [];
    
    foreach ($responseAnswers as $answer) {
        $answersArray[$answer['question_id']] = $answer['answer_text'];
    }
    
    $responseData = [
        'id' => $response['id'],
        'created_at' => $response['created_at'],
        'answers' => $answersArray
    ];
    
    $formattedResponses[] = $responseData;
}

// Formatar as questões para retorno no JSON
$formattedQuestions = [];

foreach ($questions as $question) {
    $questionData = [
        'id' => $question['id'],
        'text' => $question['text'],
        'type' => $question['type'],
        'is_required' => (bool)$question['is_required']
    ];
    
    // Carregar opções para questões de escolha
    if ($question['type'] === 'single_choice' || $question['type'] === 'multiple_choice') {
        $options = getQuestionOptions($question['id']);
        $questionData['options'] = $options;
    }
    
    $formattedQuestions[] = $questionData;
}

// Retornar as respostas e as questões como JSON
sendJsonResponse([
    'status' => 'success',
    'inspection' => [
        'id' => $inspection['id'],
        'title' => $inspection['title'],
        'status' => $inspection['status'],
        'response_count' => $inspection['response_count'],
        'response_limit' => $inspection['response_limit'],
        'max_responses' => $inspection['max_responses']
    ],
    'questions' => $formattedQuestions,
    'responses' => $formattedResponses,
    'total_responses' => count($responses),
    'has_more' => count($responses) < $inspection['response_count']
]);