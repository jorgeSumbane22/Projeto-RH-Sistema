<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// ============================================================
// PERMISSÕES ATUALIZADAS
// ============================================================
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Cargos';
$cargoModel = new Cargo();
$cargos = $cargoModel->listar();

// Calcular estatísticas
$totalCargos = count($cargos);
$totalAtivos = 0;
$totalInativos = 0;
$totalSalario = 0;
$totalFuncionarios = 0;

foreach ($cargos as $c) {
    if ($c['ativo']) {
        $totalAtivos++;
    } else {
        $totalInativos++;
    }
    $totalSalario += $c['salario_base'] ?? 0;
    $totalFuncionarios += $c['total_funcionarios'] ?? 0;
}
$mediaSalarial = $totalCargos > 0 ? $totalSalario / $totalCargos : 0;

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   CARGOS - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER ----- */
.cargo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.cargo-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cargo-header-left h5 i {
    color: var(--rh-accent);
}

.cargo-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.cargo-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* ----- STATS CARDS ----- */
.cargo-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.cargo-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem 1.5rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cargo-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.cargo-stat-card .stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.cargo-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.cargo-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.cargo-stat-card .stat-icon.danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}
.cargo-stat-card .stat-icon.purple {
    background: rgba(139, 92, 246, 0.12);
    color: #8b5cf6;
}
.cargo-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.cargo-stat-card .stat-info .stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.cargo-stat-card .stat-info .stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- TABLE CARD ----- */
.cargo-table-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    overflow: hidden;
    transition: all 0.3s ease;
}

.cargo-table-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.cargo-table-card .table {
    margin-bottom: 0;
}

.cargo-table-card .table thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.85rem 1rem;
    border-bottom: 2px solid var(--rh-border);
}

.cargo-table-card .table thead th i {
    margin-right: 6px;
    font-size: 0.7rem;
}

.cargo-table-card .table tbody td {
    padding: 0.7rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    color: var(--rh-text-primary);
}

.cargo-table-card .table tbody tr {
    transition: all 0.2s ease;
}

.cargo-table-card .table tbody tr:hover {
    background: rgba(21, 94, 117, 0.03);
}

.cargo-table-card .table tbody tr:last-child td {
    border-bottom: none;
}

/* ----- CARGO INFO ----- */
.cargo-info {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.cargo-info .cargo-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    background: var(--bs-tertiary-bg);
    color: var(--rh-primary);
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.cargo-info:hover .cargo-icon {
    background: var(--rh-primary);
    color: #fff;
    transform: scale(1.05) rotate(-3deg);
}

.cargo-info .cargo-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--rh-text-primary);
}

.cargo-info .cargo-meta {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.cargo-info .cargo-meta i {
    margin-right: 4px;
}

/* ----- DEPARTMENT BADGE ----- */
.dept-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.75rem;
    font-weight: 500;
}

.dept-badge i {
    font-size: 0.65rem;
    color: var(--rh-primary);
}

/* ----- SALARY ----- */
.salary-value {
    font-weight: 600;
    color: var(--rh-accent);
    font-size: 0.9rem;
}

