<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/achievements/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/admin/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é admin
requireAdmin();

// Configurações para color picker e ícones
$badgeColors = [
    'primary' => '#0d6efd',
    'secondary' => '#6c757d',
    'success' => '#198754',
    'danger' => '#dc3545',
    'warning' => '#ffc107',
    'info' => '#0dcaf0',
    'dark' => '#212529',
    'purple' => '#6f42c1',
    'pink' => '#d63384',
    'teal' => '#20c997',
    'indigo' => '#6610f2',
    'orange' => '#fd7e14',
    'cyan' => '#0dcaf0'
];

// Lista de ícones FontAwesome comuns
$fontAwesomeIcons = [
    'fa-trophy', 'fa-medal', 'fa-award', 'fa-certificate', 'fa-star',
    'fa-check-circle', 'fa-thumbs-up', 'fa-graduation-cap', 'fa-crown',
    'fa-bolt', 'fa-rocket', 'fa-heart', 'fa-diamond', 'fa-gem',
    'fa-fire', 'fa-flag', 'fa-paper-plane', 'fa-lightbulb', 'fa-gift',
    'fa-building', 'fa-clipboard', 'fa-image', 'fa-calendar', 'fa-user',
    'fa-users', 'fa-leaf', 'fa-seedling', 'fa-tree', 'fa-mountain'
];

// Critérios disponíveis
$criteria = [
    'forms_created' => 'Formulários criados',
    'responses_collected' => 'Respostas coletadas',
    'companies_created' => 'Empresas criadas',
    'photo_questions' => 'Questões de foto',
    'days_active' => 'Dias ativo'
];

// Processar criação de badge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $criteria = sanitizeInput($_POST['criteria'] ?? '');
    $criteriaValue = (int)($_POST['criteria_value'] ?? 0);
    $level = (int)($_POST['level'] ?? 1);
    $backgroundColor = sanitizeInput($_POST['background_color'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? '');
    
    if (empty($name) || empty($description) || empty($criteria) || $criteriaValue <= 0) {
        addError("Todos os campos são obrigatórios e o valor do critério deve ser maior que zero.");
    } else {
        // Criar a badge SVG dinamicamente
        $svgContent = createBadgeSvg($backgroundColor, $icon);
        
        // Salvar o arquivo SVG
        $fileName = 'badge_' . strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.svg';
        $filePath = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/img/badges/' . $fileName;
        file_put_contents($filePath, $svgContent);
        
        // Adicionar badge ao banco de dados
        $badgeId = createBadge(
            $name,
            $description,
            '/assets/img/badges/' . $fileName,
            $criteria,
            $criteriaValue,
            $level
        );
        
        if ($badgeId) {
            addSuccessMessage("Badge criada com sucesso!");
            redirect('/admin/badges.php');
            exit;
        } else {
            addError("Erro ao criar a badge.");
        }
    }
}

// Processar edição de badge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $badgeId = sanitizeInput($_POST['badge_id'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $criteria = sanitizeInput($_POST['criteria'] ?? '');
    $criteriaValue = (int)($_POST['criteria_value'] ?? 0);
    $level = (int)($_POST['level'] ?? 1);
    $backgroundColor = sanitizeInput($_POST['background_color'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? '');
    
    if (empty($badgeId) || empty($name) || empty($description) || empty($criteria) || $criteriaValue <= 0) {
        addError("Todos os campos são obrigatórios e o valor do critério deve ser maior que zero.");
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get existing badge data
        $query = "SELECT * FROM achievement_badges WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $badgeId);
        $stmt->execute();
        $badge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($badge) {
            $updateImagePath = false;
            $imagePath = $badge['image_path'];
            
            // If background color or icon has changed, create a new SVG
            if (isset($_POST['update_image']) && $_POST['update_image'] === '1') {
                // Create new SVG
                $svgContent = createBadgeSvg($backgroundColor, $icon);
                
                // Save the SVG file
                $fileName = 'badge_' . strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.svg';
                $filePath = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/img/badges/' . $fileName;
                file_put_contents($filePath, $svgContent);
                
                $imagePath = '/assets/img/badges/' . $fileName;
                $updateImagePath = true;
            }
            
            // Update badge in database
            $query = "UPDATE achievement_badges 
                      SET name = :name, 
                          description = :description, 
                          criteria = :criteria, 
                          criteria_value = :criteria_value, 
                          level = :level" . 
                      ($updateImagePath ? ", image_path = :image_path" : "") . 
                      " WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':criteria', $criteria);
            $stmt->bindParam(':criteria_value', $criteriaValue, PDO::PARAM_INT);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->bindParam(':id', $badgeId);
            
            if ($updateImagePath) {
                $stmt->bindParam(':image_path', $imagePath);
            }
            
            if ($stmt->execute()) {
                addSuccessMessage("Badge atualizada com sucesso!");
                redirect('/admin/badges.php');
                exit;
            } else {
                addError("Erro ao atualizar a badge.");
            }
        } else {
            addError("Badge não encontrada.");
        }
    }
}

