<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/achievements/notifications.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se tem companhia ativa
requireActiveCompany();

// Obter o ID da inspeção do POST
$inspectionId = sanitizeInput($_POST['inspection_id'] ?? '');

if (empty($inspectionId)) {
    $_SESSION['error_msg'] = 'ID da inspeção é obrigatório.';
    redirect('/inspections/index.php');
    exit;
}

// Obter dados da inspeção
$inspection = getInspectionById($inspectionId);

// Verificar se a inspeção existe e pertence a esta empresa
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    $_SESSION['error_msg'] = 'Inspeção não encontrada ou você não tem permissão para enviar respostas.';
    redirect('/inspections/index.php');
    exit;
}

// Verificar se a inspeção está publicada
if ($inspection['status'] !== 'published') {
    $_SESSION['error_msg'] = 'Esta inspeção não está publicada. Não é possível enviar respostas.';
    redirect('/inspections/preview.php?id=' . $inspectionId);
    exit;
}

// Verificar se a inspeção ainda pode aceitar respostas
if (!canSubmitResponse($inspectionId)) {
    $_SESSION['error_msg'] = 'Esta inspeção atingiu seu limite de respostas.';
    redirect('/inspections/preview.php?id=' . $inspectionId);
    exit;
}

// Obter questões para esta inspeção
$questions = getQuestionsByInspectionId($inspectionId);

// Validar questões obrigatórias
$errors = [];
foreach ($questions as $question) {
    $questionId = $question['id'];
    
    // Pular questões não obrigatórias
    if (!$question['is_required']) {
        continue;
    }
    
    // Verificar se a questão foi respondida
    if ($question['type'] === 'multiple_choice') {
        if (!isset($_POST['question_' . $questionId]) || !is_array($_POST['question_' . $questionId]) || empty($_POST['question_' . $questionId])) {
            $errors[] = 'A questão "' . $question['text'] . '" é obrigatória.';
        }
    } elseif ($question['type'] === 'photo') {
        if (!isset($_FILES['question_photo_' . $questionId]) || 
            $_FILES['question_photo_' . $questionId]['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'A foto para a questão "' . $question['text'] . '" é obrigatória.';
        }
    } else {
        if (!isset($_POST['question_' . $questionId]) || trim($_POST['question_' . $questionId]) === '') {
            $errors[] = 'A questão "' . $question['text'] . '" é obrigatória.';
        }
    }
}

// Se houver erros de validação, retornar com os erros
if (!empty($errors)) {
    $_SESSION['error_msg'] = implode('<br>', $errors);
    redirect('/inspections/preview.php?id=' . $inspectionId);
    exit;
}

// Criar uma nova resposta
$responseId = createResponse($inspectionId);

if (!$responseId) {
    $_SESSION['error_msg'] = 'Falha ao criar a resposta. Por favor, tente novamente.';
    redirect('/inspections/preview.php?id=' . $inspectionId);
    exit;
}

// Processar cada resposta de questão
$successfulAnswers = 0;
foreach ($questions as $question) {
    $questionId = $question['id'];
    $questionText = $question['text'];
    $answerText = '';
    
    // Tratar diferentes tipos de questões
    if ($question['type'] === 'single_choice') {
        $answer = $_POST['question_' . $questionId] ?? '';
        
        if (!empty($answer)) {
            // Obter o texto da opção
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
    } elseif ($question['type'] === 'photo') {
        // Processar upload de foto
        if (isset($_FILES['question_photo_' . $questionId]) && 
            $_FILES['question_photo_' . $questionId]['error'] === UPLOAD_ERR_OK) {
            
            // Processar o upload e obter o caminho relativo
            $filePath = processPhotoUpload(
                $_FILES['question_photo_' . $questionId],
                $responseId,
                $questionId
            );
            
            if ($filePath) {
                // Armazenar o caminho da imagem como resposta
                $answerText = $filePath;
            } else {
                // Falha ao processar o upload
                $answerText = '[Erro no upload da foto]';
            }
        }
    } else {
        // Para texto, data e hora
        $answerText = sanitizeInput($_POST['question_' . $questionId] ?? '');
    }
    
    // Salvar a resposta
    if (createResponseAnswer($responseId, $questionId, $questionText, $answerText)) {
        $successfulAnswers++;
    }
}

// Verificar se todas as respostas foram salvas com sucesso
if ($successfulAnswers === count($questions)) {
    $_SESSION['success_msg'] = 'Resposta enviada com sucesso.';
} else {
    // Se algumas respostas falharam ao salvar, ainda consideramos um sucesso parcial
    $_SESSION['warning_msg'] = 'Resposta enviada, mas algumas respostas não foram salvas corretamente.';
}

// Verificar e atribuir conquistas
checkForNewAchievements();

// Redirecionar para a página de respostas
redirect('/inspections/responses/index.php?id=' . $inspectionId);