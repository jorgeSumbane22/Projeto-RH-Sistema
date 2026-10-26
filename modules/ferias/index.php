<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$pageTitle = 'Férias e Licenças';
$feriasModel = new Ferias();
$funcionarioModel = new Funcionario();

// ============================================================
// FILTROS
// ============================================================
$filtros = ['status' => $_GET['status'] ?? ''];
$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = $_GET['tipo'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

if (usuarioAtualPerfil() === PERFIL_FUNCIONARIO) {
    $filtros['funcionario_id'] = usuarioAtualFuncionarioId();
} elseif (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    $filtros['departamento_id'] = $meuFuncionario['departamento_id'] ?? 0;
}

// Buscar dados
$lista = $feriasModel->listar(array_filter($filtros));

// Filtrar por busca textual (nome do funcionário)
if (!empty($busca) && usuarioAtualPerfil() !== PERFIL_FUNCIONARIO) {
    $lista = array_filter($lista, function($item) use ($busca) {
        return stripos($item['funcionario_nome'], $busca) !== false;
    });
}

// Filtrar por tipo
if (!empty($tipoFiltro)) {
    $lista = array_filter($lista, function($item) use ($tipoFiltro) {
        return $item['tipo'] === $tipoFiltro;
    });
}

// Filtrar por período
if (!empty($dataInicio) && !empty($dataFim)) {
    $lista = array_filter($lista, function($item) use ($dataInicio, $dataFim) {
        return $item['data_inicio'] >= $dataInicio && $item['data_fim'] <= $dataFim;
    });
}

// Estatísticas
$totalPendentes = 0;
$totalAprovados = 0;
$totalRejeitados = 0;
$totalCancelados = 0;
$totalDias = 0;
$totalDiasAprovados = 0;

foreach ($lista as $item) {
    if ($item['status'] === 'pendente') $totalPendentes++;
    if ($item['status'] === 'aprovado') {
        $totalAprovados++;
        $totalDiasAprovados += $item['dias'] ?? 0;
    }
    if ($item['status'] === 'rejeitado') $totalRejeitados++;
    if ($item['status'] === 'cancelado') $totalCancelados++;
    $totalDias += $item['dias'] ?? 0;
}

// Ordenar por data (mais recentes primeiro)
usort($lista, function($a, $b) {
    return strtotime($b['data_inicio']) - strtotime($a['data_inicio']);
});

// Verificar se o usuário pode gerenciar
$podeGerenciar = usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_SUPER_ADMIN]);

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   FÉRIAS - DESIGN PREMIUM MELHORADO
   ============================================================ */

.ferias-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.ferias-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ferias-header-left h5 i {
    color: var(--rh-accent);
}

.ferias-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.ferias-header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* ----- STATS CARDS MELHORADOS ----- */
.ferias-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.ferias-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 0.9rem 1.1rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    overflow: hidden;
}

.ferias-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--stat-color, var(--rh-primary));
}

.ferias-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.ferias-stat-card .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.ferias-stat-card .stat-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.ferias-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.ferias-stat-card .stat-icon.danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}
.ferias-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.ferias-stat-card .stat-icon.secondary {
    background: rgba(107, 124, 136, 0.12);
    color: #6b7c88;
}
.ferias-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.ferias-stat-card .stat-info .stat-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.ferias-stat-card .stat-info .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- FILTER CARD MELHORADO ----- */
.filter-card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.1rem 1.25rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.filter-card-premium:hover {
    box-shadow: var(--rh-shadow-hover);
}

.filter-card-premium .form-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.filter-card-premium .form-control,
.filter-card-premium .form-select {
    border-radius: 8px;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.filter-card-premium .form-control:focus,
.filter-card-premium .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
}

.filter-card-premium .btn-filter {
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 0.35rem 1.2rem;
    background: var(--rh-primary);
    border: none;
    color: #fff;
    transition: all 0.3s ease;
    width: 100%;
}

.filter-card-premium .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.25);
}

.filter-card-premium .btn-clear {
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 0.35rem 1.2rem;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
    width: 100%;
}