// Processar exclusão de badge
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $badgeId = sanitizeInput($_GET['delete']);
    
    // Get badge info to delete the image
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT image_path FROM achievement_badges WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $badgeId);
    $stmt->execute();
    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($badge && deleteBadge($badgeId)) {
        // Delete the image file if it exists
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $badge['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        addSuccessMessage("Badge excluída com sucesso!");
    } else {
        addError("Erro ao excluir a badge.");
    }
    
    redirect('/admin/badges.php');
    exit;
}

// Obter todas as badges
$badges = getAllBadges();

/**
 * Retorna o path SVG para o ícone FontAwesome especificado
 * @param string $icon Nome do ícone (ex: fa-trophy)
 * @return string Path SVG para o ícone
 */
function getIconSvgPath($icon) {
    // Mapeamento dos ícones para seus SVG paths
    $iconMap = [
        'fa-trophy' => 'M552 64H448V24c0-13.3-10.7-24-24-24H152c-13.3 0-24 10.7-24 24v40H24C10.7 64 0 74.7 0 88v56c0 35.7 22.5 72.4 61.9 100.7 31.5 22.7 69.8 37.1 110 41.7C203.3 338.5 240 360 240 360v72h-48c-35.3 0-64 20.7-64 56v12c0 6.6 5.4 12 12 12h296c6.6 0 12-5.4 12-12v-12c0-35.3-28.7-56-64-56h-48v-72s36.7-21.5 68.1-73.6c40.3-4.6 78.6-19 110-41.7 39.3-28.3 61.9-65 61.9-100.7V88c0-13.3-10.7-24-24-24zM99.3 192.8C74.9 175.2 64 155.6 64 144v-16h64.2c1 32.6 5.8 61.2 12.8 86.2-15.1-5.2-29.2-12.4-41.7-21.4zM512 144c0 16.1-17.7 36.1-35.3 48.8-12.5 9-26.7 16.2-41.8 21.4 7-25 11.8-53.6 12.8-86.2H512v16z',
        'fa-medal' => 'M223.75 130.75L154.62 15.54A31.997 31.997 0 0 0 127.18 0H16.03C3.08 0-4.5 14.57 2.92 25.18l111.27 158.96c29.72-27.77 67.52-46.83 109.56-53.39zM495.97 0H384.82c-11.24 0-21.66 5.9-27.44 15.54l-69.13 115.21c42.04 6.56 79.84 25.62 109.56 53.38L509.08 25.18C516.5 14.57 508.92 0 495.97 0zM256 160c-97.2 0-176 78.8-176 176s78.8 176 176 176 176-78.8 176-176-78.8-176-176-176zm92.52 157.26l-37.93 36.96 8.97 52.22c1.6 9.36-8.26 16.51-16.65 12.09L256 393.88l-46.9 24.65c-8.4 4.45-18.25-2.74-16.65-12.09l8.97-52.22-37.93-36.96c-6.82-6.64-3.05-18.23 6.35-19.59l52.43-7.64 23.43-47.52c2.11-4.28 6.19-6.39 10.28-6.39 4.11 0 8.22 2.14 10.33 6.39l23.43 47.52 52.43 7.64c9.4 1.36 13.17 12.95 6.35 19.59z',
        'fa-award' => 'M97.12 362.63c-8.69-8.69-4.16-6.24-25.12-11.85-9.51-2.55-17.87-7.45-25.43-13.32L1.2 448.7c-4.39 10.77 3.81 22.47 15.43 22.03l52.69-2.01L105.56 507c8 8.89 22.04 7.61 28.84-2.41l40.04-59.17c-1.89-.13-3.76-.31-5.59-.54-11.1-1.36-23.18-5.54-36.29-14.54-12.22-8.36-26.89-24.27-35.44-37.71z M497.96 326.09c-10.28-3.07-22.39-5.45-38.29-5.45-57.86 0-100.66 31.89-100.66 80.13 0 48.23 42.81 80.13 100.66 80.13 13.91 0 26.02-1.94 36.29-5.45 10.5-3.64 20.11-9.43 27.3-18.2 6.83-8.25 10.27-18.86 10.27-30.87.01-11.71-3.45-22.27-10.26-30.5-7.25-8.76-16.83-14.54-27.31-18.16z M310.66 320.64c-12.05-3.61-23.8-4.55-34.27-4.55-57.86 0-100.66 33.74-100.66 82 0 48.25 42.81 82 100.66 82 14.44 0 26.15-2.34 35.26-4.87 28.6-7.93 50.86-32.06 50.86-77.12-.01-46.15-22.97-69.96-51.85-77.46z M106.52 320.64c-28.87 7.5-51.85 31.31-51.85 77.46 0 45.06 22.26 69.19 50.86 77.12 9.1 2.53 20.82 4.87 35.26 4.87 57.86 0 100.66-33.75 100.66-82 0-48.26-42.81-82-100.66-82-10.48 0-22.22.94-34.27 4.55z',
        'fa-certificate' => 'M458.622 255.92l45.985-45.005c13.708-12.977 7.316-36.039-10.664-40.339l-62.65-15.99 17.661-62.015c4.991-17.838-11.829-34.663-29.661-29.671l-61.994 17.667-15.984-62.671C337.085.197 313.765-6.276 300.99 7.228L256 53.57 211.011 7.229c-12.63-13.351-36.047-7.234-40.325 10.668l-15.984 62.671-61.995-17.667C74.87 57.907 58.056 74.738 63.046 92.572l17.661 62.015-62.65 15.99C.069 174.878-6.31 197.944 7.392 210.915l45.985 45.005-45.985 45.004c-13.708 12.977-7.316 36.039 10.664 40.339l62.65 15.99-17.661 62.015c-4.991 17.838 11.829 34.663 29.661 29.671l61.994-17.667 15.984 62.671c4.439 18.575 27.696 24.018 40.325 10.668L256 458.61l44.989 46.001c12.5 13.488 35.987 7.486 40.325-10.668l15.984-62.671 61.994 17.667c17.836 4.994 34.651-11.837 29.661-29.671l-17.661-62.015 62.65-15.99c17.987-4.302 24.366-27.367 10.664-40.339l-45.984-45.004z',
        'fa-star' => 'M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z',
        'fa-check-circle' => 'M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z',
        'fa-thumbs-up' => 'M104 224H24c-13.255 0-24 10.745-24 24v240c0 13.255 10.745 24 24 24h80c13.255 0 24-10.745 24-24V248c0-13.255-10.745-24-24-24zM64 472c-13.255 0-24-10.745-24-24s10.745-24 24-24 24 10.745 24 24-10.745 24-24 24zM384 81.452c0 42.416-25.97 66.208-33.277 94.548h101.723c33.397 0 59.397 27.746 59.553 58.098.084 17.938-7.546 37.249-19.439 49.197l-.11.11c9.836 23.337 8.237 56.037-9.308 79.469 8.681 25.895-.069 57.704-16.382 74.757 4.298 17.598 2.244 32.575-6.148 44.632C440.202 511.587 389.616 512 346.839 512l-2.845-.001c-48.287-.017-87.806-17.598-119.56-31.725-15.957-7.099-36.821-15.887-52.651-16.178-6.54-.12-11.783-5.457-11.783-11.998v-213.77c0-3.2 1.282-6.271 3.558-8.521 39.614-39.144 56.648-80.587 89.117-113.111 14.804-14.832 20.188-37.236 25.393-58.902C282.515 39.293 291.817 0 312 0c24 0 72 8 72 81.452z',
        'fa-graduation-cap' => 'M622.34 153.2L343.4 67.5c-15.2-4.67-31.6-4.67-46.79 0L17.66 153.2c-23.54 7.23-23.54 38.36 0 45.59l48.63 14.94c-10.67 13.19-17.23 29.28-17.88 46.9C38.78 266.15 32 276.11 32 288c0 10.78 5.68 19.85 13.86 25.65L20.33 428.53C18.11 438.52 25.71 448 35.94 448h56.11c10.24 0 17.84-9.48 15.62-19.47L82.14 313.65C90.32 307.85 96 298.78 96 288c0-11.57-6.47-21.25-15.66-26.87.76-15.02 8.44-28.3 20.69-36.72L296.6 284.5c9.06 2.78 26.44 6.25 46.79 0l278.95-85.7c23.55-7.24 23.55-38.36 0-45.6zM352.79 315.09c-28.53 8.76-52.84 3.92-65.59 0l-145.02-44.55L128 384c0 35.35 85.96 64 192 64s192-28.65 192-64l-14.18-113.47-145.03 44.56z',
        'fa-crown' => 'M528 448H112c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h416c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm64-320c-26.5 0-48 21.5-48 48 0 7.1 1.6 13.7 4.4 19.8L476 239.2c-15.4 9.2-35.3 4-44.2-11.6L350.3 85C361 76.2 368 63 368 48c0-26.5-21.5-48-48-48s-48 21.5-48 48c0 15 7 28.2 17.7 37l-81.5 142.6c-8.9 15.6-28.9 20.8-44.2 11.6l-72.3-43.4c2.7-6 4.4-12.7 4.4-19.8 0-26.5-21.5-48-48-48S0 149.5 0 176s21.5 48 48 48c2.6 0 5.2-.2 7.7-.6l74.9 136.2c1.4 2.6 3.1 5 4.9 7.4l-29.5 29c-5.4 5.3-5.5 14-.2 19.4l34.2 34.6c5 5.1 13.3 5.5 18.7.9l123.1-99.4 29 26.4c5.2 4.7 13.3 4.7 18.5 0l29-26.4L482.4 451c5.4 4.5 13.7 4.1 18.7-.9l34.2-34.6c5.3-5.4 5.2-14.1-.2-19.4l-29.5-29c1.9-2.4 3.5-4.8 4.9-7.4L585.3 224c2.5.4 5.1.6 7.7.6 26.5 0 48-21.5 48-48s-21.5-48-48-48z',
        'fa-bolt' => 'M296 160H180.6l42.6-129.8C227.2 15 215.7 0 200 0H56C44 0 33.8 8.9 32.2 20.8l-32 240C-1.7 275.2 9.5 288 24 288h118.7L96.6 482.5c-3.6 15.2 8 29.5 23.3 29.5 8.4 0 16.4-4.4 20.8-12l176-304c9.3-15.9-2.2-36-20.7-36z',
        'fa-rocket' => 'M505.05 19.1a15.89 15.89 0 0 0-12.2-12.2C460.65 0 435.46 0 410.36 0c-103.2 0-165.1 55.2-211.29 128H94.87A48 48 0 0 0 52 154.49l-49.42 98.8A24 24 0 0 0 24.07 288h103.77l-22.47 22.47a32 32 0 0 0 0 45.25l50.9 50.91a32 32 0 0 0 45.26 0L224 384.16V488a24 24 0 0 0 34.7 21.49l98.7-49.39a47.91 47.91 0 0 0 26.6-42.9V312.79c72.59-46.3 128-108.4 128-211.09.1-25.2.1-50.4-6.85-82.6zM384 168a40 40 0 1 1 40-40 40 40 0 0 1-40 40z',
        'fa-heart' => 'M462.3 62.6C407.5 15.9 326 24.3 275.7 76.2L256 96.5l-19.7-20.3C186.1 24.3 104.5 15.9 49.7 62.6c-62.8 53.6-66.1 149.8-9.9 207.9l193.5 199.8c12.5 12.9 32.8 12.9 45.3 0l193.5-199.8c56.3-58.1 53-154.3-9.8-207.9z',
        'fa-diamond' => 'M242.2 8.3c-9.6-11.1-26.8-11.1-36.4 0l-200 232c-7.8 9-7.8 22.3 0 31.3l200 232c9.6 11.1 26.8 11.1 36.4 0l200-232c7.8-9 7.8-22.3 0-31.3l-200-232z',
        'fa-gem' => 'M464 0H112c-4 0-7.8 2-10 5.4L2 152.6c-2.9 4.4-2.6 10.2.7 14.2l276 340.8c4.8 5.9 13.8 5.9 18.6 0l276-340.8c3.3-4.1 3.6-9.8.7-14.2L474.1 5.4C471.8 2 468.1 0 464 0zm-19.3 48l63.3 96h-68.4l-51.7-96h56.8zm-202.1 0h90.7l51.7 96H191l51.6-96zm-111.3 0h56.8l-51.7 96H68l63.3-96zm-43 144h51.4L208 352 88.3 192zm102.9 0h193.6L288 435.3 191.2 192zM368 352l68.2-160h51.4L368 352z',
        'fa-fire' => 'M216 23.86c0-23.8-30.65-32.77-44.15-13.04C48 191.85 224 200 224 288c0 35.63-29.11 64.46-64.85 63.99-35.17-.45-63.15-29.77-63.15-64.94v-85.51c0-21.7-26.47-32.23-41.43-16.5C27.8 213.16 0 261.33 0 320c0 105.87 86.13 192 192 192s192-86.13 192-192c0-170.29-168-193-168-296.14z',
        'fa-flag' => 'M349.565 98.783C295.978 98.783 251.721 64 184.348 64c-24.955 0-47.309 4.384-68.045 12.013a55.947 55.947 0 0 0 3.586-23.562C118.117 24.015 94.806 1.206 66.338.048 34.345-1.254 8 24.296 8 56c0 19.026 9.497 35.825 24 45.945V488c0 13.255 10.745 24 24 24h16c13.255 0 24-10.745 24-24v-94.4c28.311-12.064 63.582-22.122 114.435-22.122 53.588 0 97.844 34.783 165.217 34.783 48.169 0 86.667-16.294 122.505-40.858C506.84 359.452 512 349.571 512 339.045v-243.1c0-23.393-24.269-38.87-45.485-29.016-34.338 15.948-76.454 31.854-116.95 31.854z',
        'fa-paper-plane' => 'M476 3.2L12.5 270.6c-18.1 10.4-15.8 35.6 2.2 43.2L121 358.4l287.3-253.2c5.5-4.9 13.3 2.6 8.6 8.3L176 407v80.5c0 23.6 28.5 32.9 42.5 15.8L282 426l124.6 52.2c14.2 6 30.4-2.9 33-18.2l72-432C515 7.8 493.3-6.8 476 3.2z',
        'fa-lightbulb' => 'M176 80c0-8.84-7.16-16-16-16h-32c-8.84 0-16 7.16-16 16v48h64V80zm96 0c0-8.84-7.16-16-16-16h-32c-8.84 0-16 7.16-16 16v48h64V80zm96 0c0-8.84-7.16-16-16-16h-32c-8.84 0-16 7.16-16 16v48h64V80zm-304 0c0-8.84-7.16-16-16-16H16c-8.84 0-16 7.16-16 16v48h64V80zm432 0c0-8.84-7.16-16-16-16h-32c-8.84 0-16 7.16-16 16v48h64V80zm32 128H0c0 188.1 208.6 335.5 416 335.5 187.3 0 96-119.7 96-335.5zm-128-80h64v48h-64v-48zm-288 0h64v48h-64v-48zm128 0h64v48h-64v-48z',
        'fa-gift' => 'M32 448c0 17.7 14.3 32 32 32h160V320H32v128zm256 32h160c17.7 0 32-14.3 32-32V320H288v160zm192-320h-42.1c6.2-12.1 10.1-25.5 10.1-40 0-48.5-39.5-88-88-88-41.6 0-68.5 21.3-103 68.3-34.5-47-61.4-68.3-103-68.3-48.5 0-88 39.5-88 88 0 14.5 3.8 27.9 10.1 40H32c-17.7 0-32 14.3-32 32v80c0 8.8 7.2 16 16 16h480c8.8 0 16-7.2 16-16v-80c0-17.7-14.3-32-32-32zm-326.1 0c-22.1 0-40-17.9-40-40s17.9-40 40-40c19.9 0 34.6 3.3 86.1 80h-86.1zm206.1 0h-86.1c51.4-76.5 65.7-80 86.1-80 22.1 0 40 17.9 40 40s-17.9 40-40 40z',
        'fa-building' => 'M436 480h-20V24c0-13.255-10.745-24-24-24H56C42.745 0 32 10.745 32 24v456H12c-6.627 0-12 5.373-12 12v20h448v-20c0-6.627-5.373-12-12-12zM128 76c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12V76zm0 96c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-40zm52 148h-40c-6.627 0-12-5.373-12-12v-40c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12zm76 160h-64v-84c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v84zm64-172c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-40c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40zm0-96c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-40c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40zm0-96c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-40c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40z',
        'fa-clipboard' => 'M384 112v352c0 26.51-21.49 48-48 48H48c-26.51 0-48-21.49-48-48V112c0-26.51 21.49-48 48-48h80c0-35.29 28.71-64 64-64s64 28.71 64 64h80c26.51 0 48 21.49 48 48zM192 40c-13.26 0-24 10.74-24 24s10.74 24 24 24 24-10.74 24-24-10.74-24-24-24m96 114v-20a6 6 0 0 0-6-6H102a6 6 0 0 0-6 6v20a6 6 0 0 0 6 6h180a6 6 0 0 0 6-6z',
        'fa-image' => 'M464 448H48c-26.51 0-48-21.49-48-48V112c0-26.51 21.49-48 48-48h416c26.51 0 48 21.49 48 48v288c0 26.51-21.49 48-48 48zM112 120c-30.928 0-56 25.072-56 56s25.072 56 56 56 56-25.072 56-56-25.072-56-56-56zM64 384h384V272l-87.515-87.515c-4.686-4.686-12.284-4.686-16.971 0L208 320l-55.515-55.515c-4.686-4.686-12.284-4.686-16.971 0L64 336v48z',
        'fa-calendar' => 'M12 192h424c6.6 0 12 5.4 12 12v260c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V204c0-6.6 5.4-12 12-12zm436-44v-36c0-26.5-21.5-48-48-48h-48V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H160V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H48C21.5 64 0 85.5 0 112v36c0 6.6 5.4 12 12 12h436c6.6 0 12-5.4 12-12z',
        'fa-user' => 'M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z',
        'fa-users' => 'M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm32 32h-64c-17.6 0-33.5 7.1-45.1 18.6 40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64zm-256 0c61.9 0 112-50.1 112-112S381.9 32 320 32 208 82.1 208 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C179.6 288 128 339.6 128 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zm-223.7-13.4C161.5 263.1 145.6 256 128 256H64c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4z',
        'fa-leaf' => 'M546.2 9.7c-5.6-12.5-21.6-13-28.3-1.2C486.9 62.4 431.4 96 368 96h-80C182 96 96 182 96 288c0 7 .8 13.7 1.5 20.5C161.3 262.8 253.4 224 384 224c8.8 0 16 7.2 16 16s-7.2 16-16 16C132.6 256 26 410.1 2.4 468c-6.6 16.3 1.2 34.9 17.5 41.6 16.4 6.8 35-1.1 41.8-17.3 1.5-3.6 20.9-47.9 71.9-90.6 32.4 43.9 94 85.8 174.9 77.2C465.5 467.5 576 326.7 576 154.3c0-50.2-10.8-102.2-29.8-144.6z',
        'fa-seedling' => 'M64 96H0c0 123.7 100.3 224 224 224v144c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V320C288 196.3 187.7 96 64 96zm384-64c-84.2 0-157.4 46.5-195.7 115.2 27.7 30.2 48.2 66.9 59 107.6C424 243.1 512 147.9 512 32h-64z',
        'fa-tree' => 'M377.33 375.29l-54.89-109.8c-4.56-9.05-13.9-14.57-23.53-14.26l-3.32.08c-4.33.11-8.7-.63-12.78-2.32l-47.47-19.18c-21.02-8.48-37.33-25.27-45.24-46.55L166.9 100.58c-2.66-7.09-10.73-10.67-17.77-7.9l-7.99 3.14c-7.04 2.77-10.56 10.89-7.9 17.99l23.19 82.66c3.5 12.46 1.78 25.19-4.86 36.29L101.32 314.3C86.28 338.11 96.84 369.44 125 375.95l88.26 20.2c6.74 1.55 11.97 6.82 13.36 13.61l23.71 115.08c3.56 17.25 20.15 28.04 36.76 23.69l59.54-15.71c16.5-4.36 25.87-21.74 20.7-38.44l-47.13-166.19 10.31-2.84c8.55-2.34 15.23-9.29 17.1-17.87l5.74-26.27c.98-4.37 2.5-8.62 4.58-12.57l8.53-16.96c4.34-8.63 12.98-14.15 22.66-14.62l13.69-.65c9.76-.46 18.76 4.75 23.43 13.66l73 139.81c9.68 18.53 34.88 24.64 52.71 12.77l56.43-37.62c17.83-11.87 22.52-36.97 10.06-54.71z',
        'fa-mountain' => 'M575.92 76.6c-12.77-13.87-39.51-5.34-48.79 15.19-9.29 20.54-44.08 73.67-93.18 45.42l-1.7-.95c-10.1-5.67-20.72-9.71-31.72-12.05-8.73-56.9-36.67-104.22-72.32-129.35l-18.48 30.38c40.56 31.52 62.74 97.02 56.11 162.55-3.81-.82-7.67-1.55-11.58-2.15-14.6-2.24-29.46-2.78-44.31-1.64v-30.94l-7.32-1.18c-4.9-.79-9.82-1.41-14.76-1.86-18.58-1.71-37.21-1.26-55.55 1.36l-7.56 1.08v31.38c-14.8 1.21-29.31 4.39-43.13 9.4l-1.31.5c-55.11 20.84-93.78 72.11-94.72 130.31-1.66 10.19-1.52 20.65.46 31.1 3.74 19.86 12.36 38.41 24.9 54.72-12.86 11.33-22.04 26.93-24.77 44.22-1.65 10.42-1.31 21.01.98 31.24 6.43 29.7 28.18 54.77 56.38 67.42l344.82 49.19c22.24 3.92 44.83-2.99 61.4-17.91 10.69-9.66 18.1-22.13 21.6-36.41 4.66-19.04 2.89-38.27-5.08-55.63 5.47-8.35 9.77-17.41 12.8-26.95 5.15-16.23 5.6-33.52 1.31-49.95-1.7-6.36-3.99-12.46-6.88-18.28 4.04-1.89 8.36-3.13 12.87-3.55 17.95-1.67 25.23 9.97 35.71 24.86 14.73 21.01 37.73 10.58 43.47 4.12 5.74-6.46 11.15-17.26-3.38-29.97zM463.27 305.84c9.71-16.6 8.43-37.16-3.62-52.27-11.98-15.04-30.13-21.47-48.1-18.35l-261.94 62.54c-32.96 9.19-32.43 62.78 1.02 75.06 34.61 12.66 55.26 6.08 61.91 2 8.97 8.98 21.2 13.96 33.75 13.96 12.56 0 24.8-4.98 33.77-13.97 8.97 8.98 21.2 13.96 33.76 13.96 12.55 0 24.79-4.98 33.76-13.97 8.93 8.95 21.1 13.93 33.6 13.97 2.6.01 5.19-.21 7.74-.64 39.74-6.81 48.73-17.47 64.35-42.29z'
    ];
    
    return $iconMap[$icon] ?? $iconMap['fa-trophy']; // Usa troféu como ícone padrão
}

