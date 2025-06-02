<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

// Get public link ID from URL
$publicLinkId = sanitizeInput($_GET['id'] ?? '');

if (empty($publicLinkId)) {
    $errorMessage = "Invalid inspection link. Please check the URL and try again.";
} else {
    // Get inspection by public link
    $inspection = getInspectionByPublicLink($publicLinkId);
    
    if (!$inspection) {
        $errorMessage = "This inspection form doesn't exist or has been removed.";
    } elseif ($inspection['status'] !== 'published') {
        $errorMessage = "This inspection form is currently not available for responses.";
    } else {
        // Buscar informações da empresa associada a esta inspeção
        $company = getCompanyById($inspection['company_id']);
        
        // Check if form can still accept responses
        if (!canSubmitResponse($inspection['id'])) {
            if ($inspection['response_limit'] === 'single') {
                $errorMessage = "Este formulário já recebeu uma resposta e não aceita múltiplos envios.";
            } else {
                $errorMessage = "Este formulário atingiu o número máximo de respostas permitidas.";
            }
        } else {
            // Abordagem direta para buscar questões
            $database = new Database();
            $db = $database->getConnection();
            
            // Buscar questões ordenadas por data de criação
            $query = "SELECT * FROM questions WHERE inspection_id = :inspection_id ORDER BY created_at ASC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':inspection_id', $inspection['id']);
            $stmt->execute();
            
            $questions = [];
            error_log("====== Carregando perguntas para inspeção pública " . $inspection['id'] . " ======");
            
            // Processar cada questão uma por uma para garantir que estamos obtendo todas
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Adicione o log detalhado para cada questão
                error_log("Carregou questão pública: ID=" . $row['id'] . ", Texto=" . $row['text'] . ", Tipo=" . $row['type']);
                
                // Para questões de escolha, carregue as opções
                if ($row['type'] === 'single_choice' || $row['type'] === 'multiple_choice') {
                    $row['options'] = getQuestionOptions($row['id']);
                } else {
                    $row['options'] = [];
                }
                
                // Adicionar ao array de questões
                $questions[] = $row;
            }
            
            error_log("Total de perguntas carregadas para visualização pública: " . count($questions));
        }
    }
}