.filter-card-premium .btn-clear:hover {
    background: var(--bs-tertiary-bg);
}

/* ----- TABLE CARD MELHORADA ----- */
.table-card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    overflow: hidden;
    transition: all 0.3s ease;
}

.table-card-premium:hover {
    box-shadow: var(--rh-shadow-hover);
}

.table-card-premium .table {
    margin-bottom: 0;
}

.table-card-premium .table thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 600;
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.6rem 0.8rem;
    border-bottom: 2px solid var(--rh-border);
    white-space: nowrap;
}

.table-card-premium .table tbody td {
    padding: 0.5rem 0.8rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.82rem;
    color: var(--rh-text-primary);
}

.table-card-premium .table tbody tr {
    transition: all 0.2s ease;
}

.table-card-premium .table tbody tr:hover {
    background: rgba(21, 94, 117, 0.03);
}

.table-card-premium .table tbody tr:last-child td {
    border-bottom: none;
}

/* ----- BADGE STATUS ----- */
.badge-status {
    padding: 0.25rem 0.7rem;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.badge-status.pendente {
    background: #fef3c7;
    color: #92400e;
}
.badge-status.aprovado {
    background: #d1fae5;
    color: #065f46;
}
.badge-status.rejeitado {
    background: #fee2e2;
    color: #991b1b;
}
.badge-status.cancelado {
    background: #f3f4f6;
    color: #4b5563;
}

[data-bs-theme="dark"] .badge-status.pendente {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
}
[data-bs-theme="dark"] .badge-status.aprovado {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}
[data-bs-theme="dark"] .badge-status.rejeitado {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}
[data-bs-theme="dark"] .badge-status.cancelado {
    background: rgba(107, 114, 128, 0.2);
    color: #d1d5db;
}

.dias-badge {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 600;
}

/* ----- TYPE BADGE ----- */
.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.78rem;
}

.type-badge i {
    font-size: 0.8rem;
}

/* ----- ACTION BUTTONS ----- */
.action-group {
    display: flex;
    gap: 0.3rem;
    justify-content: flex-end;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.8rem;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.action-btn.view:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.3);
}

.action-btn.approve:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
}

.action-btn.reject:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.action-btn.cancel:hover {
    background: #f59e0b;
    border-color: #f59e0b;
    color: #fff;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    padding: 2.5rem 1rem;
    text-align: center;
}

