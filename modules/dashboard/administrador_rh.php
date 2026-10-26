<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_ADMIN, PERFIL_RH]);
require_once __DIR__ . '/_helpers.php';

// ============================================================
// FILTROS E PARÂMETROS
// ============================================================
$mes = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
$departamentoId = max(0, (int) ($_GET['departamento_id'] ?? 0));

$hoje = date('Y-m-d');
$inicio = $mes . '-01';
$fim = date('Y-m-t', strtotime($inicio));

$depSql = $departamentoId > 0 ? ' AND f.departamento_id = :dep' : '';
$depParams = $departamentoId > 0 ? [':dep' => $departamentoId] : [];
$mesParams = [':mes' => $mes] + $depParams;
$periodoParams = [':inicio' => $inicio, ':fim' => $fim] + $depParams;

// ============================================================
// MODELOS
// ============================================================
$funcionarioModel = new Funcionario();
$presencaModel = new Presenca();
$feriasModel = new Ferias();
$avaliacaoModel = new Avaliacao();
$folhaModel = new FolhaPagamento();
$vagaModel = new Vaga();
$documentoModel = new Documento();

// ============================================================
// CONSULTAS - KPIs PRINCIPAIS
// ============================================================

// --- Total de Funcionários ---
$totalFuncionarios = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status <> "inativo"' . $depSql, $depParams);
$ativos = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo"' . $depSql, $depParams);
$inativos = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "inativo"' . $depSql, $depParams);
$feriasAtuais = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ferias"' . $depSql, $depParams);

// --- Novos Funcionários ---
$novos = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo" AND DATE_FORMAT(f.data_admissao, "%Y-%m") = :mes' . $depSql, $mesParams);

// --- Férias Pendentes ---
$feriasPendentes = (int) dashScalar('SELECT COUNT(*) FROM ferias_licencas fl JOIN funcionarios f ON f.id = fl.funcionario_id WHERE fl.status = "pendente"' . $depSql, $depParams);

// --- Faltas e Atrasos (hoje) ---
$presencasHoje = $presencaModel->listarPorData($hoje);
if ($departamentoId > 0 && !empty($presencasHoje)) {
    $funcionariosDoDept = dashRows('SELECT id FROM funcionarios WHERE departamento_id = :dep', [':dep' => $departamentoId]);
    $deptIds = array_column($funcionariosDoDept, 'id');
    $presencasHoje = array_filter($presencasHoje, fn($p) => in_array($p['funcionario_id'], $deptIds));
}

$faltasHoje = count(array_filter($presencasHoje, fn($p) => $p['status'] === 'falta'));
$atrasosHoje = count(array_filter($presencasHoje, fn($p) => $p['status'] === 'atraso'));

