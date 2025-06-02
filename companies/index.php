<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

// Check if user is logged in
requireLogin();

$userId = getCurrentUserId();

// Gerenciar configuração de empresa ativa
if (isset($_GET['set_active'])) {
    $companyId = sanitizeInput($_GET['set_active']);
    $company = getCompanyById($companyId);
    
    if ($company && $company['user_id'] === $userId) {
        setActiveCompany($companyId);
        addSuccessMessage("Empresa ativa alterada para: " . $company['name']);
    }
    
    redirect(BASE_URL . "/companies/index.php");
}

// Obter todas as empresas deste usuário
$companies = getUserCompanies($userId);
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Empresas</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>/companies/create.php" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i>Nova Empresa
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Suas Empresas</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Nome</th>
                            <th>Criada em</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <p class="mb-0">Nenhuma empresa encontrada.</p>
                                    <a href="<?= BASE_URL ?>/companies/create.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i>Criar Empresa
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if (!empty($company['logo_path'])): ?>
                                            <img src="<?= htmlspecialchars($company['logo_path']) ?>" 
                                                 alt="Logo <?= htmlspecialchars($company['name']) ?>" 
                                                 class="img-thumbnail" 
                                                 style="max-height: 50px; max-width: 100px;">
                                        <?php else: ?>
                                            <div class="text-muted small">
                                                <i class="fas fa-building fa-2x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($company['name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($company['created_at'])) ?></td>
                                    <td>
                                        <?php if ($company['id'] === getActiveCompanyId()): ?>
                                            <span class="badge bg-primary">Ativa</span>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/companies/index.php?set_active=<?= $company['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                Tornar Ativa
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/inspections/index.php?company_id=<?= $company['id'] ?>" 
                                               class="btn btn-outline-info" 
                                               data-bs-toggle="tooltip" 
                                               title="Inspeções">
                                                <i class="fas fa-clipboard-list"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/companies/edit.php?id=<?= $company['id'] ?>" 
                                               class="btn btn-outline-primary" 
                                               data-bs-toggle="tooltip" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/companies/delete.php?id=<?= $company['id'] ?>" 
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

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php'; ?>
