<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações do Sistema';
require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   CONFIGURAÇÕES - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER ----- */
.config-header {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border-radius: var(--rh-radius);
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.config-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.config-header::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}

.config-header .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.config-header .header-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.config-header .header-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.1);
}

.config-header .header-title {
    color: #fff;
}

.config-header .header-title h4 {
    font-weight: 700;
    margin: 0;
    font-size: 1.5rem;
    letter-spacing: -0.5px;
}

.config-header .header-title p {
    color: rgba(255,255,255,0.75);
    margin: 0;
    font-size: 0.9rem;
}

.config-header .header-badge {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 0.4rem 1rem;
    border-radius: 50px;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    border: 1px solid rgba(255,255,255,0.1);
}

/* ----- GRID ----- */
.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

/* ----- CARD PREMIUM ----- */
.config-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.75rem 1.5rem;
    text-decoration: none;
    color: var(--bs-body-color);
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.config-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--rh-primary), var(--rh-accent));
    opacity: 0;
    transition: opacity 0.4s ease;
}

.config-card:hover::before {
    opacity: 1;
}

.config-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--rh-shadow-hover);
    text-decoration: none;
    color: var(--bs-body-color);
    border-color: var(--rh-primary);
}

.config-card .card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    width: 100%;
    margin-bottom: 1rem;
}

.config-card .config-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.config-card:hover .config-icon {
    transform: scale(1.05) rotate(-3deg);
}

.config-card .config-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}

.config-card .config-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}

.config-card .config-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}

.config-card .config-icon.danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

.config-card .config-icon.purple {
    background: rgba(139, 92, 246, 0.12);
    color: #8b5cf6;
}

.config-card .config-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.config-card .config-icon.pink {
    background: rgba(236, 72, 153, 0.12);
    color: #ec4899;
}

.config-card .config-icon.orange {
    background: rgba(234, 88, 12, 0.12);
    color: #ea580c;
}

.config-card .config-icon.teal {
    background: rgba(20, 184, 166, 0.12);
    color: #14b8a6;
}

.config-card .card-arrow {
    color: var(--rh-text-muted);
    font-size: 1.1rem;
    transition: all 0.3s ease;
    opacity: 0.5;
}

.config-card:hover .card-arrow {
    opacity: 1;
    transform: translateX(4px);
    color: var(--rh-primary);
}

.config-card h6 {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.3rem;
    color: var(--bs-body-color);
}

.config-card p {
    font-size: 0.8rem;
    color: var(--rh-text-muted);
    margin: 0 0 0.75rem 0;
    flex: 1;
    line-height: 1.5;
}

.config-card .card-footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding-top: 0.75rem;
    border-top: 1px solid var(--rh-border);
    margin-top: auto;
}

.config-card .card-footer .badge-status {
    font-size: 0.55rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.config-card .card-footer .badge-status.ready {
    background: var(--rh-success-bg);
    color: var(--rh-success-text);
}

.config-card .card-footer .badge-status.pending {
    background: var(--rh-warning-bg);
    color: var(--rh-warning-text);
}

.config-card .card-footer .badge-status.coming {
    background: var(--rh-info-bg);
    color: var(--rh-info-text);
}

.config-card .card-footer small {
    color: var(--rh-text-muted);
    font-size: 0.65rem;
}

/* ----- STATS BAR ----- */
.config-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.config-stat {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
}

.config-stat:hover {
    transform: translateY(-2px);
    box-shadow: var(--rh-shadow-hover);
}

.config-stat .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--rh-primary);
}

.config-stat .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .config-header {
        padding: 1.5rem;
    }
    
    .config-header .header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .config-header .header-left {
        flex-direction: column;
        text-align: center;
    }
    
    .config-header .header-icon {
        width: 50px;
        height: 50px;
        font-size: 1.4rem;
    }
    
    .config-grid {
        grid-template-columns: 1fr;
    }
    
    .config-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .config-stats {
        grid-template-columns: 1fr;
    }
}

