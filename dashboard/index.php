<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/achievements/notifications.php';

// Check if user is logged in
requireLogin();

// Verificar se o usuário ganhou novas conquistas
checkForNewAchievements();

$userId = getCurrentUserId();
$companies = getUserCompanies($userId);

// If user has no companies yet, redirect to create company page
if (empty($companies)) {
    addError("Por favor, crie uma empresa para começar.");
    redirect("/companies/create.php");
}

// If no active company is set, set the first one as active
if (!getActiveCompanyId() && !empty($companies)) {
    setActiveCompany($companies[0]['id']);
}

$activeCompanyId = getActiveCompanyId();
$activeCompany = getCompanyById($activeCompanyId);

// Get inspections for the active company
$inspections = getCompanyInspections($activeCompanyId);

// Count draft and published inspections
$draftCount = 0;
$publishedCount = 0;
$totalResponses = 0;

foreach ($inspections as $inspection) {
    if ($inspection['status'] === 'draft') {
        $draftCount++;
    } else if ($inspection['status'] === 'published') {
        $publishedCount++;
    }
    $totalResponses += $inspection['response_count'];
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Painel</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/inspections/create.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus me-1"></i>Nova Inspeção
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-light">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-number"><?= count($inspections) ?></div>
            <div class="stat-title">Total de Inspeções</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-light">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-number"><?= $draftCount ?></div>
            <div class="stat-title">Inspeções em Rascunho</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-light">
            <div class="stat-icon"><i class="fas fa-file-export"></i></div>
            <div class="stat-number"><?= $publishedCount ?></div>
            <div class="stat-title">Inspeções Publicadas</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-light">
            <div class="stat-icon"><i class="fas fa-reply"></i></div>
            <div class="stat-number"><?= $totalResponses ?></div>
            <div class="stat-title">Total de Respostas</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Inspeções Recentes</h5>
                <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-sm btn-outline-primary">Ver Todas</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Respostas</th>
                            <th>Criada em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inspections)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <p class="mb-0">Nenhuma inspeção encontrada.</p>
                                    <a href="<?= BASE_URL ?>/inspections/create.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i>Criar Inspeção
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            // Show only the 5 most recent inspections
                            $recentInspections = array_slice($inspections, 0, 5);
                            foreach ($recentInspections as $inspection): 
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($inspection['title']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $inspection['status'] ?>">
                                            <?= ucfirst($inspection['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $inspection['response_count'] ?></td>
                                    <td><?= date('M j, Y', strtotime($inspection['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($inspection['status'] === 'published'): ?>
                                                <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspection['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Ver Respostas">
                                                    <i class="fas fa-eye"></i>
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

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Suas Empresas</h5>
                <a href="<?= BASE_URL ?>/companies/index.php" class="btn btn-sm btn-outline-primary">Gerenciar</a>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($companies as $company): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($company['name']) ?>
                            <?php if ($company['id'] === $activeCompanyId): ?>
                                <span class="badge bg-primary rounded-pill">Ativa</span>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/companies/index.php?set_active=<?= $company['id'] ?>" 
                                   class="btn btn-sm btn-outline-secondary">
                                    Ativar
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-footer">
                <a href="<?= BASE_URL ?>/companies/create.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i>Nova Empresa
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Seu Plano</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>
                        <?php if (isPro()): ?>
                            <span class="badge bg-warning text-dark">Plano Pro</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Plano Gratuito</span>
                        <?php endif; ?>
                    </h4>
                    <?php if (!isPro()): ?>
                        <a href="#" class="btn btn-warning">Atualizar para Pro</a>
                    <?php endif; ?>
                </div>
                
                <h6 class="mb-2">Recursos do Plano:</h6>
                <ul class="list-group list-group-flush">
                    <?php if (isPro()): ?>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>Inspeções ilimitadas
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>Limites de respostas configuráveis
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>Excluir respostas individuais
                        </li>
                    <?php else: ?>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>Até 3 inspeções por empresa
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>Limitado a 3 respostas por inspeção
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times text-danger me-2"></i>Não é possível excluir respostas individuais
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php 
// Exibir notificações de conquistas, se houver
displayAchievementNotifications();

include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; 
?>
