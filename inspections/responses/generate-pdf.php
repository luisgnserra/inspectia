<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário tem uma empresa ativa
requireActiveCompany();

// Obter o ID da resposta da URL
$responseId = sanitizeInput($_GET['id'] ?? '');

if (empty($responseId)) {
    addError("ID da resposta não fornecido.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Obter dados da resposta
$response = getResponseById($responseId);

if (!$response) {
    addError("Resposta não encontrada.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Obter dados da inspeção associada
$inspection = getInspectionById($response['inspection_id']);

// Verificar se a inspeção pertence à empresa ativa do usuário
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Você não tem permissão para acessar essa resposta.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Obter as respostas para cada pergunta
$answers = getResponseAnswers($responseId);

// Obter dados da empresa
$company = getCompanyById(getActiveCompanyId());

// Para fins de demonstração, vamos gerar um HTML que pode ser exibido ou baixado
// Em uma implementação real, você usaria uma biblioteca como mPDF ou DOMPDF

header('Content-Type: text/html');
header('Content-Disposition: inline; filename="resposta_inspecao_' . date('Y-m-d') . '.html"');

// Iniciar o buffer de saída
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Inspeção</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            color: #333;
            background-color: #f9f9f9;
        }
        .report-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        .company {
            font-size: 20px;
            font-weight: bold;
        }
        .inspection {
            font-size: 16px;
            margin: 10px 0;
        }
        .date {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        .content {
            margin: 20px 0;
        }
        .question {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .question-text {
            font-weight: bold;
            margin-bottom: 8px;
            color: #0d6efd;
        }
        .answer {
            padding-left: 15px;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .answer img {
            border: 1px solid #eee;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
        .answer a {
            color: #0d6efd;
            text-decoration: none;
            font-size: 14px;
        }
        .answer a:hover {
            text-decoration: underline;
        }
        .footer {
            margin-top: 40px;
            border-top: 2px solid #eee;
            padding-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #666;
        }
        .qr-placeholder {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 1px dashed #ccc;
            background-color: #f5f5f5;
            font-size: 12px;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .report-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="header">
            <div class="logo">InspectAI</div>
            <div class="company"><?= htmlspecialchars($company['name']) ?></div>
            <div class="inspection">Relatório de Inspeção: <?= htmlspecialchars($inspection['title']) ?></div>
            <div class="date">Data: <?= date('d/m/Y', strtotime($response['created_at'])) ?></div>
        </div>
        
        <div class="content">
            <h3>Respostas da Inspeção:</h3>
            
            <?php if (empty($answers)): ?>
                <p>Não foram encontradas respostas para esta inspeção.</p>
            <?php else: ?>
                <?php foreach ($answers as $answer): ?>
                    <div class="question">
                        <div class="question-text"><?= htmlspecialchars($answer['question_text']) ?></div>
                        <div class="answer">
                        <?php 
                            $answerText = $answer['answer_text'] ?: 'Sem resposta';
                            // Verificar se a resposta é um caminho de imagem
                            if (strpos($answerText, '/uploads/images/') === 0) {
                                // É uma imagem, exibir como img
                                $imageUrl = htmlspecialchars($answerText);
                                echo '<img src="' . $imageUrl . '" style="max-width: 100%; max-height: 300px; display: block; margin-bottom: 10px;" alt="Imagem enviada">';
                                echo '<a href="' . $imageUrl . '" target="_blank">Ver imagem em tamanho completo</a>';
                            } else {
                                // Texto normal
                                echo nl2br(htmlspecialchars($answerText));
                            }
                        ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="qr-placeholder">
                Código QR para verificação digital (disponível na versão completa)
            </div>
        </div>
        
        <div class="footer">
            <p>InspectAI - Relatório gerado em <?= date('d/m/Y H:i:s') ?></p>
            <p>Inspeção #<?= $inspection['id'] ?> - Resposta #<?= $response['id'] ?></p>
            <p>Este documento é uma prévia. A versão final incluiria um PDF completo com recursos adicionais.</p>
        </div>
    </div>
</body>
</html>
<?php
// Capturar a saída do buffer
$html = ob_get_clean();

// Incluir a biblioteca de geração de PDF (usando mPDF, que precisa ser instalada)
// Como não temos acesso à biblioteca no momento, vamos apenas exibir o HTML
echo $html;

// Em um ambiente de produção, você instalaria uma biblioteca como mPDF ou DOMPDF
// Exemplo com mPDF (requer instalação via composer):
/*
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/vendor/autoload.php';
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output();
*/
?>