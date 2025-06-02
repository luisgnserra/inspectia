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

// Obter dados do usuário a ser editado
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

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $plan = sanitizeInput($_POST['plan'] ?? '');
    $newPassword = sanitizeInput($_POST['new_password'] ?? '');
    
    $errors = [];
    
    // Validar email
    if (empty($email)) {
        $errors[] = "O email é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "O email fornecido é inválido";
    } elseif ($email !== $user['email']) {
        // Verificar se o novo email já está em uso
        $existingUser = getUserByEmail($email);
        if ($existingUser && $existingUser['id'] !== $userId) {
            $errors[] = "Este email já está em uso por outro usuário";
        }
    }
    
    // Validar plano
    if (!in_array($plan, ['free', 'pro'])) {
        $errors[] = "Plano inválido";
    }
    
    // Se não há erros, atualizar o usuário
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Preparar atualização
        if (!empty($newPassword)) {
            // Atualizar email, plano e senha
            $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $query = "UPDATE users SET 
                      email = :email, 
                      plan = :plan, 
                      password_hash = :password_hash 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':plan', $plan);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':id', $userId);
        } else {
            // Atualizar apenas email e plano
            $query = "UPDATE users SET 
                      email = :email, 
                      plan = :plan 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':plan', $plan);
            $stmt->bindParam(':id', $userId);
        }
        
        if ($stmt->execute()) {
            // Registrar atividade administrativa
            $description = "Admin atualizou o usuário $email";
            logAdminActivity("user_update", $description);
            
            addSuccessMessage("Usuário atualizado com sucesso");
            redirect(url: "/inspectia/admin/index.php");
            exit;
        } else {
            $errors[] = "Erro ao atualizar o usuário. Por favor, tente novamente.";
        }
    }
    
    // Exibir erros, se houver
    foreach ($errors as $error) {
        addError($error);
    }
}



// Obter empresas do usuário
$database = new Database();
$db = $database->getConnection();
$query = "SELECT * FROM companies WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter último login do usuário
$query = "SELECT created_at FROM user_activity_logs 
          WHERE user_id = :user_id AND type = 'login' 
          ORDER BY created_at DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$lastLogin = $stmt->fetch(PDO::FETCH_COLUMN);

// Incluir template de cabeçalho
include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-user-edit me-2"></i>Editar Usuário</h1>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel Admin
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Formulário de edição -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informações do Usuário</h5>
                </div>
                <div class="card-body">
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $userId ?>" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="plan" class="form-label">Plano</label>
                            <select class="form-select" id="plan" name="plan" required>
                                <option value="free" <?= $user['plan'] === 'free' ? 'selected' : '' ?>>Free</option>
                                <option value="pro" <?= $user['plan'] === 'pro' ? 'selected' : '' ?>>Pro</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Preencha apenas se desejar alterar a senha do usuário.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data de Cadastro</label>
                            <p class="form-control-plaintext"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Último Login</label>
                            <p class="form-control-plaintext">
                                <?php if ($lastLogin): ?>
                                    <?= date('d/m/Y H:i', strtotime($lastLogin)) ?>
                                <?php else: ?>
                                    <span class="text-muted">Nunca</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Informações extras -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Resumo</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div>
                            <span class="badge bg-<?= $user['plan'] === 'pro' ? 'warning' : 'secondary' ?>">
                                <?= ucfirst($user['plan']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Empresas</label>
                        <div><?= count($companies) ?></div>
                    </div>
                    
                    <div>
                        <label class="form-label fw-bold">Badges</label>
                        <div>
                            <?php 
                            $badges = getUserBadges($userId);
                            if (empty($badges)): 
                            ?>
                                <span class="text-muted">Nenhuma badge conquistada</span>
                            <?php else: ?>
                                <?php foreach ($badges as $badge): ?>
                                    <img src="<?= $badge['image_path'] ?>" 
                                        alt="<?= htmlspecialchars($badge['name']) ?>" 
                                        title="<?= htmlspecialchars($badge['name']) ?>" 
                                        class="img-thumbnail me-1" 
                                        style="max-width: 40px;">
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Empresas do usuário -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Empresas</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($companies)): ?>
                        <p class="text-center p-3 mb-0">Nenhuma empresa cadastrada</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($companies as $company): ?>
                                <a href="<?= BASE_URL ?>/companies/edit.php?id=<?= $company['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <?php if (!empty($company['logo_path'])): ?>
                                        <img src="<?= $company['logo_path'] ?>" alt="Logo" class="me-2" style="max-width: 30px; max-height: 30px;">
                                    <?php else: ?>
                                        <i class="fas fa-building me-2 text-secondary"></i>
                                    <?php endif; ?>
                                    <div>
                                        <div><?= htmlspecialchars($company['name']) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($company['created_at'])) ?></small>
                                    </div>
                                </a>
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