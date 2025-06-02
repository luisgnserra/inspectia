<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Verificar se o usuário está autenticado
requireLogin();

// Verificar se o usuário tem uma empresa ativa
requireActiveCompany();

// Obter ID da inspeção da URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId)) {
    addError("ID da inspeção é obrigatório.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Obter dados da inspeção
$inspection = getInspectionById($inspectionId);

// Verificar se a inspeção existe e pertence a esta empresa
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspeção não encontrada ou você não tem permissão para despublicar.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Verificar se a inspeção já está em status de rascunho
if ($inspection['status'] === 'draft') {
    addError("Esta inspeção já está em status de rascunho.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Solicitar confirmação se ainda não confirmado
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Despublicar a inspeção
    if (unpublishInspection($inspectionId)) {
        addSuccessMessage("Inspeção despublicada com sucesso. Agora está em status de rascunho e não aceita mais respostas.");
    } else {
        addError("Falha ao despublicar inspeção. Por favor, tente novamente.");
    }
    
    redirect(url: "/inspectia/inspections/index.php");
} else {
    // Mostrar página de confirmação
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Confirmar Despublicação</h5>
            </div>
            <div class="card-body">
                <p>Você tem certeza que deseja despublicar esta inspeção:</p>
                <p class="lead fw-bold"><?= htmlspecialchars($inspection['title']) ?></p>
                <p>Isso irá retornar a inspeção para o status de rascunho e não aceitará mais respostas. As <?= $inspection['response_count'] ?> respostas existentes serão preservadas.</p>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <a href="<?= BASE_URL ?>/inspections/unpublish.php?id=<?= $inspectionId ?>&confirm=yes" class="btn btn-warning">
                        Despublicar Inspeção
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
    include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php';
    exit; // Interromper a execução após mostrar a confirmação
}
?>