// --- Contratos a Expirar (próximos 30 dias) ---
$contratosExpirar = (int) dashScalar("
    SELECT COUNT(*) FROM documentos d 
    JOIN funcionarios f ON f.id = d.funcionario_id 
    WHERE d.tipo = 'contrato' 
    AND d.data_validade IS NOT NULL 
    AND d.data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND f.status = 'ativo'
" . $depSql, $depParams);

// --- Avaliações Pendentes ---
$avaliacoesPendentes = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo" AND NOT EXISTS (SELECT 1 FROM avaliacoes_desempenho a WHERE a.funcionario_id = f.id AND YEAR(a.data_avaliacao) = YEAR(CURDATE()))' . $depSql, $depParams);

// --- Vagas Abertas ---
$vagasAbertas = (int) dashScalar('SELECT COUNT(*) FROM vagas WHERE status = "aberta"');

// ============================================================
// CONSULTAS - GRÁFICOS E LISTAS
// ============================================================

// --- Distribuição por Departamento ---
$departamentos = dashRows('SELECT d.nome, COUNT(f.id) total FROM departamentos d LEFT JOIN funcionarios f ON f.departamento_id = d.id AND f.status = "ativo" WHERE d.ativo = 1 GROUP BY d.id, d.nome ORDER BY total DESC, d.nome');

// --- Presenças do Mês ---
$presencasMes = (int) dashScalar('SELECT COUNT(*) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim AND p.status = "presente"' . $depSql, $periodoParams);
$faltasMes = (int) dashScalar('SELECT COUNT(*) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim AND p.status = "falta"' . $depSql, $periodoParams);
$atrasosMes = (int) dashScalar('SELECT COUNT(*) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim AND p.status = "atraso"' . $depSql, $periodoParams);
$horasExtras = (float) dashScalar('SELECT COALESCE(SUM(p.horas_extras), 0) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim' . $depSql, $periodoParams);

// --- Férias Próximas ---
$feriasProximas = dashRows('SELECT fl.data_inicio, fl.data_fim, fl.dias, f.nome FROM ferias_licencas fl JOIN funcionarios f ON f.id = fl.funcionario_id WHERE fl.status = "aprovado" AND fl.data_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)' . $depSql . ' ORDER BY fl.data_inicio LIMIT 5', $depParams);

// --- Ranking de Desempenho ---
$ranking = dashRows('SELECT f.nome, d.nome departamento, MAX(a.nota_final) nota FROM avaliacoes_desempenho a JOIN funcionarios f ON f.id = a.funcionario_id LEFT JOIN departamentos d ON d.id = f.departamento_id WHERE f.status <> "inativo"' . $depSql . ' GROUP BY f.id, f.nome, d.nome ORDER BY nota DESC LIMIT 5', $depParams);

// --- Média de Desempenho por Departamento ---
$desempenhoDeptos = dashRows('SELECT d.nome, AVG(a.nota_final) as media FROM avaliacoes_desempenho a JOIN funcionarios f ON f.id = a.funcionario_id JOIN departamentos d ON d.id = f.departamento_id WHERE a.nota_final IS NOT NULL' . $depSql . ' GROUP BY d.id, d.nome ORDER BY media DESC', $depParams);

// --- Alertas ---
$documentosPendentes = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo" AND NOT EXISTS (SELECT 1 FROM documentos d WHERE d.funcionario_id = f.id AND d.tipo = "contrato")' . $depSql, $depParams);

// --- Faltas Excessivas (mais de 5 no mês) ---
$faltasExcessivas = dashRows('
    SELECT f.nome, COUNT(p.id) as faltas 
    FROM presencas p 
    JOIN funcionarios f ON f.id = p.funcionario_id 
    WHERE p.status = "falta" 
    AND p.data BETWEEN :inicio AND :fim 
    ' . $depSql . ' 
    GROUP BY f.id, f.nome 
    HAVING faltas > 5 
    ORDER BY faltas DESC 
    LIMIT 5
', $periodoParams + $depParams);

// --- Candidatos por Vaga ---
$candidatosPorVaga = dashRows('
    SELECT v.titulo, COUNT(c.id) as total 
    FROM vagas v 
    LEFT JOIN candidatos c ON c.vaga_id = v.id 
    WHERE v.status = "aberta" 
    GROUP BY v.id, v.titulo 
    ORDER BY total DESC 
    LIMIT 5
');

// --- Atividades Recentes ---
$atividades = dashRows('SELECT l.acao, l.detalhes, l.criado_em, u.nome usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id ORDER BY l.criado_em DESC LIMIT 8');

// ============================================================
// HEADER
// ============================================================
$pageTitle = 'Administrador RH - Dashboard';
require_once __DIR__ . '/../../includes/header.php';
exibirMensagem();
?>

<style>
/* ============================================================
   ADMIN RH DASHBOARD - DESIGN PREMIUM
   ============================================================ */

:root {
    --rh-admin-primary: #155e75;
    --rh-admin-accent: #14b8a6;
    --rh-admin-success: #10b981;
    --rh-admin-danger: #ef4444;
    --rh-admin-warning: #f59e0b;
    --rh-admin-info: #3b82f6;
    --rh-admin-purple: #8b5cf6;
    --rh-admin-pink: #ec4899;
    --rh-admin-orange: #f97316;
    --rh-admin-radius: 16px;
    --rh-admin-shadow: 0 4px 24px rgba(0,0,0,0.06);
    --rh-admin-shadow-hover: 0 12px 48px rgba(0,0,0,0.12);
}

[data-bs-theme="dark"] {
    --rh-admin-shadow: 0 4px 24px rgba(0,0,0,0.2);
    --rh-admin-shadow-hover: 0 12px 48px rgba(0,0,0,0.3);
}

/* ----- Header ----- */
.rh-admin-header {
    background: linear-gradient(135deg, var(--rh-admin-primary) 0%, #0a2635 100%);
    border-radius: var(--rh-admin-radius);
    padding: 1.75rem 2.25rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.rh-admin-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(20,184,166,0.08) 0%, transparent 70%);
    pointer-events: none;
}

.rh-admin-header .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.rh-admin-header .header-left h1 {
    color: #fff;
    font-weight: 800;
    font-size: 1.4rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.rh-admin-header .header-left h1 i {
    color: var(--rh-admin-accent);
}

.rh-admin-header .header-left p {
    color: rgba(255,255,255,0.65);
    font-size: 0.85rem;
    margin: 0.25rem 0 0;
}

.rh-admin-header .header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.rh-admin-header .header-right .badge-premium {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    padding: 0.35rem 1rem;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ----- Filter Bar ----- */
.rh-admin-filter {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-admin-radius);
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.rh-admin-filter:hover {
    box-shadow: var(--rh-admin-shadow-hover);
}

.rh-admin-filter .form-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.rh-admin-filter .form-control,
.rh-admin-filter .form-select {
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.45rem 1rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.rh-admin-filter .form-control:focus,
.rh-admin-filter .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-admin-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
}

.rh-admin-filter .btn-filter {
    border-radius: 10px;
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, var(--rh-admin-primary), #0e7490);
    border: none;
    color: #fff;
    transition: all 0.3s ease;
    width: 100%;
}

.rh-admin-filter .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.25);
}

.rh-admin-filter .btn-clear {
    border-radius: 10px;
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
    width: 100%;
}

.rh-admin-filter .btn-clear:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--bs-secondary-color);
}

/* ----- KPI Grid ----- */
.rh-admin-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (max-width: 1199.98px) {
    .rh-admin-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .rh-admin-kpi-grid {
        grid-template-columns: 1fr;
    }
}

.rh-admin-kpi-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-admin-radius);
    padding: 1.1rem 1.25rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.rh-admin-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--kpi-color, var(--rh-admin-primary));
}

