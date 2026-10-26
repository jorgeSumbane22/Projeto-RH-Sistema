<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_AUDITOR, PERFIL_ADMIN_RH]);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Dashboard do Auditor - Painel de Monitoramento';

// ============================================================
// FILTROS
// ============================================================
$inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['inicio'] ?? '') ? $_GET['inicio'] : date('Y-m-01');
$fim = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fim'] ?? '') ? $_GET['fim'] : date('Y-m-d');
$params = [':inicio' => $inicio . ' 00:00:00', ':fim' => $fim . ' 23:59:59'];

$db = Database::getConnection();

// ============================================================
// KPIs PRINCIPAIS
// ============================================================

$totalEventos = (int) dashScalar('SELECT COUNT(*) FROM log_auditoria WHERE criado_em BETWEEN :inicio AND :fim', $params);
$acessos = (int) dashScalar('SELECT COUNT(*) FROM log_auditoria WHERE acao IN ("login", "logout") AND criado_em BETWEEN :inicio AND :fim', $params);
$alteracoes = (int) dashScalar('SELECT COUNT(*) FROM log_auditoria WHERE acao NOT IN ("login", "logout") AND criado_em BETWEEN :inicio AND :fim', $params);
$acoesCriticas = (int) dashScalar('SELECT COUNT(*) FROM log_auditoria WHERE (acao LIKE "%excluido%" OR acao LIKE "%delete%" OR acao LIKE "%desligar%" OR acao LIKE "%rejeitado%") AND criado_em BETWEEN :inicio AND :fim', $params);
$eventosSuspeitos = (int) dashScalar('SELECT COUNT(*) FROM log_auditoria WHERE (acao LIKE "%falha%" OR acao LIKE "%tentativa%" OR acao LIKE "%bloqueado%") AND criado_em BETWEEN :inicio AND :fim', $params);
$utilizadoresMonitorizados = (int) dashScalar('SELECT COUNT(*) FROM usuarios WHERE ativo = 1');
$contasBloqueadas = (int) dashScalar('SELECT COUNT(*) FROM usuarios WHERE bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()');
$tiposAcoes = dashRows('SELECT acao, COUNT(*) as total FROM log_auditoria WHERE criado_em BETWEEN :inicio AND :fim GROUP BY acao ORDER BY total DESC LIMIT 10', $params);

// ============================================================
// ALERTAS DE AUDITORIA
// ============================================================

$tentativasFalhas = dashRows('SELECT l.*, u.nome as usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE (l.acao LIKE "%falha%" OR l.acao LIKE "%tentativa%") AND l.criado_em BETWEEN :inicio AND :fim ORDER BY l.criado_em DESC LIMIT 20', $params);
$alteracoesPorUsuario = dashRows('SELECT l.usuario_id, u.nome as usuario_nome, COUNT(*) as total FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE l.acao NOT IN ("login", "logout") AND l.criado_em BETWEEN :inicio AND :fim GROUP BY l.usuario_id, u.nome HAVING total > 10 ORDER BY total DESC', $params);
$alteracoesSalario = dashRows('SELECT l.*, u.nome as usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE (l.acao LIKE "%salario%" OR l.acao LIKE "%folha%") AND l.criado_em BETWEEN :inicio AND :fim ORDER BY l.criado_em DESC LIMIT 20', $params);
$alteracoesPermissoes = dashRows('SELECT l.*, u.nome as usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE (l.acao LIKE "%perfil%" OR l.acao LIKE "%permissao%" OR l.acao LIKE "%usuario_%") AND l.criado_em BETWEEN :inicio AND :fim ORDER BY l.criado_em DESC LIMIT 20', $params);
$exclusoesDados = dashRows('SELECT l.*, u.nome as usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE (l.acao LIKE "%excluido%" OR l.acao LIKE "%delete%" OR l.acao LIKE "%removido%") AND l.criado_em BETWEEN :inicio AND :fim ORDER BY l.criado_em DESC LIMIT 20', $params);
$acessosForaPadrao = dashRows('SELECT l.*, u.nome as usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE l.acao IN ("login") AND (HOUR(l.criado_em) < 6 OR HOUR(l.criado_em) > 22) AND l.criado_em BETWEEN :inicio AND :fim ORDER BY l.criado_em DESC LIMIT 20', $params);
$alteracoesSensiveis = dashRows('SELECT l.*, u.nome as usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE (l.acao LIKE "%documento%" OR l.acao LIKE "%contrato%" OR l.acao LIKE "%bi%" OR l.acao LIKE "%cpf%") AND l.criado_em BETWEEN :inicio AND :fim ORDER BY l.criado_em DESC LIMIT 20', $params);