/* ----- BADGE STATUS ----- */
.badge-status {
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.badge-status.ativo {
    background: #d1fae5;
    color: #065f46;
}
.badge-status.inativo {
    background: #fee2e2;
    color: #991b1b;
}

[data-bs-theme="dark"] .badge-status.ativo {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}
[data-bs-theme="dark"] .badge-status.inativo {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

/* ----- FUNCIONÁRIOS COUNT ----- */
.func-count {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.5rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.65rem;
    font-weight: 500;
}

.func-count i {
    font-size: 0.6rem;
    color: var(--rh-primary);
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
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.85rem;
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

.action-btn.edit:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
}

.action-btn.delete:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* ----- EMPTY STATE ----- */
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state .empty-icon {
    font-size: 3.5rem;
    color: var(--rh-border);
    margin-bottom: 1rem;
    display: block;
    opacity: 0.5;
}

.empty-state h6 {
    font-weight: 600;
    color: var(--rh-text-primary);
    margin-bottom: 0.25rem;
}

.empty-state p {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* ----- ANIMAÇÃO ----- */
.animate-in {
    animation: fadeUp 0.5s ease both;
}
.animate-in:nth-child(1) { animation-delay: 0.05s; }
.animate-in:nth-child(2) { animation-delay: 0.10s; }
.animate-in:nth-child(3) { animation-delay: 0.15s; }
.animate-in:nth-child(4) { animation-delay: 0.20s; }

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ----- RESPONSIVE ----- */
@media (max-width: 991.98px) {
    .cargo-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .cargo-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .cargo-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .cargo-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .cargo-stat-card {
        padding: 1rem;
    }
    .cargo-stat-card .stat-icon {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }
    .cargo-stat-card .stat-info .stat-value {
        font-size: 1.2rem;
    }
    .cargo-table-card .table thead th {
        font-size: 0.6rem;
        padding: 0.5rem 0.6rem;
    }
    .cargo-table-card .table tbody td {
        padding: 0.5rem 0.6rem;
        font-size: 0.8rem;
    }
    .cargo-info .cargo-icon {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
    }
    .cargo-info .cargo-name {
        font-size: 0.85rem;
    }
    .action-btn {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
    .dept-badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.5rem;
    }
    .salary-value {
        font-size: 0.8rem;
    }
}

@media (max-width: 575.98px) {
    .cargo-stats {
        grid-template-columns: 1fr;
    }
    .cargo-header-left h5 {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="cargo-header">
    <div class="cargo-header-left">
        <h5>
            <i class="bi bi-briefcase"></i> Cargos
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($totalCargos) ?>
            </span>
        </h5>
        <p>Gerencie os cargos e salários da organização</p>
    </div>
    <div class="cargo-header-right">
        <a href="criar.php" class="btn btn-primary btn-sm" style="border-radius:10px;padding:0.5rem 1.2rem;">
            <i class="bi bi-plus-lg me-1"></i> Novo Cargo
        </a>
    </div>
</div>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="cargo-stats">
    <div class="cargo-stat-card animate-in">
        <div class="stat-icon primary"><i class="bi bi-briefcase"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalCargos) ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="cargo-stat-card animate-in">
        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAtivos) ?></div>
            <div class="stat-label">Ativos</div>
        </div>
    </div>
    <div class="cargo-stat-card animate-in">
        <div class="stat-icon danger"><i class="bi bi-x-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalInativos) ?></div>
            <div class="stat-label">Inativos</div>
        </div>
    </div>
    <div class="cargo-stat-card animate-in">
        <div class="stat-icon info"><i class="bi bi-people"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalFuncionarios) ?></div>
            <div class="stat-label">Funcionários</div>
        </div>
    </div>
</div>

<!-- ============================================================
     TABLE CARD
     ============================================================ -->
<div class="cargo-table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="bi bi-briefcase"></i> Cargo</th>
                    <th><i class="bi bi-building"></i> Departamento</th>
                    <th><i class="bi bi-people"></i> Funcionários</th>
                    <th><i class="bi bi-cash-coin"></i> Salário base</th>
                    <th><i class="bi bi-circle"></i> Status</th>
                    <th class="text-end"><i class="bi bi-gear"></i> Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cargos)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="empty-icon"><i class="bi bi-briefcase-slash"></i></span>
                                <h6>Nenhum cargo cadastrado</h6>
                                <p>Clique em <strong>"Novo Cargo"</strong> para começar.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($cargos as $c): ?>
                    <tr>
                        <td>
                            <div class="cargo-info">
                                <div class="cargo-icon">
                                    <i class="bi bi-briefcase"></i>
                                </div>
                                <div>
                                    <div class="cargo-name"><?= e($c['titulo']) ?></div>
                                    <div class="cargo-meta">
                                        <i class="bi bi-calendar3"></i> Criado em <?= formatarData($c['criado_em'] ?? date('Y-m-d')) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="dept-badge">
                                <i class="bi bi-building"></i>
                                <?= e($c['departamento_nome'] ?? 'Sem departamento') ?>
                            </span>
                        </td>
                        <td>
                            <span class="func-count">
                                <i class="bi bi-people"></i>
                                <?= $c['total_funcionarios'] ?? 0 ?>
                            </span>
                        </td>
                        <td>
                            <span class="salary-value">
                                <i class="bi bi-cash-coin me-1"></i>
                                <?= dashMoney($c['salario_base'] ?? 0) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-status <?= $c['ativo'] ? 'ativo' : 'inativo' ?>">
                                <i class="bi <?= $c['ativo'] ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                                <?= $c['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="visualizar.php?id=<?= $c['id'] ?>" class="action-btn view" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar.php?id=<?= $c['id'] ?>" class="action-btn edit" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($cargoModel->podeExcluir($c['id'])): ?>
                                    <a href="excluir.php?id=<?= $c['id'] ?>" class="action-btn delete js-confirm" 
                                       data-confirm-msg="Excluir o cargo <strong><?= e($c['titulo']) ?></strong>?" 
                                       title="Excluir">
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