.rh-admin-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--rh-admin-shadow-hover);
}

.rh-admin-kpi-card .kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.rh-admin-kpi-card .kpi-label {
    font-size: 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
}

.rh-admin-kpi-card .kpi-label i {
    margin-right: 4px;
}

.rh-admin-kpi-card .kpi-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--rh-text-primary);
    line-height: 1.2;
    margin: 2px 0 4px;
    letter-spacing: -0.5px;
}

.rh-admin-kpi-card .kpi-sub {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.rh-admin-kpi-card .kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.rh-admin-kpi-card:hover .kpi-icon {
    transform: scale(1.08) rotate(-4deg);
}

.rh-admin-kpi-card .kpi-icon.primary { background: rgba(21,94,117,0.12); color: var(--rh-admin-primary); }
.rh-admin-kpi-card .kpi-icon.success { background: rgba(16,185,129,0.12); color: var(--rh-admin-success); }
.rh-admin-kpi-card .kpi-icon.warning { background: rgba(245,158,11,0.12); color: #f59e0b; }
.rh-admin-kpi-card .kpi-icon.danger { background: rgba(239,68,68,0.12); color: #ef4444; }
.rh-admin-kpi-card .kpi-icon.info { background: rgba(59,130,246,0.12); color: #3b82f6; }
.rh-admin-kpi-card .kpi-icon.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
.rh-admin-kpi-card .kpi-icon.pink { background: rgba(236,72,153,0.12); color: #ec4899; }
.rh-admin-kpi-card .kpi-icon.orange { background: rgba(249,115,22,0.12); color: #f97316; }

/* ----- Cards ----- */
.rh-admin-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-admin-radius);
    padding: 1.1rem 1.25rem;
    transition: all 0.3s ease;
    height: 100%;
}

.rh-admin-card:hover {
    box-shadow: var(--rh-admin-shadow-hover);
}

.rh-admin-card .card-title {
    font-weight: 700;
    font-size: 0.82rem;
    color: var(--rh-text-primary);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rh-admin-card .card-title i {
    color: var(--rh-admin-accent);
}

.rh-admin-card .card-title .badge-count {
    font-size: 0.55rem;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 500;
}

/* ----- Chart Container ----- */
.rh-admin-chart {
    position: relative;
    min-height: 220px;
    width: 100%;
}

.rh-admin-chart.tall {
    min-height: 260px;
}

/* ----- List Items ----- */
.rh-admin-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.rh-admin-list-item:last-child {
    border-bottom: none;
}

.rh-admin-list-item .item-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}

.rh-admin-list-item .item-left .item-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-admin-primary), #0e7490);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.55rem;
    text-transform: uppercase;
    flex-shrink: 0;
}

.rh-admin-list-item .item-left .item-text {
    font-size: 0.78rem;
    color: var(--rh-text-primary);
}

.rh-admin-list-item .item-left .item-text .item-sub {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
}

.rh-admin-list-item .item-right {
    font-size: 0.65rem;
    font-weight: 600;
}

/* ----- Badge ----- */
.rh-admin-badge {
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    font-size: 0.55rem;
    font-weight: 600;
}

.rh-admin-badge.success { background: rgba(16,185,129,0.15); color: var(--rh-admin-success); }
.rh-admin-badge.danger { background: rgba(239,68,68,0.15); color: #ef4444; }
.rh-admin-badge.warning { background: rgba(245,158,11,0.15); color: #f59e0b; }
.rh-admin-badge.info { background: rgba(59,130,246,0.15); color: #3b82f6; }
.rh-admin-badge.primary { background: rgba(21,94,117,0.15); color: var(--rh-admin-primary); }
.rh-admin-badge.purple { background: rgba(139,92,246,0.15); color: #8b5cf6; }

/* ----- Alert Item ----- */
.rh-admin-alert {
    padding: 0.4rem 0.6rem;
    border-radius: 8px;
    border-left: 3px solid var(--alert-color, var(--rh-admin-primary));
    background: var(--bs-tertiary-bg);
    margin-bottom: 0.4rem;
    transition: all 0.3s ease;
}

.rh-admin-alert:hover {
    background: var(--bs-secondary-bg);
    transform: translateX(4px);
}

.rh-admin-alert .alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--rh-text-primary);
}

.rh-admin-alert .alert-header i {
    margin-right: 4px;
}

.rh-admin-alert .alert-desc {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
}

/* ----- Quick Actions ----- */
.rh-admin-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.rh-admin-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border: 1px solid var(--rh-border);
    border-radius: 8px;
    color: var(--rh-text-primary);
    text-decoration: none;
    font-size: 0.72rem;
    font-weight: 500;
    transition: all 0.3s ease;
    background: var(--rh-card-bg);
}

.rh-admin-action-btn:hover {
    background: var(--rh-admin-primary);
    border-color: var(--rh-admin-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21,94,117,0.2);
    text-decoration: none;
}

.rh-admin-action-btn i {
    font-size: 0.8rem;
}

/* ----- Empty State ----- */
.rh-admin-empty {
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.rh-admin-empty i {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.3rem;
    opacity: 0.4;
}

/* ----- Responsive ----- */
@media (max-width: 991.98px) {
    .rh-admin-header { padding: 1.25rem; }
    .rh-admin-header .header-left h1 { font-size: 1.2rem; }
}

@media (max-width: 767.98px) {
    .rh-admin-header .header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    .rh-admin-header .header-right { justify-content: center; }
    .rh-admin-filter { padding: 1rem; }
    .rh-admin-kpi-card { padding: 0.9rem 1rem; }
    .rh-admin-kpi-card .kpi-value { font-size: 1.2rem; }
    .rh-admin-kpi-card .kpi-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    .rh-admin-card { padding: 1rem; }
    .rh-admin-chart { min-height: 180px; }
    .rh-admin-chart.tall { min-height: 200px; }
    .rh-admin-list-item { flex-wrap: wrap; gap: 0.3rem; }
}

@media (max-width: 575.98px) {
    .rh-admin-kpi-grid { grid-template-columns: 1fr; }
    .rh-admin-header .header-left h1 { font-size: 1rem; }
    .rh-admin-actions .rh-admin-action-btn { width: 100%; justify-content: center; }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="rh-admin-header">
    <div class="header-content">
        <div class="header-left">
            <h1><i class="bi bi-person-badge"></i> Administrador RH</h1>
            <p>Gestão operacional de pessoas, presença, férias e desempenho</p>
        </div>
        <div class="header-right">
            <span class="badge-premium"><i class="bi bi-people"></i> <?= number_format($totalFuncionarios) ?> funcionários</span>
            <span class="badge-premium"><i class="bi bi-clock"></i> <?= date('d/m/Y H:i') ?></span>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTER
     ============================================================ -->
<div class="rh-admin-filter">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-3">
            <label class="form-label"><i class="bi bi-calendar3"></i> Mês</label>
            <input class="form-control" type="month" name="mes" value="<?= e($mes) ?>">
        </div>
        <div class="col-sm-5 col-md-4">
            <label class="form-label"><i class="bi bi-building"></i> Departamento</label>
            <select name="departamento_id" class="form-select">
                <option value="0">Todos os departamentos</option>
                <?php foreach (dashRows('SELECT id, nome FROM departamentos WHERE ativo = 1 ORDER BY nome') as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $departamentoId === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <button class="btn-filter"><i class="bi bi-funnel me-1"></i> Aplicar</button>
        </div>
        <div class="col-sm-3 col-md-2">
            <a class="btn-clear" href="<?= BASE_URL ?>/modules/dashboard/administrador_rh.php"><i class="bi bi-arrow-counterclockwise me-1"></i> Limpar</a>
        </div>
    </form>
</div>

<!-- ============================================================
     KPI GRID (8 KPIs)
     ============================================================ -->
<div class="rh-admin-kpi-grid">
    <div class="rh-admin-kpi-card" style="--kpi-color: var(--rh-admin-primary);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-people"></i> Total Funcionários</div>
                <div class="kpi-value"><?= number_format($totalFuncionarios) ?></div>
                <div class="kpi-sub"><?= number_format($ativos) ?> ativos</div>
            </div>
            <div class="kpi-icon primary"><i class="bi bi-people"></i></div>
        </div>
    </div>

    <div class="rh-admin-kpi-card" style="--kpi-color: var(--rh-admin-success);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-check-circle"></i> Funcionários Ativos</div>
                <div class="kpi-value"><?= number_format($ativos) ?></div>
                <div class="kpi-sub"><?= number_format($novos) ?> novos no mês</div>
            </div>
            <div class="kpi-icon success"><i class="bi bi-check-circle"></i></div>
        </div>
    </div>

    <div class="rh-admin-kpi-card" style="--kpi-color: #3b82f6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-person-plus"></i> Novos Funcionários</div>
                <div class="kpi-value"><?= number_format($novos) ?></div>
                <div class="kpi-sub">Admissões no mês</div>
            </div>
            <div class="kpi-icon info"><i class="bi bi-person-plus"></i></div>
        </div>
    </div>

    <div class="rh-admin-kpi-card" style="--kpi-color: #f59e0b;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-umbrella"></i> Férias Pendentes</div>
                <div class="kpi-value"><?= number_format($feriasPendentes) ?></div>
                <div class="kpi-sub">Aguardando aprovação</div>
            </div>
            <div class="kpi-icon warning"><i class="bi bi-umbrella"></i></div>
        </div>
    </div>

    <div class="rh-admin-kpi-card" style="--kpi-color: #ef4444;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-clock"></i> Faltas e Atrasos</div>
                <div class="kpi-value"><?= number_format($faltasHoje + $atrasosHoje) ?></div>
                <div class="kpi-sub"><?= number_format($faltasHoje) ?> faltas · <?= number_format($atrasosHoje) ?> atrasos</div>
            </div>
            <div class="kpi-icon danger"><i class="bi bi-clock"></i></div>
        </div>
    </div>

    <div class="rh-admin-kpi-card" style="--kpi-color: #ec4899;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-file-earmark-text"></i> Contratos a Expirar</div>
                <div class="kpi-value"><?= number_format($contratosExpirar) ?></div>
                <div class="kpi-sub">Próximos 30 dias</div>
            </div>
            <div class="kpi-icon pink"><i class="bi bi-file-earmark-text"></i></div>
        </div>
    </div>

    <div class="rh-admin-kpi-card" style="--kpi-color: #8b5cf6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-star"></i> Avaliações Pendentes</div>
                <div class="kpi-value"><?= number_format($avaliacoesPendentes) ?></div>
                <div class="kpi-sub">Anuais em falta</div>
            </div>
            <div class="kpi-icon purple"><i class="bi bi-star"></i></div>
        </div>
    </div>

    <div class="rh-admin-kpi-card" style="--kpi-color: #f97316;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-briefcase"></i> Vagas Abertas</div>
                <div class="kpi-value"><?= number_format($vagasAbertas) ?></div>
                <div class="kpi-sub">Recrutamento ativo</div>
            </div>
            <div class="kpi-icon orange"><i class="bi bi-briefcase"></i></div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 1
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-pie-chart"></i> Distribuição por Departamento
                <span class="badge-count ms-auto">Ativos</span>
            </div>
            <div class="rh-admin-chart tall">
                <canvas id="rhDeptChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-star"></i> Desempenho por Departamento
                <span class="badge-count ms-auto">Média</span>
            </div>
            <?php if (empty($desempenhoDeptos)): ?>
                <div class="rh-admin-empty">
                    <i class="bi bi-star-slash"></i>
                    <div>Sem avaliações registradas</div>
                </div>
            <?php else: ?>
                <div class="rh-admin-chart tall">
                    <canvas id="rhDesempenhoDeptChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 2 (ASSIDUIDADE)
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-clipboard-check"></i> Assiduidade do Mês
                <span class="badge-count ms-auto"><?= e($mes) ?></span>
            </div>
            <div class="rh-admin-chart">
                <canvas id="rhAssiduidadeChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-trophy"></i> Ranking de Desempenho
                <span class="badge-count ms-auto">Top 5</span>
            </div>
            <?php if (empty($ranking)): ?>
                <div class="rh-admin-empty">
                    <i class="bi bi-star"></i>
                    <div>Sem avaliações registradas</div>
                </div>
            <?php else: ?>
                <?php foreach ($ranking as $i => $r): 
                    $medalhas = ['🥇', '🥈', '🥉', '4º', '5º'];
                    $medalha = $medalhas[$i] ?? ($i + 1 . 'º');
                    $cores = ['#f59e0b', '#94a3b8', '#cd7f32', '#6b7c88', '#6b7c88'];
                    $cor = $cores[$i] ?? '#6b7c88';
                    $nota = (float) $r['nota'];
                ?>
                    <div class="rh-admin-list-item" style="border-left:3px solid <?= $cor ?>;border-radius:4px;padding-left:8px;">
                        <div class="item-left">
                            <span class="item-avatar" style="background:<?= $cor ?>;color:#fff;font-size:0.6rem;">
                                <?= $medalha ?>
                            </span>
                            <div class="item-text">
                                <strong><?= e($r['nome']) ?></strong>
                                <div class="item-sub"><?= e($r['departamento'] ?? 'Sem departamento') ?></div>
                            </div>
                        </div>
                        <span class="rh-admin-badge <?= $nota >= 4 ? 'success' : ($nota >= 3 ? 'warning' : 'danger') ?>" style="font-size:0.7rem;padding:0.2rem 0.6rem;">
                            <?= number_format($nota, 2) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     ALERTAS E PRÓXIMAS FÉRIAS
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-exclamation-triangle text-warning"></i> Alertas de RH
                <span class="badge-count ms-auto"><?= $feriasPendentes + $avaliacoesPendentes + $documentosPendentes + $contratosExpirar + count($faltasExcessivas) ?></span>
            </div>
            <div class="list-group list-group-flush">
                <a class="rh-admin-alert" href="<?= BASE_URL ?>/modules/ferias/index.php" style="--alert-color:#f59e0b;text-decoration:none;">
                    <div class="alert-header">
                        <span><i class="bi bi-umbrella text-warning"></i> Férias pendentes</span>
                        <span class="badge bg-warning rounded-pill" style="font-size:0.55rem;"><?= $feriasPendentes ?></span>
                    </div>
                </a>
                <a class="rh-admin-alert" href="<?= BASE_URL ?>/modules/avaliacao/index.php" style="--alert-color:#8b5cf6;text-decoration:none;">
                    <div class="alert-header">
                        <span><i class="bi bi-star text-purple"></i> Avaliações em falta</span>
                        <span class="badge bg-purple rounded-pill" style="font-size:0.55rem;"><?= $avaliacoesPendentes ?></span>
                    </div>
                </a>
                <a class="rh-admin-alert" href="<?= BASE_URL ?>/modules/documentos/index.php" style="--alert-color:#ef4444;text-decoration:none;">
                    <div class="alert-header">
                        <span><i class="bi bi-file-earmark-text text-danger"></i> Documentos em falta</span>
                        <span class="badge bg-danger rounded-pill" style="font-size:0.55rem;"><?= $documentosPendentes ?></span>
                    </div>
                </a>
                <a class="rh-admin-alert" href="<?= BASE_URL ?>/modules/documentos/index.php" style="--alert-color:#ec4899;text-decoration:none;">
                    <div class="alert-header">
                        <span><i class="bi bi-file-earmark-check text-pink"></i> Contratos a expirar</span>
                        <span class="badge bg-pink rounded-pill" style="font-size:0.55rem;"><?= $contratosExpirar ?></span>
                    </div>
                </a>
                <?php if (!empty($faltasExcessivas)): ?>
                    <a class="rh-admin-alert" href="<?= BASE_URL ?>/modules/presenca/index.php" style="--alert-color:#ef4444;text-decoration:none;">
                        <div class="alert-header">
                            <span><i class="bi bi-clock text-danger"></i> Faltas excessivas</span>
                            <span class="badge bg-danger rounded-pill" style="font-size:0.55rem;"><?= count($faltasExcessivas) ?></span>
                        </div>
                        <div class="alert-desc">
                            <?php foreach (array_slice($faltasExcessivas, 0, 3) as $f): ?>
                                <?= e($f['nome']) ?> (<?= $f['faltas'] ?> faltas) 
                            <?php endforeach; ?>
                            <?php if (count($faltasExcessivas) > 3): ?>
                                +<?= count($faltasExcessivas) - 3 ?> outros
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-umbrella"></i> Próximas Férias
                <span class="badge-count ms-auto">30 dias</span>
            </div>
            <?php if (empty($feriasProximas)): ?>
                <div class="rh-admin-empty">
                    <i class="bi bi-calendar2-week"></i>
                    <div>Nenhuma férias programada</div>
                </div>
            <?php else: ?>
                <?php foreach ($feriasProximas as $f): ?>
                    <div class="rh-admin-list-item">
                        <div class="item-left">
                            <span class="item-avatar"><?= e(strtoupper(substr($f['nome'], 0, 1))) ?></span>
                            <div class="item-text">
                                <strong><?= e($f['nome']) ?></strong>
                                <div class="item-sub"><?= formatarData($f['data_inicio']) ?> → <?= formatarData($f['data_fim']) ?></div>
                            </div>
                        </div>
                        <span class="rh-admin-badge info"><?= (int) $f['dias'] ?> dias</span>
                    </div>
                <?php endforeach; ?>
                <div class="mt-2 text-center small text-muted">
                    <i class="bi bi-arrow-right"></i> 
                    <a href="<?= BASE_URL ?>/modules/ferias/index.php" class="text-decoration-none">Ver todas as solicitações</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     VAGAS E CANDIDATOS
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-briefcase"></i> Vagas e Candidatos
                <span class="badge-count ms-auto">Recrutamento</span>
            </div>
            <?php if (empty($candidatosPorVaga)): ?>
                <div class="rh-admin-empty">
                    <i class="bi bi-briefcase-slash"></i>
                    <div>Nenhuma vaga ativa</div>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($candidatosPorVaga as $v): ?>
                        <div class="rh-admin-list-item">
                            <div class="item-left">
                                <div class="item-text">
                                    <strong><?= e($v['titulo']) ?></strong>
                                </div>
                            </div>
                            <span class="rh-admin-badge primary"><?= $v['total'] ?> candidatos</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2 text-center small text-muted">
                    <i class="bi bi-arrow-right"></i> 
                    <a href="<?= BASE_URL ?>/modules/recrutamento/index.php" class="text-decoration-none">Ver todas as vagas</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="rh-admin-card">
            <div class="card-title">
                <i class="bi bi-activity"></i> Atividades Recentes
                <span class="badge-count ms-auto">RH</span>
            </div>
            <?php if (empty($atividades)): ?>
                <div class="rh-admin-empty">
                    <i class="bi bi-inbox"></i>
                    <div>Nenhuma atividade registada</div>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($atividades, 0, 6) as $a): ?>
                    <div class="rh-admin-list-item">
                        <div class="item-left">
                            <i class="bi <?= strpos($a['acao'], 'criado') !== false ? 'bi-plus-circle text-success' : (strpos($a['acao'], 'atualizado') !== false ? 'bi-pencil-square text-info' : (strpos($a['acao'], 'excluido') !== false ? 'bi-trash text-danger' : 'bi-tag')) ?>"></i>
                            <div class="item-text">
                                <span><?= e($a['acao']) ?></span>
                                <div class="item-sub"><?= e($a['usuario_nome'] ?? 'Sistema') ?> · <?= formatarDataHora($a['criado_em']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     AÇÕES RÁPIDAS
     ============================================================ -->
<div class="rh-admin-card mb-4">
    <div class="card-title">
        <i class="bi bi-lightning-charge-fill text-warning"></i> Ações Rápidas
        <span class="badge-count ms-auto">RH</span>
    </div>
    <div class="rh-admin-actions">
        <a class="rh-admin-action-btn" href="<?= BASE_URL ?>/modules/funcionarios/criar.php"><i class="bi bi-person-plus"></i> Adicionar Funcionário</a>
        <a class="rh-admin-action-btn" href="<?= BASE_URL ?>/modules/ferias/index.php"><i class="bi bi-umbrella"></i> Aprovar Férias</a>
        <a class="rh-admin-action-btn" href="<?= BASE_URL ?>/modules/presenca/lancar.php"><i class="bi bi-clock"></i> Lançar Presença</a>
        <a class="rh-admin-action-btn" href="<?= BASE_URL ?>/modules/avaliacao/criar.php"><i class="bi bi-star"></i> Nova Avaliação</a>
        <a class="rh-admin-action-btn" href="<?= BASE_URL ?>/modules/recrutamento/criar_vaga.php"><i class="bi bi-briefcase"></i> Criar Vaga</a>
        <a class="rh-admin-action-btn" href="<?= BASE_URL ?>/modules/documentos/enviar.php"><i class="bi bi-file-earmark-arrow-up"></i> Enviar Documento</a>
        <a class="rh-admin-action-btn" href="<?= BASE_URL ?>/modules/relatorios/index.php"><i class="bi bi-file-earmark-bar-graph"></i> Relatórios</a>
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
    Chart.defaults.font.size = 10;

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

    // 1. Distribuição por Departamento
    const deptLabels = <?= json_encode(array_column($departamentos, 'nome')) ?>;
    const deptData = <?= json_encode(array_map('intval', array_column($departamentos, 'total'))) ?>;

    if (deptLabels.length > 0) {
        const deptColors = deptLabels.map((_, i) => palette[i % palette.length]);
        createChart('rhDeptChart', {
            type: 'doughnut',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptData,
                    backgroundColor: deptColors,
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
                    legend: { position: 'bottom', labels: { padding: 8, boxWidth: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 9, weight: '500' } } },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor, bodyColor: textColor, borderColor: borderColor, borderWidth: 1, cornerRadius: 12, padding: 12,
                        callbacks: { label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }}
                    }
                }
            }
        });
    }

    // 2. Desempenho por Departamento
    const desempenhoLabels = <?= json_encode(array_column($desempenhoDeptos, 'nome')) ?>;
    const desempenhoData = <?= json_encode(array_map('floatval', array_column($desempenhoDeptos, 'media'))) ?>;

    if (desempenhoLabels.length > 0) {
        const desempenhoColors = desempenhoData.map(m => m >= 4 ? colors.success : (m >= 3 ? colors.info : (m >= 2 ? colors.warning : colors.danger)));
        createChart('rhDesempenhoDeptChart', {
            type: 'bar',
            data: {
                labels: desempenhoLabels,
                datasets: [{
                    label: 'Média',
                    data: desempenhoData,
                    backgroundColor: desempenhoColors,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)', titleColor: textColor, bodyColor: textColor, borderColor: borderColor, borderWidth: 1, cornerRadius: 12, padding: 12 } },
                scales: { y: { beginAtZero: true, max: 5, ticks: { stepSize: 1, font: { size: 9 } }, grid: { color: gridColor } }, x: { grid: { display: false }, ticks: { font: { size: 9 } } } }
            }
        });
    }

    // 3. Assiduidade do Mês
    const assiduidadeData = [<?= $presencasMes ?>, <?= $faltasMes ?>, <?= $atrasosMes ?>];
    const assiduidadeLabels = ['Presenças', 'Faltas', 'Atrasos'];
    const assiduidadeColors = [colors.success, colors.danger, colors.warning];

    if (assiduidadeData.some(v => v > 0)) {
        createChart('rhAssiduidadeChart', {
            type: 'doughnut',
            data: {
                labels: assiduidadeLabels,
                datasets: [{
                    data: assiduidadeData,
                    backgroundColor: assiduidadeColors,
                    borderWidth: 3,
                    borderColor: isDark ? '#1a1a2e' : '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 8, boxWidth: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 9, weight: '500' } } },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor, bodyColor: textColor, borderColor: borderColor, borderWidth: 1, cornerRadius: 12, padding: 12,
                        callbacks: { label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }}
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>