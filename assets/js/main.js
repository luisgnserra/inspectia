document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    let tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize all popovers
    let popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    let popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Inicializar botões de pré-visualização em modal
    const previewButtons = document.querySelectorAll('.preview-modal-btn');
    const previewModal = document.getElementById('previewModal');
    
    // Limpar o conteúdo do modal quando ele for fechado
    if (previewModal) {
        previewModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('previewModalLabel').textContent = 'Visualizar Inspeção';
            document.getElementById('previewModalBody').innerHTML = '';
        });
    }
    
    previewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const inspectionId = this.getAttribute('data-inspection-id');
            const previewUrl = this.getAttribute('href');
            
            // Mostrar indicador de carregamento
            document.getElementById('previewModalBody').innerHTML = `
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3">Carregando visualização da inspeção...</p>
                </div>
            `;
            
            // Abrir o modal primeiro para mostrar o carregamento
            const bsModal = new bootstrap.Modal(previewModal);
            bsModal.show();
            
            // Carregar conteúdo da prévia no modal
            fetch(previewUrl)
                .then(response => response.text())
                .then(html => {
                    // Criar um DOM temporário para extrair apenas o conteúdo do formulário
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Extrair o título e o formulário completo
                    const title = doc.querySelector('.card-header h5')?.textContent.trim() || 'Visualizar Inspeção';
                    const previewForm = doc.querySelector('#preview-form');
                    
                    if (previewForm) {
                        // Preservar o formulário inteiro, incluindo atributos
                        const formMethod = previewForm.getAttribute('method') || 'POST';
                        const formAction = previewForm.getAttribute('action') || '#';
                        const formContent = previewForm.innerHTML;
                        
                        // Recriar o formulário preservando atributos
                        const formHtml = `
                            <form id="modal-preview-form" method="${formMethod}" action="${formAction}">
                                ${formContent}
                            </form>
                        `;
                        
                        // Configurar o modal
                        document.getElementById('previewModalLabel').textContent = title;
                        document.getElementById('previewModalBody').innerHTML = formHtml;
                        
                        // Adicionar event listener para o formulário após ele ser inserido no DOM
                        const modalForm = document.getElementById('modal-preview-form');
                        if (modalForm) {
                            modalForm.addEventListener('submit', function(e) {
                                // Não impedimos o envio, apenas fechamos o modal após o envio bem-sucedido
                                const bsModal = bootstrap.Modal.getInstance(previewModal);
                                if (bsModal) {
                                    // Mostrar uma mensagem de sucesso
                                    window.showToast('success', 'Enviado', 'Resposta enviada com sucesso!');
                                }
                            });
                        }
                    } else {
                        // Configurar o modal com mensagem de erro
                        document.getElementById('previewModalLabel').textContent = title;
                        document.getElementById('previewModalBody').innerHTML = 'Não foi possível carregar o formulário.';
                    }
                    
                    // Configurar botão de voltar
                    const backButton = document.getElementById('previewModalBackButton');
                    backButton.setAttribute('href', '/inspections/index.php');
                })
                .catch(error => {
                    console.error('Erro ao carregar prévia:', error);
                    document.getElementById('previewModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Não foi possível carregar a prévia da inspeção.
                        </div>
                    `;
                    window.showToast('error', 'Erro', 'Não foi possível carregar a prévia da inspeção.');
                });
        });
    });
    
    // Toast notification function
    window.showToast = function(type, title, message) {
        const toast = document.getElementById('toast');
        const toastIcon = document.getElementById('toast-icon');
        const toastTitle = document.getElementById('toast-title');
        const toastBody = document.getElementById('toast-body');
        
        // Set icon based on type
        if (type === 'success') {
            toastIcon.className = 'fas fa-check-circle text-success me-2';
        } else if (type === 'error') {
            toastIcon.className = 'fas fa-exclamation-circle text-danger me-2';
        } else if (type === 'warning') {
            toastIcon.className = 'fas fa-exclamation-triangle text-warning me-2';
        } else {
            toastIcon.className = 'fas fa-info-circle text-info me-2';
        }
        
        // Set title and message
        toastTitle.textContent = title;
        toastBody.textContent = message;
        
        // Show the toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    };
    
    // Delete confirmation
    const confirmDeleteForms = document.querySelectorAll('.confirm-delete-form');
    
    confirmDeleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Tem certeza de que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        });
    });
    
    // Confirmação para links de exclusão
    const confirmDeleteLinks = document.querySelectorAll('.confirm-delete');
    
    confirmDeleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Tem certeza de que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        });
    });
    
    // Copy link to clipboard
    const copyLinkBtns = document.querySelectorAll('.copy-link-btn');
    
    copyLinkBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const link = btn.getAttribute('data-link');
            
            // Create temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = link;
            document.body.appendChild(tempInput);
            
            // Select and copy the link
            tempInput.select();
            document.execCommand('copy');
            
            // Remove the temporary input
            document.body.removeChild(tempInput);
            
            // Show success toast
            showToast('success', 'Link Copiado', 'O link foi copiado para sua área de transferência.');
            
            // Change button text temporarily
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Copiado!';
            
            setTimeout(function() {
                btn.innerHTML = originalText;
            }, 2000);
        });
    });
    
    // Question form builder functionality
    initQuestionBuilder();
    
    // Initialize export buttons
    initExportButtons();
    
    // Initialize responses modal
    initResponsesModal();
    
    // Initialize response details
    initResponseDetails();
    
    // Initialize AI analysis
    initAIAnalysis();
});

function initQuestionBuilder() {
    const questionContainer = document.getElementById('question-container');
    const addQuestionBtn = document.getElementById('add-question-btn');
    
    if (!questionContainer || !addQuestionBtn) {
        return;
    }
    
    let questionCounter = 0;
    
    // Evitar múltiplos event listeners
    addQuestionBtn.removeEventListener('click', addQuestionHandler);
    addQuestionBtn.addEventListener('click', addQuestionHandler);
    
    function addQuestionHandler() {
        questionCounter++;
        
        const questionId = 'new_' + questionCounter;
        const questionCard = createQuestionCard(questionId);
        
        questionContainer.appendChild(questionCard);
        
        // Initialize the question type change handler for the new question
        const questionType = questionCard.querySelector('.question-type');
        handleQuestionTypeChange(questionType);
    }
    
    // Handle existing questions
    const existingQuestionTypes = document.querySelectorAll('.question-type');
    existingQuestionTypes.forEach(function(select) {
        handleQuestionTypeChange(select);
        
        // Update question counter based on existing questions
        const idMatch = select.id.match(/\d+$/);
        if (idMatch && parseInt(idMatch[0]) > questionCounter) {
            questionCounter = parseInt(idMatch[0]);
        }
    });
    
    // Set up event delegation for dynamically added questions
    questionContainer.addEventListener('click', function(e) {
        // Handle delete question button
        if (e.target.classList.contains('delete-question-btn') || 
            e.target.closest('.delete-question-btn')) {
            const btn = e.target.classList.contains('delete-question-btn') ? 
                        e.target : e.target.closest('.delete-question-btn');
            const questionCard = btn.closest('.question-card');
            
            if (confirm('Tem certeza que deseja excluir esta questão?')) {
                questionCard.remove();
            }
        }
        
        // Handle add option button
        if (e.target.classList.contains('add-option-btn') || 
            e.target.closest('.add-option-btn')) {
            const btn = e.target.classList.contains('add-option-btn') ? 
                        e.target : e.target.closest('.add-option-btn');
            const optionsContainer = btn.closest('.card-body').querySelector('.options-container');
            
            addOptionToQuestion(optionsContainer);
        }
        
        // Handle delete option button
        if (e.target.classList.contains('delete-option-btn') || 
            e.target.closest('.delete-option-btn')) {
            const btn = e.target.classList.contains('delete-option-btn') ? 
                        e.target : e.target.closest('.delete-option-btn');
            const optionItem = btn.closest('.option-item');
            
            optionItem.remove();
        }
    });
    
    // Handle question type changes
    questionContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('question-type')) {
            handleQuestionTypeChange(e.target);
        }
    });
}

function createQuestionCard(questionId) {
    const div = document.createElement('div');
    div.className = 'card question-card';
    div.dataset.questionId = questionId;
    
    div.innerHTML = `
        <div class="card-header bg-light">
            <span>Questão</span>
            <button type="button" class="btn btn-sm btn-outline-danger delete-question-btn">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="question_text_${questionId}" class="form-label">Texto da Questão</label>
                <input type="text" class="form-control question-text" id="question_text_${questionId}" name="questions[${questionId}][text]" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="question_type_${questionId}" class="form-label">Tipo de Questão</label>
                    <select class="form-select question-type" id="question_type_${questionId}" name="questions[${questionId}][type]" required>
                        <option value="short_text">Texto Curto</option>
                        <option value="long_text">Texto Longo</option>
                        <option value="single_choice">Escolha Única</option>
                        <option value="multiple_choice">Múltipla Escolha</option>
                        <option value="date">Data</option>
                        <option value="time">Hora</option>
                        <option value="photo">Foto</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="question_required_${questionId}" name="questions[${questionId}][required]" value="1">
                        <label class="form-check-label" for="question_required_${questionId}">
                            Questão Obrigatória
                        </label>
                    </div>
                </div>
            </div>
            <div class="options-container d-none" id="options_container_${questionId}">
                <label class="form-label">Opções</label>
                <button type="button" class="btn btn-sm btn-outline-primary add-option-btn mb-2">
                    <i class="fas fa-plus"></i> Adicionar Opção
                </button>
            </div>
        </div>
    `;
    
    return div;
}

function handleQuestionTypeChange(select) {
    const questionCard = select.closest('.question-card');
    const optionsContainer = questionCard.querySelector('.options-container');
    
    const questionType = select.value;
    
    if (questionType === 'single_choice' || questionType === 'multiple_choice') {
        optionsContainer.classList.remove('d-none');
        
        // Add at least two options if there are none
        if (optionsContainer.querySelectorAll('.option-item').length === 0) {
            addOptionToQuestion(optionsContainer);
            addOptionToQuestion(optionsContainer);
        }
    } else {
        optionsContainer.classList.add('d-none');
    }
}

function addOptionToQuestion(optionsContainer) {
    const questionId = optionsContainer.closest('.question-card').dataset.questionId;
    const optionItems = optionsContainer.querySelectorAll('.option-item');
    const optionIndex = optionItems.length;
    
    const optionItem = document.createElement('div');
    optionItem.className = 'option-item input-group mb-2';
    
    optionItem.innerHTML = `
        <span class="input-group-text handle-icon">
            <i class="fas fa-grip-vertical"></i>
        </span>
        <input type="text" class="form-control" name="questions[${questionId}][options][${optionIndex}]" placeholder="Texto da opção" required>
        <button type="button" class="btn btn-outline-danger delete-option-btn">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    optionsContainer.appendChild(optionItem);
}

function initExportButtons() {
    const exportCsvBtn = document.getElementById('export-csv-btn');
    const exportJsonBtn = document.getElementById('export-json-btn');
    
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function() {
            const inspectionId = exportCsvBtn.dataset.inspectionId;
            window.location.href = `/inspections/responses/export.php?id=${inspectionId}&format=csv`;
        });
    }
    
    if (exportJsonBtn) {
        exportJsonBtn.addEventListener('click', function() {
            const inspectionId = exportJsonBtn.dataset.inspectionId;
            window.location.href = `/inspections/responses/export.php?id=${inspectionId}&format=json`;
        });
    }
}

