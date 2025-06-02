<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

/**
 * Registra uma atividade administrativa
 * 
 * @param string $type Tipo de atividade (login, user_update, inspection_publish, etc)
 * @param string $description Descrição da atividade
 * @param string $adminId ID do admin ou null para usar o ID da sessão atual
 * @return bool
 */
function logAdminActivity($type, $description, $adminId = null) {
    if (!$adminId && !isset($_SESSION['user_id'])) {
        return false;
    }
    
    $adminId = $adminId ?? $_SESSION['user_id'];
    $database = new Database();
    $db = $database->getConnection();
    
    $id = generateUuid();
    
    $query = "INSERT INTO user_activity_logs (id, user_id, type, description) 
              VALUES (:id, :user_id, :type, :description)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':user_id', $adminId);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':description', $description);
    
    return $stmt->execute();
}

/**
 * Verifica se o usuário atual é administrador
 * 
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'admin@example.com';
}

/**
 * Requer que o usuário seja administrador para acessar a página
 * Redireciona para o dashboard se não for
 */
function requireAdmin() {
    if (!isAdmin()) {
        addError("Você não tem permissão para acessar esta página");
        redirect(url: "/dashboard/index.php");
        exit;
    }
}
?>