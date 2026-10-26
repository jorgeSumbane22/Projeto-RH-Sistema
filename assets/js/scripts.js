/**
 * scripts.js
 * JavaScript compartilhado por todas as páginas do sistema.
 * Com suporte completo a Dark/Light Mode
 */

document.addEventListener('DOMContentLoaded', function () {
    // ---------------------------------------------------------------
    // DARK MODE: alterna data-bs-theme e persiste a escolha no navegador.
    // A leitura inicial (para evitar "flash" de tela clara) já acontece
    // num <script> inline no <head> de cada página, antes do CSS carregar.
    // ---------------------------------------------------------------
    function aplicarIconeTema(tema) {
        document.querySelectorAll('.js-toggle-tema i').forEach(function (icon) {
            icon.className = tema === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        });
    }
    
    // Aplica o ícone correto baseado no tema atual
    var temaAtual = document.documentElement.getAttribute('data-bs-theme') || 'light';
    aplicarIconeTema(temaAtual);

    document.querySelectorAll('.js-toggle-tema').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var atual = document.documentElement.getAttribute('data-bs-theme') || 'light';
            var novo = atual === 'dark' ? 'light' : 'dark';
            
            // Aplica o novo tema
            document.documentElement.setAttribute('data-bs-theme', novo);
            localStorage.setItem('rh_tema', novo);
            aplicarIconeTema(novo);

            // Persiste também no servidor (best-effort, não bloqueia a UI)
            var campoCsrf = document.getElementById('csrfGlobal');
            if (campoCsrf) {
                fetch(window.RH_BASE_URL + '/modules/perfil/tema.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'csrf_token=' + encodeURIComponent(campoCsrf.value) + '&tema=' + novo
                }).catch(function () { /* silencioso: localStorage já garante a experiência */ });
            }
            
            // Força atualização dos gráficos se existirem
            if (window.Chart && window.updateCharts) {
                setTimeout(window.updateCharts, 100);
            }
        });
    });

    // ---------------------------------------------------------------
    // TOGGLE DA SIDEBAR EM TELAS PEQUENAS
    // ---------------------------------------------------------------
    const btnToggle = document.getElementById('btnToggleSidebar');
    const sidebar = document.querySelector('.rh-sidebar');
    if (btnToggle && sidebar) {
        btnToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
        
        // Fecha sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(e.target) && !btnToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // ---------------------------------------------------------------
    // CONFIRMAÇÃO GENÉRICA ANTES DE AÇÕES DESTRUTIVAS
    // Uso: <a href="..." class="js-confirm" data-confirm-msg="Tem certeza?">
    // ---------------------------------------------------------------
    document.querySelectorAll('.js-confirm').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.dataset.confirmMsg || 'Tem certeza que deseja continuar?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ---------------------------------------------------------------
    // ESTADO DE CARREGAMENTO DOS FORMULÁRIOS
    // ---------------------------------------------------------------
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) return;
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(function (button) {
                if (button.dataset.loadingApplied === '1') return;
                button.dataset.originalText = button.innerHTML;
                button.dataset.loadingApplied = '1';
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>A processar...';
            });
        });
    });

    // ---------------------------------------------------------------
    // FECHA ALERTAS AUTOMATICAMENTE APÓS ALGUNS SEGUNDOS
    // ---------------------------------------------------------------
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function (alertEl) {
        setTimeout(function () {
            try {
                const alert = bootstrap.Alert.getOrCreateInstance(alertEl);
                if (alert) alert.close();
            } catch(e) {
                // Fallback: remove manualmente
                if (alertEl) {
                    alertEl.classList.remove('show');
                    setTimeout(function() { alertEl.remove(); }, 300);
                }
            }
        }, 6000);
    });

    // ---------------------------------------------------------------
    // CÁLCULO AUTOMÁTICO DE DIAS ENTRE DUAS DATAS (usado em férias)
    // ---------------------------------------------------------------
    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');
    const totalDiasEl = document.getElementById('total_dias_preview');
    
    function atualizarDias() {
        if (dataInicio && dataFim && dataInicio.value && dataFim.value && totalDiasEl) {
            const inicio = new Date(dataInicio.value);
            const fim = new Date(dataFim.value);
            if (fim >= inicio) {
                const diff = Math.round((fim - inicio) / (1000 * 60 * 60 * 24)) + 1;
                totalDiasEl.textContent = diff > 0 ? diff + ' dia(s)' : '—';
                totalDiasEl.style.color = 'var(--rh-text-primary)';
            } else {
                totalDiasEl.textContent = '⚠️ Data final inválida';
                totalDiasEl.style.color = 'var(--rh-danger)';
            }
        } else if (totalDiasEl) {
            totalDiasEl.textContent = '—';
        }
    }
    
    if (dataInicio && dataFim) {
        dataInicio.addEventListener('change', atualizarDias);
        dataFim.addEventListener('change', atualizarDias);
        // Executa uma vez para verificar se já há valores
        atualizarDias();
    }

    // ---------------------------------------------------------------
    // CÁLCULO AUTOMÁTICO DA NOTA FINAL NA AVALIAÇÃO DE DESEMPENHO
    // ---------------------------------------------------------------
    const camposNota = ['pontualidade', 'qualidade_trabalho', 'trabalho_equipe', 'proatividade'];
    const notaFinalEl = document.getElementById('nota_final_preview');
    
    if (notaFinalEl && camposNota.every(id => document.getElementById(id))) {
        const recalcular = function () {
            let soma = 0;
            let valido = true;
            camposNota.forEach(id => {
                const input = document.getElementById(id);
                const valor = Number(input.value || 0);
                if (valor < 1 || valor > 5) valido = false;
                soma += valor;
            });
            if (valido && soma > 0) {
                const media = soma / camposNota.length;
                notaFinalEl.textContent = media.toFixed(2);
                notaFinalEl.style.color = media >= 4 ? 'var(--rh-accent)' : media >= 3 ? 'var(--rh-warning)' : 'var(--rh-danger)';
            } else {
                notaFinalEl.textContent = '-';
                notaFinalEl.style.color = '';
            }
        };
        
        camposNota.forEach(id => {
            const input = document.getElementById(id);
            input.addEventListener('input', recalcular);
            input.addEventListener('change', function() {
                let val = parseInt(this.value);
                if (isNaN(val) || val < 1) this.value = 1;
                if (val > 5) this.value = 5;
                recalcular();
            });
        });
        recalcular();
    }

    // ---------------------------------------------------------------
    // STATUS DE CARREGAMENTO PARA PÁGINAS (loading overlay)
    // ---------------------------------------------------------------
    document.querySelectorAll('.rh-loading').forEach(function(el) {
        el.classList.remove('rh-loading');
    });

    // ---------------------------------------------------------------
    // MASCARAS DE FORMULÁRIO (opcional - melhorada)
    // ---------------------------------------------------------------
    // Telefone
    document.querySelectorAll('input[name="telefone"]').forEach(function(input) {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 2) {
                value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
            }
            if (value.length > 10) {
                value = value.slice(0, 10) + '-' + value.slice(10);
            }
            this.value = value;
        });
    });

    // CPF/BI
    document.querySelectorAll('input[name="cpf"]').forEach(function(input) {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);
            if (value.length > 3) {
                value = value.slice(0, 3) + '.' + value.slice(3);
            }
            if (value.length > 7) {
                value = value.slice(0, 7) + '.' + value.slice(7);
            }
            if (value.length > 11) {
                value = value.slice(0, 11) + '-' + value.slice(11);
            }
            this.value = value;
        });
    });

    // ---------------------------------------------------------------
    // TOOLTIPS DO BOOTSTRAP (inicialização)
    // ---------------------------------------------------------------
    try {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    } catch(e) {
        // Bootstrap não disponível
    }

    // ---------------------------------------------------------------
    // POPOVERS DO BOOTSTRAP (inicialização)
    // ---------------------------------------------------------------
    try {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    } catch(e) {
        // Bootstrap não disponível
    }

    // ---------------------------------------------------------------
    // FUNÇÃO GLOBAL PARA MENSAGENS FLASH
    // ---------------------------------------------------------------
    window.showMessage = function(type, message) {
        const container = document.querySelector('#message-container');
        if (!container) {
            // Cria container se não existir
            const newContainer = document.createElement('div');
            newContainer.id = 'message-container';
            newContainer.className = 'container mt-3';
            const main = document.querySelector('main');
            if (main) {
                main.insertBefore(newContainer, main.firstChild);
            } else {
                document.body.prepend(newContainer);
            }
            return window.showMessage(type, message);
        }
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type + ' alert-dismissible fade show alert-permanent';
        alert.role = 'alert';
        alert.innerHTML = message + 
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>';
        
        container.appendChild(alert);
        
        // Fecha automaticamente após 5 segundos
        setTimeout(function() {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            } catch(e) {
                alert.classList.remove('show');
                setTimeout(function() { alert.remove(); }, 300);
            }
        }, 5000);
    };

    // ---------------------------------------------------------------
    // ATUALIZAÇÃO DE GRÁFICOS AO MUDAR TEMA
    // ---------------------------------------------------------------
    window.updateCharts = function() {
        if (!window.Chart) return;
        
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#e8edf3' : '#0f1724';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        
        // Atualiza todos os gráficos existentes
        document.querySelectorAll('canvas').forEach(function(canvas) {
            const chart = Chart.getChart(canvas);
            if (chart) {
                // Atualiza cores
                if (chart.options.scales) {
                    if (chart.options.scales.x && chart.options.scales.x.ticks) {
                        chart.options.scales.x.ticks.color = textColor;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.ticks) {
                        chart.options.scales.y.ticks.color = textColor;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.grid) {
                        chart.options.scales.y.grid.color = gridColor;
                    }
                }
                if (chart.options.plugins && chart.options.plugins.legend) {
                    if (chart.options.plugins.legend.labels) {
                        chart.options.plugins.legend.labels.color = textColor;
                    }
                }
                chart.update();
            }
        });
    };

    // Escuta mudanças no tema para atualizar gráficos
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-bs-theme') {
                if (window.updateCharts) {
                    setTimeout(window.updateCharts, 200);
                }
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true });
});

// ============================================================
// FUNÇÃO PARA FORMATAR MOEDA (global)
// ============================================================
window.formatMoney = function(value, symbol = 'MT') {
    if (isNaN(value)) return symbol + ' 0,00';
    return symbol + ' ' + Number(value).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

// ============================================================
// FUNÇÃO PARA FORMATAR DATA (global)
// ============================================================
window.formatDate = function(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    } catch(e) {
        return dateString;
    }
};

// ============================================================
// FUNÇÃO PARA TRUNCAR TEXTO (global)
// ============================================================
window.truncateText = function(text, maxLength = 100, suffix = '...') {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.slice(0, maxLength) + suffix;
};