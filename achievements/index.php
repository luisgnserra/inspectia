<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/achievements/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Obter o ID do usuário atual
$userId = getCurrentUserId();

// Verificar e conceder novas badges
$newBadges = checkAndAwardBadges($userId);

// Obter todas as badges do usuário
$userBadges = getUserBadges($userId);

// Obter todas as badges disponíveis
$allBadges = getAllBadges();

// Separar as badges em conquistadas e pendentes
$earnedBadges = [];
$pendingBadges = [];

foreach ($allBadges as $badge) {
    $earned = false;
    
    foreach ($userBadges as $userBadge) {
        if ($userBadge['id'] === $badge['id']) {
            $earned = true;
            $earnedBadges[] = $userBadge;
            break;
        }
    }
    
    if (!$earned) {
        $pendingBadges[] = $badge;
    }
}

// Estatísticas do usuário para mostrar o progresso
$stats = [
    'forms_created' => countUserInspections($userId),
    'responses_collected' => countUserResponses($userId),
    'companies_created' => countUserCompanies($userId),
    'photo_questions' => countUserPhotoQuestions($userId),
    'days_active' => getUserDaysActive($userId)
];

// Strings de exibição para os critérios
$criteriaLabels = [
    'forms_created' => 'Formulários criados',
    'responses_collected' => 'Respostas coletadas',
    'companies_created' => 'Empresas criadas',
    'photo_questions' => 'Questões de foto criadas',
    'days_active' => 'Dias ativo no sistema'
];

// Incluir template de cabeçalho
include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-trophy text-warning me-2"></i>Conquistas e Badges</h1>
        <?php if ($_SESSION['user_email'] === 'admin@example.com'): ?>
            <a href="<?= BASE_URL ?>/achievements/admin.php" class="btn btn-outline-primary">
                <i class="fas fa-cog me-1"></i>Gerenciar Badges
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($newBadges)): ?>
        <div class="alert alert-success">
            <h4 class="alert-heading">
                <i class="fas fa-award me-2"></i>Parabéns!
            </h4>
            <p>Você conquistou <?= count($newBadges) ?> nova(s) badge(s):</p>
            <ul>
                <?php foreach ($newBadges as $badge): ?>
                    <li><strong><?= htmlspecialchars($badge['name']) ?></strong> - <?= htmlspecialchars($badge['description']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Seu Progresso</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6><?= htmlspecialchars($criteriaLabels['forms_created']) ?></h6>
                        <div class="progress">
                            <?php 
                            $maxFormsRequired = 20; // Valor máximo para o badge de nível 3
                            $formsProgress = min(100, ($stats['forms_created'] / $maxFormsRequired) * 100);
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $formsProgress ?>%">
                                <?= $stats['forms_created'] ?>/<?= $maxFormsRequired ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6><?= htmlspecialchars($criteriaLabels['responses_collected']) ?></h6>
                        <div class="progress">
                            <?php 
                            $maxResponsesRequired = 200; // Valor máximo para o badge de nível 3
                            $responsesProgress = min(100, ($stats['responses_collected'] / $maxResponsesRequired) * 100);
                            ?>
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= $responsesProgress ?>%">
                                <?= $stats['responses_collected'] ?>/<?= $maxResponsesRequired ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6><?= htmlspecialchars($criteriaLabels['companies_created']) ?></h6>
                        <div class="progress">
                            <?php 
                            $maxCompaniesRequired = 3; // Valor máximo para o badge de nível 2
                            $companiesProgress = min(100, ($stats['companies_created'] / $maxCompaniesRequired) * 100);
                            ?>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $companiesProgress ?>%">
                                <?= $stats['companies_created'] ?>/<?= $maxCompaniesRequired ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6><?= htmlspecialchars($criteriaLabels['photo_questions']) ?></h6>
                        <div class="progress">
                            <?php 
                            $maxPhotoRequired = 10; // Valor máximo para o badge de nível 2
                            $photoProgress = min(100, ($stats['photo_questions'] / $maxPhotoRequired) * 100);
                            ?>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $photoProgress ?>%">
                                <?= $stats['photo_questions'] ?>/<?= $maxPhotoRequired ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6><?= htmlspecialchars($criteriaLabels['days_active']) ?></h6>
                        <div class="progress">
                            <?php 
                            $maxDaysRequired = 90; // Valor máximo para o badge de nível 3
                            $daysProgress = min(100, ($stats['days_active'] / $maxDaysRequired) * 100);
                            ?>
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $daysProgress ?>%">
                                <?= $stats['days_active'] ?>/<?= $maxDaysRequired ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Badges conquistadas -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-unlock me-2"></i>Badges Conquistadas (<?= count($earnedBadges) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($earnedBadges)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Você ainda não conquistou nenhuma badge. Continue usando o sistema para desbloquear conquistas!
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($earnedBadges as $badge): ?>
                                <div class="col">
                                    <div class="card h-100 border-success">
                                        <div class="text-center p-2">
                                            <img src="<?= htmlspecialchars($badge['image_path']) ?>" alt="<?= htmlspecialchars($badge['name']) ?>" class="img-fluid" style="height: 120px;">
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?= htmlspecialchars($badge['name']) ?>
                                                <?php for ($i = 0; $i < $badge['level']; $i++): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php endfor; ?>
                                            </h5>
                                            <p class="card-text"><?= htmlspecialchars($badge['description']) ?></p>
                                            <p class="card-text text-muted small">
                                                <i class="fas fa-calendar-check me-1"></i>
                                                Conquistada em: <?= date('d/m/Y', strtotime($badge['achieved_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Badges pendentes -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Badges Pendentes (<?= count($pendingBadges) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingBadges)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>Parabéns! Você conquistou todas as badges disponíveis.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($pendingBadges as $badge): ?>
                                <div class="col">
                                    <div class="card h-100 border-secondary">
                                        <div class="text-center p-2 opacity-50">
                                            <img src="<?= htmlspecialchars($badge['image_path']) ?>" alt="<?= htmlspecialchars($badge['name']) ?>" class="img-fluid" style="height: 120px; filter: grayscale(100%);">
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title text-muted">
                                                <?= htmlspecialchars($badge['name']) ?>
                                                <?php for ($i = 0; $i < $badge['level']; $i++): ?>
                                                    <i class="fas fa-star text-secondary"></i>
                                                <?php endfor; ?>
                                            </h5>
                                            <p class="card-text text-muted"><?= htmlspecialchars($badge['description']) ?></p>
                                            
                                            <?php
                                            // Calcular o progresso atual para esta badge
                                            $currentValue = $stats[$badge['criteria']] ?? 0;
                                            $targetValue = $badge['criteria_value'];
                                            $progress = min(100, ($currentValue / $targetValue) * 100);
                                            ?>
                                            
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($criteriaLabels[$badge['criteria']]) ?>: 
                                                    <?= $currentValue ?>/<?= $targetValue ?>
                                                </small>
                                                <div class="progress mt-1">
                                                    <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
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