/* ----- DARK MODE ----- */
[data-bs-theme="dark"] .config-header {
    background: linear-gradient(135deg, #0a2a38, #0e7490);
}

[data-bs-theme="dark"] .config-card:hover {
    border-color: var(--rh-primary-light);
}

/* ----- ANIMAÇÕES ----- */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.config-card {
    animation: fadeInUp 0.5s ease forwards;
    opacity: 0;
}

.config-card:nth-child(1) { animation-delay: 0.05s; }
.config-card:nth-child(2) { animation-delay: 0.10s; }
.config-card:nth-child(3) { animation-delay: 0.15s; }
.config-card:nth-child(4) { animation-delay: 0.20s; }
.config-card:nth-child(5) { animation-delay: 0.25s; }
.config-card:nth-child(6) { animation-delay: 0.30s; }
.config-card:nth-child(7) { animation-delay: 0.35s; }
.config-card:nth-child(8) { animation-delay: 0.40s; }
.config-card:nth-child(9) { animation-delay: 0.45s; }
.config-card:nth-child(10) { animation-delay: 0.50s; }
</style>

<!-- ============================================================
     HEADER PREMIUM
     ============================================================ -->
<div class="config-header">
    <div class="header-content">
        <div class="header-left">
            <div class="header-icon">
                <i class="bi bi-sliders2"></i>
            </div>
            <div class="header-title">
                <h4>Configurações do Sistema</h4>
                <p>Gerencie todas as configurações do RH de forma centralizada</p>
            </div>
        </div>
        <div class="header-badge">
            <i class="bi bi-shield-check me-1"></i> Administração
        </div>
    </div>
</div>

<!-- ============================================================
     STATS
     ============================================================ -->
<div class="config-stats">
    <div class="config-stat">
        <div class="stat-number">8</div>
        <div class="stat-label">Módulos</div>
    </div>
    <div class="config-stat">
        <div class="stat-number">32</div>
        <div class="stat-label">Opções</div>
    </div>
    <div class="config-stat">
        <div class="stat-number">✓</div>
        <div class="stat-label">Todos configurados</div>
    </div>
    <div class="config-stat">
        <div class="stat-number" style="color:var(--rh-accent);">100%</div>
        <div class="stat-label">Disponibilidade</div>
    </div>
</div>

<!-- ============================================================
     GRID DE CONFIGURAÇÕES
     ============================================================ -->
<div class="config-grid">
    <!-- Empresa -->
    <a href="empresa.php" class="config-card">
        <div class="card-top">
            <div class="config-icon primary"><i class="bi bi-building"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Empresa</h6>
        <p>Dados cadastrais, logo, endereço e informações institucionais da organização</p>
        <div class="card-footer">
            <span class="badge-status ready">Configurado</span>
            <small>última atualização: hoje</small>
        </div>
    </a>

    <!-- Usuários -->
    <a href="usuarios.php" class="config-card">
        <div class="card-top">
            <div class="config-icon success"><i class="bi bi-people"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Usuários</h6>
        <p>Políticas de acesso, cadastro, permissões e níveis de usuários do sistema</p>
        <div class="card-footer">
            <span class="badge-status ready">Configurado</span>
            <small>5 usuários ativos</small>
        </div>
    </a>

    <!-- Segurança -->
    <a href="seguranca.php" class="config-card">
        <div class="card-top">
            <div class="config-icon danger"><i class="bi bi-shield-lock"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Segurança</h6>
        <p>Autenticação, políticas de senhas, 2FA, sessões e controle de acesso</p>
        <div class="card-footer">
            <span class="badge-status ready">Configurado</span>
            <small>2FA disponível</small>
        </div>
    </a>

    <!-- Notificações -->
    <a href="notificacoes.php" class="config-card">
        <div class="card-top">
            <div class="config-icon warning"><i class="bi bi-bell"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Notificações</h6>
        <p>Alertas, lembretes, e-mails, push notifications e configurações de envio</p>
        <div class="card-footer">
            <span class="badge-status ready">Configurado</span>
            <small>SMTP ativo</small>
        </div>
    </a>

    <!-- Folha de Pagamento -->
    <a href="folha.php" class="config-card">
        <div class="card-top">
            <div class="config-icon info"><i class="bi bi-cash-stack"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Folha de Pagamento</h6>
        <p>Parâmetros de cálculo, impostos, benefícios, encargos e configurações financeiras</p>
        <div class="card-footer">
            <span class="badge-status ready">Configurado</span>
            <small>INSS 11% • IRRF 7.5%</small>
        </div>
    </a>

    <!-- Férias -->
    <a href="ferias.php" class="config-card">
        <div class="card-top">
            <div class="config-icon purple"><i class="bi bi-umbrella"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Férias e Licenças</h6>
        <p>Períodos aquisitivos, dias de férias, licenças médicas, maternidade e nojo</p>
        <div class="card-footer">
            <span class="badge-status ready">Configurado</span>
            <small>30 dias • 12 meses</small>
        </div>
    </a>

    <!-- Sistema -->
    <a href="sistema.php" class="config-card">
        <div class="card-top">
            <div class="config-icon primary"><i class="bi bi-gear"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Sistema</h6>
        <p>Manutenção, backup, logs de auditoria, limpeza de cache e informações técnicas</p>
        <div class="card-footer">
            <span class="badge-status ready">Configurado</span>
            <small>v2.0.1 • PHP <?= phpversion() ?></small>
        </div>
    </a>

    <!-- Integrações (adicional) -->
    <a href="integracoes.php" class="config-card">
        <div class="card-top">
            <div class="config-icon teal"><i class="bi bi-plug"></i></div>
            <span class="card-arrow"><i class="bi bi-arrow-right"></i></span>
        </div>
        <h6>Integrações</h6>
        <p>APIs, webhooks, integração com sistemas externos e serviços de terceiros</p>
        <div class="card-footer">
            <span class="badge-status coming">Em breve</span>
            <small>2 integrações disponíveis</small>
        </div>
    </a>
</div>

<!-- ============================================================
     FOOTER INFO
     ============================================================ -->
<div class="mt-4 p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--rh-border);">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <i class="bi bi-info-circle" style="color:var(--rh-primary);font-size:1.1rem;"></i>
        <span style="font-size:0.8rem;color:var(--rh-text-muted);">
            <strong class="text-body">Dica:</strong> As configurações afetam todo o sistema. 
            Recomendamos revisar cada seção e salvar as alterações após modificações.
        </span>
        <span class="badge bg-light text-dark ms-auto" style="font-size:0.6rem;">
            <i class="bi bi-clock me-1"></i> <?= date('d/m/Y H:i') ?>
        </span>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>