// Handle form submission
$submissionSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response']) && empty($errorMessage)) {
    // Check again if form can accept responses (for race conditions)
    if (!canSubmitResponse($inspection['id'])) {
        $errorMessage = "Desculpe, este formulário não está mais aceitando respostas.";
    } else {
        // Create a new response
        $responseId = createResponse($inspection['id']);
        
        if ($responseId) {
            // Process each question's answer
            foreach ($questions as $question) {
                $questionId = $question['id'];
                $questionText = $question['text'];
                $answerText = '';
                
                // Handle different question types
                if ($question['type'] === 'single_choice') {
                    $answer = $_POST['question_' . $questionId] ?? '';
                    
                    // Validate that the selected option belongs to this question
                    if (!empty($answer)) {
                        foreach ($question['options'] as $option) {
                            if ($option['id'] === $answer) {
                                $answerText = $option['text'];
                                break;
                            }
                        }
                    }
                } elseif ($question['type'] === 'multiple_choice') {
                    $selectedOptions = $_POST['question_' . $questionId] ?? [];
                    
                    if (!empty($selectedOptions) && is_array($selectedOptions)) {
                        $validOptions = [];
                        
                        // Validate that all selected options belong to this question
                        foreach ($question['options'] as $option) {
                            if (in_array($option['id'], $selectedOptions)) {
                                $validOptions[] = $option['text'];
                            }
                        }

                        
                        $answerText = implode(', ', $validOptions);
                    }
                } elseif ($question['type'] === 'photo') {
                    // Process photo upload
                    if (isset($_FILES['question_photo_' . $questionId]) && 
                        $_FILES['question_photo_' . $questionId]['error'] === UPLOAD_ERR_OK) {
                        
                        // Process the upload and get the relative path
                      //  $filePath = processPhotoUpload(
                      //      $_FILES['question_photo_' . $questionId],
                      //      $responseId,
                      //      $questionId
                      //  );



// Converter o arquivo $_FILES em base64 antes de passar para a função
$file = $_FILES['question_photo_' . $questionId];
if (isset($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
    $fileContent = file_get_contents($file['tmp_name']);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);

    $filePath = processPhotoUpload($pdo, $base64, $responseId);
}






































































                        
                        if ($filePath) {
                            // Store the image path as the answer
                            $answerText = $filePath;
                        } else {
                            // Failed to process the upload
                            $answerText = '[Erro no upload da foto]';
                        }
                    }
                } else {
                    // For text, date, and time questions
                    $answerText = sanitizeInput($_POST['question_' . $questionId] ?? '');
                }
                
                // Save the answer
                createResponseAnswer($responseId, $questionId, $questionText, $answerText);
            }
            
            $submissionSuccess = true;
        } else {
            $errorMessage = "Falha ao enviar resposta. Por favor, tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($inspection) ? htmlspecialchars($inspection['title']) : 'Formulário de Inspeção' ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex-grow: 1;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-top-left-radius: 8px !important;
            border-top-right-radius: 8px !important;
            padding: 1.5rem;
        }
        .footer {
            margin-top: 2rem;
            padding: 1rem 0;
            background-color: #f8f9fa;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .required-indicator {
            color: #dc3545;
            font-weight: bold;
            margin-left: 0.25rem;
        }
    </style>
</head>
<body>
    <main>
        <div class="form-container">
            <?php if ($submissionSuccess): ?>
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="mb-0">Obrigado!</h3>
                    </div>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                        <h4>Sua resposta foi enviada com sucesso.</h4>
                        <p class="text-muted mb-4">Obrigado por completar o formulário de inspeção.</p>
                        <a href="<?= BASE_URL ?>/public/inspection.php?id=<?= $publicLinkId ?>" class="btn btn-outline-primary">
                            Enviar Outra Resposta
                        </a>
                    </div>
                </div>
            
            <?php elseif (isset($errorMessage)): ?>
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="mb-0">Formulário Indisponível</h3>
                    </div>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-circle text-danger fa-5x mb-4"></i>
                        <h4>Não foi possível acessar o formulário</h4>
                        <p class="text-muted mb-0"><?= $errorMessage ?></p>
                    </div>
                </div>
            
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <?php if (isset($company) && !empty($company['logo_path'])): ?>
                                    <img src="<?= htmlspecialchars($company['logo_path']) ?>" 
                                         alt="Logo <?= htmlspecialchars($company['name']) ?>"
                                         class="img-fluid"
                                         style="max-height: 60px; max-width: 120px;">
                                <?php else: ?>
                                    <!-- Espaço reservado para manter o layout consistente quando não há logo -->
                                    <div style="width: 30px;"></div>
                                <?php endif; ?>
                            </div>
                            <h2 class="mb-0"><?= htmlspecialchars($inspection['title']) ?></h2>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $publicLinkId ?>" method="POST" enctype="multipart/form-data" novalidate>
                            <?php 
                            // Garantir que todas as perguntas sejam mostradas, na ordem correta
                            foreach ($questions as $index => $question):
                                error_log("Rendering question in public view: " . $question['id'] . " - " . $question['text'] . " (index: " . $index . ")");
                            ?>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <?= htmlspecialchars($question['text']) ?>
                                        <?php if ($question['is_required']): ?>
                                            <span class="required-indicator">*</span>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <?php if ($question['type'] === 'short_text'): ?>
                                        <input type="text" class="form-control" name="question_<?= $question['id'] ?>" <?= $question['is_required'] ? 'required' : '' ?>>
                                    
                                    <?php elseif ($question['type'] === 'long_text'): ?>
                                        <textarea class="form-control" name="question_<?= $question['id'] ?>" rows="3" <?= $question['is_required'] ? 'required' : '' ?>></textarea>
                                    
                                    <?php elseif ($question['type'] === 'single_choice'): ?>
                                        <?php if (empty($question['options'])): ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Esta questão não possui opções.
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($question['options'] as $option): ?>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="radio" name="question_<?= $question['id'] ?>" id="option_<?= $option['id'] ?>" value="<?= $option['id'] ?>" <?= $question['is_required'] ? 'required' : '' ?>>
                                                    <label class="form-check-label" for="option_<?= $option['id'] ?>">
                                                        <?= htmlspecialchars($option['text']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    
                                    <?php elseif ($question['type'] === 'multiple_choice'): ?>
                                        <?php if (empty($question['options'])): ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Esta questão não possui opções.
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($question['options'] as $option): ?>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="question_<?= $question['id'] ?>[]" id="option_<?= $option['id'] ?>" value="<?= $option['id'] ?>">
                                                    <label class="form-check-label" for="option_<?= $option['id'] ?>">
                                                        <?= htmlspecialchars($option['text']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    
                                    <?php elseif ($question['type'] === 'date'): ?>
                                        <input type="date" class="form-control" name="question_<?= $question['id'] ?>" <?= $question['is_required'] ? 'required' : '' ?>>
                                    
                                    <?php elseif ($question['type'] === 'time'): ?>
                                        <input type="time" class="form-control" name="question_<?= $question['id'] ?>" <?= $question['is_required'] ? 'required' : '' ?>>
                                    
                                    <?php elseif ($question['type'] === 'photo'): ?>
                                        <div class="mb-3">
                                            <input type="file" class="form-control" name="question_photo_<?= $question['id'] ?>" 
                                                accept="image/*" capture="environment" <?= $question['is_required'] ? 'required' : '' ?>>
                                            <small class="form-text text-muted">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB</small>
                                            <div class="form-text mt-2">
                                                <i class="fas fa-camera me-1"></i> Você pode tirar uma foto ou selecionar uma imagem do seu dispositivo
                                            </div>
                                        </div>
                                    
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-4">
                                <?php if ($inspection['response_limit'] === 'single'): ?>
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Este formulário só pode ser enviado uma vez.
                                    </div>
                                <?php elseif ($inspection['response_limit'] === 'multiple'): ?>
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Este formulário aceita até <?= $inspection['max_responses'] ?> respostas
                                        (<?= $inspection['response_count'] ?>/<?= $inspection['max_responses'] ?> recebidas).
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid">
                                    <button type="submit" name="submit_response" class="btn btn-primary btn-lg">
                                        Enviar Resposta
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p class="mb-0">
                &copy; <?= date('Y') ?> InspectAI. Todos os direitos reservados. - 
                <a href="https://www.consultoriaexcelencia.com.br" target="_blank" class="text-decoration-none">Excelência Consultoria e Educação</a>
            </p>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
