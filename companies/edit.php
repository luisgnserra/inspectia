<?php
require_once  '../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

// Check if user is logged in
requireLogin();

$userId = getCurrentUserId();
$companyId = sanitizeInput($_GET['id'] ?? '');

// Get company data
$company = getCompanyById($companyId);

// Check if company exists and belongs to this user
if (!$company || $company['user_id'] !== $userId) {
    addError("Company not found or you don't have permission to edit it.");
    redirect(url: "/companies/index.php");
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $logoPath = null;
    
    // Processar upload de logo, se fornecido
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $logoPath = uploadCompanyLogo($_FILES['logo'], $companyId);
        
        if ($logoPath === false && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            addError("Falha ao fazer upload do logo. Verifique se o arquivo é uma imagem válida (JPG, PNG, GIF ou SVG) e tem menos de 2MB.");
        }
    }
    
    if (empty($name)) {
        addError("O nome da empresa é obrigatório.");
    } else if (!hasErrors()) {
        // Atualizar a empresa
        if (updateCompany($companyId, $name, $logoPath)) {
            addSuccessMessage("Empresa atualizada com sucesso!");
            redirect(url: "/companies/index.php");
        } else {
            addError("Falha ao atualizar empresa. Por favor, tente novamente.");
        }
    }
}
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Empresa</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>/companies/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Empresas
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações da Empresa</h5>
            </div>
            <div class="card-body">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $companyId ?>" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Empresa</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($company['name']) ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="logo" class="form-label">Logo da Empresa</label>
                        
                        <?php if (!empty($company['logo_path'])): ?>
                            <div class="mb-3">
                                <p class="mb-2">Logo atual:</p>
                                <img src="<?= htmlspecialchars($company['logo_path']) ?>" 
                                     alt="Logo atual" 
                                     class="img-thumbnail"
                                     style="max-height: 150px; max-width: 300px;">
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <div class="form-text">
                            Envie um novo logo (opcional). Formatos aceitos: JPG, PNG, GIF, SVG. Tamanho máximo: 2MB.
                            Deixe em branco para manter o logo atual.
                        </div>
                        
                        <!-- Prévia do novo logo -->
                        <div class="mt-2 d-none" id="logoPreview">
                            <p>Prévia do novo logo:</p>
                            <img src="#" alt="Prévia do logo" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= BASE_URL ?>/companies/index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Atualizar Empresa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script para prévia do logo -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logoPreview');
    const previewImg = logoPreview.querySelector('img');
    
    logoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Verificar se é uma imagem
            if (!file.type.match('image.*')) {
                alert('Por favor, selecione um arquivo de imagem válido.');
                this.value = '';
                logoPreview.classList.add('d-none');
                return;
            }
            
            // Verificar tamanho (máx 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('O arquivo é muito grande! Por favor, selecione uma imagem com menos de 2MB.');
                this.value = '';
                logoPreview.classList.add('d-none');
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                logoPreview.classList.remove('d-none');
            }
            
            reader.readAsDataURL(file);
        } else {
            logoPreview.classList.add('d-none');
        }
    });
});
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php'; ?>
