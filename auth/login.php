<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url: "/inspectia/dashboard/index.php");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados de login padrão para o usuário admin
   // $defaultEmail = 'admin@example.com1';
   // $defaultPassword = '12345678';
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Mensagem para instrução do usuário
    addSuccessMessage("");
    
    // Verificando o usuário
    $user = getUserByEmail($email);
    
    if (!$user) {
        addError("Usuário não encontrado. Por favor, tente novamente.");
    } else {
        // Verificando a senha
        if (password_verify($password, $user['password_hash'])) {
            // Configurando variáveis de sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_plan'] = $user['plan'];
            
            // Verificando as empresas do usuário
            $companies = getUserCompanies($user['id']);
            
            if (count($companies) > 0) {
                // Definindo a primeira empresa como ativa
                setActiveCompany($companies[0]['id']);
            }
            
            addSuccessMessage("Login bem-sucedido! Bem-vindo de volta.");
            redirect(url: "/inspectiadashboard/index.php");
        } else {
            addError("Senha inválida. Por favor, tente novamente.");
        }
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card my-5">
            <div class="card-header">
                <h3 class="mb-0">Acesse Sua Conta</h3>
            </div>
            <div class="card-body">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Endereço de Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="text-end mt-2">
                            <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="small">Esqueceu a Senha?</a>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Não tem uma conta? <a href="<?= BASE_URL ?>/auth/register.php">Cadastre-se</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; ?>
