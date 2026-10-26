<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$pageTitle = 'Folha de Pagamento';
$folhaModel = new FolhaPagamento();

$mesReferencia = $_GET['mes'] ?? date('Y-m');
$statusFilter = $_GET['status'] ?? 'todos';
$searchTerm = $_GET['busca'] ?? '';

// Validar formato do mês
if (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia)) {
    $mesReferencia = date('Y-m');
    definirMensagem('warning', 'Mês inválido. Usando o mês atual.');
}

// Processar geração de folha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gerar') {
    validarCsrf();
    
    if (!preg_match('/^\d{4}-\d{2}$/', $_POST['mes_referencia'])) {
        definirMensagem('danger', 'Formato de mês inválido.');
        redirecionar('/modules/folha-pagamento/index.php');
    }
    
    try {
        $resultado = $folhaModel->gerarFolhaMensal($_POST['mes_referencia']);
        registrarAuditoria('folha_gerada', "Mês {$_POST['mes_referencia']} - {$resultado['gerados']} registros");
        definirMensagem('success', 'Folha gerada/recalculada para ' . $resultado['gerados'] . ' funcionário(s).');
    } catch (Exception $e) {
        definirMensagem('danger', 'Erro ao gerar folha: ' . $e->getMessage());
        error_log('Payroll generation error: ' . $e->getMessage());
    }
    
    redirecionar('/modules/folha-pagamento/index.php?mes=' . urlencode($_POST['mes_referencia']));
}

// Buscar dados com filtros
$folhas = $folhaModel->listarComFiltros($mesReferencia, $statusFilter, $searchTerm);
$totalMes = array_sum(array_column($folhas, 'salario_liquido'));
$totalFuncionarios = count($folhas);
$totalPagos = count(array_filter($folhas, function($f) { return $f['status'] === 'pago'; }));
$totalPendentes = $totalFuncionarios - $totalPagos;

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   FOLHA DE PAGAMENTO - DESIGN PREMIUM
   ============================================================ */

/* ----- STATS CARDS ----- */
.folha-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.folha-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.folha-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.folha-stat-card .stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.folha-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.folha-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.folha-stat-card .stat-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.folha-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.folha-stat-card .stat-info .stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--bs-body-color);
    line-height: 1.2;
}

.folha-stat-card .stat-info .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- HEADER ----- */
.folha-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.folha-header-left h5 {
    font-weight: 700;
    color: var(--rh-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.folha-header-left h5 i {
    color: var(--rh-accent);
}

.folha-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.folha-header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.folha-header-right .btn {
    border-radius: 8px;
    font-size: 0.8rem;
    padding: 0.3rem 1rem;
}

/* ----- FILTER CARD ----- */
.filter-card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
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
    border-radius: 8px;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

.filter-card-premium .form-control:focus,
.filter-card-premium .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
}

.filter-card-premium .btn-outline-secondary {
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.75rem;
    border-color: var(--rh-border);
    color: var(--bs-secondary-color);
    transition: all 0.3s ease;
}

.filter-card-premium .btn-outline-secondary:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    transform: translateY(-2px);
}

/* ----- TABLE CARD ----- */
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
    padding: 0.6rem 0.6rem;
    border-bottom: 2px solid var(--rh-border);
    white-space: nowrap;
}

.table-card-premium .table thead th i {
    margin-right: 4px;
    font-size: 0.65rem;
}

