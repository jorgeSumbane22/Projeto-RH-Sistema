<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$pageTitle = 'Relatório de Pagamentos';
$folhaModel = new FolhaPagamento();
$ano = $_GET['ano'] ?? date('Y');

$dados = [];
$meses = range(1, 12);

foreach ($meses as $mes) {
    $mesRef = sprintf('%04d-%02d', $ano, $mes);
    $folhas = $folhaModel->listar($mesRef);
    $total = array_sum(array_column($folhas, 'salario_liquido'));
    $totalFuncionarios = count($folhas);
    $pagos = count(array_filter($folhas, function($f) { return $f['status'] === 'pago'; }));
    
    $dados[] = [
        'mes' => $mesRef,
        'total' => $total,
        'funcionarios' => $totalFuncionarios,
        'pagos' => $pagos
    ];
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
.relatorio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.relatorio-stat {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem;
    text-align: center;
}

.relatorio-stat .stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--rh-primary);
}

.relatorio-stat .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.progress-taxa {
    height: 6px;
    border-radius: 3px;
    background: var(--bs-tertiary-bg);
    overflow: hidden;
}

.progress-taxa .bar {
    height: 100%;
    border-radius: 3px;
    background: linear-gradient(90deg, var(--rh-accent), var(--rh-primary));
    transition: width 0.6s ease;
}

@media print {
    .no-print { display: none !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">
        <i class="bi bi-graph-up me-2" style="color:var(--rh-primary);"></i> 
        Relatório de Pagamentos - <?= $ano ?>
    </h5>
    <div class="no-print d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            <input type="number" name="ano" class="form-control form-control-sm" 
                   value="<?= e($ano) ?>" style="width:100px;border-radius:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;">
                <i class="bi bi-search"></i>
            </button>
        </form>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
            <i class="bi bi-printer"></i>
        </button>
    </div>
</div>

<?php 
$totalGeral = array_sum(array_column($dados, 'total'));
$totalFuncionarios = array_sum(array_column($dados, 'funcionarios'));
$totalPagos = array_sum(array_column($dados, 'pagos'));
$mesesComDados = count(array_filter($dados, function($d) { return $d['total'] > 0; }));
$mediaMensal = $mesesComDados > 0 ? $totalGeral / $mesesComDados : 0;
?>

<div class="relatorio-grid">
    <div class="relatorio-stat">
        <div class="stat-value"><?= dashMoney($totalGeral) ?></div>
        <div class="stat-label">Total Pago no Ano</div>
    </div>
    <div class="relatorio-stat">
        <div class="stat-value"><?= number_format($totalFuncionarios) ?></div>
        <div class="stat-label">Total Folhas</div>
    </div>
    <div class="relatorio-stat">
        <div class="stat-value"><?= number_format($totalPagos) ?></div>
        <div class="stat-label">Pagamentos Realizados</div>
    </div>
    <div class="relatorio-stat">
        <div class="stat-value"><?= dashMoney($mediaMensal) ?></div>
        <div class="stat-label">Média Mensal</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th class="text-end">Total Pago</th>
                        <th class="text-center">Funcionários</th>
                        <th class="text-center">Pagamentos</th>
                        <th class="text-center">Taxa</th>
                        <th class="text-end no-print">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $d): ?>
                        <tr>
                            <td><strong><?= formatarData($d['mes'] . '-01') ?></strong></td>
                            <td class="text-end fw-bold"><?= dashMoney($d['total']) ?></td>
                            <td class="text-center"><?= number_format($d['funcionarios']) ?></td>
                            <td class="text-center">
                                <span class="badge-status <?= $d['pagos'] > 0 ? 'pago' : 'pendente' ?>">
                                    <?= number_format($d['pagos']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $taxa = $d['funcionarios'] > 0 
                                        ? round(($d['pagos'] / $d['funcionarios']) * 100) 
                                        : 0;
                                ?>
                                <div class="progress-taxa">
                                    <div class="bar" style="width:<?= $taxa ?>%;"></div>
                                </div>
                                <small class="text-muted"><?= $taxa ?>%</small>
                            </td>
                            <td class="text-end no-print">
                                <a href="index.php?mes=<?= $d['mes'] ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>