// Modal para visualização de respostas
function initResponseDetails() {
    // Variável global para rastrear instâncias de modal
    let activeResponseModal = null;
    
    // Delegação de eventos para capturar cliques em botões de visualização
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.view-response-btn');
        if (!button) return;
        
        e.preventDefault();
        
        const responseId = button.dataset.responseId;
        const responseIndex = button.dataset.responseIndex;
        
        if (!responseId) return;
        
        // Se já existir um modal ativo, destrua-o primeiro
        if (activeResponseModal) {
            activeResponseModal.hide();
            setTimeout(() => {
                // Aguarde a transição de fechamento terminar
                const oldModal = document.getElementById('dynamicResponseDetailsModal');
                if (oldModal) oldModal.remove();
                
                // Criar o novo modal
                showResponseDetailsModal(responseId, responseIndex);
            }, 300);
        } else {
            // Caso não exista modal ativo, simplesmente crie um novo
            showResponseDetailsModal(responseId, responseIndex);
        }
    });
    
    // Função para criar e mostrar o modal dinamicamente
    function showResponseDetailsModal(responseId, responseIndex) {
        // Criar novo elemento modal
        const modalDiv = document.createElement('div');
        modalDiv.className = 'modal fade';
        modalDiv.id = 'dynamicResponseDetailsModal';
        modalDiv.setAttribute('tabindex', '-1');
        modalDiv.setAttribute('aria-labelledby', 'dynamicResponseDetailsModalLabel');
        modalDiv.setAttribute('aria-hidden', 'true');
        modalDiv.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dynamicResponseDetailsModalLabel">Detalhes da Resposta #${responseIndex}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="dynamicResponseDetailsContent">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <p class="mt-2">Carregando detalhes da resposta...</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        `;
        
        // Adicionar modal ao corpo do documento
        document.body.appendChild(modalDiv);
        
        // Registrar evento para limpeza na desmontagem
        modalDiv.addEventListener('hidden.bs.modal', function() {
            activeResponseModal = null; // Limpar referência ao modal
            // Será removido automaticamente após o fechamento em uma nova interação
        });
        
        // Inicializar e mostrar o modal
        activeResponseModal = new bootstrap.Modal(modalDiv);
        activeResponseModal.show();
        
        // Carregar os dados
        loadResponseDetails(responseId);
    }
    
    // Função para carregar os detalhes da resposta via AJAX
    function loadResponseDetails(responseId) {
        fetch(`/api/get-response-details.php?id=${responseId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Falha ao carregar detalhes da resposta');
                }
                return response.json();
            })
            .then(data => {
                let html = '';
                
                if (data.answers && data.answers.length > 0) {
                    html += '<dl class="row">';
                    data.answers.forEach(answer => {
                        // Verifica se a resposta é um caminho de imagem
                        const answerText = answer.answer_text || '';
                        let displayValue = '';
                        
                        // Verifica se parece ser uma imagem (começa com /uploads/images/)
                        if (answerText && answerText.startsWith('/uploads/images/')) {
                            // Exibe a imagem com caminho absoluto
                            displayValue = `
                                <div class="mt-2 mb-2">
                                    <img src="${escapeHtml(answerText)}" class="img-fluid rounded" 
                                         alt="Imagem enviada" style="max-height: 300px;">
                                    <a href="${escapeHtml(answerText)}" target="_blank" class="d-block mt-1">
                                        <i class="fas fa-external-link-alt me-1"></i> Ver em tamanho completo
                                    </a>
                                </div>
                            `;
                        } else if (answerText) {
                            // Texto normal (sem ser imagem)
                            displayValue = escapeHtml(answerText).replace(/\n/g, '<br>');
                        }
                        
                        html += `
                            <dt class="col-sm-4">${escapeHtml(answer.question_text)}</dt>
                            <dd class="col-sm-8">${displayValue}</dd>
                        `;
                    });
                    html += '</dl>';
                } else {
                    html = '<div class="alert alert-info">Nenhum detalhe encontrado para esta resposta.</div>';
                }
                
                const contentDiv = document.getElementById('dynamicResponseDetailsContent');
                if (contentDiv) {
                    contentDiv.innerHTML = html;
                }
            })
            .catch(error => {
                const contentDiv = document.getElementById('dynamicResponseDetailsContent');
                if (contentDiv) {
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erro ao carregar detalhes: ${error.message}
                        </div>
                    `;
                }
                console.error(error);
            });
    }
}

