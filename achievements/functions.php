<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

/**
 * Retorna todas as badges disponíveis no sistema
 */
function getAllBadges() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM achievement_badges ORDER BY level ASC, name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retorna uma badge específica pelo ID
 */
function getBadgeById($badgeId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM achievement_badges WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $badgeId);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Retorna todas as badges conquistadas por um usuário
 */
function getUserBadges($userId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT ab.*, ua.achieved_at
              FROM achievement_badges ab
              JOIN user_achievements ua ON ab.id = ua.badge_id
              WHERE ua.user_id = :user_id
              ORDER BY ua.achieved_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Verifica se o usuário já tem uma badge específica
 */
function userHasBadge($userId, $badgeId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM user_achievements 
              WHERE user_id = :user_id AND badge_id = :badge_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':badge_id', $badgeId);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Concede uma badge a um usuário
 */
function awardBadgeToUser($userId, $badgeId) {
    if (userHasBadge($userId, $badgeId)) {
        error_log("Usuário $userId já possui a badge $badgeId");
        return false; // Usuário já tem essa badge
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $id = generateUuid();
    $now = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO user_achievements (id, user_id, badge_id, achieved_at)
              VALUES (:id, :user_id, :badge_id, :achieved_at)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':badge_id', $badgeId);
    $stmt->bindParam(':achieved_at', $now);
    
    $result = $stmt->execute();
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("Erro ao conceder badge: " . json_encode($errorInfo));
    }
    
    return $result;
}

/**
 * Cria uma nova badge no sistema
 */
function createBadge($name, $description, $imagePath, $criteria, $criteriaValue, $level = 1) {
    $database = new Database();
    $db = $database->getConnection();
    
    $id = generateUuid();
    
    $query = "INSERT INTO achievement_badges (id, name, description, image_path, criteria, criteria_value, level)
              VALUES (:id, :name, :description, :image_path, :criteria, :criteria_value, :level)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':image_path', $imagePath);
    $stmt->bindParam(':criteria', $criteria);
    $stmt->bindParam(':criteria_value', $criteriaValue, PDO::PARAM_INT);
    $stmt->bindParam(':level', $level, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        return $id;
    }
    
    return false;
}

/**
 * Remove uma badge do sistema
 */
function deleteBadge($badgeId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM achievement_badges WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $badgeId);
    
    return $stmt->execute();
}

/**
 * Verifica critérios e concede badges quando apropriado
 */
function checkAndAwardBadges($userId) {
    error_log("Verificando badges para o usuário: $userId");
    $badges = getAllBadges();
    $achievements = [];
    
    error_log("Total de badges disponíveis: " . count($badges));
    
    foreach ($badges as $badge) {
        error_log("Verificando badge: {$badge['name']} (ID: {$badge['id']})");
        
        if (userHasBadge($userId, $badge['id'])) {
            error_log("Usuário já tem a badge {$badge['name']}");
            continue; // Usuário já tem essa badge
        }
        
        $shouldAward = false;
        
        // Verificar cada tipo de critério
        switch ($badge['criteria']) {
            case 'forms_created':
                $count = countUserInspections($userId);
                $shouldAward = $count >= $badge['criteria_value'];
                error_log("Critério forms_created: $count/{$badge['criteria_value']} - Conceder: " . ($shouldAward ? 'Sim' : 'Não'));
                break;
                
            case 'responses_collected':
                $count = countUserResponses($userId);
                $shouldAward = $count >= $badge['criteria_value'];
                error_log("Critério responses_collected: $count/{$badge['criteria_value']} - Conceder: " . ($shouldAward ? 'Sim' : 'Não'));
                break;
                
            case 'companies_created':
                $count = countUserCompanies($userId);
                $shouldAward = $count >= $badge['criteria_value'];
                error_log("Critério companies_created: $count/{$badge['criteria_value']} - Conceder: " . ($shouldAward ? 'Sim' : 'Não'));
                break;
                
            case 'photo_questions':
                $count = countUserPhotoQuestions($userId);
                $shouldAward = $count >= $badge['criteria_value'];
                error_log("Critério photo_questions: $count/{$badge['criteria_value']} - Conceder: " . ($shouldAward ? 'Sim' : 'Não'));
                break;
                
            case 'days_active':
                $count = getUserDaysActive($userId);
                $shouldAward = $count >= $badge['criteria_value'];
                error_log("Critério days_active: $count/{$badge['criteria_value']} - Conceder: " . ($shouldAward ? 'Sim' : 'Não'));
                break;
        }
        
        if ($shouldAward) {
            error_log("Concedendo badge {$badge['name']} ao usuário $userId");
            if (awardBadgeToUser($userId, $badge['id'])) {
                error_log("Badge {$badge['name']} concedida com sucesso!");
                $achievements[] = $badge;
            } else {
                error_log("FALHA ao conceder badge {$badge['name']}");
            }
        }
    }
    
    error_log("Total de novas badges concedidas: " . count($achievements));
    
    return $achievements;
}

/**
 * Conta o número de inspeções criadas pelo usuário
 */
function countUserInspections($userId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM inspections i
              JOIN companies c ON i.company_id = c.id
              WHERE c.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    return (int)$stmt->fetchColumn();
}

/**
 * Conta o número de respostas coletadas nas inspeções do usuário
 */
function countUserResponses($userId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT SUM(response_count) FROM inspections i
              JOIN companies c ON i.company_id = c.id
              WHERE c.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    return (int)$stmt->fetchColumn();
}

/**
 * Conta o número de empresas criadas pelo usuário
 */
function countUserCompanies($userId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM companies WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $count = (int)$stmt->fetchColumn();
    
    // Log para debug
    error_log("Usuário $userId tem $count empresas");
    
    return $count;
}

/**
 * Conta o número de questões de foto criadas pelo usuário
 */
function countUserPhotoQuestions($userId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM questions q
              JOIN inspections i ON q.inspection_id = i.id
              JOIN companies c ON i.company_id = c.id
              WHERE c.user_id = :user_id AND q.type = 'photo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    return (int)$stmt->fetchColumn();
}

/**
 * Calcula há quantos dias o usuário está ativo no sistema
 */
function getUserDaysActive($userId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT CURRENT_DATE - MIN(created_at::date) as days FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    return (int)$stmt->fetchColumn();
}