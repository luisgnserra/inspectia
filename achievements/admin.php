<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/achievements/functions.php';

// Verificar se o usuário está logado e é admin
requireLogin();

// Somente administradores podem acessar esta página (verificação simplificada por enquanto)
// Em um sistema real, você teria um sistema de permissões mais robusto
if ($_SESSION['user_email'] !== 'admin@example.com') {
    addError("Acesso negado. Somente administradores podem acessar esta página!!");
    redirect('/dashboard/index.php');
    exit;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Criar nova badge
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $imagePath = trim($_POST['image_path']);
        $criteria = trim($_POST['criteria']);
        $criteriaValue = (int)$_POST['criteria_value'];
        $level = (int)$_POST['level'];
        
        if (empty($name) || empty($description) || empty($imagePath) || empty($criteria) || $criteriaValue <= 0) {
            addError("Todos os campos são obrigatórios e o valor do critério deve ser maior que zero.");
        } else {
            $badgeId = createBadge($name, $description, $imagePath, $criteria, $criteriaValue, $level);
            
            if ($badgeId) {
                addSuccess("Badge criada com sucesso!");
                redirect('/achievements/admin.php');
                exit;
            } else {
                addError("Erro ao criar badge.");
            }
        }
    }
    
    // Excluir badge
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $badgeId = $_POST['badge_id'];
        
        if (deleteBadge($badgeId)) {
            addSuccess("Badge excluída com sucesso!");
        } else {
            addError("Erro ao excluir badge.");
        }
        
        redirect('/achievements/admin.php');
        exit;
    }
}

// Obter todas as badges
$badges = getAllBadges();

// Incluir template de cabeçalho
include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-trophy text-warning me-2"></i>Gerenciar Badges</h1>
        <div>
            <a href="<?= BASE_URL ?>/achievements/init_badges.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-sync me-1"></i>Inicializar Badges Padrão
            </a>
            <a href="<?= BASE_URL ?>/achievements/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Voltar
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Badges Existentes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($badges)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Nenhuma badge cadastrada ainda.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Imagem</th>
                                        <th>Nome</th>
                                        <th>Critério</th>
                                        <th>Nível</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($badges as $badge): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= htmlspecialchars($badge['image_path']) ?>" alt="<?= htmlspecialchars($badge['name']) ?>" style="height: 40px; width: auto;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($badge['name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($badge['description']) ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $criteriaLabels = [
                                                    'forms_created' => 'Formulários criados',
                                                    'responses_collected' => 'Respostas coletadas',
                                                    'companies_created' => 'Empresas criadas',
                                                    'photo_questions' => 'Questões de foto criadas',
                                                    'days_active' => 'Dias ativo no sistema'
                                                ];
                                                
                                                $criteriaLabel = $criteriaLabels[$badge['criteria']] ?? $badge['criteria'];
                                                ?>
                                                <?= htmlspecialchars($criteriaLabel) ?>: <?= $badge['criteria_value'] ?>
                                            </td>
                                            <td>
                                                <?php for ($i = 0; $i < $badge['level']; $i++): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php endfor; ?>
                                            </td>
                                            <td>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta badge?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="badge_id" value="<?= $badge['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
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
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Adicionar Nova Badge</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image_path" class="form-label">Caminho da Imagem</label>
                            <select class="form-control" id="image_path" name="image_path" required>
                                <option value="/achievements/images/badge_first_form.svg">Badge Formulário</option>
                                <option value="/achievements/images/badge_response_collector.svg">Badge Respostas</option>
                                <option value="/achievements/images/badge_company_creator.svg">Badge Empresa</option>
                                <option value="/achievements/images/badge_photo_master.svg">Badge Foto</option>
                                <option value="/achievements/images/badge_power_user.svg">Badge Usuário Fiel</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="criteria" class="form-label">Critério</label>
                            <select class="form-control" id="criteria" name="criteria" required>
                                <option value="forms_created">Formulários criados</option>
                                <option value="responses_collected">Respostas coletadas</option>
                                <option value="companies_created">Empresas criadas</option>
                                <option value="photo_questions">Questões de foto criadas</option>
                                <option value="days_active">Dias ativo no sistema</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="criteria_value" class="form-label">Valor do Critério</label>
                            <input type="number" class="form-control" id="criteria_value" name="criteria_value" min="1" value="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="level" class="form-label">Nível da Badge</label>
                            <select class="form-control" id="level" name="level" required>
                                <option value="1">Nível 1 (Iniciante)</option>
                                <option value="2">Nível 2 (Intermediário)</option>
                                <option value="3">Nível 3 (Avançado)</option>
                            </select>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Adicionar Badge
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir template de rodapé
include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php';
?>
