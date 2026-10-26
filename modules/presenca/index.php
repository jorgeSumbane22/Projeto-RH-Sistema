<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$pageTitle = 'Presença / Ponto';
$presencaModel = new Presenca();
$funcionarioModel = new Funcionario();

// Filtros
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');
$funcionarioId = $_GET['funcionario_id'] ?? '';
$statusFiltro = $_GET['status'] ?? '';

// Gestor só vê seu departamento
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    $funcionarios = $funcionarioModel->listar(['departamento_id' => $meuFuncionario['departamento_id'] ?? 0]);
} else {
    $funcionarios = $funcionarioModel->listar(['status' => 'ativo']);
}

// Buscar presenças
$presencas = $presencaModel->listarComFiltros($dataInicio, $dataFim, $funcionarioId, $statusFiltro);

// Estatísticas
$totalPresencas = count($presencas);
$totalPresentes = count(array_filter($presencas, fn($p) => ($p['status'] ?? '') === 'presente'));
$totalFaltas = count(array_filter($presencas, fn($p) => ($p['status'] ?? '') === 'falta'));
$totalAtrasos = count(array_filter($presencas, fn($p) => ($p['status'] ?? '') === 'atraso'));
$totalJustificados = count(array_filter($presencas, fn($p) => ($p['status'] ?? '') === 'justificado'));
$totalHorasExtras = array_sum(array_column($presencas, 'horas_extras'));

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

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   PRESENÇA - DESIGN PREMIUM
   ============================================================ */

.presenca-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.presenca-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.presenca-header-left h5 i {
    color: var(--rh-accent);
}

.presenca-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.presenca-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

/* ----- STATS CARDS ----- */
.presenca-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.presenca-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.presenca-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--rh-shadow-hover);
}

.presenca-stat-card .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.presenca-stat-card .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.presenca-stat-card .stat-value.success { color: var(--rh-accent); }
.presenca-stat-card .stat-value.danger { color: #ef4444; }
.presenca-stat-card .stat-value.warning { color: #f59e0b; }
.presenca-stat-card .stat-value.info { color: #3b82f6; }
.presenca-stat-card .stat-value.primary { color: var(--rh-primary); }

/* ----- FILTER ----- */
.filter-card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.filter-card-premium:hover {
    box-shadow: var(--rh-shadow-hover);
}

.filter-card-premium .form-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.filter-card-premium .form-control,
.filter-card-premium .form-select {
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.45rem 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.filter-card-premium .form-control:focus,
.filter-card-premium .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
}

.filter-card-premium .btn-filter {
    border-radius: 10px;
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
    transition: all 0.3s ease;
}

.filter-card-premium .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.25);
}

.filter-card-premium .btn-clear {
    border-radius: 10px;
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
}

.filter-card-premium .btn-clear:hover {
    background: var(--bs-tertiary-bg);
}

/* ----- TABLE ----- */
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
    letter-spacing: 0.05em;
    padding: 0.6rem 0.75rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-card-premium .table tbody td {
    padding: 0.5rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.82rem;
    color: var(--rh-text-primary);
}

.table-card-premium .table tbody tr {
    transition: all 0.2s ease;
}

.table-card-premium .table tbody tr:hover {
    background: rgba(21,94,117,0.03);
}

.table-card-premium .table tbody tr:last-child td {
    border-bottom: none;
}

/* ----- BADGE STATUS ----- */
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
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.75rem;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.action-btn.view:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(21,94,117,0.3);
}

.action-btn.edit:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(20,184,166,0.3);
}

.action-btn.delete:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    box-shadow: 0 4px 12px rgba(239,68,68,0.3);
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    padding: 2rem 1rem;
    text-align: center;
}

