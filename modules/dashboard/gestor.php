<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_GESTOR]);
require_once __DIR__ . '/_helpers.php';

// ============================================================
// FILTROS E PARÂMETROS
// ============================================================
$mes = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
$hoje = date('Y-m-d');
$inicio = $mes . '-01';
$fim = date('Y-m-t', strtotime($inicio));

// ============================================================
// MODELOS
// ============================================================
$funcionarioModel = new Funcionario();
$presencaModel = new Presenca();
$feriasModel = new Ferias();
$avaliacaoModel = new Avaliacao();

// ============================================================
// DADOS DO GESTOR
// ============================================================
$funcionarioId = usuarioAtualFuncionarioId();
$meuFuncionario = $funcionarioModel->buscarPorId($funcionarioId);
$departamentoId = $meuFuncionario['departamento_id'] ?? 0;
$meuNome = $meuFuncionario['nome'] ?? 'Gestor';

// ============================================================
// CONSULTAS - FILTRADAS PELO DEPARTAMENTO DO GESTOR
// ============================================================

// Funcionários do departamento
$funcionariosDept = $funcionarioModel->listar(['departamento_id' => $departamentoId, 'status' => 'ativo']);
$totalEquipe = count($funcionariosDept);

// Presenças de hoje do departamento
$presencasHoje = $presencaModel->listarPorData($hoje);
$presencasDept = array_filter($presencasHoje, function($p) use ($funcionariosDept) {
    $ids = array_column($funcionariosDept, 'id');
    return in_array($p['funcionario_id'], $ids);
});

$presentesHoje = count(array_filter($presencasDept, fn($p) => $p['status'] === 'presente'));
$faltasHoje = count(array_filter($presencasDept, fn($p) => $p['status'] === 'falta'));
$atrasosHoje = count(array_filter($presencasDept, fn($p) => $p['status'] === 'atraso'));

// Férias pendentes do departamento
$feriasPendentes = (int) dashScalar(
    'SELECT COUNT(*) FROM ferias_licencas fl 
     JOIN funcionarios f ON f.id = fl.funcionario_id 
     WHERE fl.status = "pendente" AND f.departamento_id = :dept',
    [':dept' => $departamentoId]
);

// Avaliações pendentes do departamento
$avaliacoesPendentes = (int) dashScalar(
    'SELECT COUNT(*) FROM funcionarios f 
     WHERE f.status = "ativo" AND f.departamento_id = :dept 
     AND NOT EXISTS (
         SELECT 1 FROM avaliacoes_desempenho a 
         WHERE a.funcionario_id = f.id 
         AND YEAR(a.data_avaliacao) = YEAR(CURDATE())
     )',
    [':dept' => $departamentoId]
);

// Faltas do mês por funcionário
$faltasMes = dashRows(
    'SELECT f.nome, COUNT(p.id) as faltas 
     FROM presencas p 
     JOIN funcionarios f ON f.id = p.funcionario_id 
     WHERE p.status = "falta" 
     AND p.data BETWEEN :inicio AND :fim 
     AND f.departamento_id = :dept 
     GROUP BY f.id, f.nome 
     ORDER BY faltas DESC 
     LIMIT 5',
    [':inicio' => $inicio, ':fim' => $fim, ':dept' => $departamentoId]
);

// Desempenho da equipe
$desempenhoEquipe = dashRows(
    'SELECT f.nome, AVG(a.nota_final) as media 
     FROM avaliacoes_desempenho a 
     JOIN funcionarios f ON f.id = a.funcionario_id 
     WHERE f.departamento_id = :dept 
     AND a.nota_final IS NOT NULL 
     GROUP BY f.id, f.nome 
     ORDER BY media DESC',
    [':dept' => $departamentoId]
);

// Atividades recentes do departamento
$atividades = dashRows(
    'SELECT l.acao, l.detalhes, l.criado_em, u.nome usuario_nome 
     FROM log_auditoria l 
     LEFT JOIN usuarios u ON u.id = l.usuario_id 
     ORDER BY l.criado_em DESC 
     LIMIT 8'
);

// Próximas férias da equipe
$proximasFerias = dashRows(
    'SELECT f.nome, fl.data_inicio, fl.data_fim, fl.dias 
     FROM ferias_licencas fl 
     JOIN funcionarios f ON f.id = fl.funcionario_id 
     WHERE fl.status = "aprovado" 
     AND f.departamento_id = :dept 
     AND fl.data_inicio >= CURDATE() 
     ORDER BY fl.data_inicio ASC 
     LIMIT 5',
    [':dept' => $departamentoId]
);

// Minhas informações
$minhasFerias = $feriasModel->listar(['funcionario_id' => $funcionarioId]);
$minhasFeriasPendentes = count(array_filter($minhasFerias, fn($f) => $f['status'] === 'pendente'));
$minhasFeriasAprovadas = count(array_filter($minhasFerias, fn($f) => $f['status'] === 'aprovado'));

