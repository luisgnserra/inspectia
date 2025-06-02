<?php
require_once  '../../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';
//require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/js/main.js';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

// Get inspection ID from URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId)) {
    addError("Inspection ID is required.");
    redirect(url: "/inspections/responses/index.php");
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to this company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspection not found or you don't have permission to view responses.");
    redirect(url: "/inspections/responses/index.php");
}

// Get responses for this inspection
$responses = getResponsesByInspectionId($inspectionId);

// Get questions for this inspection
$questions = getQuestionsByInspectionId($inspectionId);
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Respostas</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" id="analyze-ai-btn" class="btn btn-sm btn-primary me-2">
            <i class="fas fa-robot me-1"></i>Analisar com IA
        </button>
        <div class="btn-group me-2">
            <button type="button" id="export-csv-btn" class="btn btn-sm btn-outline-secondary" data-inspection-id="<?= $inspectionId ?>">
                <i class="fas fa-file-csv me-1"></i>Exportar CSV
            </button>
            <button type="button" id="export-json-btn" class="btn btn-sm btn-outline-secondary" data-inspection-id="<?= $inspectionId ?>">
                <i class="fas fa-file-code me-1"></i>Exportar JSON
            </button>
        </div>
        <a href="<?= BASE_URL ?>/inspections/responses/share.php?id=<?= $inspectionId ?>" class="btn btn-sm btn-outline-primary me-2">
            <i class="fas fa-share-alt me-1"></i>Compartilhar
        </a>
        <?php if (!empty($responses)): ?>
            <a href="<?= BASE_URL ?>/inspections/responses/delete-all.php?id=<?= $inspectionId ?>" class="btn btn-sm btn-outline-danger me-2 confirm-delete">
                <i class="fas fa-trash-alt me-1"></i>Excluir Tudo
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Inspeções
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?= htmlspecialchars($inspection['title']) ?> - Respostas
                </h5>
                <div>
                    <span class="status-badge status-<?= $inspection['status'] ?> me-2">
                        <?php 
                        $status = $inspection['status'];
                        if ($status === 'draft') {
                            echo 'Rascunho';
                        } elseif ($status === 'published') {
                            echo 'Publicado';
                        } else {
                            echo ucfirst($status);
                        }
                        ?>
                    </span>
                    <?php if ($inspection['status'] === 'published'): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary copy-link-btn" data-link="<?= BASE_URL ?>/public/inspection.php?id=<?= $inspection['public_link'] ?>">
                            <i class="fas fa-link me-1"></i>Copiar Link do Formulário
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($responses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                        <h5>Nenhuma Resposta Ainda</h5>
                        <p class="text-muted">Não há respostas para este formulário de inspeção ainda.</p>
                        <?php if ($inspection['status'] === 'published'): ?>
                            <button type="button" class="btn btn-primary copy-link-btn mt-2" data-link="<?= BASE_URL ?>/public/inspection.php?id=<?= $inspection['public_link'] ?>">
                                <i class="fas fa-link me-1"></i>Copiar Link para Compartilhar
                            </button>
                        <?php else: ?>
                            <p>Esta inspeção não está publicada. Publique-a para coletar respostas.</p>
                            <a href="<?= BASE_URL ?>/inspections/publish.php?id=<?= $inspectionId ?>" class="btn btn-primary mt-2">
                                <i class="fas fa-file-export me-1"></i>Publicar Agora
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <h6>Total de Respostas: <span class="badge bg-primary"><?= count($responses) ?></span></h6>
                        <?php if ($inspection['response_limit'] === 'single'): ?>
                            <p class="text-muted mb-0">Este formulário aceita apenas uma resposta.</p>
                        <?php elseif ($inspection['response_limit'] === 'multiple'): ?>
                            <p class="text-muted mb-0">
                                Este formulário aceita até <?= $inspection['max_responses'] ?> respostas 
                                (<?= $inspection['response_count'] ?>/<?= $inspection['max_responses'] ?> recebidas).
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-0">Este formulário aceita respostas ilimitadas.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Data</th>
                                    <?php 
                                    // Extrair todas as perguntas do primeiro conjunto de respostas para criar os cabeçalhos
                                    if (!empty($responses) && !empty($questions)) {
                                        foreach ($questions as $question) {
                                            // Verificar se a chave 'text' existe para evitar avisos
                                            $questionText = isset($question['text']) ? $question['text'] : '';
                                            echo '<th>' . htmlspecialchars($questionText) . '</th>';
                                        }
                                    }
                                    ?>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responses as $index => $response): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($response['created_at'])) ?></td>
                                    <?php 
                                    $responseAnswers = getResponseAnswers($response['id']);
                                    $answersMap = [];
                                    
                                    // Mapear respostas por ID da pergunta para facilitar a exibição na ordem correta
                                    foreach ($responseAnswers as $answer) {
                                        $answersMap[$answer['question_id']] = $answer['answer_text'];
                                    }
                                    
                                    // Exibir as respostas na mesma ordem das colunas
                                    foreach ($questions as $question) {
                                        $answerText = isset($answersMap[$question['id']]) ? $answersMap[$question['id']] : '';
                                        
                                        // Tratar campos vazios
                                        if (empty($answerText)) {
                                            echo '<td><span class="text-muted">-</span></td>';
                                        } 
                                        // Verificar se é uma imagem
                                        else if (strpos($answerText, '/uploads/images/') === 0) {
                                            echo '<td><img src="' . htmlspecialchars($answerText) . '" class="img-thumbnail" style="max-height: 50px;"></td>';
                                        } 
                                        // Texto normal
                                        else {
                                            echo '<td>' . nl2br(htmlspecialchars($answerText)) . '</td>';
                                        }
                                    }
                                    ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info view-response-btn" 
                                                    data-response-id="<?= $response['id'] ?>" data-response-index="<?= $index + 1 ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                           <!-- <button type="button" class="btn btn-outline-primary" 
                                                    onclick="generatePDF('<?= $response['id'] ?>'); return false;">
                                                <i class="fas fa-file-pdf"></i>
                                            </button> -->
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDelete('<?= $response['id'] ?>', '<?= $inspectionId ?>', <?= $index + 1 ?>); return false;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- O modal antigo foi removido -->
                    <!-- Vamos criar um novo modal para detalhes da resposta via JavaScript -->
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a resposta <span id="deleteResponseNumber" class="fw-bold"></span>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Excluir</a>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php'; ?>
