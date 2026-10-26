<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Relatório de Presença';
$presencaModel = new Presenca();
$funcionarioModel = new Funcionario();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');
$funcionarioId = $_GET['funcionario_id'] ?? '';

$presencas = $presencaModel->listarComFiltros($dataInicio, $dataFim, $funcionarioId);

// Funções auxiliares
function formatarHoraSeguro($hora) {
    if (empty($hora) || $hora === null) return '--:--';
    return date('H:i', strtotime($hora));
}

function calcularHorasTrabalhadasSeguro($p) {
    $total = 0;
    if (!empty($p['hora_entrada']) && !empty($p['hora_saida'])) {
        $entrada = strtotime($p['hora_entrada']);
        $saida = strtotime($p['hora_saida']);
        $total = ($saida - $entrada) / 3600;
        if (isset($p['hora_saida_almoco']) && !empty($p['hora_saida_almoco']) && 
            isset($p['hora_retorno']) && !empty($p['hora_retorno'])) {
            $almoco = strtotime($p['hora_retorno']) - strtotime($p['hora_saida_almoco']);
            $total -= $almoco / 3600;
        }
    }
    return max(0, $total);
}

// Estatísticas
$totalRegistros = count($presencas);
$totalPresentes = 0;
$totalFaltas = 0;
$totalAtrasos = 0;
$totalJustificados = 0;
$totalHorasExtras = 0;

foreach ($presencas as $p) {
    $status = $p['status'] ?? 'falta';
    if ($status === 'presente') $totalPresentes++;
    elseif ($status === 'falta') $totalFaltas++;
    elseif ($status === 'atraso') $totalAtrasos++;
    elseif ($status === 'justificado') $totalJustificados++;
    $totalHorasExtras += $p['horas_extras'] ?? 0;
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
.relatorio-container {
    max-width: 1400px;
    margin: 0 auto;
}

.relatorio-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.5rem;
    box-shadow: var(--rh-shadow);
    transition: all 0.3s ease;
}

.relatorio-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.relatorio-card .card-header {
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--rh-border);
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.relatorio-card .card-header h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin: 0;
}

.relatorio-card .card-header h5 i {
    color: var(--rh-accent);
}

.filter-row .form-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.filter-row .form-control,
.filter-row .form-select {
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.45rem 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.filter-row .form-control:focus,
.filter-row .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
}

.filter-row .btn-filter {
    border-radius: 10px;
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
    transition: all 0.3s ease;
}

.filter-row .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.25);
}

.filter-row .btn-secondary {
    border-radius: 10px;
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
}

.filter-row .btn-secondary:hover {
    background: var(--bs-tertiary-bg);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--rh-shadow-hover);
}

