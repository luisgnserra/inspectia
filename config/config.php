<?php



// Application configuration
session_start();

// Base URL
define('BASE_URL', '/inspectia');

// Email settings
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'luisgustavo@consultoriaexcelencia.com.br');
//define('SMTP_PASSWORD', '');
//define('SMTP_PASSWORD', $config['SMTP_PASSWORD']);
define('SMTP_FROM_EMAIL', 'luisgustavo@consultoriaexcelencia.com.br');
define('SMTP_FROM_NAME', 'InspectIA - Excelência Consultoria e Educação');

// Plan limitations
define('FREE_PLAN_MAX_INSPECTIONS', 3);
define('FREE_PLAN_MAX_RESPONSES', 3);

// Utility functions
function redirect($url)
{
    // Simplesmente redireciona para a URL fornecida
    if (substr($url, 0, 1) !== '/') {
        $url = '/' . $url;
    }

    $finalUrl = $url;

    // Debug info antes do redirecionamento
    // echo "<!DOCTYPE html>
    //        <html>
    //        <head>
    //            <meta http-equiv='refresh' content='1;url=$finalUrl'>
    //            <title>Redirecionando...</title>
    //            <style>
    //                body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
    //                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    //            </style>
    //        </head>
    //        <body>
    //            <div class='container'>
    //                <h2>Redirecionando...</h2>
    //                <p>Você está sendo redirecionado para: $finalUrl</p>
    //                <p>Se o redirecionamento não funcionar, <a href='$finalUrl'>clique aqui</a>.</p>
    //            </div>
    //        </body>
    //        </html>";

   // exit();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserPlan()
{
    return $_SESSION['user_plan'] ?? 'free';
}

function getActiveCompanyId()
{
    return $_SESSION['active_company_id'] ?? null;
}

function setActiveCompany($companyId)
{
    $_SESSION['active_company_id'] = $companyId;
}

function isPro()
{
    //return getCurrentUserPlan() === 'pro';
    $plan = strtolower(getCurrentUserPlan()); // normaliza para evitar 'Pro'/'PRO'
    return in_array($plan, ['pro', 'adm']);

}

function generateUuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// For handling error messages and success messages
$errors = [];
$success_messages = [];

function addError($message)
{
    global $errors;
    $errors[] = $message;
}

function addSuccessMessage($message)
{
    global $success_messages;
    $success_messages[] = $message;
}

function hasErrors()
{
    global $errors;
    return count($errors) > 0;
}

function hasSuccessMessages()
{
    global $success_messages;
    return count($success_messages) > 0;
}

function getErrors()
{
    global $errors;
    return $errors;
}

function getSuccessMessages()
{
    global $success_messages;
    return $success_messages;
}

function displayErrors()
{
    global $errors;
    if (count($errors) > 0) {
        echo '<div class="alert alert-danger">';
        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
        echo '</div>';
    }
}

function displaySuccessMessages()
{
    global $success_messages;
    if (count($success_messages) > 0) {
        echo '<div class="alert alert-success">';
        foreach ($success_messages as $message) {
            echo "<p>$message</p>";
        }
        echo '</div>';
    }
}







?>