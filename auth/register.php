<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url: "/dashboard/index.php");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Register the user
    $userId = registerUser($email, $password, $confirmPassword);
    
    if ($userId) {
        // Log the user in
        if (loginUser($email, $password)) {
            addSuccessMessage("Cadastro realizado com sucesso! Bem-vindo ao Sistema de Inspeções.");
            redirect(url: "/companies/create.php");
        }
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card my-5">
            <div class="card-header">
                <h3 class="mb-0">Criar uma Conta</h3>
            </div>
            <div class="card-body">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Endereço de Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">A senha deve ter pelo menos 8 caracteres.</div>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Cadastrar
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Já tem uma conta? <a href="<?= BASE_URL ?>/auth/login.php">Entrar</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php'; ?>
