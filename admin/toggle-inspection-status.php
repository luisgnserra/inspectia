<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/admin/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é admin
requireAdmin();

// Verificar parâmetros
if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['action']) && in_array($_GET['action'], ['publish', 'unpublish'])) {
    $inspectionId = sanitizeInput($_GET['id']);
    $action = $_GET['action'];
    
    // Obter a inspeção para verificar se existe
    $inspection = getInspectionById($inspectionId);
    
    if (!$inspection) {
        addError("Inspeção não encontrada");
        redirect(url: "/inspectia/admin/index.php");
        exit;
    }
    
    // Publicar ou despublicar a inspeção
    if ($action === 'publish') {
        if (publishInspection($inspectionId)) {
            addSuccessMessage("Inspeção publicada com sucesso");
        } else {
            addError("Erro ao publicar a inspeção");
        }
    } else {
        if (unpublishInspection($inspectionId)) {
            addSuccessMessage("Inspeção despublicada com sucesso");
        } else {
            addError("Erro ao despublicar a inspeção");
        }
    }
} else {
    addError("Parâmetros inválidos");
}

// Redirecionar de volta para a página de admin
redirect(url: "/inspectia/admin/index.php#inspections-content");
?>