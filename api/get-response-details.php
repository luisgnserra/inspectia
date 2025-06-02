<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Configurar cabeçalhos para resposta JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado', 'code' => 401]);
    exit;
}

// Obter ID da resposta da URL
$responseId = sanitizeInput($_GET['id'] ?? '');

if (empty($responseId)) {
    echo json_encode(['error' => 'ID da resposta não fornecido', 'code' => 400]);
    exit;
}

try {
    // Obter a resposta do banco de dados
    $response = getResponseById($responseId);
    
    if (!$response) {
        echo json_encode(['error' => 'Resposta não encontrada', 'code' => 404]);
        exit;
    }
    
    // Verificar se o usuário tem permissão para acessar esta resposta
    $inspection = getInspectionById($response['inspection_id']);
    
    if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
        echo json_encode(['error' => 'Acesso negado a esta resposta', 'code' => 403]);
        exit;
    }
    
    // Obter as respostas para cada pergunta
    $answers = getResponseAnswers($responseId);
    
    // Retornar os dados
    echo json_encode([
        'success' => true,
        'response' => $response,
        'answers' => $answers
    ]);
    
} catch (Exception $e) {
    // Log do erro
    error_log('Erro ao obter detalhes da resposta: ' . $e->getMessage());
    
    // Retornar erro
    echo json_encode([
        'error' => 'Erro ao processar a solicitação',
        'message' => 'Ocorreu um erro ao tentar obter os detalhes da resposta.',
        'code' => 500
    ]);
}