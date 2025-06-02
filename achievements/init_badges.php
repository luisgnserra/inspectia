<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/achievements/functions.php';

// Verificar se o usuário está logado e é admin
requireLogin();

// Somente administradores podem inicializar as badges (verificação simplificada por enquanto)
// Em um sistema real, você teria um sistema de permissões mais robusto
if ($_SESSION['user_email'] !== 'admin@example.com') {
    addError("Somente administradores podem inicializar as badges.");
    redirect('/dashboard/index.php');
    exit;
}

// Array com as badges padrão do sistema
$defaultBadges = [
    [
        'name' => 'Primeiro Formulário',
        'description' => 'Parabéns por criar seu primeiro formulário de inspeção!',
        'image_path' => '/achievements/images/badge_first_form.svg',
        'criteria' => 'forms_created',
        'criteria_value' => 1,
        'level' => 1
    ],
    [
        'name' => 'Criador em Série',
        'description' => 'Você criou 5 formulários de inspeção. Está no caminho certo!',
        'image_path' => '/achievements/images/badge_first_form.svg',
        'criteria' => 'forms_created',
        'criteria_value' => 5,
        'level' => 2
    ],
    [
        'name' => 'Mestre dos Formulários',
        'description' => 'Impressionante! Você criou 20 formulários de inspeção.',
        'image_path' => '/achievements/images/badge_first_form.svg',
        'criteria' => 'forms_created',
        'criteria_value' => 20,
        'level' => 3
    ],
    [
        'name' => 'Coletor de Dados',
        'description' => 'Você coletou suas primeiras 10 respostas!',
        'image_path' => '/achievements/images/badge_response_collector.svg',
        'criteria' => 'responses_collected',
        'criteria_value' => 10,
        'level' => 1
    ],
    [
        'name' => 'Analista de Dados',
        'description' => 'Impressionante! Você já coletou 50 respostas em suas inspeções.',
        'image_path' => '/achievements/images/badge_response_collector.svg',
        'criteria' => 'responses_collected',
        'criteria_value' => 50,
        'level' => 2
    ],
    [
        'name' => 'Cientista de Dados',
        'description' => 'Uau! Você coletou 200 respostas. Isso é muito conhecimento!',
        'image_path' => '/achievements/images/badge_response_collector.svg',
        'criteria' => 'responses_collected',
        'criteria_value' => 200,
        'level' => 3
    ],
    [
        'name' => 'Primeira Empresa',
        'description' => 'Você criou sua primeira empresa no sistema. Os negócios estão crescendo!',
        'image_path' => '/achievements/images/badge_company_creator.svg',
        'criteria' => 'companies_created',
        'criteria_value' => 1,
        'level' => 1
    ],
    [
        'name' => 'Empresário',
        'description' => 'Você tem 3 empresas cadastradas. Seu império está crescendo!',
        'image_path' => '/achievements/images/badge_company_creator.svg',
        'criteria' => 'companies_created',
        'criteria_value' => 3,
        'level' => 2
    ],
    [
        'name' => 'Aprendiz de Fotografia',
        'description' => 'Você criou sua primeira questão do tipo foto. Imagens valem mais que palavras!',
        'image_path' => '/achievements/images/badge_photo_master.svg',
        'criteria' => 'photo_questions',
        'criteria_value' => 1,
        'level' => 1
    ],
    [
        'name' => 'Fotógrafo Profissional',
        'description' => 'Você criou 10 questões do tipo foto. Suas inspeções estão visuais!',
        'image_path' => '/achievements/images/badge_photo_master.svg',
        'criteria' => 'photo_questions',
        'criteria_value' => 10,
        'level' => 2
    ],
    [
        'name' => 'Usuário Dedicado',
        'description' => 'Você está usando o InspectAI há uma semana. Obrigado pela sua dedicação!',
        'image_path' => '/achievements/images/badge_power_user.svg',
        'criteria' => 'days_active',
        'criteria_value' => 7,
        'level' => 1
    ],
    [
        'name' => 'Usuário Fiel',
        'description' => 'Você está conosco há um mês! Sua fidelidade é muito apreciada.',
        'image_path' => '/achievements/images/badge_power_user.svg',
        'criteria' => 'days_active',
        'criteria_value' => 30,
        'level' => 2
    ],
    [
        'name' => 'Veterano InspectAI',
        'description' => 'Você é um usuário do InspectAI há 3 meses. Você é incrível!',
        'image_path' => '/achievements/images/badge_power_user.svg',
        'criteria' => 'days_active',
        'criteria_value' => 90,
        'level' => 3
    ]
];

// Inicializar o banco de dados com as badges padrão
$database = new Database();
$db = $database->getConnection();

try {
    // Iniciar transação
    $db->beginTransaction();
    
    // Limpar tabela de badges (cuidado: isso remove todas as badges existentes!)
    $query = "DELETE FROM achievement_badges";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Inserir as badges padrão
    $successCount = 0;
    foreach ($defaultBadges as $badge) {
        $id = createBadge(
            $badge['name'],
            $badge['description'],
            $badge['image_path'],
            $badge['criteria'],
            $badge['criteria_value'],
            $badge['level']
        );
        
        if ($id) {
            $successCount++;
        }
    }
    
    // Confirmar transação
    $db->commit();
    
    addSuccess("Badges inicializadas com sucesso! ($successCount badges criadas)");
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $db->rollBack();
    addError("Erro ao inicializar badges: " . $e->getMessage());
}

// Redirecionar para a página principal
redirect('/dashboard/index.php');
?>