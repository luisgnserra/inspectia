<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/admin/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é admin
requireAdmin();

// Obter métricas gerais do sistema
$database = new Database();
$db = $database->getConnection();

// Total de usuários
$query = "SELECT COUNT(*) FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$totalUsers = $stmt->fetchColumn();

// Total de empresas
$query = "SELECT COUNT(*) FROM companies";
$stmt = $db->prepare($query);
$stmt->execute();
$totalCompanies = $stmt->fetchColumn();

// Total de inspeções
$query = "SELECT COUNT(*) FROM inspections";
$stmt = $db->prepare($query);
$stmt->execute();
$totalInspections = $stmt->fetchColumn();

// Total de respostas
$query = "SELECT COUNT(*) FROM responses";
$stmt = $db->prepare($query);
$stmt->execute();
$totalResponses = $stmt->fetchColumn();

// Inspeções publicadas vs. rascunho
$query = "SELECT status, COUNT(*) as count FROM inspections GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$publishedInspections = $statusCounts['published'] ?? 0;
$draftInspections = $statusCounts['draft'] ?? 0;

// Obter lista de usuários
$query = "SELECT id, email, plan, created_at FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de empresas
$query = "SELECT c.id, c.name, c.created_at, u.email as user_email 
          FROM companies c 
          LEFT JOIN users u ON c.user_id = u.id 
          ORDER BY c.created_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de inspeções
$query = "SELECT i.id, i.title, i.status, i.created_at, c.name as company_name, 
                 i.response_count, i.response_limit, i.max_responses
          FROM inspections i 
          JOIN companies c ON i.company_id = c.id 
          ORDER BY i.created_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter último login dos usuários
$query = "SELECT DISTINCT ON (user_id) user_id, created_at
          FROM user_activity_logs
          WHERE type = 'login'
          ORDER BY user_id, created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$lastLogins = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lastLogins[$row['user_id']] = $row['created_at'];
}