.empty-state-premium .empty-icon {
    font-size: 3rem;
    color: var(--rh-border);
    display: block;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.empty-state-premium h6 {
    font-weight: 600;
    color: var(--rh-text-primary);
    margin-bottom: 0.25rem;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin-bottom: 0;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 991.98px) {
    .ferias-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .ferias-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .ferias-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .ferias-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .ferias-stat-card {
        padding: 0.75rem 1rem;
    }
    .ferias-stat-card .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .ferias-stat-card .stat-info .stat-value {
        font-size: 1rem;
    }
    .filter-card-premium {
        padding: 1rem;
    }
    .table-card-premium .table thead th {
        font-size: 0.5rem;
        padding: 0.3rem 0.4rem;
    }
    .table-card-premium .table tbody td {
        padding: 0.3rem 0.4rem;
        font-size: 0.7rem;
    }
    .action-btn {
        width: 28px;
        height: 28px;
        font-size: 0.7rem;
    }
}

@media (max-width: 575.98px) {
    .ferias-stats {
        grid-template-columns: 1fr;
    }
    .ferias-header-left h5 {
        font-size: 1.1rem;
    }
    .badge-status {
        font-size: 0.55rem;
        padding: 0.15rem 0.4rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="ferias-header">
    <div class="ferias-header-left">
        <h5>
            <i class="bi bi-umbrella"></i> Férias e Licenças
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.6rem;font-weight:500;">
                <?= number_format(count($lista)) ?>
            </span>
        </h5>
        <p>Gerencie solicitações de férias e licenças</p>
    </div>
    <div class="ferias-header-right">
        <a href="calendario.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;padding:0.35rem 0.9rem;font-size:0.75rem;">
            <i class="bi bi-calendar3 me-1"></i> Calendário
        </a>
        <a href="relatorio.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;padding:0.35rem 0.9rem;font-size:0.75rem;">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Relatório
        </a>
        <?php if (usuarioAtualFuncionarioId()): ?>
            <a href="solicitar.php" class="btn btn-primary btn-sm" style="border-radius:8px;padding:0.35rem 0.9rem;font-size:0.75rem;">
                <i class="bi bi-plus-lg me-1"></i> Solicitar
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     STATS CARDS (6 cards)
     ============================================================ -->
<div class="ferias-stats">
    <div class="ferias-stat-card" style="--stat-color: #f59e0b;">
        <div class="stat-icon warning"><i class="bi bi-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalPendentes) ?></div>
            <div class="stat-label">Pendentes</div>
        </div>
    </div>
    <div class="ferias-stat-card" style="--stat-color: var(--rh-accent);">
        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAprovados) ?></div>
            <div class="stat-label">Aprovados</div>
        </div>
    </div>
    <div class="ferias-stat-card" style="--stat-color: #ef4444;">
        <div class="stat-icon danger"><i class="bi bi-x-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalRejeitados) ?></div>
            <div class="stat-label">Rejeitados</div>
        </div>
    </div>
    <div class="ferias-stat-card" style="--stat-color: #6b7c88;">
        <div class="stat-icon secondary"><i class="bi bi-ban"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalCancelados) ?></div>
            <div class="stat-label">Cancelados</div>
        </div>
    </div>
    <div class="ferias-stat-card" style="--stat-color: var(--rh-primary);">
        <div class="stat-icon primary"><i class="bi bi-calendar3"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalDias) ?></div>
            <div class="stat-label">Total Dias</div>
        </div>
    </div>
    <div class="ferias-stat-card" style="--stat-color: #3b82f6;">
        <div class="stat-icon info"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalDiasAprovados) ?></div>
            <div class="stat-label">Dias Aprovados</div>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTER (Melhorado com mais opções)
     ============================================================ -->
<div class="filter-card-premium">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label"><i class="bi bi-funnel me-1"></i> Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="pendente" <?= ($filtros['status'] ?? '') === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                <option value="aprovado" <?= ($filtros['status'] ?? '') === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                <option value="rejeitado" <?= ($filtros['status'] ?? '') === 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                <option value="cancelado" <?= ($filtros['status'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label"><i class="bi bi-tag me-1"></i> Tipo</label>
            <select name="tipo" class="form-select">
                <option value="">Todos</option>
                <option value="ferias" <?= $tipoFiltro === 'ferias' ? 'selected' : '' ?>>🏖️ Férias</option>
                <option value="licenca_medica" <?= $tipoFiltro === 'licenca_medica' ? 'selected' : '' ?>>🏥 Licença Médica</option>
                <option value="licenca_maternidade" <?= $tipoFiltro === 'licenca_maternidade' ? 'selected' : '' ?>>👶 Licença Maternidade</option>
                <option value="licenca_paternidade" <?= $tipoFiltro === 'licenca_paternidade' ? 'selected' : '' ?>>👶 Licença Paternidade</option>
                <option value="outro" <?= $tipoFiltro === 'outro' ? 'selected' : '' ?>>📋 Outro</option>
            </select>
        </div>
        <?php if (usuarioAtualPerfil() !== PERFIL_FUNCIONARIO): ?>
        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-search me-1"></i> Buscar Funcionário</label>
            <input type="text" name="busca" class="form-control" placeholder="Nome do funcionário..." value="<?= e($busca) ?>">
        </div>
        <?php endif; ?>
        <div class="col-md-2">
            <label class="form-label"><i class="bi bi-calendar-range me-1"></i> Período</label>
            <div class="d-flex gap-1">
                <input type="date" name="data_inicio" class="form-control" placeholder="Início" value="<?= e($dataInicio) ?>" style="font-size:0.75rem;padding:0.3rem 0.5rem;">
                <input type="date" name="data_fim" class="form-control" placeholder="Fim" value="<?= e($dataFim) ?>" style="font-size:0.75rem;padding:0.3rem 0.5rem;">
            </div>
        </div>
        <div class="col-md-3">
            <div class="d-grid gap-2">
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                <a href="index.php" class="btn-clear">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Limpar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- ============================================================
     TABLE
     ============================================================ -->
