<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Relatório de Férias';
$feriasModel = new Ferias();
$funcionarioModel = new Funcionario();

$ano = $_GET['ano'] ?? date('Y');
$status = $_GET['status'] ?? '';
$funcionarioId = $_GET['funcionario_id'] ?? '';

$filtros = [];
if ($status) $filtros['status'] = $status;
if ($funcionarioId) $filtros['funcionario_id'] = $funcionarioId;

$lista = $feriasModel->listar($filtros);

// Estatísticas
$totalSolicitacoes = count($lista);
$totalDias = array_sum(array_column($lista, 'dias'));
$totalPendentes = count(array_filter($lista, fn($f) => $f['status'] === 'pendente'));
$totalAprovados = count(array_filter($lista, fn($f) => $f['status'] === 'aprovado'));
$totalRejeitados = count(array_filter($lista, fn($f) => $f['status'] === 'rejeitado'));

require __DIR__ . '/../../includes/header.php';
?>

<style>
.relatorio-container {
    max-width: 1200px;
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

.filter-row .form-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
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

.stat-card .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
}

.stat-card .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

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

.btn-export {
    border-radius: 10px;
    padding: 0.3rem 0.8rem;
    font-size: 0.7rem;
    font-weight: 600;
}

@media (max-width: 575.98px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .relatorio-card {
        padding: 1rem;
    }
}
</style>

<div class="relatorio-container">
    <div class="relatorio-card">
        <h5 class="mb-4"><i class="bi bi-file-earmark-bar-graph me-2"></i> Relatório de Férias</h5>

        <!-- Filtros -->
        <form method="GET" class="filter-row row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Ano</label>
                <select name="ano" class="form-select">
                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $ano == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    <option value="aprovado" <?= $status === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                    <option value="rejeitado" <?= $status === 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Funcionário</label>
                <select name="funcionario_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($funcionarioModel->listar(['status' => 'ativo']) as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $funcionarioId == $f['id'] ? 'selected' : '' ?>><?= e($f['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                <a href="relatorio.php" class="btn btn-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i></a>
            </div>
        </form>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value primary"><?= number_format($totalSolicitacoes) ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-value warning"><?= number_format($totalPendentes) ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value success"><?= number_format($totalAprovados) ?></div>
                <div class="stat-label">Aprovados</div>
            </div>
            <div class="stat-card">
                <div class="stat-value danger"><?= number_format($totalRejeitados) ?></div>
                <div class="stat-label">Rejeitados</div>
            </div>
            <div class="stat-card">
                <div class="stat-value primary"><?= number_format($totalDias) ?></div>
                <div class="stat-label">Total Dias</div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="table-responsive">
            <table class="table-relatorio table">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Tipo</th>
                        <th>Período</th>
                        <th>Dias</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lista)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma solicitação encontrada.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($lista as $f): ?>
                        <tr>
                            <td><?= e($f['funcionario_nome']) ?></td>
                            <td><?= e(ucfirst(str_replace('_', ' ', $f['tipo']))) ?></td>
                            <td><?= formatarData($f['data_inicio']) ?> → <?= formatarData($f['data_fim']) ?></td>
                            <td><?= $f['dias'] ?> dias</td>
                            <td><span class="badge-status <?= $f['status'] ?>"><?= ucfirst($f['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>