// ============================================================
// GRÁFICOS
// ============================================================

$atividadesTempo = dashRows('SELECT DATE(criado_em) as data, COUNT(*) as total FROM log_auditoria WHERE criado_em BETWEEN :inicio AND :fim GROUP BY DATE(criado_em) ORDER BY data ASC', $params);
$acoesPorTipo = dashRows('SELECT acao, COUNT(*) as total FROM log_auditoria WHERE criado_em BETWEEN :inicio AND :fim GROUP BY acao ORDER BY total DESC LIMIT 10', $params);
$atividadesPorUsuario = dashRows('SELECT u.nome as usuario_nome, COUNT(l.id) as total FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE l.criado_em BETWEEN :inicio AND :fim GROUP BY l.usuario_id, u.nome ORDER BY total DESC LIMIT 10', $params);
$loginLogout = dashRows('SELECT SUM(CASE WHEN acao = "login" THEN 1 ELSE 0 END) as logins, SUM(CASE WHEN acao = "logout" THEN 1 ELSE 0 END) as logouts FROM log_auditoria WHERE criado_em BETWEEN :inicio AND :fim', $params);
$acoesCriticasPorDia = dashRows('SELECT DATE(criado_em) as data, COUNT(*) as total FROM log_auditoria WHERE (acao LIKE "%excluido%" OR acao LIKE "%delete%" OR acao LIKE "%desligar%" OR acao LIKE "%rejeitado%") AND criado_em BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW() GROUP BY DATE(criado_em) ORDER BY data ASC', []);
$alteracoesPorModulo = dashRows('SELECT SUM(CASE WHEN acao LIKE "%funcionario%" THEN 1 ELSE 0 END) as funcionarios, SUM(CASE WHEN acao LIKE "%departamento%" THEN 1 ELSE 0 END) as departamentos, SUM(CASE WHEN acao LIKE "%folha%" OR acao LIKE "%salario%" THEN 1 ELSE 0 END) as folha, SUM(CASE WHEN acao LIKE "%ferias%" THEN 1 ELSE 0 END) as ferias, SUM(CASE WHEN acao LIKE "%usuario%" THEN 1 ELSE 0 END) as usuarios, SUM(CASE WHEN acao LIKE "%documento%" THEN 1 ELSE 0 END) as documentos, SUM(CASE WHEN acao LIKE "%vagas%" OR acao LIKE "%candidato%" THEN 1 ELSE 0 END) as recrutamento FROM log_auditoria WHERE criado_em BETWEEN :inicio AND :fim', $params);

// ============================================================
// HEADER
// ============================================================
require __DIR__ . '/../../includes/header.php';
exibirMensagem();
?>

<style>
/* ============================================================
   DASHBOARD AUDITOR - DESIGN PREMIUM EXECUTIVO
   ============================================================ */

/* ----- Variáveis ----- */
:root {
    --audit-primary: #0f1724;
    --audit-secondary: #1e293b;
    --audit-accent: #155e75;
    --audit-accent-light: rgba(21,94,117,0.12);
    --audit-success: #10b981;
    --audit-danger: #ef4444;
    --audit-warning: #f59e0b;
    --audit-info: #3b82f6;
    --audit-purple: #8b5cf6;
    --audit-pink: #ec4899;
    --audit-orange: #f97316;
    --audit-radius: 16px;
    --audit-shadow: 0 4px 24px rgba(0,0,0,0.06);
    --audit-shadow-hover: 0 12px 48px rgba(0,0,0,0.12);
}

[data-bs-theme="dark"] {
    --audit-primary: #e8edf3;
    --audit-secondary: #94a3b8;
}

/* ----- Header ----- */
.audit-header-premium {
    background: linear-gradient(135deg, var(--rh-primary) 0%, #0a2635 100%);
    border-radius: var(--audit-radius);
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.audit-header-premium::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(20,184,166,0.08) 0%, transparent 70%);
    pointer-events: none;
}

.audit-header-premium .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.audit-header-premium .header-left h1 {
    color: #fff;
    font-weight: 800;
    font-size: 1.6rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.audit-header-premium .header-left h1 i {
    color: var(--rh-accent);
}

.audit-header-premium .header-left p {
    color: rgba(255,255,255,0.65);
    font-size: 0.9rem;
    margin: 0.25rem 0 0;
}

.audit-header-premium .header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.audit-header-premium .header-right .status-badge {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    padding: 0.4rem 1.2rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.audit-header-premium .header-right .status-badge .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--rh-success);
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

/* ----- Filter Bar ----- */
.filter-bar-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--audit-radius);
    padding: 1.25rem 1.75rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.filter-bar-premium:hover {
    box-shadow: var(--audit-shadow-hover);
}

