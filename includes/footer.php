    </main>
    
    <footer class="bg-light py-3 mt-5">
        <div class="container text-center">
            <p class="mb-0">
                &copy; <?= date('Y') ?> InspectAI. Todos os direitos reservados. - 
                <a href="https://www.consultoriaexcelencia.com.br" target="_blank">Excelência Consultoria e Educação</a>
            </p>
            <p class="text-muted small mb-0">
                Uma plataforma poderosa para criar, gerenciar e analisar formulários de inspeção.
            </p>
        </div>
    </footer>

    <!-- Modal de Visualização de Inspeção -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Visualizar Inspeção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="previewModalBody" class="container-fluid">
                        <!-- O conteúdo do formulário será carregado aqui -->
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="previewModalBackButton" href="#" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar para Inspeções
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    
    <?php
    // Verificar se o arquivo de notificações de conquistas está incluído
    if (function_exists('displayAchievementNotifications')) {
        // Exibir notificações de conquistas, se houver
        displayAchievementNotifications();
    }
    ?>
</body>
</html>