function initAIAnalysis() {
    const analyzeButton = document.getElementById('analyze-ai-btn');
    
    if (!analyzeButton) {
        return;
    }
    
    analyzeButton.addEventListener('click', function() {
        // Mostrar modal de carregamento
        const loadingModal = new bootstrap.Modal(document.getElementById('aiAnalysisModal') || createAIAnalysisModal());
        loadingModal.show();
        
        // Simular análise de IA (para ser implementado com API real)
        setTimeout(() => {
            document.getElementById('aiAnalysisContent').innerHTML = `
                <div class="alert alert-info">
                    <h5 class="alert-heading">Análise de Dados</h5>
                    <p>A análise de IA será implementada com integração de API em uma futura atualização.</p>
                    <hr>
                    <p class="mb-0">Recursos planejados incluem reconhecimento de padrões, detecção de anomalias e resumo automático.</p>
                </div>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Visão Geral das Respostas</h5>
                    </div>
                    <div class="card-body">
                        <p>Este recurso permitirá insights automáticos gerados a partir dos dados coletados nas inspeções.</p>
                    </div>
                </div>
            `;
        }, 1500);
    });
}

// Criar modal de análise AI se não existir
function createAIAnalysisModal() {
    const modalDiv = document.createElement('div');
    modalDiv.className = 'modal fade';
    modalDiv.id = 'aiAnalysisModal';
    modalDiv.tabIndex = '-1';
    modalDiv.setAttribute('aria-labelledby', 'aiAnalysisModalLabel');
    modalDiv.setAttribute('aria-hidden', 'true');
    
    modalDiv.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aiAnalysisModalLabel">
                        <i class="fas fa-robot me-2"></i>Análise de IA
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="aiAnalysisContent">
                        <div class="text-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Analisando dados...</span>
                            </div>
                            <p class="mt-3">A IA está analisando as respostas...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="downloadAiReport">
                        <i class="fas fa-download me-1"></i>Baixar Relatório
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modalDiv);
    return modalDiv;
}

