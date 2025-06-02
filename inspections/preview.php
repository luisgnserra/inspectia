<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Get inspection ID from URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId)) {
    http_response_code(400);
    echo "Inspection ID is required.";
    exit;
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists
if (!$inspection) {
    http_response_code(404);
    echo "Inspection not found.";
    exit;
}

// Get company data
$company = getCompanyById($inspection['company_id']);

// Get questions for this inspection
$questions = [];
error_log("====== Carregando perguntas para inspeção " . $inspectionId . " ======");

// Load questions
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, text, type, is_required FROM questions WHERE inspection_id = :inspection_id ORDER BY created_at ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':inspection_id', $inspectionId);
$stmt->execute();

// Process each question one by one to ensure we're getting all of them
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Add detailed log for each question
    error_log("Carregou questão: ID=" . $row['id'] . ", Texto=" . $row['text'] . ", Tipo=" . $row['type']);
    
    // For choice questions, load the options
    if ($row['type'] === 'single_choice' || $row['type'] === 'multiple_choice') {
        $row['options'] = getQuestionOptions($row['id']);
    } else {
        $row['options'] = [];
    }
    
    // Add to questions array
    $questions[] = $row;
}

error_log("Total de perguntas carregadas: " . count($questions));

// Log all questions for debugging
foreach ($questions as $index => $question) {
    error_log("Pergunta [$index]: " . $question['id'] . " - " . $question['text'] . " (" . $question['type'] . ")");
}

// Include template header
include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php';
?>

<div class="container py-5">
    <div class="p-4 p-md-5 bg-light rounded-3 mb-4">
        <div class="row align-items-center">
            <?php if (!empty($company['logo_path'])): ?>
                <div class="col-auto">
                    <img src="<?= htmlspecialchars($company['logo_path']) ?>" alt="<?= htmlspecialchars($company['name']) ?>" class="img-fluid company-logo-sm me-3" style="max-height: 60px;">
                </div>
            <?php endif; ?>
            <div class="col">
                <h1><?= htmlspecialchars($inspection['title']) ?></h1>
                <p class="lead mb-0">
                    <span class="text-muted">Empresa:</span> <?= htmlspecialchars($company['name']) ?>
                </p>
            </div>
        </div>
    </div>
    
    <?php if ($inspection['status'] !== 'published'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Esta inspeção está em modo de rascunho e não está publicada. Apenas você pode vê-la.
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Perguntas</h4>
        </div>
        <div class="card-body">
            <?php if (empty($questions)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta inspeção ainda não possui perguntas. Adicione algumas perguntas para completar sua inspeção.
                </div>
            <?php else: ?>
                <form id="preview-form">
                    <?php foreach ($questions as $index => $question): ?>
                        <?php error_log("Rendering question in preview: " . $question['id'] . " - " . $question['text'] . " (index: $index)"); ?>
                        <div class="mb-4 p-3 border rounded question-container">
                            <h5>
                                <?= htmlspecialchars($question['text']) ?>
                                <?php if ($question['is_required']): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </h5>
                            
                            <?php if ($question['type'] === 'short_text'): ?>
                                <input type="text" class="form-control" 
                                       name="question_<?= $question['id'] ?>" 
                                       placeholder="Resposta curta" 
                                       <?= $question['is_required'] ? 'required' : '' ?>>
                                
                            <?php elseif ($question['type'] === 'long_text'): ?>
                                <textarea class="form-control" 
                                          name="question_<?= $question['id'] ?>" 
                                          rows="3" 
                                          placeholder="Resposta longa" 
                                          <?= $question['is_required'] ? 'required' : '' ?>></textarea>
                                
                            <?php elseif ($question['type'] === 'single_choice'): ?>
                                <?php if (empty($question['options'])): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Esta questão não possui opções. Por favor, adicione algumas opções.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($question['options'] as $optionIndex => $option): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="question_<?= $question['id'] ?>" 
                                                   value="<?= $option['id'] ?>" id="option_<?= $question['id'] ?>_<?= $option['id'] ?>" 
                                                   <?= $question['is_required'] ? 'required' : '' ?>>
                                            <label class="form-check-label" for="option_<?= $question['id'] ?>_<?= $option['id'] ?>">
                                                <?= htmlspecialchars($option['option_text'] ?? '') ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            
                            <?php elseif ($question['type'] === 'multiple_choice'): ?>
                                <?php if (empty($question['options'])): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Esta questão não possui opções. Por favor, adicione algumas opções.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($question['options'] as $optionIndex => $option): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="question_<?= $question['id'] ?>[]" 
                                                   value="<?= $option['id'] ?>" id="option_<?= $question['id'] ?>_<?= $option['id'] ?>">
                                            <label class="form-check-label" for="option_<?= $question['id'] ?>_<?= $option['id'] ?>">
                                                <?= htmlspecialchars($option['option_text'] ?? '') ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                            <?php elseif ($question['type'] === 'date'): ?>
                                <input type="date" class="form-control" 
                                       name="question_<?= $question['id'] ?>" 
                                       <?= $question['is_required'] ? 'required' : '' ?>>
                                
                            <?php elseif ($question['type'] === 'time'): ?>
                                <input type="time" class="form-control" 
                                       name="question_<?= $question['id'] ?>" 
                                       <?= $question['is_required'] ? 'required' : '' ?>>
                                
                            <?php elseif ($question['type'] === 'photo'): ?>
                                <div class="mb-3">
                                    <input type="file" class="form-control" name="question_<?= $question['id'] ?>" 
                                           accept="image/*" <?= $question['is_required'] ? 'required' : '' ?>>
                                </div>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm camera-capture-btn" 
                                            data-question-id="<?= $question['id'] ?>">
                                        <i class="fas fa-camera me-1"></i>Capturar com Câmera
                                    </button>
                                </div>
                                <div class="camera-container d-none" id="camera_container_<?= $question['id'] ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card mb-2">
                                                <div class="card-body p-2">
                                                    <video id="camera_<?= $question['id'] ?>" class="w-100" autoplay></video>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary mb-3 camera-capture-photo-btn" 
                                                    data-question-id="<?= $question['id'] ?>">
                                                <i class="fas fa-camera me-1"></i>Tirar Foto
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card mb-2">
                                                <div class="card-body p-2">
                                                    <canvas id="canvas_<?= $question['id'] ?>" class="w-100"></canvas>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-success mb-3 camera-save-photo-btn" 
                                                    data-question-id="<?= $question['id'] ?>">
                                                <i class="fas fa-save me-1"></i>Usar Foto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Tipo de questão não implementado: <?= htmlspecialchars($question['type']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <!-- Sempre mostrar o botão de enviar resposta, independente do status da inspeção -->
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Enviar Resposta
                        </button>
                        
                        <?php if ($inspection['status'] !== 'published'): ?>
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="fas fa-arrow-left me-1"></i>Voltar
                            </button>
                            <a href="<?= BASE_URL ?>/inspections/edit.php?id=<?= $inspection['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i>Editar Inspeção
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; ?>