<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InspectAI - Inspeções com IA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?= BASE_URL ?>">
                    <?php if (isLoggedIn() && getActiveCompanyId()) {
                        $activeCompany = getCompanyById(getActiveCompanyId());
                        if (!empty($activeCompany['logo_path'])) {
                    ?>
                        <img src="<?= htmlspecialchars($activeCompany['logo_path']) ?>" 
                             alt="Logo" class="me-2" 
                             style="max-height: 30px; max-width: 80px;">
                    <?php 
                        } else {
                    ?>
                        <i class="fas fa-clipboard-check me-2"></i>
                    <?php 
                        }
                    } else {
                    ?>
                        <i class="fas fa-clipboard-check me-2"></i>
                    <?php } ?>
                    InspectAI
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php if (isLoggedIn()) { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/dashboard/index.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Painel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/companies/index.php">
                                    <i class="fas fa-building me-1"></i>Empresas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/inspections/index.php">
                                    <i class="fas fa-clipboard-list me-1"></i>Inspeções
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/achievements/index.php">
                                    <i class="fas fa-trophy me-1"></i>Conquistas
                                    <?php 
                                    // Verificar se há conquistas não visualizadas
                                    if (isset($_SESSION['new_achievements']) && !empty($_SESSION['new_achievements'])) {
                                        echo '<span class="badge bg-danger">New</span>';
                                    }
                                    ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if (isLoggedIn()) { ?>
                            <?php if (getActiveCompanyId()) { 
                                $activeCompany = getCompanyById(getActiveCompanyId());
                            ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="companyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?php if (!empty($activeCompany['logo_path'])) { ?>
                                            <img src="<?= htmlspecialchars($activeCompany['logo_path']) ?>" 
                                                 alt="Logo" class="me-1" 
                                                 style="max-height: 20px; max-width: 40px;">
                                        <?php } else { ?>
                                            <i class="fas fa-building me-1"></i>
                                        <?php } ?>
                                        <?= htmlspecialchars($activeCompany['name']) ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="companyDropdown">
                                        <?php 
                                        $companies = getUserCompanies(getCurrentUserId());
                                        foreach ($companies as $company) { 
                                        ?>
                                            <li>
                                                <a class="dropdown-item <?= $company['id'] === getActiveCompanyId() ? 'active' : '' ?>" 
                                                   href="<?= BASE_URL ?>/companies/index.php?set_active=<?= $company['id'] ?>">
                                                    <?= htmlspecialchars($company['name']) ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="<?= BASE_URL ?>/companies/create.php">
                                                <i class="fas fa-plus me-1"></i>Nova Empresa
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php } ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_email']) ?>
                                    <span class="badge bg-<?= isPro() ? 'warning' : 'secondary' ?> ms-1">
                                        <?= ucfirst(getCurrentUserPlan()) ?>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <?php if ($_SESSION['user_email'] === 'admin@example.com'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                                            <i class="fas fa-cogs me-1"></i>Painel Administrativo
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php endif; ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">
                                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php } else { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i>Entrar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/auth/register.php">
                                    <i class="fas fa-user-plus me-1"></i>Cadastrar
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container my-4">
        <!-- Toast container for notifications -->
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i id="toast-icon" class="fas me-2"></i>
                    <strong id="toast-title" class="me-auto"></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div id="toast-body" class="toast-body"></div>
            </div>
        </div>
        
        <?php 
        displayErrors();
        displaySuccessMessages(); 
        ?>