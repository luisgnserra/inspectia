<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/achievements/notifications.php';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

$companyId = getActiveCompanyId();
$company = getCompanyById($companyId);

// Verificar se o usuário pode criar mais inspeções
if (!canCreateMoreInspections($companyId)) {
    addError("Você atingiu o número máximo de inspeções para o plano gratuito. Faça upgrade para o Pro para ter inspeções ilimitadas.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $responseLimit = sanitizeInput($_POST['response_limit'] ?? 'unlimited');
    $maxResponses = null;
    
    // Validar entradas
    if (empty($title)) {
        addError("O título da inspeção é obrigatório.");
    }
    
    // Definir limite de respostas padrão para usuários gratuitos
    if (!isPro()) {
        $responseLimit = 'multiple';
        $maxResponses = FREE_PLAN_MAX_RESPONSES;
    } else if ($responseLimit === 'multiple') {
        $maxResponses = (int)sanitizeInput($_POST['max_responses'] ?? 10);
        
        if ($maxResponses <= 0) {
            addError("O número máximo de respostas deve ser um número positivo.");
        }
    }
    
    if (!hasErrors()) {
        // Criar a inspeção
        $inspectionId = createInspection($companyId, $title, $responseLimit, $maxResponses);
        
        if ($inspectionId) {
            // Processar questões se alguma foi enviada
            if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                foreach ($_POST['questions'] as $questionId => $questionData) {
                    $questionText = sanitizeInput($questionData['text'] ?? '');
                    $questionType = sanitizeInput($questionData['type'] ?? '');
                    $isRequired = isset($questionData['required']) ? true : false;
                    
                    // Pular questões vazias
                    if (empty($questionText) || empty($questionType)) {
                        continue;
                    }
                    
                    // Criar a questão
                    $newQuestionId = createQuestion($inspectionId, $questionText, $questionType, $isRequired);
                    
                    // Adicionar opções para questões de escolha
                    if (($questionType === 'single_choice' || $questionType === 'multiple_choice') && 
                        isset($questionData['options']) && is_array($questionData['options'])) {
                        foreach ($questionData['options'] as $optionText) {
                            $optionText = sanitizeInput($optionText);
                            
                            // Pular opções vazias
                            if (empty($optionText)) {
                                continue;
                            }
                            
                            createQuestionOption($newQuestionId, $optionText);
                        }
                    }
                }
            }
            
            // Verificar e atribuir conquistas
            checkForNewAchievements();
            
            addSuccessMessage("Inspeção criada com sucesso!");
            //redirect(url: "/inspectia/inspections/edit.php?id=" . $inspectionId);
            redirect(url: "/inspectia/dashboard/index.php");
        } else {
            addError("Falha ao criar inspeção. Por favor, tente novamente.");
        }
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Criar Inspeção</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Inspeções
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Nova Inspeção para <?= htmlspecialchars($company['name']) ?></h5>
            </div>
            <div class="card-body">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="title" class="form-label">Título da Inspeção</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Limite de Respostas</label>
                        
                        <?php if (!isPro()): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                O plano gratuito é limitado a 3 respostas por inspeção. 
                                <a href="#">Faça upgrade para o Pro</a> para limites de respostas configuráveis.
                            </div>
                            <input type="hidden" name="response_limit" value="multiple">
                            <input type="hidden" name="max_responses" value="<?= FREE_PLAN_MAX_RESPONSES ?>">
                            <div class="form-control-plaintext">Limite fixo: <?= FREE_PLAN_MAX_RESPONSES ?> respostas</div>
                        <?php else: ?>
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="response_limit" id="response_limit_single" value="single" autocomplete="off">
                                <label class="btn btn-outline-primary" for="response_limit_single">
                                    Resposta Única
                                </label>
                                
                                <input type="radio" class="btn-check" name="response_limit" id="response_limit_multiple" value="multiple" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="response_limit_multiple">
                                    Múltiplas Respostas
                                </label>
                                
                                <input type="radio" class="btn-check" name="response_limit" id="response_limit_unlimited" value="unlimited" autocomplete="off">
                                <label class="btn btn-outline-primary" for="response_limit_unlimited">
                                    Respostas Ilimitadas
                                </label>
                            </div>
                            
                            <div id="max_responses_container">
                                <label for="max_responses" class="form-label">Número Máximo de Respostas</label>
                                <input type="number" class="form-control" id="max_responses" name="max_responses" min="1" value="10">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="mb-3">Questões</h5>
                    
                    <div id="question-container">
                        <!-- As questões serão adicionadas aqui -->
                    </div>
                    
                    <div class="mb-4">
                        <button type="button" id="add-question-btn" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Adicionar Questão
                        </button>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Criar Inspeção</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide max responses input based on response limit selection
        const responseLimitRadios = document.querySelectorAll('input[name="response_limit"]');
        const maxResponsesContainer = document.getElementById('max_responses_container');
        
        if (responseLimitRadios.length > 0 && maxResponsesContainer) {
            responseLimitRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    maxResponsesContainer.style.display = 
                        (this.value === 'multiple') ? 'block' : 'none';
                });
            });
        }
    });
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; ?>