// Minhas avaliações
$minhasAvaliacoes = $avaliacaoModel->listar(['funcionario_id' => $funcionarioId]);
$minhaUltimaNota = !empty($minhasAvaliacoes) ? end($minhasAvaliacoes)['nota_final'] ?? null : null;

// ============================================================
// HEADER (usando o header do sistema)
// ============================================================
$pageTitle = 'Dashboard Gestor';
require_once __DIR__ . '/../../includes/header.php';
exibirMensagem();
?>

<style>
/* ============================================================
   DASHBOARD GESTOR - DESIGN PREMIUM
   ============================================================ */

/* ----- Stats Cards ----- */
.gestor-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 991.98px) {
    .gestor-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .gestor-stats-grid {
        grid-template-columns: 1fr;
    }
}

.kpi-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.2rem 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--kpi-color, var(--rh-primary));
    opacity: 0.8;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--rh-shadow-hover);
}

.kpi-card .kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.kpi-card .kpi-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
}

.kpi-card .kpi-label i {
    margin-right: 4px;
}

.kpi-card .kpi-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--rh-text-primary);
    line-height: 1.2;
    margin: 4px 0 2px;
    letter-spacing: -1px;
}

.kpi-card .kpi-sub {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.kpi-card .kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.kpi-card:hover .kpi-icon {
    transform: scale(1.08) rotate(-4deg);
}

.kpi-card .kpi-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.kpi-card .kpi-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.kpi-card .kpi-icon.danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}
.kpi-card .kpi-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.kpi-card .kpi-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.kpi-card .kpi-icon.purple {
    background: rgba(139, 92, 246, 0.12);
    color: #8b5cf6;
}

/* ----- Welcome Banner ----- */
.welcome-banner-gestor {
    background: linear-gradient(135deg, var(--rh-primary) 0%, #0B3242 100%);
    border-radius: var(--rh-radius);
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.welcome-banner-gestor::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(20, 184, 166, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.welcome-banner-gestor h4 {
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #fff;
}

.welcome-banner-gestor p {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
    font-size: 0.9rem;
}

.welcome-banner-gestor .badge-dept {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    padding: 0.4rem 1.2rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ----- Filter ----- */
.dashboard-filter {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.dashboard-filter:hover {
    box-shadow: var(--rh-shadow-hover);
}

.dashboard-filter .form-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.dashboard-filter .form-control {
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.45rem 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.dashboard-filter .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
}

.dashboard-filter .btn-primary {
    border-radius: 10px;
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
    transition: all 0.3s ease;
}

.dashboard-filter .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.25);
}

/* ----- Cards ----- */
.card-custom {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
    height: 100%;
}

.card-custom:hover {
    box-shadow: var(--rh-shadow-hover);
}

.card-custom h6 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-custom h6 i {
    color: var(--rh-accent);
}

/* ----- Alerts ----- */
.alert-item-gestor {
    padding: 0.65rem 0.5rem;
    border-bottom: 1px solid var(--rh-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-decoration: none;
    color: var(--rh-text-primary);
    transition: all 0.2s ease;
    border-radius: 8px;
}

.alert-item-gestor:last-child {
    border-bottom: none;
}

.alert-item-gestor:hover {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-primary);
}

.alert-item-gestor .alert-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-item-gestor .alert-text i {
    font-size: 0.9rem;
}

.alert-item-gestor .badge-count {
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 700;
}

/* ----- Team List ----- */
.team-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.team-item:last-child {
    border-bottom: none;
}

.team-item .team-info {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.team-item .team-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.65rem;
    text-transform: uppercase;
}

.team-item .team-name {
    font-weight: 500;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
}

.team-item .team-status {
    font-size: 0.7rem;
    font-weight: 600;
}

.team-item .team-status.presente {
    color: var(--rh-accent);
}
.team-item .team-status.falta {
    color: #ef4444;
}
.team-item .team-status.atraso {
    color: #f59e0b;
}

/* ----- Activity ----- */
.activity-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--rh-border);
    color: var(--rh-text-primary);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item .activity-action {
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--rh-text-primary);
}

.activity-item .activity-meta {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.activity-item .activity-time {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
}

/* ----- Quick Actions ----- */
.quick-actions-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.quick-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    color: var(--rh-text-primary);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    background: var(--rh-card-bg);
}

.quick-action:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.25);
    text-decoration: none;
}

.quick-action i {
    font-size: 0.9rem;
}

/* ----- Chart Container ----- */
.chart-container {
    position: relative;
    min-height: 230px;
    width: 100%;
}

