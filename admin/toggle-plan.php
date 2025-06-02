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
if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['plan']) && in_array($_GET['plan'], ['free', 'pro'])) {
    $userId = sanitizeInput($_GET['id']);
    $plan = $_GET['plan'];
    
    // Obter o usuário para verificar se existe
    $user = getUserById($userId);
    
    if (!$user) {
        addError("Usuário não encontrado");
        redirect(url: "/inspectia/admin/index.php");
        exit;
    }
    
    // Atualizar o plano do usuário
    if (updateUserPlan($userId, $plan)) {
        addSuccessMessage("Plano do usuário alterado para " . ucfirst($plan) . " com sucesso");
    } else {
        addError("Erro ao atualizar o plano do usuário");
    }
} else {
    addError("Parâmetros inválidos");
}

// Redirecionar de volta para a página de admin
redirect(url: "/inspectia/admin/index.php");
?>