// A função viewResponseDetails foi removida e substituída por um event listener em initResponseDetails

// Função para gerar relatório em PDF
function generatePDF(responseId) {
    // Mostrar toast de informação
    window.showToast('info', 'Gerando PDF', 'Preparando o relatório em PDF...');
    
    // Redirecionar para o endpoint de geração de PDF
    window.location.href = `/inspections/responses/generate-pdf.php?id=${responseId}`;
}

// Função para exibir modal de confirmação de exclusão
function confirmDelete(responseId, inspectionId, responseNumber) {
    // Configurar o modal de confirmação
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    
    // Atualizar número da resposta no texto
    document.getElementById('deleteResponseNumber').textContent = '#' + responseNumber;
    
    // Configurar o link de confirmação
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.setAttribute('href', `/inspections/responses/delete.php?id=${responseId}&inspection_id=${inspectionId}`);
    
    // Mostrar o modal
    deleteModal.show();
}

// Função auxiliar para escapar HTML
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function initResponsesModal() {
    const responseButtons = document.querySelectorAll('.view-responses-modal-btn');
    const modal = document.getElementById('responsesModal');
    
    if (!responseButtons.length || !modal) {
        return;
    }
    
    // Inicializar manipulador de eventos para todos os botões de visualização de respostas
    responseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const inspectionId = this.dataset.inspectionId;
            const inspectionTitle = this.dataset.inspectionTitle;
            
            // Atualizar título no modal
            document.getElementById('modal-inspection-title').textContent = inspectionTitle;
            
            // Atualizar link "Ver Todas as Respostas"
            document.getElementById('modal-view-all-btn').href = `/inspections/responses/index.php?id=${inspectionId}`;
            
            // Mostrar loading e esconder conteúdo
            document.getElementById('modal-loading').classList.remove('d-none');
            document.getElementById('modal-content').classList.add('d-none');
            document.getElementById('modal-error').classList.add('d-none');
            
            // Carregar respostas via AJAX
            fetch(`/api/get-responses.php?id=${inspectionId}&limit=5`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Falha ao carregar respostas');
                    }
                    return response.json();
                })
                .then(data => {
                    // Ocultar loading
                    document.getElementById('modal-loading').classList.add('d-none');
                    
                    // Exibir conteúdo
                    document.getElementById('modal-content').classList.remove('d-none');
                    
                    // Renderizar respostas
                    const container = document.getElementById('modal-responses-container');
                    container.innerHTML = '';
                    
                    if (data.responses.length === 0) {
                        container.innerHTML = '<div class="alert alert-info">Nenhuma resposta encontrada para esta inspeção.</div>';
                        return;
                    }
                    
                    // Criar acordeão para as respostas
                    const accordion = document.createElement('div');
                    accordion.className = 'accordion';
                    accordion.id = 'modalResponsesAccordion';
                    
                    data.responses.forEach((response, index) => {
                        const accordionItem = document.createElement('div');
                        accordionItem.className = 'accordion-item';
                        
                        // Criar cabeçalho do acordeão
                        const header = document.createElement('h2');
                        header.className = 'accordion-header';
                        header.id = `modalHeading${index}`;
                        
                        const button = document.createElement('button');
                        button.className = `accordion-button ${index !== 0 ? 'collapsed' : ''}`;
                        button.type = 'button';
                        button.setAttribute('data-bs-toggle', 'collapse');
                        button.setAttribute('data-bs-target', `#modalCollapse${index}`);
                        button.setAttribute('aria-expanded', index === 0 ? 'true' : 'false');
                        button.setAttribute('aria-controls', `modalCollapse${index}`);
                        
                        // Adicionar informações da resposta ao botão
                        const responseDate = new Date(response.created_at);
                        const formattedDate = responseDate.toLocaleDateString('pt-BR') + ' ' + 
                                            responseDate.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                        
                        button.innerHTML = `
                            <div class="w-100 d-flex justify-content-between align-items-center">
                                <span>
                                    Resposta #${index + 1} 
                                    <small class="text-muted ms-2">${formattedDate}</small>
                                </span>
                            </div>
                        `;
                        
                        header.appendChild(button);
                        accordionItem.appendChild(header);
                        
                        // Criar conteúdo do acordeão
                        const collapseDiv = document.createElement('div');
                        collapseDiv.id = `modalCollapse${index}`;
                        collapseDiv.className = `accordion-collapse collapse ${index === 0 ? 'show' : ''}`;
                        collapseDiv.setAttribute('aria-labelledby', `modalHeading${index}`);
                        collapseDiv.setAttribute('data-bs-parent', '#modalResponsesAccordion');
                        
                        const accordionBody = document.createElement('div');
                        accordionBody.className = 'accordion-body';
                        
                        // Renderizar respostas como uma lista de definição
                        if (response.answers && Object.keys(response.answers).length > 0) {
                            const dl = document.createElement('dl');
                            dl.className = 'row';
                            
                            data.questions.forEach(question => {
                                const answer = response.answers[question.id] || '';
                                
                                const dt = document.createElement('dt');
                                dt.className = 'col-sm-3';
                                dt.textContent = question.text;
                                
                                const dd = document.createElement('dd');
                                dd.className = 'col-sm-9';
                                
                                // Verifica se a resposta parece ser um caminho de imagem
                                if (answer && typeof answer === 'string' && answer.startsWith('/uploads/images/')) {
                                    // Criar contêiner para a imagem
                                    const imageContainer = document.createElement('div');
                                    imageContainer.className = 'mt-2 mb-2';
                                    
                                    // Criar elemento de imagem
                                    const img = document.createElement('img');
                                    img.src = answer;
                                    img.className = 'img-fluid rounded';
                                    img.alt = 'Imagem enviada';
                                    img.style.maxHeight = '300px';
                                    imageContainer.appendChild(img);
                                    
                                    // Adicionar link para ver em tamanho completo
                                    const link = document.createElement('a');
                                    link.href = answer;
                                    link.target = '_blank';
                                    link.className = 'd-block mt-1';
                                    link.innerHTML = '<i class="fas fa-external-link-alt me-1"></i> Ver em tamanho completo';
                                    imageContainer.appendChild(link);
                                    
                                    dd.appendChild(imageContainer);
                                } else if (answer) {
                                    // Texto normal (somente se houver resposta)
                                    dd.textContent = answer;
                                }
                                
                                dl.appendChild(dt);
                                dl.appendChild(dd);
                            });
                            
                            accordionBody.appendChild(dl);
                        } else {
                            accordionBody.innerHTML = '<p class="text-muted">Nenhuma resposta detalhada disponível.</p>';
                        }
                        
                        collapseDiv.appendChild(accordionBody);
                        accordionItem.appendChild(collapseDiv);
                        
                        accordion.appendChild(accordionItem);
                    });
                    
                    container.appendChild(accordion);
                })
                .catch(error => {
                    // Exibir erro
                    document.getElementById('modal-loading').classList.add('d-none');
                    document.getElementById('modal-error').classList.remove('d-none');
                    document.getElementById('modal-error-message').textContent = error.message;
                });
        });
    });
}