.stat-card .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.stat-card .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.stat-card .stat-value.success { color: var(--rh-accent); }
.stat-card .stat-value.danger { color: #ef4444; }
.stat-card .stat-value.warning { color: #f59e0b; }
.stat-card .stat-value.info { color: #3b82f6; }
.stat-card .stat-value.primary { color: var(--rh-primary); }

.table-relatorio {
    margin-bottom: 0;
}

.table-relatorio thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 600;
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.5rem 0.75rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-relatorio tbody td {
    padding: 0.4rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.82rem;
    color: var(--rh-text-primary);
}

.table-relatorio tbody tr {
    transition: all 0.2s ease;
}

.table-relatorio tbody tr:hover {
    background: rgba(21,94,117,0.03);
}

.table-relatorio tbody tr:last-child td {
    border-bottom: none;
}

.badge-status-presenca {
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.badge-status-presenca.presente { background: #d1fae5; color: #065f46; }
.badge-status-presenca.falta { background: #fee2e2; color: #991b1b; }
.badge-status-presenca.atraso { background: #fef3c7; color: #92400e; }
.badge-status-presenca.justificado { background: #dbeafe; color: #1e40af; }

[data-bs-theme="dark"] .badge-status-presenca.presente { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-status-presenca.falta { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .badge-status-presenca.atraso { background: #92400e; color: #fef3c7; }
[data-bs-theme="dark"] .badge-status-presenca.justificado { background: #1e40af; color: #dbeafe; }

.empty-state {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.empty-state .empty-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

@media (max-width: 767.98px) {
    .relatorio-card {
        padding: 1rem;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .filter-row .btn {
        width: 100%;
    }
    .table-relatorio thead th {
        font-size: 0.5rem;
        padding: 0.3rem 0.4rem;
    }
    .table-relatorio tbody td {
        padding: 0.3rem 0.4rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 575.98px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="relatorio-container">
    <div class="relatorio-card">
        <!-- Header -->
        <div class="card-header">
            <h5>
                <i class="bi bi-file-earmark-bar-graph me-2"></i> Relatório de Presença
                <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.6rem;">
                    <?= date('d/m/Y', strtotime($dataInicio)) ?> a <?= date('d/m/Y', strtotime($dataFim)) ?>
                </span>
            </h5>
            <div class="d-flex gap-2">
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="filter-row row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-calendar-start"></i> Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?= e($dataInicio) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-calendar-end"></i> Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?= e($dataFim) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-person"></i> Funcionário</label>
                <select name="funcionario_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($funcionarioModel->listar(['status' => 'ativo']) as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $funcionarioId == $f['id'] ? 'selected' : '' ?>>
                            <?= e($f['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2 align-items-end">
                <button type="submit" class="btn-filter w-100">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                <a href="relatorio.php" class="btn-secondary" style="padding:0.45rem 1.5rem;border-radius:10px;border:1.5px solid var(--rh-border);background:transparent;color:var(--rh-text-muted);text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;font-weight:600;font-size:0.85rem;white-space:nowrap;">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value primary"><?= number_format($totalRegistros) ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-value success"><?= number_format($totalPresentes) ?></div>
                <div class="stat-label">Presentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value danger"><?= number_format($totalFaltas) ?></div>
                <div class="stat-label">Faltas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value warning"><?= number_format($totalAtrasos) ?></div>
                <div class="stat-label">Atrasos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value info"><?= number_format($totalJustificados) ?></div>
                <div class="stat-label">Justificados</div>
            </div>
            <div class="stat-card">
                <div class="stat-value primary"><?= number_format($totalHorasExtras, 1) ?>h</div>
                <div class="stat-label">Horas Extras</div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="table-responsive">
            <table class="table-relatorio table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Funcionário</th>
                        <th>Entrada</th>
                        <th>Saída Almoço</th>
                        <th>Retorno</th>
                        <th>Saída</th>
                        <th>Total</th>
                        <th>H. Extras</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($presencas)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <span class="empty-icon"><i class="bi bi-clock-slash"></i></span>
                                    <p>Nenhum registro encontrado para o período selecionado.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($presencas as $p): ?>
                        <tr>
                            <td><?= formatarData($p['data']) ?></td>
                            <td><?= e($p['funcionario_nome'] ?? 'Desconhecido') ?></td>
                            <td><?= formatarHoraSeguro($p['hora_entrada'] ?? null) ?></td>
                            <td><?= formatarHoraSeguro($p['hora_saida_almoco'] ?? null) ?></td>
                            <td><?= formatarHoraSeguro($p['hora_retorno'] ?? null) ?></td>
                            <td><?= formatarHoraSeguro($p['hora_saida'] ?? null) ?></td>
                            <td><?= number_format(calcularHorasTrabalhadasSeguro($p), 1) ?>h</td>
                            <td>
                                <?php if (($p['horas_extras'] ?? 0) > 0): ?>
                                    <span class="badge bg-warning text-dark">+<?= number_format($p['horas_extras'], 1) ?>h</span>
                                <?php else: ?>
                                    <span class="text-muted">0h</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status-presenca <?= $p['status'] ?? 'falta' ?>">
                                    <i class="bi <?= ($p['status'] ?? '') === 'presente' ? 'bi-check-circle' : (($p['status'] ?? '') === 'atraso' ? 'bi-clock' : (($p['status'] ?? '') === 'justificado' ? 'bi-check-circle' : 'bi-x-circle')) ?>"></i>
                                    <?= ucfirst($p['status'] ?? 'Falta') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>