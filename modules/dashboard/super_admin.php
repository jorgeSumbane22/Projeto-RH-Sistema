<?php
/**
 * super_admin.php - Dashboard Super Admin Premium
 * 
 * PERFIL_SUPER_ADMIN
 * Visão global completa do sistema com design executivo e gráficos modernos
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);
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
$funcionarios = new Funcionario();
$presenca = new Presenca();
$ferias = new Ferias();
$avaliacoes = new Avaliacao();
$folha = new FolhaPagamento();
$documentoModel = new Documento();

// ============================================================
// CONSULTAS - KPIs PRINCIPAIS
// ============================================================

$totalFuncionarios = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status <> "inativo"' . $depSql, $depParams);
$ativos = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo"' . $depSql, $depParams);
$inativos = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "inativo"' . $depSql, $depParams);
$feriasAtuais = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ferias"' . $depSql, $depParams);
$novos = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo" AND DATE_FORMAT(f.data_admissao, "%Y-%m") = :mes' . $depSql, $mesParams);

$utilizadoresAtivos = (int) dashScalar('SELECT COUNT(*) FROM usuarios WHERE ativo = 1');
$totalUtilizadores = (int) dashScalar('SELECT COUNT(*) FROM usuarios');
$totalDepartamentos = (int) dashScalar('SELECT COUNT(*) FROM departamentos WHERE ativo = 1');

$folhaAtual = (float) dashScalar('SELECT COALESCE(SUM(fp.salario_liquido), 0) FROM folha_pagamento fp JOIN funcionarios f ON f.id = fp.funcionario_id WHERE fp.mes_referencia = :mes' . $depSql, $mesParams);
$pagamentosPendentes = (int) dashScalar('SELECT COUNT(*) FROM folha_pagamento fp JOIN funcionarios f ON f.id = fp.funcionario_id WHERE fp.mes_referencia = :mes AND fp.status = "pendente"' . $depSql, $mesParams);
$pagamentosPagos = (int) dashScalar('SELECT COUNT(*) FROM folha_pagamento fp JOIN funcionarios f ON f.id = fp.funcionario_id WHERE fp.mes_referencia = :mes AND fp.status = "pago"' . $depSql, $mesParams);

$presencasHoje = $presenca->listarPorData($hoje);
if ($departamentoId > 0 && !empty($presencasHoje)) {
    $funcionariosDoDept = dashRows('SELECT id FROM funcionarios WHERE departamento_id = :dep', [':dep' => $departamentoId]);
    $deptIds = array_column($funcionariosDoDept, 'id');
    $presencasHoje = array_filter($presencasHoje, fn($p) => in_array($p['funcionario_id'], $deptIds));
}

$presentesHoje = count(array_filter($presencasHoje, fn($p) => $p['status'] === 'presente'));
$faltasHoje = count(array_filter($presencasHoje, fn($p) => $p['status'] === 'falta'));
$atrasosHoje = count(array_filter($presencasHoje, fn($p) => $p['status'] === 'atraso'));

$feriasPendentes = (int) dashScalar('SELECT COUNT(*) FROM ferias_licencas fl JOIN funcionarios f ON f.id = fl.funcionario_id WHERE fl.status = "pendente"' . $depSql, $depParams);
$feriasAprovadas = (int) dashScalar('SELECT COUNT(*) FROM ferias_licencas fl JOIN funcionarios f ON f.id = fl.funcionario_id WHERE fl.status = "aprovado"' . $depSql, $depParams);

$contratosAtivos = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo" AND EXISTS (SELECT 1 FROM documentos d WHERE d.funcionario_id = f.id AND d.tipo = "contrato")' . $depSql, $depParams);
$documentosPendentes = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo" AND NOT EXISTS (SELECT 1 FROM documentos d WHERE d.funcionario_id = f.id AND d.tipo = "contrato")' . $depSql, $depParams);

$contasBloqueadas = (int) dashScalar('SELECT COUNT(*) FROM usuarios WHERE bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()');
$tentativasFalhas = (int) dashScalar('SELECT COUNT(*) FROM log_auditoria WHERE acao LIKE "%falha%" AND DATE(criado_em) = CURDATE()');

// ============================================================
// CONSULTAS - GRÁFICOS
// ============================================================

// --- Evolução de funcionários (6 meses) ---
$meses = [];
$contratacoes = [];
$folhaHistorica = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime('-' . $i . ' months', strtotime($inicio)));
    $meses[] = $m;
    $contratacoes[] = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE DATE_FORMAT(f.data_admissao, "%Y-%m") = :mes' . $depSql, [':mes' => $m] + $depParams);
    $folhaHistorica[] = (float) dashScalar('SELECT COALESCE(SUM(fp.salario_liquido), 0) FROM folha_pagamento fp JOIN funcionarios f ON f.id = fp.funcionario_id WHERE fp.mes_referencia = :mes' . $depSql, [':mes' => $m] + $depParams);
}

// --- Funcionários por departamento ---
$departamentos = dashRows('SELECT d.nome, COUNT(f.id) total FROM departamentos d LEFT JOIN funcionarios f ON f.departamento_id = d.id AND f.status = "ativo" WHERE d.ativo = 1 GROUP BY d.id, d.nome ORDER BY total DESC, d.nome');

// --- Distribuição por Gênero ---
$genero = dashRows("
    SELECT 
        CASE 
            WHEN genero = 'M' OR genero = 'Masculino' THEN 'Masculino'
            WHEN genero = 'F' OR genero = 'Feminino' THEN 'Feminino'
            ELSE 'Não informado'
        END as genero,
        COUNT(*) as total
    FROM funcionarios
    WHERE status = 'ativo'
    GROUP BY genero
");

// --- Presenças vs Faltas (mês atual) ---
$presencasMes = (int) dashScalar('SELECT COUNT(*) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim AND p.status = "presente"' . $depSql, $periodoParams);
$faltasMes = (int) dashScalar('SELECT COUNT(*) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim AND p.status = "falta"' . $depSql, $periodoParams);
$atrasosMes = (int) dashScalar('SELECT COUNT(*) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim AND p.status = "atraso"' . $depSql, $periodoParams);
$horasExtras = (float) dashScalar('SELECT COALESCE(SUM(p.horas_extras), 0) FROM presencas p JOIN funcionarios f ON f.id = p.funcionario_id WHERE p.data BETWEEN :inicio AND :fim' . $depSql, $periodoParams);

// --- Distribuição Etária ---
$etaria = dashRows("
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 25 THEN '18-25'
            WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 25 AND 35 THEN '26-35'
            WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
            WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 46 AND 55 THEN '46-55'
            ELSE '56+'
        END as faixa,
        COUNT(*) as total
    FROM funcionarios
    WHERE status = 'ativo' AND data_nascimento IS NOT NULL
    GROUP BY faixa
    ORDER BY 
        CASE 
            WHEN faixa = '18-25' THEN 1
            WHEN faixa = '26-35' THEN 2
            WHEN faixa = '36-45' THEN 3
            WHEN faixa = '46-55' THEN 4
            ELSE 5
        END
");

// --- Férias e Ausências ---
$feriasProximas = dashRows('SELECT fl.data_inicio, fl.data_fim, fl.dias, f.nome FROM ferias_licencas fl JOIN funcionarios f ON f.id = fl.funcionario_id WHERE fl.status = "aprovado" AND fl.data_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)' . $depSql . ' ORDER BY fl.data_inicio LIMIT 6', $depParams);

// --- Avaliações de Desempenho ---
$mediaDesempenho = (float) dashScalar('SELECT COALESCE(AVG(a.nota_final), 0) FROM avaliacoes_desempenho a JOIN funcionarios f ON f.id = a.funcionario_id WHERE a.data_avaliacao BETWEEN :inicio AND :fim' . $depSql, $periodoParams);
$semAvaliacao = (int) dashScalar('SELECT COUNT(*) FROM funcionarios f WHERE f.status = "ativo" AND NOT EXISTS (SELECT 1 FROM avaliacoes_desempenho a WHERE a.funcionario_id = f.id AND YEAR(a.data_avaliacao) = YEAR(CURDATE()))' . $depSql, $depParams);
$ranking = dashRows('SELECT f.nome, d.nome departamento, MAX(a.nota_final) nota FROM avaliacoes_desempenho a JOIN funcionarios f ON f.id = a.funcionario_id LEFT JOIN departamentos d ON d.id = f.departamento_id WHERE f.status <> "inativo"' . $depSql . ' GROUP BY f.id, f.nome, d.nome ORDER BY nota DESC LIMIT 5', $depParams);

// --- Níveis de Satisfação ---
$satisfacao = dashRows("
    SELECT 
        CASE 
            WHEN nota_final >= 4.5 THEN 'Excelente'
            WHEN nota_final >= 3.5 THEN 'Bom'
            WHEN nota_final >= 2.5 THEN 'Regular'
            ELSE 'Insatisfatório'
        END as nivel,
        COUNT(*) as total
    FROM avaliacoes_desempenho
    WHERE nota_final IS NOT NULL
    GROUP BY nivel
    ORDER BY 
        CASE 
            WHEN nivel = 'Excelente' THEN 1
            WHEN nivel = 'Bom' THEN 2
            WHEN nivel = 'Regular' THEN 3
            ELSE 4
        END
");

// --- Recrutamento ---
$vagasAbertas = (int) dashScalar('SELECT COUNT(*) FROM vagas WHERE status = "aberta"');
$candidatos = (int) dashScalar('SELECT COUNT(*) FROM candidatos WHERE status <> "rejeitado"');

// --- Atividades recentes ---
$atividades = dashRows('SELECT l.acao, l.detalhes, l.criado_em, u.nome usuario_nome FROM log_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id ORDER BY l.criado_em DESC LIMIT 8');

// ============================================================
// HEADER
// ============================================================
$pageTitle = 'Super Admin - Dashboard';
require_once __DIR__ . '/../../includes/header.php';
exibirMensagem();
?>

<style>
/* ============================================================
   SUPER ADMIN DASHBOARD - DESIGN PREMIUM EXECUTIVO
   ============================================================ */