.empty-state-premium .empty-icon {
    font-size: 2.5rem;
    color: var(--rh-border);
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin-bottom: 0;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .presenca-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .presenca-header-right {
        flex-direction: column;
        align-items: stretch;
    }
    .presenca-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .presenca-stats {
        grid-template-columns: repeat(2, 1fr);
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
}

@media (max-width: 575.98px) {
    .presenca-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="presenca-header">
    <div class="presenca-header-left">
        <h5>
            <i class="bi bi-clock-history"></i> Presença / Ponto
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($totalPresencas) ?>
            </span>
        </h5>
        <p>Registre e acompanhe a assiduidade dos colaboradores</p>
    </div>
    <div class="presenca-header-right">
        <?php if (usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN])): ?>
            <a href="lancar.php" class="btn btn-primary btn-sm" style="border-radius:10px;padding:0.4rem 1rem;">
                <i class="bi bi-plus-lg me-1"></i> Lançar Presença
            </a>
        <?php endif; ?>
        <a href="relatorio.php" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;padding:0.4rem 1rem;">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Relatório
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;padding:0.4rem 1rem;">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
    </div>
</div>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="presenca-stats">
    <div class="presenca-stat-card">
        <div class="stat-value primary"><?= number_format($totalPresencas) ?></div>
        <div class="stat-label">Total Registos</div>
    </div>
    <div class="presenca-stat-card">
        <div class="stat-value success"><?= number_format($totalPresentes) ?></div>
        <div class="stat-label">Presentes</div>
    </div>
    <div class="presenca-stat-card">
        <div class="stat-value danger"><?= number_format($totalFaltas) ?></div>
        <div class="stat-label">Faltas</div>
    </div>
    <div class="presenca-stat-card">
        <div class="stat-value warning"><?= number_format($totalAtrasos) ?></div>
        <div class="stat-label">Atrasos</div>
    </div>
    <div class="presenca-stat-card">
        <div class="stat-value info"><?= number_format($totalJustificados) ?></div>
        <div class="stat-label">Justificados</div>
    </div>
    <div class="presenca-stat-card">
        <div class="stat-value primary"><?= number_format($totalHorasExtras, 1) ?>h</div>
        <div class="stat-label">Horas Extras</div>
    </div>
</div>

<!-- ============================================================
     FILTER
     ============================================================ -->
<div class="filter-card-premium">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label"><i class="bi bi-calendar-start"></i> Início</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= e($dataInicio) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label"><i class="bi bi-calendar-end"></i> Fim</label>
            <input type="date" name="data_fim" class="form-control" value="<?= e($dataFim) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-person"></i> Funcionário</label>
            <select name="funcionario_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($funcionarios as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= $funcionarioId == $f['id'] ? 'selected' : '' ?>>
                        <?= e($f['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label"><i class="bi bi-circle"></i> Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="presente" <?= $statusFiltro === 'presente' ? 'selected' : '' ?>>✅ Presente</option>
                <option value="falta" <?= $statusFiltro === 'falta' ? 'selected' : '' ?>>❌ Falta</option>
                <option value="atraso" <?= $statusFiltro === 'atraso' ? 'selected' : '' ?>>⏰ Atraso</option>
                <option value="justificado" <?= $statusFiltro === 'justificado' ? 'selected' : '' ?>>📋 Justificado</option>
            </select>
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
                    <th>Data</th>
                    <th>Funcionário</th>
                    <th>Entrada</th>
                    <th>Saída Almoço</th>
                    <th>Retorno</th>
                    <th>Saída</th>
                    <th>Total</th>
                    <th>H. Extras</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($presencas)): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state-premium">
                                <span class="empty-icon"><i class="bi bi-clock-slash"></i></span>
                                <p>Nenhum registro de presença encontrado.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($presencas as $p): ?>
                    <tr>
                        <td><?= formatarData($p['data']) ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--rh-primary),var(--rh-accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.5rem;text-transform:uppercase;flex-shrink:0;">
                                    <?= e(strtoupper(substr($p['funcionario_nome'], 0, 1))) ?>
                                </span>
                                <span><?= e($p['funcionario_nome']) ?></span>
                            </div>
                        </td>
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
                        <td class="text-end">
                            <div class="action-group">
                                <a href="visualizar.php?id=<?= $p['id'] ?>" class="action-btn view" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN])): ?>
                                    <a href="editar.php?id=<?= $p['id'] ?>" class="action-btn edit" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="excluir.php?id=<?= $p['id'] ?>" class="action-btn delete js-confirm" 
                                       data-confirm-msg="Excluir este registro de presença?" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>