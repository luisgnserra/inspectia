<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

// Get inspection and response IDs from URL
$inspectionId = sanitizeInput($_GET['inspection_id'] ?? '');
$responseId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId) || empty($responseId)) {
    addError("Inspection ID and Response ID are required.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to this company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspection not found or you don't have permission to view responses.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Get response data
$response = getResponseById($responseId);

// Check if response exists and belongs to this inspection
if (!$response || $response['inspection_id'] !== $inspectionId) {
    addError("Response not found or doesn't belong to the specified inspection.");
    redirect(url: "/inspectia/inspections/responses/index.php?id=" . $inspectionId);
}

// Get response answers
$responseAnswers = getResponseAnswers($responseId);

// Get questions for reference
$questions = getQuestionsByInspectionId($inspectionId);

// Create a map of question IDs to question objects for easier reference
$questionMap = [];
foreach ($questions as $question) {
    $questionMap[$question['id']] = $question;
    
    // Load options for choice questions
    if ($question['type'] === 'single_choice' || $question['type'] === 'multiple_choice') {
        $questionMap[$question['id']]['options'] = getQuestionOptions($question['id']);
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">View Response</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if (isPro()): ?>
            <a href="<?= BASE_URL ?>/inspections/responses/delete.php?id=<?= $responseId ?>&inspection_id=<?= $inspectionId ?>" 
               class="btn btn-sm btn-outline-danger me-2 confirm-delete">
                <i class="fas fa-trash-alt me-1"></i>Delete Response
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspectionId ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Responses
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Response for: <?= htmlspecialchars($inspection['title']) ?>
                </h5>
                <span class="text-muted">
                    Submitted: <?= date('M j, Y g:i A', strtotime($response['created_at'])) ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($responseAnswers)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No answers found for this response.
                    </div>
                <?php else: ?>
                    <div class="response-details">
                        <?php foreach ($responseAnswers as $answer): ?>
                            <div class="mb-4 pb-3 border-bottom">
                                <?php
                                $question = $questionMap[$answer['question_id']] ?? null;
                                $questionType = $question ? $question['type'] : 'unknown';
                                ?>
                                
                                <h5 class="fw-bold"><?= htmlspecialchars($answer['question_text']) ?></h5>
                                
                                <?php if (empty($answer['answer_text'])): ?>
                                    <p class="text-muted fst-italic">No answer provided</p>
                                
                                <?php elseif ($questionType === 'single_choice'): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-primary"><?= htmlspecialchars($answer['answer_text']) ?></span>
                                    </div>
                                
                                <?php elseif ($questionType === 'multiple_choice'): ?>
                                    <div class="mt-2">
                                        <?php 
                                        $choices = explode(', ', $answer['answer_text']);
                                        foreach ($choices as $choice): 
                                        ?>
                                            <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars($choice) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                
                                <?php elseif ($questionType === 'date'): ?>
                                    <p><?= date('F j, Y', strtotime($answer['answer_text'])) ?></p>
                                
                                <?php elseif ($questionType === 'time'): ?>
                                    <p><?= date('g:i A', strtotime($answer['answer_text'])) ?></p>
                                
                                <?php elseif ($questionType === 'long_text'): ?>
                                    <div class="p-3 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($answer['answer_text'])) ?>
                                    </div>
                                
                                <?php else: ?>
                                    <p><?= htmlspecialchars($answer['answer_text']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspectionId ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to All Responses
                    </a>
                    
                    <div>
                        <?php if (isPro()): ?>
                            <a href="<?= BASE_URL ?>/inspections/responses/share.php?id=<?= $inspectionId ?>" class="btn btn-outline-primary me-2">
                                <i class="fas fa-share-alt me-1"></i>Share
                            </a>
                            <a href="<?= BASE_URL ?>/inspections/responses/delete.php?id=<?= $responseId ?>&inspection_id=<?= $inspectionId ?>" 
                               class="btn btn-outline-danger confirm-delete">
                                <i class="fas fa-trash-alt me-1"></i>Delete
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; ?>