:root {
    --sa-primary: #155e75;
    --sa-primary-dark: #0a2635;
    --sa-accent: #14b8a6;
    --sa-success: #10b981;
    --sa-danger: #ef4444;
    --sa-warning: #f59e0b;
    --sa-info: #3b82f6;
    --sa-purple: #8b5cf6;
    --sa-pink: #ec4899;
    --sa-orange: #f97316;
    --sa-radius: 16px;
    --sa-shadow: 0 4px 24px rgba(0,0,0,0.06);
    --sa-shadow-hover: 0 12px 48px rgba(0,0,0,0.12);
}

[data-bs-theme="dark"] {
    --sa-primary: #0e7490;
    --sa-shadow: 0 4px 24px rgba(0,0,0,0.2);
    --sa-shadow-hover: 0 12px 48px rgba(0,0,0,0.3);
}

/* ----- Header Premium ----- */
.sa-header-premium {
    background: linear-gradient(135deg, var(--sa-primary) 0%, var(--sa-primary-dark) 100%);
    border-radius: var(--sa-radius);
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.sa-header-premium::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(20,184,166,0.08) 0%, transparent 70%);
    pointer-events: none;
}

.sa-header-premium .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.sa-header-premium .header-left h1 {
    color: #fff;
    font-weight: 800;
    font-size: 1.6rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sa-header-premium .header-left h1 i {
    color: var(--sa-accent);
}

.sa-header-premium .header-left p {
    color: rgba(255,255,255,0.65);
    font-size: 0.9rem;
    margin: 0.25rem 0 0;
}

.sa-header-premium .header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.sa-header-premium .header-right .badge-premium {
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

.sa-header-premium .header-right .badge-premium .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--sa-success);
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

/* ----- Filter Bar ----- */
.sa-filter-bar {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--sa-radius);
    padding: 1.25rem 1.75rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.sa-filter-bar:hover {
    box-shadow: var(--sa-shadow-hover);
}

.sa-filter-bar .form-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.sa-filter-bar .form-control,
.sa-filter-bar .form-select {
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.sa-filter-bar .form-control:focus,
.sa-filter-bar .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--sa-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
}

.sa-filter-bar .btn-filter {
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, var(--sa-primary), #0e7490);
    border: none;
    color: #fff;
    transition: all 0.3s ease;
    width: 100%;
}

.sa-filter-bar .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.25);
}

