<?php
require_once  '../../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Verificar se o usuário está autenticado
requireLogin();
requireActiveCompany();

// Verificar os parâmetros obrigatórios
if (!isset($_GET['id']) || !isset($_GET['inspection_id'])) {
    addError('Parâmetros inválidos para exclusão');
    redirect('dashboard/index.php');
}

$responseId = $_GET['id'];
$inspectionId = $_GET['inspection_id'];

// Verificar se a resposta existe usando as funções do sistema
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM responses WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $responseId);
$stmt->execute();
$response = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$response) {
    addError('Resposta não encontrada');
    redirect('inspections/responses/index.php?id=' . $inspectionId);
}

// Verificar se o usuário tem permissão para excluir esta resposta
$inspection = getInspectionById($inspectionId);
if (!$inspection || $inspection['company_id'] != getActiveCompanyId()) {
    addError('Você não tem permissão para excluir esta resposta');
    redirect('dashboard/index.php');
}

try {
    // Iniciar transação
    $db->beginTransaction();

    // Excluir todas as respostas às perguntas relacionadas a esta resposta
    $stmt = $db->prepare("DELETE FROM response_answers WHERE response_id = :response_id");
    $stmt->bindParam(':response_id', $responseId);
    $stmt->execute();

    // Excluir a resposta
    $stmt = $db->prepare("DELETE FROM responses WHERE id = :id");
    $stmt->bindParam(':id', $responseId);
    $stmt->execute();

    // Decrementar o contador de respostas da inspeção
    $stmt = $db->prepare("UPDATE inspections SET response_count = response_count - 1 WHERE id = :id");
    $stmt->bindParam(':id', $inspectionId);
    $stmt->execute();

    // Confirmar transação
    $db->commit();

    addSuccessMessage('Resposta excluída com sucesso');
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    addError('Falha ao excluir resposta: ' . $e->getMessage());
}

// Redirecionar de volta para a lista de respostas
redirect('inspections/responses/index.php?id=' . $inspectionId);