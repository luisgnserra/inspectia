<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/achievements/notifications.php';

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
    addError("Inspection not found or you don't have permission to edit it.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Check if the inspection is in draft status
if ($inspection['status'] !== 'draft') {
    addError("Only draft inspections can be edited. Please unpublish this inspection first.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Get company data
$companyId = getActiveCompanyId();
$company = getCompanyById($companyId);

// Get questions for this inspection
$questions = getQuestionsByInspectionId($inspectionId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $responseLimit = sanitizeInput($_POST['response_limit'] ?? 'unlimited');
    $maxResponses = null;
    
    // Validate inputs
    if (empty($title)) {
        addError("Inspection title is required.");
    }
    
    // Set default response limit for free users
    if (!isPro()) {
        $responseLimit = 'multiple';
        $maxResponses = FREE_PLAN_MAX_RESPONSES;
    } else if ($responseLimit === 'multiple') {
        $maxResponses = (int)sanitizeInput($_POST['max_responses'] ?? 10);
        
        if ($maxResponses <= 0) {
            addError("Maximum responses must be a positive number.");
        }
    }
    
    if (!hasErrors()) {
        // Update the inspection
        if (updateInspection($inspectionId, $title, $responseLimit, $maxResponses)) {
            
            // Process existing questions - delete all current questions and re-add them
            // This is a simpler approach than trying to determine which ones to update vs. delete vs. add
            $questionsToDelete = getQuestionsByInspectionId($inspectionId);
            
            foreach ($questionsToDelete as $questionToDelete) {
                deleteQuestion($questionToDelete['id']);
            }
            
            // Process questions from the form
            if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                foreach ($_POST['questions'] as $questionId => $questionData) {
                    $questionText = sanitizeInput($questionData['text'] ?? '');
                    $questionType = sanitizeInput($questionData['type'] ?? '');
                    $isRequired = isset($questionData['required']) ? true : false;
                    
                    // Skip empty questions
                    if (empty($questionText) || empty($questionType)) {
                        continue;
                    }
                    
                    // Create the question
                    $newQuestionId = createQuestion($inspectionId, $questionText, $questionType, $isRequired);
                    
                    // Add options for choice questions
                    if (($questionType === 'single_choice' || $questionType === 'multiple_choice') && 
                        isset($questionData['options']) && is_array($questionData['options'])) {
                        foreach ($questionData['options'] as $optionText) {
                            $optionText = sanitizeInput($optionText);
                            
                            // Skip empty options
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
            
            addSuccessMessage("Inspection updated successfully!");
            redirect(url: "/inspectia/inspections/index.php");
        } else {
            addError("Failed to update inspection. Please try again.");
        }
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<!-- Adicionar biblioteca Sortable.js para permitir arrastar e soltar -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<!-- Estilos para elementos arrastáveis -->
<style>
    .card-header {
        cursor: grab;
    }
    .card-header:active {
        cursor: grabbing;
    }
    .handle-icon {
        cursor: grab;
    }
    .handle-icon:active {
        cursor: grabbing;
    }
    .sortable-ghost {
        opacity: 0.5;
    }
    .sortable-drag {
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    .option-item-sortable-chosen {
        background-color: #f8f9fa;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Inspeção</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="<?= BASE_URL ?>/inspections/preview.php?id=<?= $inspectionId ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-eye me-1"></i>Visualizar
            </a>
            <a href="<?= BASE_URL ?>/inspections/publish.php?id=<?= $inspectionId ?>" class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-export me-1"></i>Publicar
            </a>
        </div>
        <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Inspeções
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Editar Inspeção para <?= htmlspecialchars($company['name']) ?></h5>
            </div>
            <div class="card-body">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $inspectionId ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="title" class="form-label">Título da Inspeção</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($inspection['title']) ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Limite de Respostas</label>
                        
                        <?php if (!isPro()): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                O plano gratuito está limitado a 3 respostas por inspeção. 
                                <a href="#">Faça upgrade para Pro</a> para limites de resposta configuráveis.
                            </div>
                            <input type="hidden" name="response_limit" value="multiple">
                            <input type="hidden" name="max_responses" value="<?= FREE_PLAN_MAX_RESPONSES ?>">
                            <div class="form-control-plaintext">Limite fixo: <?= FREE_PLAN_MAX_RESPONSES ?> respostas</div>
                        <?php else: ?>
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="response_limit" id="response_limit_single" value="single" <?= $inspection['response_limit'] === 'single' ? 'checked' : '' ?> autocomplete="off">
                                <label class="btn btn-outline-primary" for="response_limit_single">
                                    Resposta Única
                                </label>
                                
                                <input type="radio" class="btn-check" name="response_limit" id="response_limit_multiple" value="multiple" <?= $inspection['response_limit'] === 'multiple' ? 'checked' : '' ?> autocomplete="off">
                                <label class="btn btn-outline-primary" for="response_limit_multiple">
                                    Múltiplas Respostas
                                </label>
                                
                                <input type="radio" class="btn-check" name="response_limit" id="response_limit_unlimited" value="unlimited" <?= $inspection['response_limit'] === 'unlimited' ? 'checked' : '' ?> autocomplete="off">
                                <label class="btn btn-outline-primary" for="response_limit_unlimited">
                                    Respostas Ilimitadas
                                </label>
                            </div>
                            
                            <div id="max_responses_container" <?= $inspection['response_limit'] !== 'multiple' ? 'style="display: none;"' : '' ?>>
                                <label for="max_responses" class="form-label">Número Máximo de Respostas</label>
                                <input type="number" class="form-control" id="max_responses" name="max_responses" min="1" value="<?= $inspection['max_responses'] ?? 10 ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="mb-3">Questões</h5>
                    
                    <div id="question-container">
                        <?php foreach ($questions as $index => $question): 
                            // Verificar se estamos usando os nomes de coluna corretos
                            $questionType = $question['question_type'] ?? $question['type'] ?? '';
                            $options = ($questionType === 'single_choice' || $questionType === 'multiple_choice') 
                                     ? getQuestionOptions($question['id']) 
                                     : [];
                        ?>
                            <div class="card question-card mb-3" data-question-id="<?= $index ?>">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <span>Questão</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-question-btn">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="question_text_<?= $index ?>" class="form-label">Texto da Questão</label>
                                        <input type="text" class="form-control question-text" id="question_text_<?= $index ?>" name="questions[<?= $index ?>][text]" value="<?= htmlspecialchars($question['question_text'] ?? $question['text'] ?? '') ?>" required>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="question_type_<?= $index ?>" class="form-label">Tipo de Questão</label>
                                            <select class="form-select question-type" id="question_type_<?= $index ?>" name="questions[<?= $index ?>][type]" required>
                                                <option value="short_text" <?= $questionType === 'short_text' ? 'selected' : '' ?>>Texto Curto</option>
                                                <option value="long_text" <?= $questionType === 'long_text' ? 'selected' : '' ?>>Texto Longo</option>
                                                <option value="single_choice" <?= $questionType === 'single_choice' ? 'selected' : '' ?>>Escolha Única</option>
                                                <option value="multiple_choice" <?= $questionType === 'multiple_choice' ? 'selected' : '' ?>>Múltipla Escolha</option>
                                                <option value="date" <?= $questionType === 'date' ? 'selected' : '' ?>>Data</option>
                                                <option value="time" <?= $questionType === 'time' ? 'selected' : '' ?>>Hora</option>
                                                <option value="photo" <?= $questionType === 'photo' ? 'selected' : '' ?>>Foto</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="question_required_<?= $index ?>" name="questions[<?= $index ?>][required]" value="1" <?= (isset($question['required']) && $question['required']) || (isset($question['is_required']) && $question['is_required']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="question_required_<?= $index ?>">
                                                    Questão Obrigatória
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="options-container <?= ($questionType === 'single_choice' || $questionType === 'multiple_choice') ? '' : 'd-none' ?>" id="options_container_<?= $index ?>">
                                        <label class="form-label">Opções</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary add-option-btn mb-2">
                                            <i class="fas fa-plus"></i> Adicionar Opção
                                        </button>
                                        
                                        <?php foreach ($options as $optionIndex => $option): ?>
                                            <div class="option-item input-group mb-2">
                                                <span class="input-group-text handle-icon">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </span>
                                                <input type="text" class="form-control" name="questions[<?= $index ?>][options][<?= $optionIndex ?>]" placeholder="Texto da opção" value="<?= htmlspecialchars($option['option_text'] ?? $option['text'] ?? '') ?>" required>
                                                <button type="button" class="btn btn-outline-danger delete-option-btn">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-4">
                        <button type="button" id="add-question-btn" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Adicionar Questão
                        </button>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Atualizar Inspeção</button>
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
        
        // Inicializa o construtor de questões
        initQuestionBuilder();
    });
    
    // Função para inicializar o construtor de questões
    function initQuestionBuilder() {
        const questionContainer = document.getElementById('question-container');
        const addQuestionBtn = document.getElementById('add-question-btn');
        
        if (!questionContainer || !addQuestionBtn) return;
        
        // Event listener para adicionar uma nova questão
        addQuestionBtn.addEventListener('click', function() {
            const questionId = Date.now(); // Use timestamp como ID temporário
            createQuestionCard(questionId);
        });
        
        // Event listener para deletar questões (usando delegação de eventos)
        questionContainer.addEventListener('click', function(e) {
            if (e.target.closest('.delete-question-btn')) {
                if (confirm('Tem certeza que deseja excluir esta questão?')) {
                    const questionCard = e.target.closest('.question-card');
                    questionCard.remove();
                }
            }
        });
        
        // Event listener para mudar tipo de questão (usando delegação de eventos)
        questionContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('question-type')) {
                handleQuestionTypeChange(e.target);
            }
        });
        
        // Event listener para adicionar opções (usando delegação de eventos)
        questionContainer.addEventListener('click', function(e) {
            if (e.target.closest('.add-option-btn')) {
                const optionsContainer = e.target.closest('.options-container');
                addOptionToQuestion(optionsContainer);
            }
        });
        
        // Event listener para deletar opções (usando delegação de eventos)
        questionContainer.addEventListener('click', function(e) {
            if (e.target.closest('.delete-option-btn')) {
                const optionItem = e.target.closest('.option-item');
                optionItem.remove();
            }
        });
    }
    
    // Função para criar um novo card de questão
    function createQuestionCard(questionId) {
        const questionContainer = document.getElementById('question-container');
        
        const card = document.createElement('div');
        card.className = 'card question-card mb-3';
        card.dataset.questionId = questionId;
        
        card.innerHTML = `
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span>Questão</span>
                <button type="button" class="btn btn-sm btn-outline-danger delete-question-btn">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="question_text_${questionId}" class="form-label">Texto da Questão</label>
                    <input type="text" class="form-control question-text" id="question_text_${questionId}" name="questions[${questionId}][text]" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="question_type_${questionId}" class="form-label">Tipo de Questão</label>
                        <select class="form-select question-type" id="question_type_${questionId}" name="questions[${questionId}][type]" required>
                            <option value="short_text">Texto Curto</option>
                            <option value="long_text">Texto Longo</option>
                            <option value="single_choice">Escolha Única</option>
                            <option value="multiple_choice">Múltipla Escolha</option>
                            <option value="date">Data</option>
                            <option value="time">Hora</option>
                            <option value="photo">Foto</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="question_required_${questionId}" name="questions[${questionId}][required]" value="1">
                            <label class="form-check-label" for="question_required_${questionId}">
                                Questão Obrigatória
                            </label>
                        </div>
                    </div>
                </div>
                <div class="options-container d-none" id="options_container_${questionId}">
                    <label class="form-label">Opções</label>
                    <button type="button" class="btn btn-sm btn-outline-primary add-option-btn mb-2">
                        <i class="fas fa-plus"></i> Adicionar Opção
                    </button>
                </div>
            </div>
        `;
        
        questionContainer.appendChild(card);
    }
    
    // Função para lidar com a mudança do tipo de questão
    function handleQuestionTypeChange(select) {
        const questionCard = select.closest('.question-card');
        const questionId = questionCard.dataset.questionId;
        const optionsContainer = questionCard.querySelector('.options-container');
        
        if (select.value === 'single_choice' || select.value === 'multiple_choice') {
            optionsContainer.classList.remove('d-none');
            
            // Se não houver opções ainda, adicione duas por padrão
            if (optionsContainer.querySelectorAll('.option-item').length === 0) {
                addOptionToQuestion(optionsContainer);
                addOptionToQuestion(optionsContainer);
            }
        } else {
            optionsContainer.classList.add('d-none');
        }
    }
    
    // Função para adicionar uma opção a uma questão
    function addOptionToQuestion(optionsContainer) {
        const questionCard = optionsContainer.closest('.question-card');
        const questionId = questionCard.dataset.questionId;
        const optionCount = optionsContainer.querySelectorAll('.option-item').length;
        
        const optionItem = document.createElement('div');
        optionItem.className = 'option-item input-group mb-2';
        
        optionItem.innerHTML = `
            <span class="input-group-text handle-icon">
                <i class="fas fa-grip-vertical"></i>
            </span>
            <input type="text" class="form-control" name="questions[${questionId}][options][${optionCount}]" placeholder="Texto da opção" required>
            <button type="button" class="btn btn-outline-danger delete-option-btn">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        optionsContainer.appendChild(optionItem);
    }
    
    // Função para permitir o reordenamento de questões
    function initSortableQuestions() {
        const questionContainer = document.getElementById('question-container');
        if (questionContainer) {
            // Verifica se a biblioteca Sortable está disponível
            if (typeof Sortable !== 'undefined') {
                new Sortable(questionContainer, {
                    animation: 150,
                    handle: '.card-header',
                    ghostClass: 'bg-light'
                });
            }
        }
        
        // Inicializa ordenação de opções para cada questão
        document.querySelectorAll('.options-container').forEach(function(container) {
            if (typeof Sortable !== 'undefined') {
                new Sortable(container, {
                    animation: 150,
                    handle: '.handle-icon',
                    ghostClass: 'bg-light',
                    filter: '.add-option-btn, label',
                    onMove: function(evt) {
                        return !evt.related.classList.contains('add-option-btn') && !evt.related.classList.contains('form-label');
                    }
                });
            }
        });
    }
    
    // Chama a função quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        initSortableQuestions();
    });
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; ?>