// Incluir template de cabeçalho
include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-cogs me-2"></i>Painel Administrativo</h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/badges.php" class="btn btn-warning me-2">
                <i class="fas fa-trophy me-1"></i>Gerenciar Badges
            </a>
            <a href="<?= BASE_URL ?>/dashboard/index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard
            </a>
        </div>
    </div>

    <!-- Cartões com métricas -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Usuários</h5>
                    <p class="display-4"><?= number_format($totalUsers) ?></p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="#users" class="text-white text-decoration-none">Ver detalhes</a>
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Empresas</h5>
                    <p class="display-4"><?= number_format($totalCompanies) ?></p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="#companies" class="text-white text-decoration-none">Ver detalhes</a>
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Inspeções</h5>
                    <p class="display-4"><?= number_format($totalInspections) ?></p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="#inspections" class="text-white text-decoration-none">Ver detalhes</a>
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title">Respostas</h5>
                    <p class="display-4"><?= number_format($totalResponses) ?></p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="#responses" class="text-dark text-decoration-none">Ver detalhes</a>
                    <i class="fas fa-reply"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e estatísticas -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Status das Inspeções</h5>
                </div>
                <div class="card-body">
                    <canvas id="inspectionStatusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Atividade do Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Métrica</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Empresas por usuário (média)</td>
                                    <td><?= number_format($totalUsers > 0 ? $totalCompanies / $totalUsers : 0, 1) ?></td>
                                </tr>
                                <tr>
                                    <td>Inspeções por empresa (média)</td>
                                    <td><?= number_format($totalCompanies > 0 ? $totalInspections / $totalCompanies : 0, 1) ?></td>
                                </tr>
                                <tr>
                                    <td>Respostas por inspeção (média)</td>
                                    <td><?= number_format($totalInspections > 0 ? $totalResponses / $totalInspections : 0, 1) ?></td>
                                </tr>
                                <tr>
                                    <td>% Inspeções publicadas</td>
                                    <td><?= number_format($totalInspections > 0 ? ($publishedInspections / $totalInspections) * 100 : 0, 1) ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs para gerenciamento de entidades -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-content" type="button" role="tab">
                        <i class="fas fa-users me-1"></i>Usuários
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="companies-tab" data-bs-toggle="tab" data-bs-target="#companies-content" type="button" role="tab">
                        <i class="fas fa-building me-1"></i>Empresas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inspections-tab" data-bs-toggle="tab" data-bs-target="#inspections-content" type="button" role="tab">
                        <i class="fas fa-clipboard-list me-1"></i>Inspeções
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="adminTabsContent">
                <!-- Aba de Usuários -->
                <div class="tab-pane fade show active" id="users-content" role="tabpanel" aria-labelledby="users-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Plano</th>
                                    <th>Data de Cadastro</th>
                                    <th>Último Login</th>
                                    <th>Empresas</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $user['plan'] === 'pro' ? 'warning' : 'secondary' ?>">
                                            <?= ucfirst($user['plan']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php if (isset($lastLogins[$user['id']])): ?>
                                            <?= date('d/m/Y H:i', strtotime($lastLogins[$user['id']])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $query = "SELECT COUNT(*) FROM companies WHERE user_id = :user_id";
                                        $stmt = $db->prepare($query);
                                        $stmt->bindParam(':user_id', $user['id']);
                                        $stmt->execute();
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userActions<?= $user['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Ações
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userActions<?= $user['id'] ?>">
                                                <li>
                                                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin/edit-user.php?id=<?= $user['id'] ?>">
                                                        <i class="fas fa-edit me-1"></i>Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin/user-details.php?id=<?= $user['id'] ?>">
                                                        <i class="fas fa-info-circle me-1"></i>Detalhes
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-<?= $user['plan'] === 'free' ? 'warning' : 'secondary' ?>" 
                                                       href="<?= BASE_URL ?>/admin/toggle-plan.php?id=<?= $user['id'] ?>&plan=<?= $user['plan'] === 'free' ? 'pro' : 'free' ?>">
                                                        <i class="fas fa-<?= $user['plan'] === 'free' ? 'crown' : 'user' ?> me-1"></i>
                                                        Mudar para <?= $user['plan'] === 'free' ? 'Pro' : 'Free' ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aba de Empresas -->
                <div class="tab-pane fade" id="companies-content" role="tabpanel" aria-labelledby="companies-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Últimas 10 empresas cadastradas</h5>
                        <a href="<?= BASE_URL ?>/admin/all-companies.php" class="btn btn-sm btn-outline-primary">
                            Ver todas
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Proprietário</th>
                                    <th>Data de Criação</th>
                                    <th>Inspeções</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td><?= htmlspecialchars($company['name']) ?></td>
                                    <td><?= htmlspecialchars($company['user_email'] ?? 'N/A') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($company['created_at'])) ?></td>
                                    <td>
                                        <?php 
                                        $query = "SELECT COUNT(*) FROM inspections WHERE company_id = :company_id";
                                        $stmt = $db->prepare($query);
                                        $stmt->bindParam(':company_id', $company['id']);
                                        $stmt->execute();
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/companies/edit.php?id=<?= $company['id'] ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/company-details.php?id=<?= $company['id'] ?>" class="btn btn-outline-info">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aba de Inspeções -->
                <div class="tab-pane fade" id="inspections-content" role="tabpanel" aria-labelledby="inspections-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Últimas 10 inspeções cadastradas</h5>
                        <a href="<?= BASE_URL ?>/admin/all-inspections.php" class="btn btn-sm btn-outline-primary">
                            Ver todas
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Empresa</th>
                                    <th>Status</th>
                                    <th>Respostas</th>
                                    <th>Data de Criação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inspections as $inspection): ?>
                                <tr>
                                    <td><?= htmlspecialchars($inspection['title']) ?></td>
                                    <td><?= htmlspecialchars($inspection['company_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $inspection['status'] === 'published' ? 'success' : 'secondary' ?>">
                                            <?= $inspection['status'] === 'published' ? 'Publicado' : 'Rascunho' ?>
                                        </span>
                                    </td>
                                    <td><?= $inspection['response_count'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($inspection['created_at'])) ?></td>
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para o gráfico de status das inspeções -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de status das inspeções
    const inspectionStatusChart = new Chart(
        document.getElementById('inspectionStatusChart').getContext('2d'),
        {
            type: 'pie',
            data: {
                labels: ['Publicadas', 'Rascunho'],
                datasets: [{
                    label: 'Status das Inspeções',
                    data: [<?= $publishedInspections ?>, <?= $draftInspections ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = <?= $totalInspections ?>;
                                const value = context.raw;
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        }
    );

    // Inicialização das tabelas com DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#usersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            },
            order: [[2, 'desc']]
        });
    }
});
</script>

<?php 
// Incluir template de rodapé
include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php';
?>