<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/achievements/functions.php';

// Verifica novas conquistas e armazena na sessão
function checkForNewAchievements() {
    if (!isLoggedIn()) {
        return [];
    }
    
    $userId = getCurrentUserId();
    $newAchievements = checkAndAwardBadges($userId);
    
    if (!empty($newAchievements)) {
        // Armazenar na sessão para exibir notificação
        $_SESSION['new_achievements'] = $newAchievements;
    }
    
    return $newAchievements;
}

// Exibe um modal com as novas conquistas
function displayAchievementNotifications() {
    if (!isset($_SESSION['new_achievements']) || empty($_SESSION['new_achievements'])) {
        return;
    }
    
    $achievements = $_SESSION['new_achievements'];
    
    // Código do modal para notificações de conquistas
    echo '<div class="modal fade" id="achievementModal" tabindex="-1" aria-labelledby="achievementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="achievementModalLabel">
                        <i class="fas fa-trophy me-2"></i>Nova Conquista!
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body text-center">';
    
    foreach ($achievements as $achievement) {
        echo '<div class="my-3 py-3 border-bottom">
            <img src="' . htmlspecialchars($achievement['image_path']) . '" alt="Badge" class="img-fluid mb-3" style="max-height: 150px;">
            <h4>' . htmlspecialchars($achievement['name']) . '</h4>
            <p class="text-muted">' . htmlspecialchars($achievement['description']) . '</p>
        </div>';
    }
    
    echo '</div>
            <div class="modal-footer">
                <a href="' . BASE_URL . '/achievements/index.php" class="btn btn-primary">
                    <i class="fas fa-trophy me-1"></i>Ver todas as Conquistas
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var achievementModal = new bootstrap.Modal(document.getElementById("achievementModal"));
            achievementModal.show();
        });
    </script>';
    
    // Limpar as conquistas da sessão para não mostrar novamente
    unset($_SESSION['new_achievements']);
}
?>