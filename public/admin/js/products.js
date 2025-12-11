/**
 * Ações rápidas para produtos no admin
 */

(function() {
    'use strict';

    console.log('[Produtos] JS inicializado');

    // Obter basePath do contexto
    var basePath = '';
    // Tentar obter do contexto global primeiro
    if (typeof window.basePath !== 'undefined') {
        basePath = window.basePath;
        console.log('[Produtos] basePath obtido de window.basePath:', basePath);
    } else {
        // Fallback: tentar obter do script src
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].src || '';
            if (src.indexOf('/admin/js/products.js') !== -1) {
                var match = src.match(/^(.+)\/admin\/js\/products\.js/);
                if (match) {
                    basePath = match[1];
                }
                break;
            }
        }
        console.log('[Produtos] basePath obtido do script src:', basePath);
    }

    // Função auxiliar para fazer requisições AJAX
    function makeRequest(url, method, data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        callback(null, response);
                    } catch (e) {
                        callback('Erro ao processar resposta do servidor', null);
                    }
                } else {
                    callback('Erro na requisição: ' + xhr.status, null);
                }
            }
        };
        
        var formData = '';
        if (data) {
            var pairs = [];
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    if (Array.isArray(data[key])) {
                        // Para arrays, usar notação [] para garantir que PHP receba como array
                        data[key].forEach(function(value) {
                            pairs.push(encodeURIComponent(key) + '[]=' + encodeURIComponent(value));
                        });
                    } else {
                        pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                    }
                }
            }
            formData = pairs.join('&');
        }
        
        xhr.send(formData);
    }

    // Toggle de status
    document.addEventListener('DOMContentLoaded', function() {
        // Delegar evento para toggle de status
        document.addEventListener('click', function(e) {
            var toggleBtn = e.target.closest('.js-toggle-status');
            if (toggleBtn) {
                e.preventDefault();
                var produtoId = toggleBtn.getAttribute('data-id');
                var currentStatus = toggleBtn.getAttribute('data-status');
                
                // Desabilitar botão e mostrar loading
                toggleBtn.disabled = true;
                var originalContent = toggleBtn.innerHTML;
                toggleBtn.innerHTML = '<span class="admin-status-badge">Carregando...</span>';
                
                makeRequest(
                    basePath + '/admin/produtos/' + produtoId + '/toggle-status',
                    'POST',
                    {},
                    function(error, response) {
                        toggleBtn.disabled = false;
                        
                        if (error || !response || !response.success) {
                            alert('Erro ao atualizar status: ' + (error || (response && response.message) || 'Erro desconhecido'));
                            toggleBtn.innerHTML = originalContent;
                        } else {
                            // Atualizar HTML do badge
                            toggleBtn.innerHTML = response.label_html;
                            toggleBtn.setAttribute('data-status', response.novo_status);
                        }
                    }
                );
            }
        });

        // Abrir modal de exclusão
        document.addEventListener('click', function(e) {
            var deleteBtn = e.target.closest('.js-open-excluir-produto-modal');
            if (deleteBtn) {
                e.preventDefault();
                var produtoId = deleteBtn.getAttribute('data-id');
                var produtoNome = deleteBtn.getAttribute('data-nome');
                
                var modal = document.getElementById('modal-excluir-produto');
                var modalNome = document.getElementById('modal-produto-nome');
                var formExcluir = document.getElementById('form-excluir-produto');
                
                if (modal && modalNome && formExcluir) {
                    modalNome.textContent = produtoNome;
                    formExcluir.action = basePath + '/admin/produtos/' + produtoId + '/excluir';
                    modal.style.display = 'flex';
                }
            }
        });

        // Fechar modal de exclusão
        window.fecharModalExclusaoProduto = function() {
            var modal = document.getElementById('modal-excluir-produto');
            if (modal) {
                modal.style.display = 'none';
            }
        };

        // Fechar modal ao clicar no overlay
        var modalExcluir = document.getElementById('modal-excluir-produto');
        if (modalExcluir) {
            modalExcluir.addEventListener('click', function(e) {
                if (e.target === this) {
                    window.fecharModalExclusaoProduto();
                }
            });
        }

        // Abrir modal de categorias
        document.addEventListener('click', function(e) {
            var categoriasBtn = e.target.closest('.js-open-categorias-modal');
            if (categoriasBtn) {
                e.preventDefault();
                var produtoId = categoriasBtn.getAttribute('data-id');
                var produtoNome = categoriasBtn.getAttribute('data-nome');
                var categoriasJson = categoriasBtn.getAttribute('data-categorias');
                
                var modal = document.getElementById('modal-categorias-produto');
                var modalNome = document.getElementById('modal-categorias-produto-nome');
                
                if (modal && modalNome) {
                    modalNome.textContent = produtoNome;
                    
                    // Parsear categorias atuais
                    var categoriasAtuais = [];
                    try {
                        categoriasAtuais = JSON.parse(categoriasJson || '[]');
                    } catch (e) {
                        categoriasAtuais = [];
                    }
                    
                    // Marcar checkboxes
                    var checkboxes = modal.querySelectorAll('.categoria-checkbox');
                    checkboxes.forEach(function(checkbox) {
                        var categoriaId = parseInt(checkbox.getAttribute('data-categoria-id'));
                        checkbox.checked = categoriasAtuais.indexOf(categoriaId) !== -1;
                    });
                    
                    // Armazenar produto ID no modal
                    modal.setAttribute('data-produto-id', produtoId);
                    
                    modal.style.display = 'flex';
                }
            }
        });

        // Fechar modal de categorias
        window.fecharModalCategorias = function() {
            var modal = document.getElementById('modal-categorias-produto');
            if (modal) {
                modal.style.display = 'none';
            }
        };

        // Fechar modal de categorias ao clicar no overlay
        var modalCategorias = document.getElementById('modal-categorias-produto');
        if (modalCategorias) {
            modalCategorias.addEventListener('click', function(e) {
                if (e.target === this) {
                    window.fecharModalCategorias();
                }
            });
        }

        // Salvar categorias
        var btnSalvarCategorias = document.getElementById('btn-salvar-categorias');
        if (btnSalvarCategorias) {
            btnSalvarCategorias.addEventListener('click', function() {
                var modal = document.getElementById('modal-categorias-produto');
                if (!modal) return;
                
                var produtoId = modal.getAttribute('data-produto-id');
                if (!produtoId) return;
                
                // Coletar categorias selecionadas
                var checkboxes = modal.querySelectorAll('.categoria-checkbox:checked');
                var categoriaIds = [];
                checkboxes.forEach(function(checkbox) {
                    categoriaIds.push(checkbox.value);
                });
                
                // Desabilitar botão e mostrar loading
                btnSalvarCategorias.disabled = true;
                var originalText = btnSalvarCategorias.innerHTML;
                btnSalvarCategorias.innerHTML = '<i class="bi bi-hourglass-split icon"></i> Salvando...';
                
                // Encontrar a linha da tabela correspondente
                var row = document.querySelector('[data-produto-id="' + produtoId + '"]');
                if (!row) {
                    // Tentar encontrar pela célula de categorias
                    var categoriasBtn = document.querySelector('.js-open-categorias-modal[data-id="' + produtoId + '"]');
                    if (categoriasBtn) {
                        row = categoriasBtn.closest('tr');
                    }
                }
                
                makeRequest(
                    basePath + '/admin/produtos/' + produtoId + '/atualizar-categorias',
                    'POST',
                    { categorias: categoriaIds },
                    function(error, response) {
                        btnSalvarCategorias.disabled = false;
                        btnSalvarCategorias.innerHTML = originalText;
                        
                        if (error || !response || !response.success) {
                            alert('Erro ao atualizar categorias: ' + (error || (response && response.message) || 'Erro desconhecido'));
                        } else {
                            // Atualizar coluna de categorias na tabela
                            if (row) {
                                // Encontrar célula de categorias procurando pelo botão
                                var cells = row.querySelectorAll('td');
                                var categoriasCell = null;
                                for (var i = 0; i < cells.length; i++) {
                                    var cell = cells[i];
                                    if (cell.querySelector('.js-open-categorias-modal')) {
                                        categoriasCell = cell;
                                        break;
                                    }
                                }
                                
                                if (categoriasCell) {
                                    // Estrutura esperada: 
                                    // <div style="display: flex...">
                                    //   <div style="flex: 1; min-width: 0;">
                                    //     <div style="display: flex...">badges</div> OU <span>Sem categorias</span>
                                    //   </div>
                                    //   <button>...</button>
                                    // </div>
                                    
                                    var primeiroDiv = categoriasCell.querySelector('div');
                                    var containerFlex = null;
                                    
                                    if (primeiroDiv) {
                                        // Procurar pelo primeiro div filho que não seja o botão
                                        var filhos = primeiroDiv.children;
                                        for (var j = 0; j < filhos.length; j++) {
                                            var filho = filhos[j];
                                            if (filho.tagName === 'DIV' && !filho.classList.contains('js-open-categorias-modal')) {
                                                containerFlex = filho;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    if (containerFlex) {
                                        // Atualizar conteúdo do container flex
                                        // Limpar tudo e inserir novo conteúdo
                                        if (response.categorias_labels_html) {
                                            containerFlex.innerHTML = response.categorias_labels_html;
                                        } else {
                                            containerFlex.innerHTML = '<span style="color: #999; font-style: italic; font-size: 0.875rem;">Sem categorias</span>';
                                        }
                                    } else {
                                        // Se não encontrou, reconstruir toda a estrutura
                                        var btn = categoriasCell.querySelector('.js-open-categorias-modal');
                                        if (btn) {
                                            var categoriasHtml = response.categorias_labels_html || '<span style="color: #999; font-style: italic; font-size: 0.875rem;">Sem categorias</span>';
                                            categoriasCell.innerHTML = '<div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;"><div style="flex: 1; min-width: 0;">' + 
                                                categoriasHtml + 
                                                '</div>' + btn.outerHTML + '</div>';
                                        }
                                    }
                                    
                                    // Atualizar data-categorias do botão com os IDs retornados pelo servidor
                                    var categoriasBtn = categoriasCell.querySelector('.js-open-categorias-modal');
                                    if (categoriasBtn && response.categoria_ids) {
                                        categoriasBtn.setAttribute('data-categorias', JSON.stringify(response.categoria_ids));
                                    } else if (categoriasBtn) {
                                        // Se não houver IDs retornados, usar os IDs enviados
                                        categoriasBtn.setAttribute('data-categorias', JSON.stringify(categoriaIds));
                                    }
                                }
                            }
                            
                            // Fechar modal
                            window.fecharModalCategorias();
                        }
                    }
                );
            });
        }
    });
})();

