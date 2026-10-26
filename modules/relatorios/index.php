<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR]);

$pageTitle = 'Business Intelligence - RH';

$funcionarioModel = new Funcionario();
$departamentoModel = new Departamento();
$folhaModel = new FolhaPagamento();
$presencaModel = new Presenca();
$avaliacaoModel = new Avaliacao();
$vagaModel = new Vaga();
$feriasModel = new Ferias();

$departamentos = $departamentoModel->listar(true);
$mesAtual = date('Y-m');
$anoAtual = date('Y');

// ============================================================
// DADOS PARA RELATÓRIOS
// ============================================================

$db = Database::getConnection();

// 1. Evolução da folha (últimos 12 meses)
$stmt = $db->query(
    "SELECT mes_referencia, 
            SUM(salario_liquido) AS total,
            COUNT(*) AS qtd_funcionarios
     FROM folha_pagamento
     WHERE mes_referencia >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 12 MONTH), '%Y-%m')
     GROUP BY mes_referencia 
     ORDER BY mes_referencia ASC"
);
$evolucaoFolha = $stmt->fetchAll();

// 2. Faltas por departamento no mês atual
$stmtFaltas = $db->prepare(
    "SELECT d.nome, COUNT(p.id) AS total_faltas
     FROM presencas p
     JOIN funcionarios f ON f.id = p.funcionario_id
     JOIN departamentos d ON d.id = f.departamento_id
     WHERE p.status = 'falta' AND DATE_FORMAT(p.data, '%Y-%m') = :mes
     GROUP BY d.id, d.nome ORDER BY total_faltas DESC"
);
$stmtFaltas->execute([':mes' => $mesAtual]);
$faltasPorDepartamento = $stmtFaltas->fetchAll();

// 3. Desempenho médio por departamento
$stmtDesempenho = $db->query(
    "SELECT d.nome, AVG(a.nota_final) AS media, COUNT(a.id) AS total_avaliacoes
     FROM avaliacoes_desempenho a
     JOIN funcionarios f ON f.id = a.funcionario_id
     JOIN departamentos d ON d.id = f.departamento_id
     WHERE a.nota_final IS NOT NULL
     GROUP BY d.id, d.nome ORDER BY media DESC"
);
$desempenhoPorDepartamento = $stmtDesempenho->fetchAll();

// 4. Contratações por mês (últimos 12 meses)
$stmtContratacoes = $db->query(
    "SELECT DATE_FORMAT(data_admissao, '%Y-%m') AS mes, COUNT(*) AS total
     FROM funcionarios
     WHERE data_admissao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY mes ORDER BY mes ASC"
);
$contratacoes = $stmtContratacoes->fetchAll();

// 5. Férias por status
$feriasStatus = [
    'Pendentes' => $feriasModel->contarPendentes(),
    'Aprovadas' => $feriasModel->contarAprovadas(),
    'Rejeitadas' => $feriasModel->contarRejeitadas()
];

// 6. Vagas por status
$vagasStatus = [
    'Abertas' => (int) $db->query("SELECT COUNT(*) FROM vagas WHERE status = 'aberta'")->fetchColumn(),
    'Pausadas' => (int) $db->query("SELECT COUNT(*) FROM vagas WHERE status = 'pausada'")->fetchColumn(),
    'Fechadas' => (int) $db->query("SELECT COUNT(*) FROM vagas WHERE status = 'fechada'")->fetchColumn()
];

// 7. Estatísticas gerais
$totalFuncionarios = $funcionarioModel->contarTotal();
$funcionariosAtivos = $funcionarioModel->contarPorStatus('ativo');
$totalAvaliacoes = (int) $db->query("SELECT COUNT(*) FROM avaliacoes_desempenho")->fetchColumn();
$mediaGeral = (float) $db->query("SELECT AVG(nota_final) FROM avaliacoes_desempenho WHERE nota_final IS NOT NULL")->fetchColumn();