.sa-filter-bar .btn-clear {
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
    width: 100%;
    display: inline-block;
    text-align: center;
    text-decoration: none;
}

.sa-filter-bar .btn-clear:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--bs-secondary-color);
    text-decoration: none;
}

/* ----- KPI Grid ----- */
.sa-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (max-width: 1199.98px) {
    .sa-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .sa-kpi-grid {
        grid-template-columns: 1fr;
    }
}

.sa-kpi-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--sa-radius);
    padding: 1.25rem 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.sa-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--kpi-color, var(--sa-primary));
}

.sa-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--sa-shadow-hover);
}

.sa-kpi-card .kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.sa-kpi-card .kpi-label {
    font-size: 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--rh-text-muted);
    font-weight: 700;
}

.sa-kpi-card .kpi-label i {
    margin-right: 4px;
}

.sa-kpi-card .kpi-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--rh-text-primary);
    line-height: 1.2;
    margin: 2px 0 4px;
    letter-spacing: -0.5px;
}

.sa-kpi-card .kpi-sub {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.sa-kpi-card .kpi-icon {
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

.sa-kpi-card:hover .kpi-icon {
    transform: scale(1.08) rotate(-4deg);
}

.sa-kpi-card .kpi-icon.primary { background: rgba(21,94,117,0.12); color: var(--sa-primary); }
.sa-kpi-card .kpi-icon.success { background: rgba(16,185,129,0.12); color: var(--sa-success); }
.sa-kpi-card .kpi-icon.warning { background: rgba(245,158,11,0.12); color: #f59e0b; }
.sa-kpi-card .kpi-icon.danger { background: rgba(239,68,68,0.12); color: #ef4444; }
.sa-kpi-card .kpi-icon.info { background: rgba(59,130,246,0.12); color: #3b82f6; }
.sa-kpi-card .kpi-icon.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
.sa-kpi-card .kpi-icon.pink { background: rgba(236,72,153,0.12); color: #ec4899; }
.sa-kpi-card .kpi-icon.secondary { background: rgba(107,124,136,0.12); color: #6b7c88; }
.sa-kpi-card .kpi-icon.orange { background: rgba(249,115,22,0.12); color: #f97316; }

.sa-kpi-card .kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    margin-top: 4px;
}

.sa-kpi-card .kpi-trend.up { color: var(--sa-success); background: rgba(16,185,129,0.1); }
.sa-kpi-card .kpi-trend.down { color: #ef4444; background: rgba(239,68,68,0.1); }
.sa-kpi-card .kpi-trend.warning { color: #f59e0b; background: rgba(245,158,11,0.1); }

/* ----- Section Title ----- */
.sa-section-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--rh-text-primary);
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sa-section-title i {
    color: var(--sa-accent);
}

.sa-section-title .badge-count {
    font-size: 0.6rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 500;
}

/* ----- Cards ----- */
.sa-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--sa-radius);
    padding: 1.25rem 1.5rem;
    transition: all 0.3s ease;
    height: 100%;
}

.sa-card:hover {
    box-shadow: var(--sa-shadow-hover);
}

.sa-card .card-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sa-card .card-title i {
    color: var(--sa-accent);
}

.sa-card .card-title .badge-count {
    font-size: 0.6rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 500;
}

/* ----- Chart Container ----- */
.sa-chart-container {
    position: relative;
    min-height: 240px;
    width: 100%;
}

.sa-chart-container.tall {
    min-height: 280px;
}

.sa-chart-container.short {
    min-height: 200px;
}

/* ----- List Items ----- */
.sa-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.sa-list-item:last-child {
    border-bottom: none;
}

.sa-list-item .item-left {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    min-width: 0;
}

.sa-list-item .item-left .item-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--sa-primary), #0e7490);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.6rem;
    text-transform: uppercase;
    flex-shrink: 0;
}

.sa-list-item .item-left .item-text {
    font-size: 0.82rem;
    color: var(--rh-text-primary);
    min-width: 0;
}

.sa-list-item .item-left .item-text .item-sub {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

/* ----- Badge ----- */
.sa-badge {
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
}

.sa-badge.success { background: rgba(16,185,129,0.15); color: var(--sa-success); }
.sa-badge.danger { background: rgba(239,68,68,0.15); color: #ef4444; }
.sa-badge.warning { background: rgba(245,158,11,0.15); color: #f59e0b; }
.sa-badge.info { background: rgba(59,130,246,0.15); color: #3b82f6; }
.sa-badge.primary { background: rgba(21,94,117,0.15); color: var(--sa-primary); }
.sa-badge.purple { background: rgba(139,92,246,0.15); color: #8b5cf6; }

/* ----- Quick Actions ----- */
.sa-actions-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.sa-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    color: var(--rh-text-primary);
    text-decoration: none;
    font-size: 0.78rem;
    font-weight: 500;
    transition: all 0.3s ease;
    background: var(--rh-card-bg);
}

.sa-action-btn:hover {
    background: var(--sa-primary);
    border-color: var(--sa-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.25);
    text-decoration: none;
}

.sa-action-btn i {
    font-size: 0.9rem;
}

/* ----- Empty State ----- */
.sa-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.sa-empty i {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.4;
}

.sa-empty div {
    font-size: 0.9rem;
}

/* ----- Security Item ----- */
.sa-security-item {
    padding: 0.4rem 0.6rem;
    border-radius: 8px;
    border-left: 3px solid var(--alert-color, var(--sa-primary));
    background: var(--bs-tertiary-bg);
    margin-bottom: 0.4rem;
    transition: all 0.3s ease;
}

.sa-security-item:hover {
    background: var(--bs-secondary-bg);
    transform: translateX(4px);
}

.sa-security-item .sec-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.78rem;
    color: var(--rh-text-primary);
}

.sa-security-item .sec-header i {
    margin-right: 4px;
}

.sa-security-item .sec-time {
    font-size: 0.6rem;
    color: var(--rh-text-muted);
}

.sa-security-item .sec-desc {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
    margin-top: 0.1rem;
}

/* ----- Responsive ----- */
@media (max-width: 991.98px) {
    .sa-header-premium { padding: 1.5rem; }
    .sa-header-premium .header-left h1 { font-size: 1.3rem; }
}

@media (max-width: 767.98px) {
    .sa-header-premium .header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    .sa-header-premium .header-right { justify-content: center; }
    .sa-filter-bar { padding: 1rem; }
    .sa-kpi-card { padding: 1rem; }
    .sa-kpi-card .kpi-value { font-size: 1.4rem; }
    .sa-kpi-card .kpi-icon { width: 36px; height: 36px; font-size: 1rem; }
    .sa-card { padding: 1rem; }
    .sa-chart-container { min-height: 190px; }
    .sa-chart-container.tall { min-height: 220px; }
    .sa-list-item { flex-wrap: wrap; gap: 0.5rem; }
}

@media (max-width: 575.98px) {
    .sa-kpi-grid { grid-template-columns: 1fr; }
    .sa-header-premium .header-left h1 { font-size: 1.1rem; }
    .sa-actions-grid .sa-action-btn { width: 100%; justify-content: center; }
}
</style>

<!-- ============================================================
     HEADER PREMIUM
     ============================================================ -->
<div class="sa-header-premium">
    <div class="header-content">
        <div class="header-left">
            <h1><i class="bi bi-shield-check"></i> Super Admin Dashboard</h1>
            <p>Visão executiva completa do sistema de RH</p>
        </div>
        <div class="header-right">
            <span class="badge-premium"><span class="dot"></span> Sistema Online</span>
            <span class="badge-premium"><i class="bi bi-people"></i> <?= number_format($totalFuncionarios) ?> funcionários</span>
            <span class="badge-premium"><i class="bi bi-clock"></i> <?= date('H:i') ?></span>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTER BAR
     ============================================================ -->
<div class="sa-filter-bar">
    <form method="GET" class="row g-3 align-items-end">
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
            <button class="btn-filter"><i class="bi bi-funnel me-1"></i> Aplicar filtro</button>
        </div>
        <div class="col-sm-3 col-md-2">
            <a class="btn-clear" href="<?= BASE_URL ?>/modules/dashboard/super_admin.php"><i class="bi bi-arrow-counterclockwise me-1"></i> Limpar</a>
        </div>
    </form>
</div>

<!-- ============================================================
     KPI GRID (8 KPIs)
     ============================================================ -->
<div class="sa-kpi-grid">
    <div class="sa-kpi-card" style="--kpi-color: var(--sa-primary);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-people"></i> Total Funcionários</div>
                <div class="kpi-value"><?= number_format($totalFuncionarios) ?></div>
                <div class="kpi-sub"><?= number_format($ativos) ?> ativos · <?= number_format($inativos) ?> inativos</div>
            </div>
            <div class="kpi-icon primary"><i class="bi bi-people"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> +<?= number_format($novos) ?> novos no mês</div>
    </div>

    <div class="sa-kpi-card" style="--kpi-color: #3b82f6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-person-gear"></i> Utilizadores</div>
                <div class="kpi-value"><?= number_format($totalUtilizadores) ?></div>
                <div class="kpi-sub"><?= number_format($utilizadoresAtivos) ?> ativos</div>
            </div>
            <div class="kpi-icon info"><i class="bi bi-person-gear"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> <?= number_format($totalUtilizadores > 0 ? round($utilizadoresAtivos / $totalUtilizadores * 100) : 0) ?>% ativos</div>
    </div>

    <div class="sa-kpi-card" style="--kpi-color: #8b5cf6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-building"></i> Departamentos</div>
                <div class="kpi-value"><?= number_format($totalDepartamentos) ?></div>
                <div class="kpi-sub">Estrutura organizacional</div>
            </div>
            <div class="kpi-icon purple"><i class="bi bi-building"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> <?= number_format($totalDepartamentos > 0 ? round($totalFuncionarios / $totalDepartamentos, 1) : 0) ?> méd/func</div>
    </div>

    <div class="sa-kpi-card" style="--kpi-color: var(--sa-success);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-cash-stack"></i> Folha Salarial</div>
                <div class="kpi-value"><?= dashMoney($folhaAtual) ?></div>
                <div class="kpi-sub"><?= number_format($pagamentosPendentes) ?> pendentes</div>
            </div>
            <div class="kpi-icon success"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> <?= number_format($pagamentosPagos) ?> pagos</div>
    </div>

    <div class="sa-kpi-card" style="--kpi-color: var(--sa-success);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-calendar-check"></i> Presenças Hoje</div>
                <div class="kpi-value"><?= number_format($presentesHoje) ?></div>
                <div class="kpi-sub"><?= number_format($faltasHoje) ?> faltas · <?= number_format($atrasosHoje) ?> atrasos</div>
            </div>
            <div class="kpi-icon success"><i class="bi bi-calendar-check"></i></div>
        </div>
        <div class="kpi-trend <?= $presentesHoje > 0 ? 'up' : 'warning' ?>">
            <i class="bi bi-arrow-<?= $presentesHoje > 0 ? 'up' : 'right' ?>"></i>
            <?= $presentesHoje > 0 ? number_format(($presentesHoje / max(1, $ativos)) * 100, 0) : 0 ?>% presente
        </div>
    </div>

    <div class="sa-kpi-card" style="--kpi-color: #f59e0b;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-umbrella"></i> Férias Pendentes</div>
                <div class="kpi-value"><?= number_format($feriasPendentes) ?></div>
                <div class="kpi-sub">Aguardando aprovação</div>
            </div>
            <div class="kpi-icon warning"><i class="bi bi-umbrella"></i></div>
        </div>
        <div class="kpi-trend warning"><i class="bi bi-clock"></i> <?= number_format($feriasAprovadas) ?> aprovadas</div>
    </div>

    <div class="sa-kpi-card" style="--kpi-color: #ec4899;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-file-earmark-text"></i> Contratos Ativos</div>
                <div class="kpi-value"><?= number_format($contratosAtivos) ?></div>
                <div class="kpi-sub"><?= number_format($documentosPendentes) ?> em falta</div>
            </div>
            <div class="kpi-icon pink"><i class="bi bi-file-earmark-text"></i></div>
        </div>
        <div class="kpi-trend <?= $contratosAtivos > 0 ? 'up' : 'down' ?>">
            <i class="bi bi-arrow-<?= $contratosAtivos > 0 ? 'up' : 'down' ?>"></i>
            <?= number_format(($contratosAtivos / max(1, $ativos)) * 100, 0) ?>% cobertura
        </div>
    </div>

    <div class="sa-kpi-card" style="--kpi-color: #ef4444;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-shield-exclamation"></i> Alertas de Segurança</div>
                <div class="kpi-value"><?= number_format($contasBloqueadas + $tentativasFalhas) ?></div>
                <div class="kpi-sub"><?= number_format($contasBloqueadas) ?> bloqueadas · <?= number_format($tentativasFalhas) ?> tentativas</div>
            </div>
            <div class="kpi-icon danger"><i class="bi bi-shield-exclamation"></i></div>
        </div>
        <div class="kpi-trend <?= $contasBloqueadas > 0 ? 'down' : 'up' ?>">
            <i class="bi bi-arrow-<?= $contasBloqueadas > 0 ? 'down' : 'up' ?>"></i>
            <?= $contasBloqueadas > 0 ? 'Atenção necessária' : 'Sistema seguro' ?>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS MODERNOS - LINHA 1
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-graph-up-arrow"></i> Evolução de Funcionários
                <span class="badge-count ms-auto">6 meses</span>
            </div>
            <div class="sa-chart-container tall">
                <canvas id="saEvolucaoFuncionarios"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-pie-chart"></i> Distribuição por Departamento
                <span class="badge-count ms-auto">Ativos</span>
            </div>
            <div class="sa-chart-container tall">
                <canvas id="saDeptosChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS MODERNOS - LINHA 2 (GÊNERO + ETÁRIA)
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-gender-ambiguous"></i> Distribuição por Gênero
                <span class="badge-count ms-auto">👨👩</span>
            </div>
            <div class="sa-chart-container tall">
                <canvas id="saGeneroChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-people"></i> Distribuição Etária
                <span class="badge-count ms-auto">Faixas</span>
            </div>
            <div class="sa-chart-container tall">
                <canvas id="saEtariaChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS MODERNOS - LINHA 3
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-clipboard-check"></i> Presenças vs Faltas
                <span class="badge-count ms-auto">Mês atual</span>
            </div>
            <div class="sa-chart-container">
                <canvas id="saPresencasChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-cash-stack"></i> Evolução Salarial
                <span class="badge-count ms-auto">6 meses</span>
            </div>
            <div class="sa-chart-container">
                <canvas id="saSalarialChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS MODERNOS - LINHA 4 (SATISFAÇÃO + RANKING)
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-emoji-smile"></i> Nível de Satisfação
                <span class="badge-count ms-auto">
                    <?php 
                    $totalSatisfacao = array_sum(array_column($satisfacao, 'total'));
                    echo number_format($totalSatisfacao) . ' avaliações';
                    ?>
                </span>
            </div>
            <?php if (empty($satisfacao) || $totalSatisfacao === 0): ?>
                <div class="sa-empty">
                    <i class="bi bi-star-slash"></i>
                    <div>Nenhuma avaliação registrada</div>
                    <div class="small text-muted mt-1">As avaliações aparecerão aqui quando disponíveis</div>
                </div>
            <?php else: ?>
                <div class="sa-chart-container">
                    <canvas id="saSatisfacaoChart"></canvas>
                </div>
                <div class="row g-1 mt-3">
                    <?php 
                    $satisfacaoColors = [
                        'Excelente' => '#10b981',
                        'Bom' => '#3b82f6',
                        'Regular' => '#f59e0b',
                        'Insatisfatório' => '#ef4444'
                    ];
                    foreach ($satisfacao as $s): 
                        $cor = $satisfacaoColors[$s['nivel']] ?? '#6c757d';
                        $percent = $totalSatisfacao > 0 ? round(($s['total'] / $totalSatisfacao) * 100) : 0;
                    ?>
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center gap-2 p-1 rounded" style="background:var(--bs-tertiary-bg);">
                                <span style="width:12px;height:12px;border-radius:3px;background:<?= $cor ?>;flex-shrink:0;"></span>
                                <div class="small" style="flex:1;">
                                    <span class="fw-semibold"><?= e($s['nivel']) ?></span>
                                    <span class="text-muted"><?= number_format($s['total']) ?> (<?= $percent ?>%)</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-trophy text-warning"></i> Ranking de Desempenho
                <span class="badge-count ms-auto">Top 5</span>
            </div>
            <?php if (empty($ranking)): ?>
                <div class="sa-empty">
                    <i class="bi bi-star"></i>
                    <div>Ainda não existem avaliações</div>
                    <div class="small text-muted mt-1">Avalie os funcionários para ver o ranking</div>
                </div>
            <?php else: ?>
                <?php foreach ($ranking as $i => $r): 
                    $medalhas = ['🥇', '🥈', '🥉', '4º', '5º'];
                    $medalha = $medalhas[$i] ?? ($i + 1 . 'º');
                    $cores = ['#f59e0b', '#94a3b8', '#cd7f32', '#6b7c88', '#6b7c88'];
                    $cor = $cores[$i] ?? '#6b7c88';
                    $nota = (float) $r['nota'];
                    $estrelas = $nota >= 4.5 ? 5 : ($nota >= 3.5 ? 4 : ($nota >= 2.5 ? 3 : ($nota >= 1.5 ? 2 : 1)));
                ?>
                    <div class="sa-list-item" style="border-left:3px solid <?= $cor ?>;border-radius:4px;padding-left:8px;">
                        <div class="item-left">
                            <span class="item-avatar" style="background:<?= $cor ?>;color:#fff;font-size:0.7rem;">
                                <?= $medalha ?>
                            </span>
                            <div class="item-text">
                                <strong><?= e($r['nome']) ?></strong>
                                <div class="item-sub">
                                    <?= e($r['departamento'] ?? 'Sem departamento') ?>
                                    <span class="ms-2">
                                        <?= str_repeat('⭐', $estrelas) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="sa-badge <?= $nota >= 4 ? 'success' : ($nota >= 3 ? 'warning' : 'danger') ?>" style="font-size:0.75rem;padding:0.25rem 0.8rem;">
                                <?= number_format($nota, 2) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="mt-2 p-2 rounded" style="background:var(--bs-tertiary-bg);">
                    <div class="row text-center small">
                        <div class="col-4">
                            <span class="text-muted">Média geral</span>
                            <div class="fw-bold" style="color:var(--rh-text-primary);"><?= number_format($mediaDesempenho, 2) ?></div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted">Melhor nota</span>
                            <div class="fw-bold" style="color:var(--sa-success);"><?= number_format($ranking[0]['nota'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted">Avaliações pendentes</span>
                            <div class="fw-bold" style="color:var(--sa-warning);"><?= number_format($semAvaliacao) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     FÉRIAS E ATIVIDADES
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-umbrella"></i> Próximas Férias
                <span class="badge-count ms-auto">
                    <?= count($feriasProximas) > 0 ? count($feriasProximas) . ' nos próximos 30 dias' : 'Nenhuma programada' ?>
                </span>
            </div>
            <?php if (empty($feriasProximas)): ?>
                <div class="sa-empty">
                    <i class="bi bi-calendar2-week"></i>
                    <div>Nenhuma férias programada</div>
                    <div class="small text-muted mt-1">Os próximos períodos de férias aparecerão aqui</div>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($feriasProximas as $f): 
                        $hoje = new DateTime();
                        $inicio = new DateTime($f['data_inicio']);
                        $diasPara = $hoje->diff($inicio)->days;
                        $status = $diasPara <= 0 ? 'Iniciou' : ($diasPara <= 7 ? 'Em breve' : 'Programado');
                        $statusClass = $diasPara <= 0 ? 'success' : ($diasPara <= 7 ? 'warning' : 'info');
                    ?>
                        <div class="sa-list-item">
                            <div class="item-left">
                                <span class="item-avatar" style="background:<?= $diasPara <= 7 ? '#f59e0b' : 'var(--sa-primary)' ?>;">
                                    <?= e(strtoupper(substr($f['nome'], 0, 1))) ?>
                                </span>
                                <div class="item-text">
                                    <strong><?= e($f['nome']) ?></strong>
                                    <div class="item-sub">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?= formatarData($f['data_inicio']) ?> → <?= formatarData($f['data_fim']) ?>
                                        <span class="badge bg-<?= $statusClass ?> ms-1" style="font-size:0.55rem;">
                                            <?= $status ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="sa-badge info" style="font-size:0.7rem;padding:0.2rem 0.6rem;">
                                    <?= (int) $f['dias'] ?> dias
                                </span>
                                <?php if ($diasPara > 0): ?>
                                    <span class="small text-muted"><?= $diasPara ?> dias</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-2 p-2 rounded" style="background:var(--bs-tertiary-bg);">
                    <div class="row text-center small">
                        <div class="col-4">
                            <span class="text-muted">Total de dias</span>
                            <div class="fw-bold" style="color:var(--rh-text-primary);">
                                <?= array_sum(array_column($feriasProximas, 'dias')) ?> dias
                            </div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted">Funcionários</span>
                            <div class="fw-bold" style="color:var(--rh-text-primary);">
                                <?= count($feriasProximas) ?> pessoas
                            </div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted">Próxima em</span>
                            <div class="fw-bold" style="color:var(--sa-warning);">
                                <?php 
                                $proximo = !empty($feriasProximas) ? $feriasProximas[0] : null;
                                if ($proximo) {
                                    $hoje = new DateTime();
                                    $inicio = new DateTime($proximo['data_inicio']);
                                    $dias = $hoje->diff($inicio)->days;
                                    echo $dias <= 0 ? 'Hoje 🎉' : $dias . ' dias';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="sa-card">
            <div class="card-title">
                <i class="bi bi-activity"></i> Atividades Recentes
                <span class="badge-count ms-auto">Últimas</span>
            </div>
            <?php if (empty($atividades)): ?>
                <div class="sa-empty">
                    <i class="bi bi-inbox"></i>
                    <div>Nenhuma atividade registada</div>
                    <div class="small text-muted mt-1">As ações do sistema aparecerão aqui</div>
                </div>
            <?php else: ?>
                <?php foreach ($atividades as $a): ?>
                    <div class="sa-security-item" style="--alert-color: var(--sa-primary);">
                        <div class="sec-header">
                            <span>
                                <i class="bi <?= strpos($a['acao'], 'login') !== false ? 'bi-box-arrow-in-right' : (strpos($a['acao'], 'criado') !== false ? 'bi-plus-circle' : (strpos($a['acao'], 'atualizado') !== false ? 'bi-pencil-square' : (strpos($a['acao'], 'excluido') !== false ? 'bi-trash' : 'bi-tag'))) ?>"></i>
                                <?= e($a['acao']) ?>
                            </span>
                            <span class="sec-time"><?= formatarDataHora($a['criado_em']) ?></span>
                        </div>
                        <div class="sec-desc">
                            <i class="bi bi-person"></i> <?= e($a['usuario_nome'] ?? 'Sistema') ?>
                            <?php if (!empty($a['detalhes'])): ?>
                                · <?= e($a['detalhes']) ?>
                            <?php endif; ?>
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
<div class="sa-card mb-4">
    <div class="card-title">
        <i class="bi bi-lightning-charge-fill text-warning"></i> Ações Rápidas
        <span class="badge-count ms-auto">Administração</span>
    </div>
    <div class="sa-actions-grid">
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/funcionarios/criar.php"><i class="bi bi-person-plus"></i> Novo Funcionário</a>
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/usuarios/criar.php"><i class="bi bi-person-gear"></i> Novo Utilizador</a>
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/departamentos/criar.php"><i class="bi bi-building-add"></i> Criar Departamento</a>
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/folha-pagamento/index.php"><i class="bi bi-cash-stack"></i> Processar Folha</a>
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/relatorios/index.php"><i class="bi bi-file-earmark-bar-graph"></i> Gerar Relatório</a>
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/usuarios/index.php"><i class="bi bi-shield-lock"></i> Gerir Permissões</a>
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/backup/index.php"><i class="bi bi-cloud-arrow-down"></i> Backup</a>
        <a class="sa-action-btn" href="<?= BASE_URL ?>/modules/auditoria/index.php"><i class="bi bi-shield-check"></i> Auditoria</a>
    </div>
</div>

<!-- ============================================================
     SCRIPTS DOS GRÁFICOS MODERNOS
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

    // 1. Evolução de Funcionários
    const mesesLabels = <?= json_encode(array_map(fn($m) => date('M/y', strtotime($m . '-01')), $meses)) ?>;
    const contratacoesData = <?= json_encode($contratacoes) ?>;

    if (mesesLabels.length > 0) {
        const grad = document.createElement('canvas').getContext('2d');
        const gradient = grad.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, colors.primary + '50');
        gradient.addColorStop(1, colors.primary + '05');

        createChart('saEvolucaoFuncionarios', {
            type: 'line',
            data: {
                labels: mesesLabels,
                datasets: [{
                    label: 'Contratações',
                    data: contratacoesData.map(Number),
                    borderColor: colors.primary,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
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
                    y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: gridColor } }, 
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } } 
                }
            }
        });
    }

    // 2. Departamentos
    const deptosLabels = <?= json_encode(array_column($departamentos, 'nome')) ?>;
    const deptosData = <?= json_encode(array_map('intval', array_column($departamentos, 'total'))) ?>;

    if (deptosLabels.length > 0) {
        const deptosColors = deptosLabels.map((_, i) => palette[i % palette.length]);
        createChart('saDeptosChart', {
            type: 'doughnut',
            data: {
                labels: deptosLabels,
                datasets: [{
                    data: deptosData,
                    backgroundColor: deptosColors,
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
                        position: 'bottom', 
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

    // 3. GÊNERO
    const generoLabels = <?= json_encode(array_column($genero, 'genero')) ?>;
    const generoData = <?= json_encode(array_map('intval', array_column($genero, 'total'))) ?>;
    const generoColors = ['#155e75', '#ec4899', '#6c757d'];

    if (generoLabels.length > 0 && generoData.some(v => v > 0)) {
        createChart('saGeneroChart', {
            type: 'doughnut',
            data: {
                labels: generoLabels,
                datasets: [{
                    data: generoData,
                    backgroundColor: generoColors,
                    borderWidth: 4,
                    borderColor: isDark ? '#1a1a2e' : '#ffffff',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            padding: 14, 
                            boxWidth: 14, 
                            usePointStyle: true, 
                            pointStyle: 'circle', 
                            font: { size: 11, weight: '600' } 
                        } 
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor, 
                        bodyColor: textColor, 
                        borderColor: borderColor, 
                        borderWidth: 1, 
                        cornerRadius: 12, 
                        padding: 14,
                        callbacks: { 
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: function(chart) {
                    const { ctx, chartArea: { top, bottom, left, right } } = chart;
                    const centerX = (left + right) / 2;
                    const centerY = (top + bottom) / 2;
                    const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    if (total === 0) return;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = 'bold 20px "Segoe UI", sans-serif';
                    ctx.fillStyle = textColor;
                    ctx.fillText(total, centerX, centerY - 6);
                    ctx.font = '10px "Segoe UI", sans-serif';
                    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--bs-secondary-color') || '#6c757d';
                    ctx.fillText('Total', centerX, centerY + 18);
                    ctx.restore();
                }
            }]
        });
    }

    // 4. Etária
    const etariaLabels = <?= json_encode(array_column($etaria, 'faixa')) ?>;
    const etariaData = <?= json_encode(array_map('intval', array_column($etaria, 'total'))) ?>;

    if (etariaLabels.length > 0) {
        const etariaColors = ['#155e75', '#0e7490', '#14b8a6', '#2dd4bf', '#6c757d'];
        createChart('saEtariaChart', {
            type: 'bar',
            data: {
                labels: etariaLabels,
                datasets: [{
                    label: 'Funcionários',
                    data: etariaData,
                    backgroundColor: etariaColors,
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
                    y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: gridColor } }, 
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } } 
                }
            }
        });
    }

    // 5. Presenças vs Faltas
    const presencasData = [<?= $presencasMes ?>, <?= $faltasMes ?>, <?= $atrasosMes ?>];
    const presencasLabels = ['Presenças', 'Faltas', 'Atrasos'];
    const presencasColors = [colors.success, colors.danger, colors.warning];

    if (presencasData.some(v => v > 0)) {
        createChart('saPresencasChart', {
            type: 'doughnut',
            data: {
                labels: presencasLabels,
                datasets: [{
                    data: presencasData,
                    backgroundColor: presencasColors,
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
                    legend: { 
                        position: 'bottom', 
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

    // 6. Evolução Salarial
    const folhaLabels = <?= json_encode(array_map(fn($m) => date('M/y', strtotime($m . '-01')), $meses)) ?>;
    const folhaData = <?= json_encode($folhaHistorica) ?>;

    if (folhaLabels.length > 0) {
        const grad = document.createElement('canvas').getContext('2d');
        const gradient = grad.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, colors.accent + '50');
        gradient.addColorStop(1, colors.accent + '05');

        createChart('saSalarialChart', {
            type: 'line',
            data: {
                labels: folhaLabels,
                datasets: [{
                    label: 'Folha (MT)',
                    data: folhaData.map(Number),
                    borderColor: colors.accent,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: colors.accent,
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
                        padding: 12, 
                        callbacks: { 
                            label: function(context) { 
                                return 'MT ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); 
                            } 
                        } 
                    } 
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            callback: function(value) { 
                                if (value >= 1000000) return 'MT ' + (value / 1000000).toFixed(1) + 'M'; 
                                if (value >= 1000) return 'MT ' + (value / 1000).toFixed(1) + 'K'; 
                                return 'MT ' + value; 
                            }, 
                            font: { size: 10 } 
                        }, 
                        grid: { color: gridColor } 
                    }, 
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } } 
                }
            }
        });
    }

    // 7. Satisfação
    const satisfacaoLabels = <?= json_encode(array_column($satisfacao, 'nivel')) ?>;
    const satisfacaoData = <?= json_encode(array_map('intval', array_column($satisfacao, 'total'))) ?>;

    if (satisfacaoLabels.length > 0 && satisfacaoData.some(v => v > 0)) {
        const satisfacaoColors = { 'Excelente': colors.accent, 'Bom': colors.info, 'Regular': colors.warning, 'Insatisfatório': colors.danger };
        const colorsSat = satisfacaoLabels.map(l => satisfacaoColors[l] || colors.primary);

        createChart('saSatisfacaoChart', {
            type: 'doughnut',
            data: {
                labels: satisfacaoLabels,
                datasets: [{
                    data: satisfacaoData,
                    backgroundColor: colorsSat,
                    borderWidth: 4,
                    borderColor: isDark ? '#1a1a2e' : '#ffffff',
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            padding: 14, 
                            boxWidth: 14, 
                            usePointStyle: true, 
                            pointStyle: 'circle', 
                            font: { size: 11, weight: '600' } 
                        } 
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: textColor, 
                        bodyColor: textColor, 
                        borderColor: borderColor, 
                        borderWidth: 1, 
                        cornerRadius: 14, 
                        padding: 16,
                        callbacks: { 
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return ' ' + context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerTextSatisfacao',
                afterDraw: function(chart) {
                    const { ctx, chartArea: { top, bottom, left, right } } = chart;
                    const centerX = (left + right) / 2;
                    const centerY = (top + bottom) / 2;
                    const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    if (total === 0) return;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = 'bold 22px "Segoe UI", sans-serif';
                    ctx.fillStyle = textColor;
                    ctx.fillText(total, centerX, centerY - 6);
                    ctx.font = '10px "Segoe UI", sans-serif';
                    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--bs-secondary-color') || '#6c757d';
                    ctx.fillText('avaliações', centerX, centerY + 20);
                    ctx.restore();
                }
            }]
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>