/**
 * Cria um SVG de badge com fundo e ícone personalizados
 * @param string $backgroundColor Cor de fundo no formato hexadecimal (#000000)
 * @param string $icon Nome do ícone FontAwesome (ex: fa-trophy)
 * @return string Conteúdo do SVG
 */
function createBadgeSvg($backgroundColor, $icon) {
    $iconPath = getIconSvgPath($icon);
    
    $svgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <circle cx="50" cy="50" r="45" fill="' . htmlspecialchars($backgroundColor) . '" stroke="#000" stroke-width="2"/>
  <g transform="translate(30, 30) scale(0.04, 0.04)">
    <path d="' . $iconPath . '" fill="white" />
  </g>
</svg>';
    
    return $svgContent;
}

// Incluir template de cabeçalho
include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-trophy text-warning me-2"></i>Gerenciamento de Badges</h1>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel Admin
        </a>
    </div>
    
    <!-- Formulário para criar nova badge -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Criar Nova Badge</h5>
        </div>
        <div class="card-body">
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome da Badge</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="criteria" class="form-label">Critério</label>
                                    <select class="form-select" id="criteria" name="criteria" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($criteria as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="criteria_value" class="form-label">Valor</label>
                                    <input type="number" class="form-control" id="criteria_value" name="criteria_value" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="level" class="form-label">Nível</label>
                                    <select class="form-select" id="level" name="level" required>
                                        <option value="1">1 - Bronze</option>
                                        <option value="2">2 - Prata</option>
                                        <option value="3">3 - Ouro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="background_color" class="form-label">Cor de Fundo</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="background_color" name="background_color" value="#ffc107" title="Escolha a cor de fundo">
                                <select class="form-select" id="color_preset" onchange="document.getElementById('background_color').value = this.value">
                                    <option value="">Selecione uma cor...</option>
                                    <?php foreach ($badgeColors as $name => $color): ?>
                                        <option value="<?= $color ?>"><?= ucfirst($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="icon" class="form-label">Ícone</label>
                            <select class="form-select" id="icon" name="icon" required>
                                <?php foreach ($fontAwesomeIcons as $icon): ?>
                                    <option value="<?= $icon ?>"><?= $icon ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Pré-visualização</label>
                            <div class="badge-preview p-3 text-center" id="badgePreview">
                                <div class="rounded-circle d-inline-flex justify-content-center align-items-center text-white" 
                                     style="width: 100px; height: 100px; background-color: #ffc107;">
                                    <i class="fas fa-trophy fa-3x"></i>
                                </div>
                                <div class="mt-2">
                                    <h5 id="previewTitle">Nome da Badge</h5>
                                    <p class="text-muted" id="previewDescription">Descrição da badge</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Criar Badge
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de badges existentes -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Badges Existentes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Badge</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Critério</th>
                            <th>Valor</th>
                            <th>Nível</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($badges)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Nenhuma badge encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($badges as $badge): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($badge['image_path']) ?>" alt="<?= htmlspecialchars($badge['name']) ?>" class="img-thumbnail" style="max-width: 50px;">
                                    </td>
                                    <td><?= htmlspecialchars($badge['name']) ?></td>
                                    <td><?= htmlspecialchars($badge['description']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($criteria[$badge['criteria']] ?? $badge['criteria']) ?>
                                    </td>
                                    <td><?= $badge['criteria_value'] ?></td>
                                    <td>
                                        <?php for ($i = 0; $i < $badge['level']; $i++): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary edit-badge-btn" 
                                                    data-bs-toggle="modal" data-bs-target="#editBadgeModal"
                                                    data-badge-id="<?= $badge['id'] ?>"
                                                    data-badge-name="<?= htmlspecialchars($badge['name']) ?>"
                                                    data-badge-description="<?= htmlspecialchars($badge['description']) ?>"
                                                    data-badge-criteria="<?= $badge['criteria'] ?>"
                                                    data-badge-criteria-value="<?= $badge['criteria_value'] ?>"
                                                    data-badge-level="<?= $badge['level'] ?>"
                                                    data-badge-image="<?= htmlspecialchars($badge['image_path']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/admin/badges.php?delete=<?= $badge['id'] ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Tem certeza que deseja excluir esta badge? Esta ação não pode ser desfeita.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edição -->
<div class="modal fade" id="editBadgeModal" tabindex="-1" aria-labelledby="editBadgeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editBadgeModalLabel"><i class="fas fa-edit me-2"></i>Editar Badge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="badge_id" id="edit_badge_id">
                <input type="hidden" name="update_image" id="update_image" value="0">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Nome da Badge</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Descrição</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2" required></textarea>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_criteria" class="form-label">Critério</label>
                                        <select class="form-select" id="edit_criteria" name="criteria" required>
                                            <?php foreach ($criteria as $value => $label): ?>
                                                <option value="<?= $value ?>"><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="edit_criteria_value" class="form-label">Valor</label>
                                        <input type="number" class="form-control" id="edit_criteria_value" name="criteria_value" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="edit_level" class="form-label">Nível</label>
                                        <select class="form-select" id="edit_level" name="level" required>
                                            <option value="1">1 - Bronze</option>
                                            <option value="2">2 - Prata</option>
                                            <option value="3">3 - Ouro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Imagem Atual</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="change_image" onchange="document.getElementById('update_image').value = this.checked ? '1' : '0'; document.querySelector('.edit-image-controls').style.display = this.checked ? 'block' : 'none';">
                                        <label class="form-check-label" for="change_image">Alterar Imagem</label>
                                    </div>
                                </div>
                                <div class="text-center p-3 border rounded">
                                    <img id="badge_current_image" src="" alt="Imagem da Badge" class="img-fluid" style="max-height: 100px;">
                                </div>
                            </div>
                            
                            <div class="edit-image-controls" style="display: none;">
                                <div class="mb-3">
                                    <label for="edit_background_color" class="form-label">Nova Cor de Fundo</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="edit_background_color" name="background_color" value="#ffc107">
                                        <select class="form-select" id="edit_color_preset" onchange="document.getElementById('edit_background_color').value = this.value">
                                            <option value="">Selecione uma cor...</option>
                                            <?php foreach ($badgeColors as $name => $color): ?>
                                                <option value="<?= $color ?>"><?= ucfirst($name) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_icon" class="form-label">Novo Ícone</label>
                                    <select class="form-select" id="edit_icon" name="icon">
                                        <?php foreach ($fontAwesomeIcons as $icon): ?>
                                            <option value="<?= $icon ?>"><?= $icon ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Função para criar um preview dinâmico do SVG
function updateBadgePreview() {
    // Obter valores selecionados
    const backgroundColor = document.getElementById('background_color').value;
    const iconClass = document.getElementById('icon').value;
    const name = document.getElementById('name').value || 'Nome da Badge';
    const description = document.getElementById('description').value || 'Descrição da badge';
    
    // Atualizar título e descrição
    document.getElementById('previewTitle').textContent = name;
    document.getElementById('previewDescription').textContent = description;
    
    // Atualizar visualização do ícone
    const previewBadge = document.querySelector('#badgePreview .rounded-circle');
    previewBadge.style.backgroundColor = backgroundColor;
    
    const previewIcon = document.querySelector('#badgePreview i');
    previewIcon.className = 'fas ' + iconClass + ' fa-3x';
    
    // Crie um SVG diretamente para visualização imediata
    const iconName = iconClass.replace('fa-', '');
    createDynamicBadgeSVG(backgroundColor, iconName);
}

// Adiciona um SVG dinamicamente
function createDynamicBadgeSVG(color, iconName) {
    // Remover SVG anterior se existir
    const existingSVG = document.getElementById('preview-svg');
    if (existingSVG) {
        existingSVG.remove();
    }
    
    // Criar elemento de imagem para o SVG
    const svgPreview = document.createElement('div');
    svgPreview.id = 'preview-svg';
    svgPreview.style.margin = '10px 0';
    svgPreview.innerHTML = '<p class="text-center mb-1">Preview SVG:</p>';
    
    // Adicionar o SVG ao DOM
    const container = document.querySelector('.badge-preview');
    const iconDisplay = document.querySelector('#badgePreview .rounded-circle');
    container.insertBefore(svgPreview, iconDisplay);
    
    // Fazer uma solicitação para gerar o SVG
    fetch('/admin/generate-badge-preview.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'color=' + encodeURIComponent(color) + '&icon=' + encodeURIComponent(iconName)
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
    })
    .then(blob => {
        if (blob) {
            const url = URL.createObjectURL(blob);
            const img = document.createElement('img');
            img.src = url;
            img.style.width = '100px';
            img.style.height = '100px';
            img.alt = 'Preview SVG';
            svgPreview.appendChild(img);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Atualizar quando campos do form mudam
    document.getElementById('name').addEventListener('input', updateBadgePreview);
    document.getElementById('description').addEventListener('input', updateBadgePreview);
    document.getElementById('background_color').addEventListener('input', updateBadgePreview);
    document.getElementById('icon').addEventListener('change', updateBadgePreview);
    document.getElementById('color_preset').addEventListener('change', function() {
        if (this.value) {
            document.getElementById('background_color').value = this.value;
            updateBadgePreview();
        }
    });
    
    // Configurar modal de edição
    const editBtns = document.querySelectorAll('.edit-badge-btn');
    editBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_badge_id').value = this.dataset.badgeId;
            document.getElementById('edit_name').value = this.dataset.badgeName;
            document.getElementById('edit_description').value = this.dataset.badgeDescription;
            document.getElementById('edit_criteria').value = this.dataset.badgeCriteria;
            document.getElementById('edit_criteria_value').value = this.dataset.badgeCriteriaValue;
            document.getElementById('edit_level').value = this.dataset.badgeLevel;
            document.getElementById('badge_current_image').src = this.dataset.badgeImage;
            
            // Reset image change
            document.getElementById('change_image').checked = false;
            document.getElementById('update_image').value = '0';
            document.querySelector('.edit-image-controls').style.display = 'none';
        });
    });
    
    // Inicializar a visualização
    updateBadgePreview();
});
</script>

<?php
// Incluir template de rodapé
include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php';
?>