// 8. Distribuição por Gênero
$genero = $db->query("
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
")->fetchAll();

// 9. Distribuição Etária
$etaria = $db->query("
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
")->fetchAll();

// 10. Níveis de satisfação
$satisfacao = $db->query("
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
")->fetchAll();

// 11. Presenças vs Faltas (últimos 30 dias)
$presencasVsFaltas = $db->query("
    SELECT 
        data,
        SUM(CASE WHEN status = 'presente' OR status = 'justificado' THEN 1 ELSE 0 END) as presencas,
        SUM(CASE WHEN status = 'falta' THEN 1 ELSE 0 END) as faltas
    FROM presencas 
    WHERE data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY data
    ORDER BY data
")->fetchAll();

// 12. Funcionários por departamento
$funcionariosPorDepto = $db->query("
    SELECT d.nome, COUNT(f.id) as total
    FROM departamentos d
    LEFT JOIN funcionarios f ON f.departamento_id = d.id AND f.status = 'ativo'
    WHERE d.ativo = 1
    GROUP BY d.id, d.nome
    ORDER BY total DESC
")->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   BUSINESS INTELLIGENCE - DESIGN PREMIUM EXECUTIVO
   ============================================================ */

/* ----- Variáveis ----- */
:root {
    --bi-primary: #0f1419;
    --bi-primary-light: #1a1a2e;
    --bi-secondary: #536471;
    --bi-accent: #155e75;
    --bi-accent-light: rgba(21,94,117,0.12);
    --bi-success: #10b981;
    --bi-danger: #ef4444;
    --bi-warning: #f59e0b;
    --bi-info: #3b82f6;
    --bi-purple: #8b5cf6;
    --bi-pink: #ec4899;
    --bi-orange: #f97316;
    --bi-teal: #14b8a6;
    --bi-radius: 20px;
    --bi-shadow: 0 8px 32px rgba(0,0,0,0.06);
    --bi-shadow-hover: 0 16px 56px rgba(0,0,0,0.14);
}

[data-bs-theme="dark"] {
    --bi-primary: #e8edf3;
    --bi-secondary: #94a3b8;
}

/* ----- Header ----- */
.bi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--rh-border);
}

.bi-header .bi-title h1 {
    font-weight: 800;
    font-size: 1.8rem;
    margin: 0;
    color: var(--rh-text-primary);
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.bi-header .bi-title h1 i {
    color: var(--rh-accent);
}

.bi-header .bi-title .bi-subtitle {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.bi-header .bi-title .bi-subtitle span {
    color: var(--rh-text-muted);
}

.bi-header .bi-actions {
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.bi-header .btn-bi {
    border-radius: 10px;
    padding: 0.5rem 1.2rem;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1.5px solid var(--rh-border);
    background: var(--rh-card-bg);
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.bi-header .btn-bi:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(21, 94, 117, 0.25);
    text-decoration: none;
}

/* ----- KPI Cards ----- */
.kpi-grid-exec {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 2rem;
}

@media (max-width: 1199.98px) {
    .kpi-grid-exec {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .kpi-grid-exec {
        grid-template-columns: 1fr;
    }
}

.kpi-card-exec {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.5rem 1.75rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.kpi-card-exec::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--kpi-color, var(--rh-primary));
    opacity: 0.8;
}

.kpi-card-exec:hover {
    transform: translateY(-4px);
    box-shadow: var(--rh-shadow-hover);
}

.kpi-card-exec .kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.kpi-card-exec .kpi-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
}

.kpi-card-exec .kpi-label i {
    margin-right: 4px;
}

.kpi-card-exec .kpi-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--rh-text-primary);
    line-height: 1.2;
    margin: 4px 0 2px;
    letter-spacing: -1px;
}

.kpi-card-exec .kpi-sub {
    font-size: 0.75rem;
    color: var(--rh-text-muted);
}

.kpi-card-exec .kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.kpi-card-exec:hover .kpi-icon {
    transform: scale(1.08) rotate(-4deg);
}

.kpi-card-exec .kpi-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.kpi-card-exec .kpi-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.kpi-card-exec .kpi-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.kpi-card-exec .kpi-icon.purple {
    background: rgba(139, 92, 246, 0.12);
    color: #8b5cf6;
}

.kpi-card-exec .kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    margin-top: 4px;
}

.kpi-card-exec .kpi-trend.up {
    color: var(--rh-accent);
    background: rgba(20, 184, 166, 0.1);
}

.kpi-card-exec .kpi-trend.down {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

.kpi-card-exec .kpi-trend.warning {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
}

/* ----- Graph Grid ----- */
.graph-grid-exec {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 991.98px) {
    .graph-card-exec,
    .table-card-exec {
        grid-column: span 12;
    }
}

.graph-card-exec {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    transition: all 0.3s ease;
    overflow: hidden;
    grid-column: span 6;
}

.graph-card-exec.full {
    grid-column: span 12;
}

.graph-card-exec:hover {
    box-shadow: var(--rh-shadow-hover);
}

.graph-card-exec .graph-header {
    padding: 1.25rem 1.5rem 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.graph-card-exec .graph-header .graph-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.graph-card-exec .graph-header .graph-title i {
    color: var(--rh-accent);
    font-size: 0.9rem;
}

.graph-card-exec .graph-header .graph-badge {
    font-size: 0.6rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 500;
}

.graph-card-exec .graph-body {
    padding: 0.5rem 1.5rem 1.5rem;
}

.chart-container-exec {
    position: relative;
    height: 250px;
    width: 100%;
}

.chart-container-exec.tall {
    height: 290px;
}

.chart-container-exec.short {
    height: 200px;
}

@media (max-width: 767.98px) {
    .chart-container-exec {
        height: 180px;
    }
    .chart-container-exec.tall {
        height: 200px;
    }
}

/* ----- Table Card ----- */
.table-card-exec {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    transition: all 0.3s ease;
    overflow: hidden;
    grid-column: span 6;
}

.table-card-exec:hover {
    box-shadow: var(--rh-shadow-hover);
}

.table-card-exec .table-header {
    padding: 1.25rem 1.5rem 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.table-card-exec .table-header .table-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.table-card-exec .table-header .table-title i {
    color: var(--rh-accent);
}

.table-card-exec .table-body {
    padding: 0 1.5rem 1.5rem;
}

.table-card-exec .table {
    margin-bottom: 0;
}

.table-card-exec .table thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 700;
    font-size: 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.5rem 0.75rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-card-exec .table thead th i {
    margin-right: 4px;
}

.table-card-exec .table tbody td {
    padding: 0.5rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.8rem;
    color: var(--rh-text-primary);
}

.table-card-exec .table tbody tr:hover {
    background: rgba(21, 94, 117, 0.03);
}

/* ----- Badges ----- */
.badge-exec {
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.badge-exec.excelente {
    background: rgba(20, 184, 166, 0.15);
    color: var(--rh-accent);
}
.badge-exec.bom {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}
.badge-exec.regular {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}
.badge-exec.insatisfatorio {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}
.badge-exec.ativo {
    background: rgba(20, 184, 166, 0.15);
    color: var(--rh-accent);
}
.badge-exec.inativo {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

/* ----- Empty State ----- */
.empty-state-exec {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--rh-text-muted);
}

.empty-state-exec .empty-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* ----- Responsive Header ----- */
@media (max-width: 767.98px) {
    .bi-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    .bi-header .bi-actions {
        justify-content: flex-start;
    }
    .bi-header .btn-bi {
        flex: 1;
        justify-content: center;
    }
    .bi-header .bi-title h1 {
        font-size: 1.4rem;
    }
    .kpi-card-exec {
        padding: 1rem 1.25rem;
    }
    .kpi-card-exec .kpi-value {
        font-size: 1.4rem;
    }
    .kpi-card-exec .kpi-icon {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }
    .graph-card-exec .graph-body {
        padding: 0 1rem 1rem;
    }
    .table-card-exec .table-body {
        padding: 0 1rem 1rem;
    }
    .graph-card-exec .graph-header {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    .table-card-exec .table-header {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
}

@media (max-width: 575.98px) {
    .bi-header .bi-title h1 {
        font-size: 1.2rem;
    }
    .bi-header .bi-title .bi-subtitle {
        font-size: 0.7rem;
        gap: 0.5rem;
    }
    .table-card-exec .table thead th,
    .table-card-exec .table tbody td {
        padding: 0.3rem 0.4rem;
        font-size: 0.65rem;
    }
}
</style>

<!-- ============================================================
     HEADER - SEM DATA E SEM HORA (usa as do header)
     ============================================================ -->
<div class="bi-header">
    <div class="bi-title">
        <h1>
            <i class="bi bi-graph-up-arrow"></i> Business Intelligence
            <span class="badge bg-primary bg-opacity-10 text-primary ms-2" style="font-size:0.6rem;font-weight:700;padding:0.2rem 0.6rem;">RH</span>
        </h1>
        <div class="bi-subtitle">
            <span><i class="bi bi-database"></i> <?= $totalFuncionarios ?> registos</span>
            <span><i class="bi bi-people"></i> <?= $funcionariosAtivos ?> ativos</span>
            <span><i class="bi bi-star"></i> <?= number_format($mediaGeral, 2) ?> média</span>
        </div>
    </div>
    <div class="bi-actions">
        <a href="exportar_csv.php" class="btn-bi">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar CSV
        </a>
        <button type="button" onclick="window.print()" class="btn-bi">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<!-- ============================================================
     KPI CARDS
     ============================================================ -->
<div class="kpi-grid-exec">
    <div class="kpi-card-exec" style="--kpi-color: var(--rh-primary);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-people"></i> Total Funcionários</div>
                <div class="kpi-value"><?= number_format($totalFuncionarios) ?></div>
                <div class="kpi-sub"><?= number_format($funcionariosAtivos) ?> ativos</div>
            </div>
            <div class="kpi-icon primary"><i class="bi bi-people"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> +<?= $funcionariosAtivos ?> ativos</div>
    </div>

    <div class="kpi-card-exec" style="--kpi-color: var(--rh-accent);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-star"></i> Desempenho Médio</div>
                <div class="kpi-value"><?= number_format($mediaGeral, 2) ?></div>
                <div class="kpi-sub"><?= number_format($totalAvaliacoes) ?> avaliações</div>
            </div>
            <div class="kpi-icon success"><i class="bi bi-star"></i></div>
        </div>
        <div class="kpi-trend up"><i class="bi bi-arrow-up"></i> +<?= number_format($mediaGeral * 10, 1) ?>%</div>
    </div>

    <div class="kpi-card-exec" style="--kpi-color: #3b82f6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-briefcase"></i> Vagas Abertas</div>
                <div class="kpi-value"><?= number_format($vagasStatus['Abertas'] ?? 0) ?></div>
                <div class="kpi-sub"><?= number_format($vagasStatus['Pausadas'] ?? 0) ?> pausadas</div>
            </div>
            <div class="kpi-icon info"><i class="bi bi-briefcase"></i></div>
        </div>
        <div class="kpi-trend <?= ($vagasStatus['Abertas'] ?? 0) > ($vagasStatus['Fechadas'] ?? 0) ? 'up' : 'down' ?>">
            <i class="bi bi-arrow-<?= ($vagasStatus['Abertas'] ?? 0) > ($vagasStatus['Fechadas'] ?? 0) ? 'up' : 'down' ?>"></i>
            <?= number_format($vagasStatus['Fechadas'] ?? 0) ?> fechadas
        </div>
    </div>

    <div class="kpi-card-exec" style="--kpi-color: #8b5cf6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-umbrella"></i> Férias Pendentes</div>
                <div class="kpi-value"><?= number_format($feriasStatus['Pendentes'] ?? 0) ?></div>
                <div class="kpi-sub"><?= number_format($feriasStatus['Aprovadas'] ?? 0) ?> aprovadas</div>
            </div>
            <div class="kpi-icon purple"><i class="bi bi-umbrella"></i></div>
        </div>
        <div class="kpi-trend warning"><i class="bi bi-clock"></i> Aguardando aprovação</div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 1
     ============================================================ -->
<div class="graph-grid-exec">
    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-line-chart"></i> Evolução da Folha Salarial
            </div>
            <span class="graph-badge"><i class="bi bi-clock"></i> 12 meses</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec tall">
                <canvas id="graficoFolha"></canvas>
            </div>
        </div>
    </div>

    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-pie-chart"></i> Pipeline de Vagas
            </div>
            <span class="graph-badge"><i class="bi bi-circle"></i> Status</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec tall">
                <canvas id="graficoVagas"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 2
     ============================================================ -->
<div class="graph-grid-exec">
    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-gender-ambiguous"></i> Distribuição por Gênero
            </div>
            <span class="graph-badge"><i class="bi bi-person"></i> Funcionários ativos</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoGenero"></canvas>
            </div>
        </div>
    </div>

    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-people"></i> Distribuição Etária
            </div>
            <span class="graph-badge"><i class="bi bi-person"></i> Funcionários ativos</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoEtaria"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 3
     ============================================================ -->
<div class="graph-grid-exec">
    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-calendar-x"></i> Faltas por Departamento
            </div>
            <span class="graph-badge"><i class="bi bi-calendar3"></i> <?= date('m/Y') ?></span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoFaltas"></canvas>
            </div>
        </div>
    </div>

    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-person-plus"></i> Contratações por Mês
            </div>
            <span class="graph-badge"><i class="bi bi-clock"></i> 12 meses</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoContratacoes"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 4
     ============================================================ -->
<div class="graph-grid-exec">
    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-emoji-smile"></i> Nível de Satisfação
            </div>
            <span class="graph-badge"><i class="bi bi-star"></i> Avaliações</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoSatisfacao"></canvas>
            </div>
        </div>
    </div>

    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-calendar-check"></i> Presenças vs Faltas (30 dias)
            </div>
            <span class="graph-badge"><i class="bi bi-calendar3"></i> Últimos 30 dias</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoPresencasFaltas"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 5
     ============================================================ -->
<div class="graph-grid-exec">
    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-building"></i> Funcionários por Departamento
            </div>
            <span class="graph-badge"><i class="bi bi-people"></i> Distribuição</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoDeptos"></canvas>
            </div>
        </div>
    </div>

    <div class="graph-card-exec">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-star"></i> Desempenho por Departamento
            </div>
            <span class="graph-badge"><i class="bi bi-list"></i> Média</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec">
                <canvas id="graficoDesempenho"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS - LINHA 6: FÉRIAS
     ============================================================ -->
<div class="graph-grid-exec">
    <div class="graph-card-exec full">
        <div class="graph-header">
            <div class="graph-title">
                <i class="bi bi-umbrella"></i> Status das Férias
            </div>
            <span class="graph-badge"><i class="bi bi-list"></i> Solicitações</span>
        </div>
        <div class="graph-body">
            <div class="chart-container-exec short" style="height:200px;">
                <canvas id="graficoFerias"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TABELAS
     ============================================================ -->
<div class="graph-grid-exec">
    <div class="table-card-exec">
        <div class="table-header">
            <div class="table-title">
                <i class="bi bi-star"></i> Desempenho por Departamento
            </div>
            <span class="badge bg-light text-dark"><?= count($desempenhoPorDepartamento) ?> deptos</span>
        </div>
        <div class="table-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-building"></i> Departamento</th>
                            <th><i class="bi bi-star"></i> Média</th>
                            <th><i class="bi bi-list"></i> Avaliações</th>
                            <th><i class="bi bi-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($desempenhoPorDepartamento)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="bi bi-star-slash"></i> Sem avaliações registradas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($desempenhoPorDepartamento as $d): ?>
                                <tr>
                                    <td><?= e($d['nome']) ?></td>
                                    <td>
                                        <span class="fw-bold <?= $d['media'] >= 4 ? 'text-success' : ($d['media'] >= 3 ? 'text-warning' : 'text-danger') ?>">
                                            <?= number_format($d['media'], 2) ?>
                                        </span>
                                    </td>
                                    <td><?= $d['total_avaliacoes'] ?></td>
                                    <td>
                                        <span class="badge-exec <?= $d['media'] >= 4 ? 'excelente' : ($d['media'] >= 3 ? 'bom' : ($d['media'] >= 2 ? 'regular' : 'insatisfatorio')) ?>">
                                            <?= $d['media'] >= 4 ? 'Excelente' : ($d['media'] >= 3 ? 'Bom' : ($d['media'] >= 2 ? 'Regular' : 'Insatisfatório')) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="table-card-exec">
        <div class="table-header">
            <div class="table-title">
                <i class="bi bi-building"></i> Funcionários por Departamento
            </div>
            <span class="badge bg-light text-dark"><?= count($departamentos) ?> deptos</span>
        </div>
        <div class="table-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-building"></i> Departamento</th>
                            <th><i class="bi bi-people"></i> Funcionários</th>
                            <th><i class="bi bi-person-badge"></i> Gestor</th>
                            <th><i class="bi bi-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departamentos as $d): ?>
                            <tr>
                                <td><?= e($d['nome']) ?></td>
                                <td>
                                    <span class="fw-bold"><?= $d['total_funcionarios'] ?? 0 ?></span>
                                </td>
                                <td><?= e($d['gestor_nome'] ?? '-') ?></td>
                                <td>
                                    <span class="badge-exec <?= ($d['ativo'] ?? 0) ? 'ativo' : 'inativo' ?>">
                                        <i class="bi <?= ($d['ativo'] ?? 0) ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                                        <?= ($d['ativo'] ?? 0) ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

    const colors = {
        primary: '#155e75',
        primaryLight: 'rgba(21,94,117,0.15)',
        accent: '#14b8a6',
        accentLight: 'rgba(20,184,166,0.15)',
        success: '#10b981',
        danger: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6',
        purple: '#8b5cf6',
        pink: '#ec4899',
        orange: '#f97316',
        teal: '#14b8a6'
    };

    const palette = [
        colors.primary, colors.accent, colors.warning, colors.danger,
        colors.purple, colors.info, colors.pink, colors.orange, colors.teal
    ];

    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const textColor = isDark ? '#e8edf3' : '#0f1419';
    const borderColor = isDark ? '#2d2d44' : '#eef2f6';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Segoe UI', system-ui, -apple-system, sans-serif";
    Chart.defaults.font.size = 11;

    function createChart(id, config) {
        const canvas = document.getElementById(id);
        if (!canvas) return null;
        try { return new Chart(canvas, config); } catch(e) { return null; }
    }

    // ============================================================
    // CHART 1: Evolução Folha
    // ============================================================
    const folhaLabels = <?= json_encode(array_column($evolucaoFolha, 'mes_referencia')) ?>;
    const folhaData = <?= json_encode(array_map('floatval', array_column($evolucaoFolha, 'total'))) ?>;

    if (folhaLabels.length > 0) {
        const mesesNomes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        const labels = folhaLabels.map(m => {
            const [ano, mes] = m.split('-');
            return mesesNomes[parseInt(mes)-1] + '/' + ano.slice(2);
        });

        const gradient = document.createElement('canvas').getContext('2d');
        const grad = gradient.createLinearGradient(0, 0, 0, 300);
        grad.addColorStop(0, colors.accent + '50');
        grad.addColorStop(1, colors.accent + '05');

        createChart('graficoFolha', {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Gasto total (MT)',
                    data: folhaData,
                    borderColor: colors.accent,
                    backgroundColor: grad,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: colors.accent,
                    pointBorderColor: isDark ? '#1a1a2e' : '#ffffff',
                    pointBorderWidth: 3,
                    borderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16,
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
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    // ============================================================
    // CHART 2: Vagas Status
    // ============================================================
    const vagasLabels = <?= json_encode(array_keys($vagasStatus)) ?>;
    const vagasData = <?= json_encode(array_values($vagasStatus)) ?>;
    const vagasColors = [colors.success, colors.warning, colors.danger];

    if (vagasLabels.length > 0 && vagasData.some(v => v > 0)) {
        createChart('graficoVagas', {
            type: 'doughnut',
            data: {
                labels: vagasLabels,
                datasets: [{
                    data: vagasData,
                    backgroundColor: vagasColors,
                    borderWidth: 4,
                    borderColor: isDark ? '#1a1a2e' : '#ffffff',
                    hoverOffset: 12,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            boxWidth: 14,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16,
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

    // ============================================================
    // CHART 3: Gênero
    // ============================================================
    const generoLabels = <?= json_encode(array_column($genero, 'genero')) ?>;
    const generoData = <?= json_encode(array_map('intval', array_column($genero, 'total'))) ?>;
    const generoColors = ['#155e75', '#ec4899', '#6c757d'];

    if (generoLabels.length > 0 && generoData.some(v => v > 0)) {
        createChart('graficoGenero', {
            type: 'doughnut',
            data: {
                labels: generoLabels,
                datasets: [{
                    data: generoData,
                    backgroundColor: generoColors,
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
                            padding: 12,
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16,
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

    // ============================================================
    // CHART 4: Etária
    // ============================================================
    const etariaLabels = <?= json_encode(array_column($etaria, 'faixa')) ?>;
    const etariaData = <?= json_encode(array_map('intval', array_column($etaria, 'total'))) ?>;

    if (etariaLabels.length > 0) {
        const etariaColors = ['#155e75', '#0e7490', '#14b8a6', '#2dd4bf', '#6c757d'];
        createChart('graficoEtaria', {
            type: 'bar',
            data: {
                labels: etariaLabels,
                datasets: [{
                    label: 'Funcionários',
                    data: etariaData,
                    backgroundColor: etariaColors,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16
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
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ============================================================
    // CHART 5: Faltas por Departamento
    // ============================================================
    const faltasLabels = <?= json_encode(array_column($faltasPorDepartamento, 'nome')) ?>;
    const faltasData = <?= json_encode(array_map('intval', array_column($faltasPorDepartamento, 'total_faltas'))) ?>;

    if (faltasLabels.length > 0) {
        const faltasColors = faltasLabels.map((_, i) => palette[i % palette.length]);
        createChart('graficoFaltas', {
            type: 'bar',
            data: {
                labels: faltasLabels,
                datasets: [{
                    label: 'Faltas',
                    data: faltasData,
                    backgroundColor: faltasColors,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: gridColor }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ============================================================
    // CHART 6: Contratações
    // ============================================================
    const contratacoesLabels = <?= json_encode(array_column($contratacoes, 'mes')) ?>;
    const contratacoesData = <?= json_encode(array_map('intval', array_column($contratacoes, 'total'))) ?>;

    if (contratacoesLabels.length > 0) {
        const mesesNomes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        const labels = contratacoesLabels.map(m => {
            const [ano, mes] = m.split('-');
            return mesesNomes[parseInt(mes)-1] + '/' + ano.slice(2);
        });

        createChart('graficoContratacoes', {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Contratações',
                    data: contratacoesData,
                    backgroundColor: colors.accent,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16
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
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ============================================================
    // CHART 7: Satisfação
    // ============================================================
    const satisfacaoLabels = <?= json_encode(array_column($satisfacao, 'nivel')) ?>;
    const satisfacaoData = <?= json_encode(array_map('intval', array_column($satisfacao, 'total'))) ?>;

    if (satisfacaoLabels.length > 0 && satisfacaoData.some(v => v > 0)) {
        const satisfacaoColors = {
            'Excelente': colors.accent,
            'Bom': colors.info,
            'Regular': colors.warning,
            'Insatisfatório': colors.danger
        };
        const colorsSat = satisfacaoLabels.map(l => satisfacaoColors[l] || colors.primary);

        createChart('graficoSatisfacao', {
            type: 'doughnut',
            data: {
                labels: satisfacaoLabels,
                datasets: [{
                    data: satisfacaoData,
                    backgroundColor: colorsSat,
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
                            padding: 12,
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16,
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

    // ============================================================
    // CHART 8: Presenças vs Faltas
    // ============================================================
    const presencasDias = <?= json_encode(array_column($presencasVsFaltas, 'data')) ?>;
    const presencasData = <?= json_encode(array_column($presencasVsFaltas, 'presencas')) ?>;
    const faltasDataPres = <?= json_encode(array_column($presencasVsFaltas, 'faltas')) ?>;

    if (presencasDias.length > 0) {
        const diasLabels = presencasDias.map(d => {
            const data = new Date(d);
            return data.getDate() + '/' + (data.getMonth() + 1);
        });

        createChart('graficoPresencasFaltas', {
            type: 'bar',
            data: {
                labels: diasLabels,
                datasets: [{
                    label: 'Presenças',
                    data: presencasData,
                    backgroundColor: colors.accent,
                    borderRadius: 4,
                    borderSkipped: false,
                }, {
                    label: 'Faltas',
                    data: faltasDataPres,
                    backgroundColor: colors.danger,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16
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
                        ticks: { font: { size: 8 } }
                    }
                }
            }
        });
    }

    // ============================================================
    // CHART 9: Funcionários por Departamento
    // ============================================================
    const deptosLabels = <?= json_encode(array_column($funcionariosPorDepto, 'nome')) ?>;
    const deptosData = <?= json_encode(array_map('intval', array_column($funcionariosPorDepto, 'total'))) ?>;

    if (deptosLabels.length > 0) {
        const deptosColors = deptosLabels.map((_, i) => palette[i % palette.length]);
        createChart('graficoDeptos', {
            type: 'bar',
            data: {
                labels: deptosLabels,
                datasets: [{
                    label: 'Funcionários',
                    data: deptosData,
                    backgroundColor: deptosColors,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16
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
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ============================================================
    // CHART 10: Desempenho por Departamento
    // ============================================================
    const desempenhoDeptos = <?= json_encode(array_column($desempenhoPorDepartamento, 'nome')) ?>;
    const desempenhoMedias = <?= json_encode(array_column($desempenhoPorDepartamento, 'media')) ?>;

    if (desempenhoDeptos.length > 0) {
        const desempenhoColors = desempenhoMedias.map(m => {
            if (m >= 4) return colors.accent;
            if (m >= 3) return colors.info;
            if (m >= 2) return colors.warning;
            return colors.danger;
        });

        createChart('graficoDesempenho', {
            type: 'bar',
            data: {
                labels: desempenhoDeptos,
                datasets: [{
                    label: 'Média de Avaliação',
                    data: desempenhoMedias,
                    backgroundColor: desempenhoColors,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: { stepSize: 1, font: { size: 10 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ============================================================
    // CHART 11: Férias Status
    // ============================================================
    const feriasLabels = <?= json_encode(array_keys($feriasStatus)) ?>;
    const feriasData = <?= json_encode(array_values($feriasStatus)) ?>;
    const feriasColors = [colors.warning, colors.success, colors.danger];

    if (feriasData.some(v => v > 0)) {
        createChart('graficoFerias', {
            type: 'doughnut',
            data: {
                labels: feriasLabels,
                datasets: [{
                    data: feriasData,
                    backgroundColor: feriasColors,
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
                            padding: 12,
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,26,46,0.95)' : 'rgba(255,255,255,0.95)',
                        titleColor: isDark ? '#e8edf3' : '#0f1419',
                        bodyColor: isDark ? '#e8edf3' : '#0f1419',
                        borderColor: borderColor,
                        borderWidth: 1,
                        cornerRadius: 14,
                        padding: 16,
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
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>