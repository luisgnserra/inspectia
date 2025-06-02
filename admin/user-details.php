<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/achievements/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/admin/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é admin
requireAdmin();

// Obter dados do usuário
if (!isset($_GET['id']) || empty($_GET['id'])) {
    addError("ID do usuário não fornecido");
    redirect(url: "/inspectia/admin/index.php");
    exit;
}

$userId = sanitizeInput($_GET['id']);
$user = getUserById($userId);

if (!$user) {
    addError("Usuário não encontrado");
    redirect(url: "/inspectia/admin/index.php");
    exit;
}

// Obter empresas do usuário
$companies = getUserCompanies($userId);

// Obter formulários do usuário
$database = new Database();
$db = $database->getConnection();
$query = "SELECT i.* FROM inspections i 
          JOIN companies c ON i.company_id = c.id 
          WHERE c.user_id = :user_id 
          ORDER BY i.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter respostas coletadas
$query = "SELECT COUNT(*) FROM responses r 
          JOIN inspections i ON r.inspection_id = i.id 
          JOIN companies c ON i.company_id = c.id 
          WHERE c.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$totalResponses = $stmt->fetchColumn();

// Obter atividades do usuário
$query = "SELECT * FROM user_activity_logs 
          WHERE user_id = :user_id 
          ORDER BY created_at DESC 
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter badges do usuário
$badges = getUserBadges($userId);

// Incluir template de cabeçalho
include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-user me-2"></i>Detalhes do Usuário</h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/edit-user.php?id=<?= $userId ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i>Editar Usuário
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel Admin
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Perfil do usuário -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Perfil</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded-circle p-3 me-3">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($user['email']) ?></h5>
                                <span class="badge bg-<?= $user['plan'] === 'pro' ? 'warning' : 'secondary' ?>">
                                    <?= ucfirst($user['plan']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Data de Cadastro</small>
                        <div><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Último Login</small>
                        <?php 
                        $query = "SELECT created_at FROM user_activity_logs 
                                  WHERE user_id = :user_id AND type = 'login' 
                                  ORDER BY created_at DESC LIMIT 1";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $userId);
                        $stmt->execute();
                        $lastLogin = $stmt->fetch(PDO::FETCH_COLUMN);
                        ?>
                        <div>
                            <?php if ($lastLogin): ?>
                                <?= date('d/m/Y H:i', strtotime($lastLogin)) ?>
                            <?php else: ?>
                                <span class="text-muted">Nunca</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/admin/toggle-plan.php?id=<?= $userId ?>&plan=<?= $user['plan'] === 'free' ? 'pro' : 'free' ?>" 
                           class="btn btn-sm btn-<?= $user['plan'] === 'free' ? 'warning' : 'secondary' ?>">
                            <i class="fas fa-<?= $user['plan'] === 'free' ? 'crown' : 'user' ?> me-1"></i>
                            Mudar para plano <?= $user['plan'] === 'free' ? 'Pro' : 'Free' ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Estatísticas</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="display-6"><?= count($companies) ?></div>
                            <small class="text-muted">Empresas</small>
                        </div>
                        <div class="col-4">
                            <div class="display-6"><?= count($inspections) ?></div>
                            <small class="text-muted">Formulários</small>
                        </div>
                        <div class="col-4">
                            <div class="display-6"><?= $totalResponses ?></div>
                            <small class="text-muted">Respostas</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Badges -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Conquistas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($badges)): ?>
                        <p class="text-center text-muted mb-0">Nenhuma badge conquistada</p>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($badges as $badge): ?>
                                <div class="col-4 text-center">
                                    <img src="<?= $badge['image_path'] ?>" 
                                         alt="<?= htmlspecialchars($badge['name']) ?>" 
                                         title="<?= htmlspecialchars($badge['description']) ?>" 
                                         class="img-fluid" 
                                         style="max-width: 60px;">
                                    <div class="small mt-1"><?= htmlspecialchars($badge['name']) ?></div>
                                    <div class="small text-muted">
                                        <?= date('d/m/Y', strtotime($badge['achieved_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Empresas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Empresas</h5>
                    <span class="badge bg-light text-dark"><?= count($companies) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($companies)): ?>
                        <p class="text-center p-3 mb-0">Nenhuma empresa cadastrada</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Logo</th>
                                        <th>Nome</th>
                                        <th>Data de Criação</th>
                                        <th>Formulários</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($company['logo_path'])): ?>
                                                    <img src="<?= $company['logo_path'] ?>" alt="Logo" style="max-width: 40px; max-height: 40px;">
                                                <?php else: ?>
                                                    <i class="fas fa-building text-secondary"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($company['name']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($company['created_at'])) ?></td>
                                            <td>
                                                <?php 
                                                $count = countCompanyInspections($company['id']);
                                                echo $count;
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
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Atividades recentes -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Atividades Recentes</h5>
                    <span class="badge bg-light text-dark"><?= count($activities) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($activities)): ?>
                        <p class="text-center p-3 mb-0">Nenhuma atividade registrada</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($activities as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-<?= $activity['type'] === 'login' ? 'sign-in-alt' : 'file-alt' ?> me-2 text-<?= $activity['type'] === 'login' ? 'success' : 'secondary' ?>"></i>
                                            <?= $activity['type'] === 'login' ? 'Login' : ($activity['description'] ?? ucfirst($activity['type'])) ?>
                                        </div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Incluir template de rodapé
include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php';
?>