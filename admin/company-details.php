<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/achievements/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é admin
requireAdmin();

// Obter dados da empresa
if (!isset($_GET['id']) || empty($_GET['id'])) {
    addError("ID da empresa não fornecido");
    redirect(url: "/admin/index.php");
    exit;
}

$companyId = sanitizeInput($_GET['id']);
$company = getCompanyById($companyId);

if (!$company) {
    addError("Empresa não encontrada");
    redirect(url: "/admin/index.php");
    exit;
}

// Obter dados do proprietário
$owner = getUserById($company['user_id']);

// Obter formulários da empresa
$inspections = getCompanyInspections($companyId);

// Obter total de respostas
$database = new Database();
$db = $database->getConnection();
$query = "SELECT SUM(response_count) FROM inspections WHERE company_id = :company_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':company_id', $companyId);
$stmt->execute();
$totalResponses = $stmt->fetchColumn() ?: 0;

// Incluir template de cabeçalho
include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-building me-2"></i>Detalhes da Empresa</h1>
        <div>
            <a href="<?= BASE_URL ?>/companies/edit.php?id=<?= $companyId ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i>Editar Empresa
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel Admin
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Perfil da empresa -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Perfil da Empresa</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4 text-center">
                        <?php if (!empty($company['logo_path'])): ?>
                            <img src="<?= $company['logo_path'] ?>" alt="Logo" class="img-fluid mb-3" style="max-height: 100px;">
                        <?php else: ?>
                            <div class="bg-light rounded p-4 mb-3 d-inline-block">
                                <i class="fas fa-building fa-4x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mb-0"><?= htmlspecialchars($company['name']) ?></h4>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Proprietário</small>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user me-2 text-primary"></i>
                            <a href="<?= BASE_URL ?>/admin/user-details.php?id=<?= $company['user_id'] ?>">
                                <?= htmlspecialchars($owner['email']) ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Plano do Proprietário</small>
                        <div>
                            <span class="badge bg-<?= $owner['plan'] === 'pro' ? 'warning' : 'secondary' ?>">
                                <?= ucfirst($owner['plan']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Data de Criação</small>
                        <div><?= date('d/m/Y H:i', strtotime($company['created_at'])) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Estatísticas</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="display-6"><?= count($inspections) ?></div>
                            <small class="text-muted">Formulários</small>
                        </div>
                        <div class="col-6">
                            <div class="display-6"><?= $totalResponses ?></div>
                            <small class="text-muted">Respostas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Formulários -->
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Formulários de Inspeção</h5>
                    <span class="badge bg-light text-dark"><?= count($inspections) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($inspections)): ?>
                        <p class="text-center p-3 mb-0">Nenhum formulário cadastrado</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Título</th>
                                        <th>Status</th>
                                        <th>Respostas</th>
                                        <th>Limite</th>
                                        <th>Data de Criação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inspections as $inspection): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($inspection['title']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $inspection['status'] === 'published' ? 'success' : 'secondary' ?>">
                                                    <?= $inspection['status'] === 'published' ? 'Publicado' : 'Rascunho' ?>
                                                </span>
                                            </td>
                                            <td><?= $inspection['response_count'] ?></td>
                                            <td>
                                                <?php if ($inspection['response_limit'] === 'single'): ?>
                                                    <span class="badge bg-info">Única</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Múltiplas</span>
                                                    <?php if (isset($inspection['max_responses'])): ?>
                                                        <span class="badge bg-secondary"><?= $inspection['max_responses'] ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($inspection['created_at'])) ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Ações
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="<?= BASE_URL ?>/inspections/edit.php?id=<?= $inspection['id'] ?>">
                                                                <i class="fas fa-edit me-1"></i>Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspection['id'] ?>">
                                                                <i class="fas fa-reply me-1"></i>Ver Respostas
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <?php if ($inspection['status'] === 'draft'): ?>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="<?= BASE_URL ?>/admin/toggle-inspection-status.php?id=<?= $inspection['id'] ?>&action=publish">
                                                                <i class="fas fa-check-circle me-1"></i>Publicar
                                                            </a>
                                                        </li>
                                                        <?php else: ?>
                                                        <li>
                                                            <a class="dropdown-item text-secondary" href="<?= BASE_URL ?>/admin/toggle-inspection-status.php?id=<?= $inspection['id'] ?>&action=unpublish">
                                                                <i class="fas fa-ban me-1"></i>Despublicar
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Incluir template de rodapé
include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php';
?>