.filter-bar-premium .form-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.filter-bar-premium .form-control {
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.filter-bar-premium .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
}

.filter-bar-premium .btn-filter {
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
    transition: all 0.3s ease;
    width: 100%;
}

.filter-bar-premium .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.25);
}

/* ----- KPI Cards Premium ----- */
.kpi-grid-premium {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (max-width: 1199.98px) {
    .kpi-grid-premium {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .kpi-grid-premium {
        grid-template-columns: 1fr;
    }
}

.kpi-card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--audit-radius);
    padding: 1.25rem 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.kpi-card-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--kpi-color, var(--rh-primary));
}

.kpi-card-premium:hover {
    transform: translateY(-4px);
    box-shadow: var(--audit-shadow-hover);
}

.kpi-card-premium .kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.kpi-card-premium .kpi-label {
    font-size: 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--rh-text-muted);
    font-weight: 700;
}

.kpi-card-premium .kpi-label i {
    margin-right: 4px;
}

.kpi-card-premium .kpi-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--rh-text-primary);
    line-height: 1.2;
    margin: 2px 0 4px;
    letter-spacing: -0.5px;
}

.kpi-card-premium .kpi-sub {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.kpi-card-premium .kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.kpi-card-premium:hover .kpi-icon {
    transform: scale(1.08) rotate(-4deg);
}

.kpi-card-premium .kpi-icon.primary { background: rgba(21,94,117,0.12); color: var(--rh-primary); }
.kpi-card-premium .kpi-icon.success { background: rgba(16,185,129,0.12); color: var(--rh-success); }
.kpi-card-premium .kpi-icon.warning { background: rgba(245,158,11,0.12); color: #f59e0b; }
.kpi-card-premium .kpi-icon.danger { background: rgba(239,68,68,0.12); color: #ef4444; }
.kpi-card-premium .kpi-icon.info { background: rgba(59,130,246,0.12); color: #3b82f6; }
.kpi-card-premium .kpi-icon.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
.kpi-card-premium .kpi-icon.secondary { background: rgba(107,124,136,0.12); color: #6b7c88; }

.kpi-card-premium .kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    margin-top: 4px;
}

.kpi-card-premium .kpi-trend.up { color: var(--rh-success); background: rgba(16,185,129,0.1); }
.kpi-card-premium .kpi-trend.down { color: #ef4444; background: rgba(239,68,68,0.1); }
.kpi-card-premium .kpi-trend.warning { color: #f59e0b; background: rgba(245,158,11,0.1); }

/* ----- Section Titles ----- */
.section-title-premium {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--rh-text-primary);
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title-premium i {
    color: var(--rh-accent);
}

.section-title-premium .badge-count {
    font-size: 0.6rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 500;
}

/* ----- Cards ----- */
.card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--audit-radius);
    padding: 1.25rem 1.5rem;
    transition: all 0.3s ease;
    height: 100%;
}

.card-premium:hover {
    box-shadow: var(--audit-shadow-hover);
}

.card-premium .card-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-premium .card-title i {
    color: var(--rh-accent);
}

/* ----- Alert Items ----- */
.alert-item-executive {
    padding: 0.6rem 0.8rem;
    border-radius: 10px;
    border-left: 4px solid var(--alert-color, var(--rh-primary));
    background: var(--bs-tertiary-bg);
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.alert-item-executive:hover {
    background: var(--bs-secondary-bg);
    transform: translateX(4px);
}

.alert-item-executive .alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.alert-item-executive .alert-title {
    font-weight: 600;
    font-size: 0.82rem;
    color: var(--rh-text-primary);
}

.alert-item-executive .alert-title i {
    margin-right: 6px;
}

.alert-item-executive .alert-desc {
    font-size: 0.72rem;
    color: var(--rh-text-muted);
    margin-top: 0.1rem;
}

.alert-item-executive .alert-time {
    font-size: 0.6rem;
    color: var(--rh-text-muted);
}

.alert-item-executive .badge-critical {
    font-size: 0.55rem;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    font-weight: 700;
}

.badge-critical.danger { background: #ef4444; color: #fff; }
.badge-critical.warning { background: #f59e0b; color: #fff; }
.badge-critical.info { background: #3b82f6; color: #fff; }
.badge-critical.success { background: #10b981; color: #fff; }

/* ----- Chart Container ----- */
.chart-container-premium {
    position: relative;
    min-height: 240px;
    width: 100%;
}

.chart-container-premium.tall {
    min-height: 280px;
}

/* ----- Empty State ----- */
.empty-state-premium {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.empty-state-premium .empty-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.4;
}

.empty-state-premium .empty-text {
    font-size: 0.85rem;
}

/* ----- Responsive ----- */
@media (max-width: 991.98px) {
    .audit-header-premium {
        padding: 1.5rem;
    }
    .audit-header-premium .header-left h1 {
        font-size: 1.3rem;
    }
    .kpi-grid-premium {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .audit-header-premium .header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    .audit-header-premium .header-right {
        justify-content: center;
    }
    .filter-bar-premium {
        padding: 1rem;
    }
    .kpi-card-premium {
        padding: 1rem;
    }
    .kpi-card-premium .kpi-value {
        font-size: 1.4rem;
    }
    .kpi-card-premium .kpi-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    .card-premium {
        padding: 1rem;
    }
    .chart-container-premium {
        min-height: 190px;
    }
    .chart-container-premium.tall {
        min-height: 220px;
    }
}

@media (max-width: 575.98px) {
    .kpi-grid-premium {
        grid-template-columns: 1fr;
    }
    .audit-header-premium .header-left h1 {
        font-size: 1.1rem;
    }
    .alert-item-executive .alert-header {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<!-- ============================================================
     HEADER PREMIUM
     ============================================================ -->
<div class="audit-header-premium">
    <div class="header-content">
        <div class="header-left">
            <h1>
                <i class="bi bi-shield-check"></i> Painel de Auditoria
                <span class="badge bg-light text-dark ms-2" style="font-size:0.6rem;padding:0.2rem 0.6rem;">
                    <?= date('d/m/Y') ?>
                </span>
            </h1>
            <p>Monitoramento de segurança, conformidade e integridade do sistema</p>
        </div>
        <div class="header-right">
            <span class="status-badge">
                <span class="dot"></span>
                Sistema monitorado
            </span>
            <span class="status-badge">
                <i class="bi bi-clock"></i>
                <?= date('H:i') ?>
            </span>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTER BAR PREMIUM
     ============================================================ -->
<div class="filter-bar-premium">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-sm-4 col-md-3">
            <label class="form-label"><i class="bi bi-calendar-start"></i> Data início</label>
            <input class="form-control" type="date" name="inicio" value="<?= e($inicio) ?>">
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label"><i class="bi bi-calendar-end"></i> Data fim</label>
            <input class="form-control" type="date" name="fim" value="<?= e($fim) ?>">
        </div>
        <div class="col-sm-4 col-md-2">
            <button class="btn-filter">
                <i class="bi bi-funnel me-1"></i> Aplicar filtro
            </button>
        </div>
        <div class="col-sm-4 col-md-2">
            <a href="dashboard.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;padding:0.5rem 1.5rem;font-weight:600;font-size:0.85rem;border-color:var(--rh-border);color:var(--rh-text-muted);">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpar
            </a>
        </div>
    </form>
</div>

<!-- ============================================================
     KPI GRID PREMIUM
     ============================================================ -->
<div class="kpi-grid-premium">
    <div class="kpi-card-premium" style="--kpi-color: var(--rh-primary);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-journal-text"></i> Total eventos</div>
                <div class="kpi-value"><?= number_format($totalEventos) ?></div>
                <div class="kpi-sub">No período selecionado</div>
            </div>
            <div class="kpi-icon primary"><i class="bi bi-journal-text"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> <?= number_format($totalEventos > 0 ? round($totalEventos / 30, 1) : 0) ?> / dia</div>
    </div>

    <div class="kpi-card-premium" style="--kpi-color: var(--rh-success);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-door-open"></i> Acessos e sessões</div>
                <div class="kpi-value"><?= number_format($acessos) ?></div>
                <div class="kpi-sub">Logins e Logouts</div>
            </div>
            <div class="kpi-icon success"><i class="bi bi-door-open"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> <?= number_format($acessos > 0 ? round($acessos / 30, 1) : 0) ?> / dia</div>
    </div>

    <div class="kpi-card-premium" style="--kpi-color: #f59e0b;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-pencil-square"></i> Alterações</div>
                <div class="kpi-value"><?= number_format($alteracoes) ?></div>
                <div class="kpi-sub">Modificações no sistema</div>
            </div>
            <div class="kpi-icon warning"><i class="bi bi-pencil-square"></i></div>
        </div>
        <div class="kpi-trend warning"><i class="bi bi-arrow-up"></i> <?= number_format($alteracoes > 0 ? round($alteracoes / 30, 1) : 0) ?> / dia</div>
    </div>

    <div class="kpi-card-premium" style="--kpi-color: #ef4444;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-exclamation-triangle"></i> Ações críticas</div>
                <div class="kpi-value"><?= number_format($acoesCriticas) ?></div>
                <div class="kpi-sub">Exclusões e desligamentos</div>
            </div>
            <div class="kpi-icon danger"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
        <div class="kpi-trend down"><i class="bi bi-arrow-down"></i> Monitorar</div>
    </div>

    <div class="kpi-card-premium" style="--kpi-color: #8b5cf6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-shield-exclamation"></i> Eventos suspeitos</div>
                <div class="kpi-value"><?= number_format($eventosSuspeitos) ?></div>
                <div class="kpi-sub">Alertas de segurança</div>
            </div>
            <div class="kpi-icon purple"><i class="bi bi-shield-exclamation"></i></div>
        </div>
        <div class="kpi-trend down"><i class="bi bi-arrow-down"></i> Atenção necessária</div>
    </div>

    <div class="kpi-card-premium" style="--kpi-color: #3b82f6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-people"></i> Utilizadores</div>
                <div class="kpi-value"><?= number_format($utilizadoresMonitorizados) ?></div>
                <div class="kpi-sub">Monitorizados ativos</div>
            </div>
            <div class="kpi-icon info"><i class="bi bi-people"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> <?= number_format($utilizadoresMonitorizados > 0 ? round($acessos / $utilizadoresMonitorizados, 1) : 0) ?> acessos/utilizador</div>
    </div>

    <div class="kpi-card-premium" style="--kpi-color: #ef4444;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-lock"></i> Contas bloqueadas</div>
                <div class="kpi-value"><?= number_format($contasBloqueadas) ?></div>
                <div class="kpi-sub">Temporariamente</div>
            </div>
            <div class="kpi-icon danger"><i class="bi bi-lock"></i></div>
        </div>
        <div class="kpi-trend <?= $contasBloqueadas > 0 ? 'down' : 'up' ?>">
            <i class="bi bi-arrow-<?= $contasBloqueadas > 0 ? 'down' : 'up' ?>"></i>
            <?= $contasBloqueadas > 0 ? 'Atenção' : 'Nenhuma bloqueada' ?>
        </div>
    </div>

    <div class="kpi-card-premium" style="--kpi-color: #6b7c88;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-tags"></i> Tipos de ações</div>
                <div class="kpi-value"><?= number_format(count($tiposAcoes)) ?></div>
                <div class="kpi-sub">Diferentes ações registadas</div>
            </div>
            <div class="kpi-icon secondary"><i class="bi bi-tags"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> Variedade de ações</div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 1
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-premium">
            <div class="card-title">
                <i class="bi bi-line-chart"></i> Atividades ao longo do tempo
                <span class="badge-count ms-auto">Últimos dias</span>
            </div>
            <div class="chart-container-premium tall">
                <canvas id="atividadesTempo"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-premium">
            <div class="card-title">
                <i class="bi bi-pie-chart"></i> Ações por tipo
                <span class="badge-count ms-auto">Top 10</span>
            </div>
            <div class="chart-container-premium tall">
                <canvas id="acoesPorTipo"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 2
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-premium">
            <div class="card-title">
                <i class="bi bi-person"></i> Atividades por utilizador
                <span class="badge-count ms-auto">Top 10</span>
            </div>
            <div class="chart-container-premium">
                <canvas id="atividadesPorUsuario"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-premium">
            <div class="card-title">
                <i class="bi bi-arrow-left-right"></i> Login vs Logout
                <span class="badge-count ms-auto">Comparação</span>
            </div>
            <div class="chart-container-premium">
                <canvas id="loginLogout"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 3
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-premium">
            <div class="card-title">
                <i class="bi bi-exclamation-triangle text-danger"></i> Ações críticas por período
                <span class="badge-count ms-auto">Últimos 7 dias</span>
            </div>
            <div class="chart-container-premium">
                <canvas id="acoesCriticasPorDia"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-premium">
            <div class="card-title">
                <i class="bi bi-building"></i> Alterações por módulo
                <span class="badge-count ms-auto">Distribuição</span>
            </div>
            <div class="chart-container-premium">
                <canvas id="alteracoesPorModulo"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ALERTAS DE AUDITORIA - ÁREA MAIS IMPORTANTE
     ============================================================ -->
<div class="card-premium mb-4" style="border-left: 4px solid #ef4444;">
    <div class="card-title" style="font-size:1rem;">
        <i class="bi bi-exclamation-triangle text-danger"></i> 🚨 Alertas de Auditoria
        <span class="badge-count ms-auto">
            <?= count($tentativasFalhas) + count($alteracoesPorUsuario) + count($alteracoesSalario) + count($alteracoesPermissoes) + count($exclusoesDados) + count($acessosForaPadrao) + count($alteracoesSensiveis) ?> alertas
        </span>
    </div>

    <div class="row g-3">
        <!-- 1. Tentativas de login falhadas -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-x-circle text-danger"></i> Tentativas falhadas</span>
                    <span class="badge-critical danger"><?= count($tentativasFalhas) ?></span>
                </div>
                <?php if (empty($tentativasFalhas)): ?>
                    <div class="empty-state-premium">
                        <span class="empty-icon"><i class="bi bi-check-circle text-success"></i></span>
                        <span class="empty-text">Nenhuma tentativa falhada</span>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($tentativasFalhas, 0, 4) as $t): ?>
                        <div class="alert-item-executive" style="--alert-color:#ef4444;">
                            <div class="alert-header">
                                <span class="alert-title"><i class="bi bi-person"></i> <?= e($t['usuario_nome'] ?? 'Desconhecido') ?></span>
                                <span class="alert-time"><?= formatarDataHora($t['criado_em']) ?></span>
                            </div>
                            <div class="alert-desc"><?= e($t['detalhes'] ?? 'Tentativa falhada') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Muitas alterações -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-person-plus text-warning"></i> Muitas alterações</span>
                    <span class="badge-critical warning"><?= count($alteracoesPorUsuario) ?></span>
                </div>
                <?php if (empty($alteracoesPorUsuario)): ?>
                    <div class="empty-state-premium">
                        <span class="empty-icon"><i class="bi bi-check-circle text-success"></i></span>
                        <span class="empty-text">Nenhum utilizador excessivo</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($alteracoesPorUsuario as $a): ?>
                        <div class="alert-item-executive" style="--alert-color:#f59e0b;">
                            <div class="alert-header">
                                <span class="alert-title"><i class="bi bi-person"></i> <?= e($a['usuario_nome'] ?? 'Desconhecido') ?></span>
                                <span class="badge-critical warning"><?= $a['total'] ?> alterações</span>
                            </div>
                            <div class="alert-desc">Realizou <?= $a['total'] ?> alterações no período</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Alterações de salário -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-cash-stack text-success"></i> Alterações de salário</span>
                    <span class="badge-critical info"><?= count($alteracoesSalario) ?></span>
                </div>
                <?php if (empty($alteracoesSalario)): ?>
                    <div class="empty-state-premium">
                        <span class="empty-icon"><i class="bi bi-check-circle text-success"></i></span>
                        <span class="empty-text">Nenhuma alteração salarial</span>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($alteracoesSalario, 0, 4) as $a): ?>
                        <div class="alert-item-executive" style="--alert-color:#10b981;">
                            <div class="alert-header">
                                <span class="alert-title"><i class="bi bi-person"></i> <?= e($a['usuario_nome'] ?? 'Sistema') ?></span>
                                <span class="alert-time"><?= formatarDataHora($a['criado_em']) ?></span>
                            </div>
                            <div class="alert-desc"><?= e($a['detalhes'] ?? 'Alteração salarial') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Exclusões de dados -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-trash text-danger"></i> Exclusões de dados</span>
                    <span class="badge-critical danger"><?= count($exclusoesDados) ?></span>
                </div>
                <?php if (empty($exclusoesDados)): ?>
                    <div class="empty-state-premium">
                        <span class="empty-icon"><i class="bi bi-check-circle text-success"></i></span>
                        <span class="empty-text">Nenhuma exclusão registada</span>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($exclusoesDados, 0, 4) as $e): ?>
                        <div class="alert-item-executive" style="--alert-color:#ef4444;">
                            <div class="alert-header">
                                <span class="alert-title"><i class="bi bi-person"></i> <?= e($e['usuario_nome'] ?? 'Sistema') ?></span>
                                <span class="alert-time"><?= formatarDataHora($e['criado_em']) ?></span>
                            </div>
                            <div class="alert-desc"><?= e($e['detalhes'] ?? 'Exclusão de dados') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Linha 2 dos Alertas -->
    <div class="row g-3 mt-2">
        <!-- 5. Acessos fora do padrão -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-clock text-warning"></i> Acessos fora do padrão</span>
                    <span class="badge-critical warning"><?= count($acessosForaPadrao) ?></span>
                </div>
                <?php if (empty($acessosForaPadrao)): ?>
                    <div class="empty-state-premium">
                        <span class="empty-icon"><i class="bi bi-check-circle text-success"></i></span>
                        <span class="empty-text">Nenhum acesso anormal</span>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($acessosForaPadrao, 0, 4) as $a): ?>
                        <div class="alert-item-executive" style="--alert-color:#f59e0b;">
                            <div class="alert-header">
                                <span class="alert-title"><i class="bi bi-person"></i> <?= e($a['usuario_nome'] ?? 'Desconhecido') ?></span>
                                <span class="alert-time"><?= formatarDataHora($a['criado_em']) ?></span>
                            </div>
                            <div class="alert-desc">Acesso fora do horário comercial</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 6. Alterações de permissões -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-shield-lock text-info"></i> Alterações de permissões</span>
                    <span class="badge-critical info"><?= count($alteracoesPermissoes) ?></span>
                </div>
                <?php if (empty($alteracoesPermissoes)): ?>
                    <div class="empty-state-premium">
                        <span class="empty-icon"><i class="bi bi-check-circle text-success"></i></span>
                        <span class="empty-text">Nenhuma alteração de permissões</span>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($alteracoesPermissoes, 0, 4) as $a): ?>
                        <div class="alert-item-executive" style="--alert-color:#3b82f6;">
                            <div class="alert-header">
                                <span class="alert-title"><i class="bi bi-person"></i> <?= e($a['usuario_nome'] ?? 'Sistema') ?></span>
                                <span class="alert-time"><?= formatarDataHora($a['criado_em']) ?></span>
                            </div>
                            <div class="alert-desc"><?= e($a['detalhes'] ?? 'Alteração de permissões') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 7. Dados sensíveis alterados -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-shield-exclamation text-danger"></i> Dados sensíveis alterados</span>
                    <span class="badge-critical danger"><?= count($alteracoesSensiveis) ?></span>
                </div>
                <?php if (empty($alteracoesSensiveis)): ?>
                    <div class="empty-state-premium">
                        <span class="empty-icon"><i class="bi bi-check-circle text-success"></i></span>
                        <span class="empty-text">Nenhuma alteração sensível</span>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($alteracoesSensiveis, 0, 4) as $a): ?>
                        <div class="alert-item-executive" style="--alert-color:#ef4444;">
                            <div class="alert-header">
                                <span class="alert-title"><i class="bi bi-person"></i> <?= e($a['usuario_nome'] ?? 'Sistema') ?></span>
                                <span class="alert-time"><?= formatarDataHora($a['criado_em']) ?></span>
                            </div>
                            <div class="alert-desc"><?= e($a['detalhes'] ?? 'Alteração em dados sensíveis') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 8. Contas bloqueadas -->
        <div class="col-xl-3 col-lg-6">
            <div class="p-2" style="border-radius:10px;background:var(--bs-tertiary-bg);height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-lock text-danger"></i> Contas bloqueadas</span>
                    <span class="badge-critical danger"><?= number_format($contasBloqueadas) ?></span>
                </div>
                <div class="text-center py-3">
                    <div class="display-4 fw-bold" style="color:<?= $contasBloqueadas > 0 ? '#ef4444' : 'var(--rh-success)' ?>;">
                        <?= number_format($contasBloqueadas) ?>
                    </div>
                    <div class="text-muted small">contas temporariamente bloqueadas</div>
                    <?php if ($contasBloqueadas > 0): ?>
                        <div class="mt-2">
                            <span class="badge-critical danger"><i class="bi bi-exclamation-triangle"></i> Atenção necessária</span>
                        </div>
                    <?php else: ?>
                        <div class="mt-2">
                            <span class="badge-critical success"><i class="bi bi-check-circle"></i> Sistema seguro</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS DOS GRÁFICOS
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const textColor = isDark ? '#e8edf3' : '#0f1724';
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const borderColor = isDark ? '#2d2d44' : '#eef2f6';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Segoe UI', system-ui, -apple-system, sans-serif";
    Chart.defaults.font.size = 11;

    function createChart(id, config) {
        const canvas = document.getElementById(id);
        if (!canvas) return null;
        try { return new Chart(canvas, config); } catch(e) { return null; }
    }

    const colors = {
        primary: '#155e75',
        accent: '#14b8a6',
        success: '#10b981',
        danger: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6',
        purple: '#8b5cf6',
        pink: '#ec4899',
        orange: '#f97316'
    };

    const palette = [colors.primary, colors.accent, colors.warning, colors.danger, colors.purple, colors.info, colors.pink, colors.orange];

    // 1. Atividades ao longo do tempo
    const atividadesLabels = <?= json_encode(array_column($atividadesTempo, 'data')) ?>;
    const atividadesData = <?= json_encode(array_map('intval', array_column($atividadesTempo, 'total'))) ?>;

    if (atividadesLabels.length > 0) {
        const grad = document.createElement('canvas').getContext('2d');
        const gradient = grad.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, colors.primary + '50');
        gradient.addColorStop(1, colors.primary + '05');

        createChart('atividadesTempo', {
            type: 'line',
            data: {
                labels: atividadesLabels,
                datasets: [{
                    label: 'Eventos',
                    data: atividadesData,
                    borderColor: colors.primary,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: isDark ? '#1a1a2e' : '#ffffff',
                    pointBorderWidth: 2,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    }
                }
            }
        });
    }

    // 2. Ações por tipo
    const acoesLabels = <?= json_encode(array_column($acoesPorTipo, 'acao')) ?>;
    const acoesData = <?= json_encode(array_map('intval', array_column($acoesPorTipo, 'total'))) ?>;

    if (acoesLabels.length > 0) {
        const acoesColors = acoesLabels.map((_, i) => palette[i % palette.length]);
        createChart('acoesPorTipo', {
            type: 'doughnut',
            data: {
                labels: acoesLabels,
                datasets: [{
                    data: acoesData,
                    backgroundColor: acoesColors,
                    borderWidth: 3,
                    borderColor: isDark ? '#1a1a2e' : '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 10,
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 9, weight: '500' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Atividades por utilizador
    const userLabels = <?= json_encode(array_column($atividadesPorUsuario, 'usuario_nome')) ?>;
    const userData = <?= json_encode(array_map('intval', array_column($atividadesPorUsuario, 'total'))) ?>;

    if (userLabels.length > 0) {
        const userColors = userLabels.map((_, i) => palette[i % palette.length]);
        createChart('atividadesPorUsuario', {
            type: 'bar',
            data: {
                labels: userLabels,
                datasets: [{
                    label: 'Atividades',
                    data: userData,
                    backgroundColor: userColors,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    }
                }
            }
        });
    }

    // 4. Login vs Logout
    const llData = <?= json_encode($loginLogout[0] ?? ['logins' => 0, 'logouts' => 0]) ?>;
    if (llData.logins > 0 || llData.logouts > 0) {
        createChart('loginLogout', {
            type: 'bar',
            data: {
                labels: ['Logins', 'Logouts'],
                datasets: [{
                    label: 'Acessos',
                    data: [llData.logins, llData.logouts],
                    backgroundColor: [colors.accent, colors.danger],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

    // 5. Ações críticas por período
    const acCriticasLabels = <?= json_encode(array_column($acoesCriticasPorDia, 'data')) ?>;
    const acCriticasData = <?= json_encode(array_map('intval', array_column($acoesCriticasPorDia, 'total'))) ?>;

    if (acCriticasLabels.length > 0) {
        const grad = document.createElement('canvas').getContext('2d');
        const gradient = grad.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, colors.danger + '50');
        gradient.addColorStop(1, colors.danger + '05');

        createChart('acoesCriticasPorDia', {
            type: 'line',
            data: {
                labels: acCriticasLabels,
                datasets: [{
                    label: 'Ações críticas',
                    data: acCriticasData,
                    borderColor: colors.danger,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: colors.danger,
                    pointBorderColor: isDark ? '#1a1a2e' : '#ffffff',
                    pointBorderWidth: 2,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    }
                }
            }
        });
    }

    // 6. Alterações por módulo
    const modulos = <?= json_encode($alteracoesPorModulo[0] ?? []) ?>;
    const modulosLabels = ['Funcionários', 'Departamentos', 'Folha', 'Férias', 'Usuários', 'Documentos', 'Recrutamento'];
    const modulosData = [
        modulos.funcionarios || 0,
        modulos.departamentos || 0,
        modulos.folha || 0,
        modulos.ferias || 0,
        modulos.usuarios || 0,
        modulos.documentos || 0,
        modulos.recrutamento || 0
    ];

    if (modulosData.some(v => v > 0)) {
        const modColors = [colors.primary, colors.info, colors.accent, colors.warning, colors.purple, colors.pink, colors.orange];
        createChart('alteracoesPorModulo', {
            type: 'bar',
            data: {
                labels: modulosLabels,
                datasets: [{
                    label: 'Alterações',
                    data: modulosData,
                    backgroundColor: modColors,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    }
                }
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>