<div class="table-card-premium">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <?php if (usuarioAtualPerfil() !== PERFIL_FUNCIONARIO): ?>
                        <th style="min-width:150px;">Funcionário</th>
                    <?php endif; ?>
                    <th style="min-width:100px;">Tipo</th>
                    <th style="min-width:180px;">Período</th>
                    <th style="min-width:70px;">Dias</th>
                    <th style="min-width:120px;">Motivo</th>
                    <th style="min-width:100px;">Status</th>
                    <?php if ($podeGerenciar): ?>
                        <th style="min-width:140px;" class="text-end">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lista)): ?>
                    <tr>
                        <td colspan="<?= ($podeGerenciar ? 7 : 6) ?>">
                            <div class="empty-state-premium">
                                <span class="empty-icon"><i class="bi bi-umbrella"></i></span>
                                <h6>Nenhuma solicitação encontrada</h6>
                                <p>Tente ajustar os filtros ou <a href="solicitar.php" style="color:var(--rh-primary);">criar uma nova solicitação</a>.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($lista as $item): ?>
                    <tr>
                        <?php if (usuarioAtualPerfil() !== PERFIL_FUNCIONARIO): ?>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--rh-primary),var(--rh-accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.55rem;text-transform:uppercase;flex-shrink:0;">
                                        <?= e(strtoupper(substr($item['funcionario_nome'], 0, 1))) ?>
                                    </span>
                                    <span style="font-weight:500;"><?= e($item['funcionario_nome']) ?></span>
                                </div>
                            </td>
                        <?php endif; ?>
                        <td>
                            <span class="type-badge">
                                <?php if ($item['tipo'] === 'ferias'): ?>
                                    <i class="bi bi-umbrella" style="color:var(--rh-primary);"></i>
                                <?php elseif (strpos($item['tipo'], 'licenca') !== false): ?>
                                    <i class="bi bi-heart-pulse" style="color:#ef4444;"></i>
                                <?php else: ?>
                                    <i class="bi bi-file-text" style="color:#6b7c88;"></i>
                                <?php endif; ?>
                                <?= e(ucfirst(str_replace('_', ' ', $item['tipo']))) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size:0.8rem;">
                                <?= formatarData($item['data_inicio']) ?> → <?= formatarData($item['data_fim']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="dias-badge">
                                <i class="bi bi-calendar3 me-1"></i> <?= $item['dias'] ?>
                            </span>
                        </td>
                        <td style="color:var(--rh-text-muted);font-size:0.75rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= e($item['motivo'] ?: '—') ?>
                        </td>
                        <td>
                            <span class="badge-status <?= $item['status'] ?>">
                                <i class="bi <?= $item['status'] === 'aprovado' ? 'bi-check-circle' : ($item['status'] === 'pendente' ? 'bi-clock' : ($item['status'] === 'cancelado' ? 'bi-x-circle' : 'bi-x-circle')) ?>"></i>
                                <?= ucfirst($item['status']) ?>
                            </span>
                        </td>
                        <?php if ($podeGerenciar): ?>
                            <td class="text-end">
                                <div class="action-group">
                                    <a href="visualizar.php?id=<?= $item['id'] ?>" class="action-btn view" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($item['status'] === 'pendente'): ?>
                                        <form method="POST" action="aprovar.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="status" value="aprovado">
                                            <button type="submit" class="action-btn approve" title="Aprovar">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="rejeitar.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="status" value="rejeitado">
                                            <button type="submit" class="action-btn reject" title="Rejeitar">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($item['status'] === 'pendente' && usuarioAtualFuncionarioId() == $item['funcionario_id']): ?>
                                        <a href="cancelar.php?id=<?= $item['id'] ?>" class="action-btn cancel js-confirm" 
                                           data-confirm-msg="Cancelar esta solicitação?" title="Cancelar">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>