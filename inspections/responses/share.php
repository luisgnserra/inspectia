<?php
require_once  '../../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

// Get inspection ID from URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId)) {
    addError("ID da inspeção é obrigatório.");
    redirect(url: "/inspections/index.php");
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to this company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspeção não encontrada ou você não tem permissão para compartilhar respostas.");
    redirect(url: "/inspections/index.php");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailTo = sanitizeInput($_POST['email_to'] ?? '');
    $emailSubject = sanitizeInput($_POST['email_subject'] ?? '');
    $emailMessage = sanitizeInput($_POST['email_message'] ?? '');
    
    // Validate email
    if (!validateEmail($emailTo)) {
        addError("Por favor, insira um endereço de email válido.");
    }
    
    // Validate subject
    if (empty($emailSubject)) {
        addError("O assunto do email é obrigatório.");
    }
    
    if (!hasErrors()) {
        // Get the responses data
        $data = getFormattedResponseData($inspectionId);
        
        // Format the email content
        $emailContent = $emailMessage . "\n\n";
        $emailContent .= "Inspeção: " . $inspection['title'] . "\n";
        $emailContent .= "Respostas: " . count($data['responses']) . "\n\n";
        
        // Add responses data
        foreach ($data['responses'] as $index => $response) {
            $emailContent .= "Resposta #" . ($index + 1) . " (" . $response['created_at'] . ")\n";
            $emailContent .= "----------------------------------------\n";
            
            foreach ($data['questions'] as $question) {
                $answer = $response['answers'][$question['id']] ?? 'Sem resposta';
                $emailContent .= $question['text'] . ": " . $answer . "\n";
            }
            
            $emailContent .= "----------------------------------------\n\n";
        }
        
        // Set email headers
        $headers = "From: " . SMTP_FROM_EMAIL . "\r\n";
        $headers .= "Reply-To: " . $_SESSION['user_email'] . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send the email
        if (mail($emailTo, $emailSubject, $emailContent, $headers)) {
            addSuccessMessage("Respostas compartilhadas com sucesso por email.");
            redirect(url: "/inspections/responses/index.php?id=" . $inspectionId);
        } else {
            addError("Falha ao enviar email. Por favor, tente novamente.");
        }
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Compartilhar Respostas</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspectionId ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Respostas
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Compartilhar Respostas por Email</h5>
            </div>
            <div class="card-body">
                <p class="mb-4">
                    Envie as respostas de <strong><?= htmlspecialchars($inspection['title']) ?></strong> 
                    para um endereço de email. O email incluirá todas as respostas e suas respostas.
                </p>
                
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $inspectionId ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email_to" class="form-label">Email do Destinatário</label>
                        <input type="email" class="form-control" id="email_to" name="email_to" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email_subject" class="form-label">Assunto do Email</label>
                        <input type="text" class="form-control" id="email_subject" name="email_subject" 
                               value="Respostas para: <?= htmlspecialchars($inspection['title']) ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email_message" class="form-label">Mensagem (Opcional)</label>
                        <textarea class="form-control" id="email_message" name="email_message" rows="3" placeholder="Adicione uma mensagem pessoal para incluir com as respostas."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        O email incluirá todas as <?= $inspection['response_count'] ?> respostas para esta inspeção.
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspectionId ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Enviar Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php'; ?>