.table-card-premium .table tbody td {
    padding: 0.5rem 0.6rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.8rem;
    color: var(--bs-body-color);
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

/* ----- AVATAR ----- */
.avatar-sm {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    color: #fff;
    font-weight: 700;
    font-size: 0.6rem;
    flex-shrink: 0;
    text-transform: uppercase;
}

/* ----- BADGE STATUS ----- */
.badge-status {
    padding: 0.2rem 0.6rem;
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
.badge-status.pago {
    background: #d1fae5;
    color: #065f46;
}

[data-bs-theme="dark"] .badge-status.pendente {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
}
[data-bs-theme="dark"] .badge-status.pago {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}

/* ----- VALUES ----- */
.value-positive {
    color: var(--rh-accent);
    font-weight: 500;
}
.value-negative {
    color: #ef4444;
    font-weight: 500;
}
.value-total {
    font-weight: 700;
    color: var(--bs-body-color);
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
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.75rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    text-decoration: none;
}

.action-btn.view:hover {
    background: var(--rh-info);
    border-color: var(--rh-info);
    color: #fff;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.action-btn.edit:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.action-btn.pay:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
}

.action-btn.receipt:hover {
    background: var(--rh-purple);
    border-color: var(--rh-purple);
    color: #fff;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state-premium .empty-icon {
    font-size: 4rem;
    color: var(--rh-border);
    display: block;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.95rem;
    margin-bottom: 0;
}

.empty-state-premium p strong {
    color: var(--rh-primary);
}

/* ----- FOOTER INFO ----- */
.footer-info-premium {
    color: var(--rh-text-muted);
    font-size: 0.75rem;
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: var(--rh-radius);
    border: 1px solid var(--rh-border);
}

.footer-info-premium i {
    margin-right: 4px;
    color: var(--rh-primary);
}

/* ----- RESPONSIVE ----- */
@media (max-width: 991.98px) {
    .folha-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .folha-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .folha-header-right {
        flex-direction: column;
        align-items: stretch;
    }
    .folha-header-right form {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    .folha-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .folha-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .folha-stat-card {
        padding: 0.75rem 1rem;
    }
    .folha-stat-card .stat-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    .folha-stat-card .stat-info .stat-value {
        font-size: 1.1rem;
    }
    .filter-card-premium .btn-outline-secondary {
        width: 100%;
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
        width: 26px;
        height: 26px;
        font-size: 0.65rem;
    }
}

@media (max-width: 575.98px) {
    .folha-stats {
        grid-template-columns: 1fr;
    }
    .folha-header-left h5 {
        font-size: 1.1rem;
    }
    .badge-status {
        font-size: 0.55rem;
        padding: 0.1rem 0.4rem;
    }
    .value-total {
        font-size: 0.75rem;
    }
}

/* ----- PRINT ----- */
@media print {
    .no-print {
        display: none !important;
    }
    .table-card-premium {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .folha-stat-card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .action-group {
        display: none !important;
    }
}
</style>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="folha-stats">
    <div class="folha-stat-card">
        <div class="stat-icon primary"><i class="bi bi-people"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalFuncionarios) ?></div>
            <div class="stat-label">Total Funcionários</div>
        </div>
    </div>
    <div class="folha-stat-card">
        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalPagos) ?></div>
            <div class="stat-label">Pagamentos Realizados</div>
        </div>
    </div>
    <div class="folha-stat-card">
        <div class="stat-icon warning"><i class="bi bi-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalPendentes) ?></div>
            <div class="stat-label">Pendentes</div>
        </div>
    </div>
    <div class="folha-stat-card">
        <div class="stat-icon info"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= dashMoney($totalMes) ?></div>
            <div class="stat-label">Total Líquido</div>
        </div>
    </div>
</div>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="folha-header">
    <div class="folha-header-left">
        <h5>
            <i class="bi bi-wallet2"></i> Folha de Pagamento
            <span class="badge bg-light text-dark ms-2" style="font-size:0.65rem;font-weight:500;">
                <i class="bi bi-calendar3 me-1"></i> <?= formatarData($mesReferencia . '-01') ?>
            </span>
        </h5>
        <p>Gerencie salários, bônus e descontos dos colaboradores</p>
    </div>
    <div class="folha-header-right">
        <form method="POST" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="acao" value="gerar">
            <input type="month" name="mes_referencia" class="form-control form-control-sm" 
                   value="<?= e($mesReferencia) ?>" required 
                   style="width:auto;min-width:150px;border-radius:8px;">
            <button type="submit" class="btn btn-primary btn-sm js-confirm" 
                    data-confirm-msg="Gerar/recalcular a folha deste mês para todos os funcionários ativos?"
                    style="border-radius:8px;padding:0.3rem 1rem;">
                <i class="bi bi-calculator me-1"></i> Gerar
            </button>
        </form>
        <a href="criar.php?mes=<?= e($mesReferencia) ?>" class="btn btn-success btn-sm" 
           style="border-radius:8px;padding:0.3rem 1rem;">
            <i class="bi bi-plus-circle me-1"></i> Novo
        </a>
    </div>
</div>

<!-- ============================================================
     FILTER CARD
     ============================================================ -->
<div class="filter-card-premium">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3 col-6">
            <label class="form-label"><i class="bi bi-calendar3 me-1"></i> Mês</label>
            <input type="month" name="mes" class="form-control form-control-sm" 
                   value="<?= e($mesReferencia) ?>">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label"><i class="bi bi-funnel me-1"></i> Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="todos" <?= $statusFilter === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="pendente" <?= $statusFilter === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                <option value="pago" <?= $statusFilter === 'pago' ? 'selected' : '' ?>>Pago</option>
            </select>
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label"><i class="bi bi-search me-1"></i> Buscar</label>
            <input type="text" name="busca" class="form-control form-control-sm" 
                   placeholder="Funcionário..." value="<?= e($searchTerm) ?>">
        </div>
        <div class="col-md-auto col-6">
            <button class="btn btn-outline-secondary w-100 btn-sm" style="border-radius:8px;">
                <i class="bi bi-search me-1"></i> Filtrar
            </button>
        </div>
        <div class="col-md-auto ms-md-auto text-end">
            <div class="text-muted small"><i class="bi bi-cash-coin me-1"></i> Total líquido</div>
            <div class="fs-5 fw-bold" style="color:var(--rh-primary);"><?= dashMoney($totalMes) ?></div>
        </div>
        <div class="col-md-auto d-flex gap-1">
            <a href="exportar_csv.php?mes=<?= e($mesReferencia) ?>" class="btn btn-outline-secondary btn-sm" 
               style="border-radius:8px;">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </a>
            <a href="exportar_pdf.php?mes=<?= e($mesReferencia) ?>" class="btn btn-outline-secondary btn-sm" 
               target="_blank" style="border-radius:8px;">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
            <a href="relatorio.php?ano=<?= date('Y', strtotime($mesReferencia)) ?>" class="btn btn-outline-secondary btn-sm" 
               style="border-radius:8px;" title="Relatório">
                <i class="bi bi-graph-up"></i>
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm" 
                    style="border-radius:8px;" title="Imprimir">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </form>
</div>

<!-- ============================================================
     TABLE CARD
     ============================================================ -->
<div class="table-card-premium">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="bi bi-person"></i> Funcionário</th>
                    <th><i class="bi bi-building"></i> Departamento</th>
                    <th><i class="bi bi-cash-coin"></i> Base</th>
                    <th><i class="bi bi-clock"></i> H. Extras</th>
                    <th><i class="bi bi-gift"></i> Bônus</th>
                    <th><i class="bi bi-x-circle"></i> Faltas</th>
                    <th><i class="bi bi-scissors"></i> Descontos</th>
                    <th><i class="bi bi-coins"></i> Líquido</th>
                    <th><i class="bi bi-circle"></i> Status</th>
                    <th class="text-end"><i class="bi bi-gear"></i> Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($folhas)): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state-premium">
                                <span class="empty-icon"><i class="bi bi-file-earmark-text"></i></span>
                                <p>Nenhuma folha encontrada para este mês. Use <strong>"Gerar/Recalcular"</strong> acima.</p>
                                <?php if ($searchTerm): ?>
                                    <p class="mt-2">
                                        <a href="?mes=<?= e($mesReferencia) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-x-circle me-1"></i> Limpar busca
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($folhas as $f): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm"><?= e(strtoupper(substr($f['funcionario_nome'] ?? 'N', 0, 1))) ?></span>
                                <span style="font-weight:500;font-size:0.8rem;"><?= e($f['funcionario_nome'] ?? 'N/A') ?></span>
                            </div>
                        </td>
                        <td style="color:var(--rh-text-muted);font-size:0.75rem;">
                            <?= e($f['departamento_nome'] ?? '-') ?>
                        </td>
                        <td style="font-weight:500;"><?= dashMoney($f['salario_base'] ?? 0) ?></td>
                        <td class="value-positive">+<?= dashMoney($f['horas_extras_valor'] ?? 0) ?></td>
                        <td class="value-positive">+<?= dashMoney($f['bonus'] ?? 0) ?></td>
                        <td class="value-negative">-<?= dashMoney($f['faltas_desconto'] ?? 0) ?></td>
                        <td class="value-negative">-<?= dashMoney($f['descontos'] ?? 0) ?></td>
                        <td class="value-total"><?= dashMoney($f['salario_liquido'] ?? 0) ?></td>
                        <td>
                            <span class="badge-status <?= $f['status'] ?? 'pendente' ?>">
                                <i class="bi <?= ($f['status'] ?? 'pendente') === 'pago' ? 'bi-check-circle' : 'bi-clock' ?>"></i>
                                <?= ucfirst($f['status'] ?? 'Pendente') ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="visualizar.php?id=<?= $f['id'] ?>" class="action-btn view" title="Visualizar detalhes">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar.php?id=<?= $f['id'] ?>" class="action-btn edit" title="Ajustar bônus/descontos">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="recibo.php?id=<?= $f['id'] ?>" class="action-btn receipt" title="Ver recibo" target="_blank">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <?php if (($f['status'] ?? '') === 'pendente'): ?>
                                    <a href="pagar.php?id=<?= $f['id'] ?>&mes=<?= e($mesReferencia) ?>" 
                                       class="action-btn pay js-confirm" 
                                       data-confirm-msg="Confirmar pagamento de <strong><?= e($f['funcionario_nome'] ?? '') ?></strong>?">
                                        <i class="bi bi-check-circle"></i>
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

<!-- ============================================================
     FOOTER INFO
     ============================================================ -->
<div class="footer-info-premium">
    <i class="bi bi-info-circle"></i>
    <strong>Cálculo:</strong> salário líquido = salário base + bônus + valor de horas extras (1,5×) − descontos − desconto por faltas não justificadas.
    Impostos e encargos (INSS/IRRF/FGTS ou equivalentes locais) não são calculados automaticamente — ajuste manualmente em <strong>"Descontos"</strong> conforme a legislação aplicável.
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>