/* ----- Empty State ----- */
.rh-state {
    border: 1px dashed var(--rh-border);
    border-radius: 10px;
    padding: 2rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.rh-state i {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* ----- Responsive ----- */
@media (max-width: 767.98px) {
    .kpi-card {
        padding: 1rem;
    }
    .kpi-card .kpi-value {
        font-size: 1.3rem;
    }
    .kpi-card .kpi-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .card-custom {
        padding: 1rem;
    }
    .dashboard-filter {
        padding: 1rem;
    }
    .quick-action {
        width: 100%;
        justify-content: center;
    }
    .chart-container {
        min-height: 190px;
    }
    .welcome-banner-gestor {
        padding: 1rem 1.25rem;
    }
    .welcome-banner-gestor h4 {
        font-size: 1.1rem;
    }
}

@media (max-width: 575.98px) {
    .gestor-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- ============================================================
     WELCOME BANNER
     ============================================================ -->
<div class="welcome-banner-gestor d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4><i class="bi bi-person-badge me-2"></i> <?= e($meuNome) ?></h4>
        <p>Gerenciando a equipe do departamento <?= e($meuFuncionario['departamento_nome'] ?? '') ?></p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="badge-dept"><i class="bi bi-people me-1"></i> <?= $totalEquipe ?> membros</span>
        <span class="badge-dept"><i class="bi bi-calendar-check me-1"></i> <?= date('d/m/Y') ?></span>
    </div>
</div>

<!-- ============================================================
     FILTRO
     ============================================================ -->
<div class="dashboard-filter">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-sm-4 col-md-3">
            <label class="form-label"><i class="bi bi-calendar3"></i> Mês de referência</label>
            <input class="form-control" type="month" name="mes" value="<?= e($mes) ?>">
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary w-100">
                <i class="bi bi-funnel me-1"></i> Filtrar
            </button>
        </div>
    </form>
</div>

<!-- ============================================================
     KPI CARDS
     ============================================================ -->
<div class="gestor-stats-grid">
    <div class="kpi-card" style="--kpi-color: var(--rh-primary);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-people"></i> Equipe</div>
                <div class="kpi-value"><?= number_format($totalEquipe) ?></div>
                <div class="kpi-sub">Funcionários ativos</div>
            </div>
            <div class="kpi-icon primary"><i class="bi bi-people"></i></div>
        </div>
    </div>

    <div class="kpi-card" style="--kpi-color: var(--rh-accent);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-calendar-check"></i> Presentes hoje</div>
                <div class="kpi-value"><?= number_format($presentesHoje) ?></div>
                <div class="kpi-sub"><?= $faltasHoje ?> faltas · <?= $atrasosHoje ?> atrasos</div>
            </div>
            <div class="kpi-icon success"><i class="bi bi-calendar-check"></i></div>
        </div>
    </div>

    <div class="kpi-card" style="--kpi-color: #f59e0b;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-airplane"></i> Férias pendentes</div>
                <div class="kpi-value"><?= number_format($feriasPendentes) ?></div>
                <div class="kpi-sub">Aguardando aprovação</div>
            </div>
            <div class="kpi-icon warning"><i class="bi bi-airplane"></i></div>
        </div>
    </div>

    <div class="kpi-card" style="--kpi-color: #3b82f6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-star"></i> Avaliações pendentes</div>
                <div class="kpi-value"><?= number_format($avaliacoesPendentes) ?></div>
                <div class="kpi-sub">Anuais em falta</div>
            </div>
            <div class="kpi-icon info"><i class="bi bi-star"></i></div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRÁFICOS
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-custom h-100">
            <h6><i class="bi bi-bar-chart"></i> Top 5 faltas do mês</h6>
            <?php if (empty($faltasMes)): ?>
                <?php dashEmpty('Nenhuma falta registrada no mês.'); ?>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($faltasMes as $f): ?>
                        <div class="team-item">
                            <div class="team-info">
                                <span class="team-avatar"><?= e(strtoupper(substr($f['nome'], 0, 1))) ?></span>
                                <span class="team-name"><?= e($f['nome']) ?></span>
                            </div>
                            <span class="team-status falta"><i class="bi bi-x-circle me-1"></i> <?= $f['faltas'] ?> faltas</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-custom h-100">
            <h6><i class="bi bi-trophy"></i> Desempenho da equipe</h6>
            <?php if (empty($desempenhoEquipe)): ?>
                <?php dashEmpty('Nenhuma avaliação registrada.'); ?>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($desempenhoEquipe as $d): ?>
                        <div class="team-item">
                            <div class="team-info">
                                <span class="team-avatar"><?= e(strtoupper(substr($d['nome'], 0, 1))) ?></span>
                                <span class="team-name"><?= e($d['nome']) ?></span>
                            </div>
                            <span class="badge <?= $d['media'] >= 4 ? 'bg-success' : ($d['media'] >= 3 ? 'bg-warning' : 'bg-danger') ?>" style="font-size:0.7rem;padding:0.2rem 0.6rem;">
                                <?= number_format($d['media'], 2) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     ALERTAS E MINHAS INFORMAÇÕES
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-exclamation-triangle text-warning"></i> Alertas da equipe</h6>
            <div class="list-group list-group-flush">
                <a class="alert-item-gestor" href="<?= BASE_URL ?>/modules/ferias/index.php">
                    <span class="alert-text">
                        <i class="bi bi-sun text-warning"></i>
                        <span>Férias aguardando decisão</span>
                    </span>
                    <span class="badge-count bg-warning text-dark"><?= $feriasPendentes ?></span>
                </a>
                <a class="alert-item-gestor" href="<?= BASE_URL ?>/modules/avaliacao/index.php">
                    <span class="alert-text">
                        <i class="bi bi-star text-primary"></i>
                        <span>Avaliações anuais em falta</span>
                    </span>
                    <span class="badge-count bg-primary text-white"><?= $avaliacoesPendentes ?></span>
                </a>
                <a class="alert-item-gestor" href="<?= BASE_URL ?>/modules/presenca/index.php">
                    <span class="alert-text">
                        <i class="bi bi-calendar-x text-danger"></i>
                        <span>Faltas não justificadas hoje</span>
                    </span>
                    <span class="badge-count bg-danger text-white"><?= $faltasHoje ?></span>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-person-badge text-primary"></i> Minhas informações</h6>
            <div class="row g-2">
                <div class="col-6">
                    <div class="p-2 rounded" style="background:var(--bs-tertiary-bg);">
                        <div class="text-muted small">Férias pendentes</div>
                        <div class="fw-bold fs-4" style="color:var(--rh-text-primary);"><?= $minhasFeriasPendentes ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded" style="background:var(--bs-tertiary-bg);">
                        <div class="text-muted small">Férias aprovadas</div>
                        <div class="fw-bold fs-4" style="color:var(--rh-accent);"><?= $minhasFeriasAprovadas ?></div>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <div class="p-2 rounded" style="background:var(--bs-tertiary-bg);">
                        <div class="text-muted small">Última avaliação</div>
                        <div class="fw-bold fs-4" style="color:<?= ($minhaUltimaNota ?? 0) >= 4 ? 'var(--rh-accent)' : (($minhaUltimaNota ?? 0) >= 3 ? '#f59e0b' : '#ef4444') ?>;">
                            <?= $minhaUltimaNota !== null ? number_format($minhaUltimaNota, 2) . ' / 5.00' : 'Não avaliado' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     PRÓXIMAS FÉRIAS E ATIVIDADES
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card-custom">
            <h6><i class="bi bi-calendar-event text-primary"></i> Próximas férias da equipe</h6>
            <?php if (empty($proximasFerias)): ?>
                <?php dashEmpty('Nenhuma férias programada.'); ?>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($proximasFerias as $f): ?>
                        <div class="team-item">
                            <div class="team-info">
                                <span class="team-avatar"><?= e(strtoupper(substr($f['nome'], 0, 1))) ?></span>
                                <div>
                                    <div class="team-name"><?= e($f['nome']) ?></div>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar3 me-1"></i> <?= formatarData($f['data_inicio']) ?> a <?= formatarData($f['data_fim']) ?>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-primary"><?= $f['dias'] ?>d</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-custom">
            <h6><i class="bi bi-activity text-primary"></i> Atividades recentes</h6>
            <?php if (!$atividades): ?>
                <?php dashEmpty('Ainda não existem atividades.'); ?>
            <?php else: ?>
                <?php foreach ($atividades as $a): ?>
                    <div class="activity-item">
                        <div class="activity-action"><?= e($a['acao']) ?></div>
                        <div class="activity-meta">
                            <?= e($a['usuario_nome'] ?? 'Sistema') ?> · <?= e($a['detalhes'] ?? '') ?>
                        </div>
                        <div class="activity-time">
                            <i class="bi bi-clock me-1"></i> <?= formatarDataHora($a['criado_em']) ?>
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
<div class="card-custom mb-4">
    <h6><i class="bi bi-lightning-charge-fill text-warning"></i> Ações rápidas</h6>
    <div class="quick-actions-grid">
        <a class="quick-action" href="<?= BASE_URL ?>/modules/presenca/index.php">
            <i class="bi bi-clock"></i> Ver presenças
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/ferias/index.php">
            <i class="bi bi-airplane"></i> Aprovar férias
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/avaliacao/criar.php">
            <i class="bi bi-star"></i> Avaliar equipe
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/funcionarios/index.php">
            <i class="bi bi-people"></i> Ver equipe
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/relatorios/index.php">
            <i class="bi bi-file-earmark-bar-graph"></i> Relatórios
        </a>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>