<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

// Check if user is logged in
requireLogin();

// Check if a specific company ID is provided in the URL
if (isset($_GET['company_id'])) {
    $companyId = sanitizeInput($_GET['company_id']);
    $company = getCompanyById($companyId);
    
    // Verificar se a empresa pertence ao usuário atual
    if (!$company || $company['user_id'] !== getCurrentUserId()) {
        addError("Você não tem permissão para acessar esta empresa.");
        redirect(BASE_URL . "/companies/index.php");
    }
} else {
    // Se não, use a empresa ativa
    requireActiveCompany();
    $companyId = getActiveCompanyId();
    $company = getCompanyById($companyId);
}

// Get inspections for this company
$inspections = getCompanyInspections($companyId);
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Inspeções</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>/inspections/create.php" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i>Nova Inspeção
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Inspeções de <?= htmlspecialchars($company['name']) ?>
                </h5>
                <div>
                    <?php if (isPro()): ?>
                        <span class="badge bg-warning text-dark me-2">Plano Pro</span>
                    <?php else: ?>
                        <span class="badge bg-secondary me-2">Plano Gratuito (<?= count($inspections) ?>/<?= FREE_PLAN_MAX_INSPECTIONS ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Limite de Respostas</th>
                            <th>Respostas</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inspections)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="mb-0">Nenhuma inspeção encontrada.</p>
                                    <?php if (canCreateMoreInspections($companyId)): ?>
                                        <a href="<?= BASE_URL ?>/inspections/create.php" class="btn btn-sm btn-primary mt-2">
                                            <i class="fas fa-plus me-1"></i>Criar Inspeção
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted mt-2">
                                            Você atingiu o limite de inspeções do plano gratuito.
                                            <a href="#">Atualize para o Pro</a> para inspeções ilimitadas.
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inspections as $inspection): ?>
                                <tr>
                                    <td><?= htmlspecialchars($inspection['title']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $inspection['status'] ?>">
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
                                    </td>
                                    <td>
                                        <?php if ($inspection['response_limit'] === 'single'): ?>
                                            <span class="badge bg-info">Resposta Única</span>
                                        <?php elseif ($inspection['response_limit'] === 'multiple'): ?>
                                            <span class="badge bg-info">Múltiplas (<?= $inspection['max_responses'] ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Ilimitado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $inspection['response_count'] ?>
                                        <!-- Sempre exibe o botão de visualizar respostas, independentemente do status -->
                                        <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspection['id'] ?>" 
                                           class="btn btn-sm btn-link p-0 ms-1" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver Respostas">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-link p-0 ms-1 view-responses-modal-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#responsesModal"
                                                data-inspection-id="<?= $inspection['id'] ?>"
                                                data-inspection-title="<?= htmlspecialchars($inspection['title']) ?>">
                                            <!--<i class="fas fa-list-alt"></i>  -->
                                        </button>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($inspection['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($inspection['status'] === 'published'): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-primary copy-link-btn" 
                                                        data-link="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . BASE_URL ?>/public/inspection.php?id=<?= $inspection['public_link'] ?>" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Copiar Link">
                                                    <i class="fas fa-link"></i>
                                                </button>
                                                <a href="<?= BASE_URL ?>/inspections/preview.php?id=<?= $inspection['id'] ?>" 
                                                   class="btn btn-outline-info preview-modal-btn" 
                                                   data-inspection-id="<?= $inspection['id'] ?>"
                                                   data-bs-toggle="tooltip" 
                                                   title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspection['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Ver Respostas">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/inspections/unpublish.php?id=<?= $inspection['id'] ?>" 
                                                   class="btn btn-outline-secondary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Despublicar">
                                                    <i class="fas fa-file-import"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/inspections/edit.php?id=<?= $inspection['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/inspections/preview.php?id=<?= $inspection['id'] ?>" 
                                                   class="btn btn-outline-info preview-modal-btn" 
                                                   data-inspection-id="<?= $inspection['id'] ?>"
                                                   data-bs-toggle="tooltip" 
                                                   title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/inspections/publish.php?id=<?= $inspection['id'] ?>" 
                                                   class="btn btn-outline-success" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Publicar">
                                                    <i class="fas fa-file-export"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= BASE_URL ?>/inspections/delete.php?id=<?= $inspection['id'] ?>" 
                                               class="btn btn-outline-danger confirm-delete" 
                                               data-bs-toggle="tooltip" 
                                               title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Respostas -->
<div class="modal fade" id="responsesModal" tabindex="-1" aria-labelledby="responsesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responsesModalLabel">Respostas da Inspeção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center p-5" id="modal-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando respostas...</p>
                </div>
                <div id="modal-content" class="d-none">
                    <h6 class="mb-3" id="modal-inspection-title"></h6>
                    <div id="modal-responses-container">
                        <!-- As respostas serão carregadas aqui via AJAX -->
                    </div>
                </div>
                <div class="alert alert-danger d-none" id="modal-error">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="modal-error-message">Erro ao carregar respostas.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="#" class="btn btn-primary" id="modal-view-all-btn">Ver Todas as Respostas</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Visualização -->
<div class="modal fade modal-fullscreen" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Visualizar Inspeção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="previewModalBody">
                    <!-- O conteúdo do formulário será carregado aqui via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <?php /*<a href="<?= isset($_GET['company_id']) ? '/inspections/index.php?company_id=' . htmlspecialchars($companyId) : '/inspections/index.php' ?>" class="btn btn-secondary" id="previewModalBackButton">Voltar</a> */ ?>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php'; ?>
