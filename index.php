<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Redirect to dashboard if logged in
if (isLoggedIn()) {
    redirect(url: "/inspectia/dashboard/index.php");
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="px-4 py-5 my-5 text-center">
    <h1 class="display-5 fw-bold text-primary">
        <i class="fas fa-clipboard-check me-2"></i>Sistema de Inspeções
    </h1>
    <div class="col-lg-6 mx-auto">
        <p class="lead mb-4">
            Crie, gerencie e analise formulários de inspeção personalizados com nossa plataforma poderosa.
            Comece a capturar dados de forma eficaz hoje mesmo!
        </p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg px-4 gap-3">
                <i class="fas fa-user-plus me-2"></i>Cadastre-se Grátis
            </a>
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-secondary btn-lg px-4">
                <i class="fas fa-sign-in-alt me-2"></i>Entrar
            </a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-4 py-5">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-edit text-primary fa-3x mb-3"></i>
                    <h3 class="card-title">Crie Formulários</h3>
                    <p class="card-text">
                        Projete formulários de inspeção personalizados com vários tipos de perguntas para capturar exatamente os dados que você precisa.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-share-alt text-primary fa-3x mb-3"></i>
                    <h3 class="card-title">Colete Respostas</h3>
                    <p class="card-text">
                        Compartilhe seus formulários com um link simples e colete respostas de qualquer pessoa, em qualquer lugar.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar text-primary fa-3x mb-3"></i>
                    <h3 class="card-title">Analise Resultados com IA</h3>
                    <p class="card-text">
                        Visualize e exporte dados coletados para obter insights e tomar decisões informadas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Planos de Preços</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header bg-white text-center border-bottom-0 pt-4">
                        <h3 class="card-title">Plano Gratuito</h3>
                        <div class="h1 card-text mb-0">R$0</div>
                        <div class="text-muted mb-3">por mês</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Até 3 inspeções por empresa</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Limitado a 3 respostas por inspeção</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Funcionalidade básica de exportação</li>
                            <li class="list-group-item"><i class="fas fa-times text-danger me-2"></i>Não é possível excluir respostas individuais</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center border-top-0 pb-4">
                        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline-primary">Começar Agora</a>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white text-center border-bottom-0 pt-4">
                        <h3 class="card-title">Plano Pro</h3>
                        <div class="h1 card-text mb-0">R$99,90</div>
                        <div class="text-white-50 mb-3">por mês</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Inspeções ilimitadas</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Limites de respostas configuráveis</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Opções avançadas de exportação</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Excluir respostas individuais</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center border-top-0 pb-4">
                        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary">Atualizar para Pro</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php'; ?>
