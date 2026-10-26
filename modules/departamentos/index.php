<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// ============================================================
// PERMISSÕES ATUALIZADAS
// ============================================================
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Departamentos';
$departamentoModel = new Departamento();
$departamentos = $departamentoModel->listar();

// Calcular estatísticas
$totalDepartamentos = count($departamentos);
$totalAtivos = 0;
$totalInativos = 0;
$totalFuncionarios = 0;

foreach ($departamentos as $d) {
    if ($d['ativo']) {
        $totalAtivos++;
    } else {
        $totalInativos++;
    }
    $totalFuncionarios += $d['total_funcionarios'] ?? 0;
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   DEPARTAMENTOS - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER ----- */
.dept-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.dept-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dept-header-left h5 i {
    color: var(--rh-accent);
}

.dept-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.dept-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* ----- STATS CARDS ----- */
.dept-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.dept-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem 1.5rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dept-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.dept-stat-card .stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.dept-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.dept-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.dept-stat-card .stat-icon.danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}
.dept-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.dept-stat-card .stat-info .stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.dept-stat-card .stat-info .stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- TABLE CARD ----- */
.dept-table-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    overflow: hidden;
    transition: all 0.3s ease;
}

.dept-table-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.dept-table-card .table {
    margin-bottom: 0;
}

.dept-table-card .table thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.85rem 1rem;
    border-bottom: 2px solid var(--rh-border);
}

.dept-table-card .table thead th i {
    margin-right: 6px;
    font-size: 0.7rem;
}

.dept-table-card .table tbody td {
    padding: 0.7rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    color: var(--rh-text-primary);
}

.dept-table-card .table tbody tr {
    transition: all 0.2s ease;
}

.dept-table-card .table tbody tr:hover {
    background: rgba(21, 94, 117, 0.03);
}

.dept-table-card .table tbody tr:last-child td {
    border-bottom: none;
}

/* ----- DEPARTAMENTO INFO ----- */
.dept-info {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.dept-info .dept-icon {
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

.dept-info:hover .dept-icon {
    background: var(--rh-primary);
    color: #fff;
    transform: scale(1.05) rotate(-3deg);
}

.dept-info .dept-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--rh-text-primary);
}

.dept-info .dept-meta {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.dept-info .dept-meta i {
    margin-right: 4px;
}

/* ----- GESTOR ----- */
.gestor-info {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.gestor-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.7rem;
    flex-shrink: 0;
    text-transform: uppercase;
}

.gestor-avatar.empty {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
}

.gestor-nome {
    font-weight: 500;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
}

.gestor-nome .text-muted {
    font-weight: 400;
}

/* ----- BADGES ----- */
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

.count-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.7rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.75rem;
    font-weight: 500;
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
    .dept-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .dept-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .dept-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .dept-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .dept-stat-card {
        padding: 1rem;
    }
    .dept-stat-card .stat-icon {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }
    .dept-stat-card .stat-info .stat-value {
        font-size: 1.2rem;
    }
    .dept-table-card .table thead th {
        font-size: 0.6rem;
        padding: 0.5rem 0.6rem;
    }
    .dept-table-card .table tbody td {
        padding: 0.5rem 0.6rem;
        font-size: 0.8rem;
    }
    .dept-info .dept-icon {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
    }
    .dept-info .dept-name {
        font-size: 0.85rem;
    }
    .action-btn {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
}

@media (max-width: 575.98px) {
    .dept-stats {
        grid-template-columns: 1fr;
    }
    .dept-header-left h5 {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="dept-header">
    <div class="dept-header-left">
        <h5>
            <i class="bi bi-diagram-3"></i> Departamentos
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($totalDepartamentos) ?>
            </span>
        </h5>
        <p>Gerencie a estrutura organizacional da empresa</p>
    </div>
    <div class="dept-header-right">
        <a href="criar.php" class="btn btn-primary btn-sm" style="border-radius:10px;padding:0.5rem 1.2rem;">
            <i class="bi bi-plus-lg me-1"></i> Novo Departamento
        </a>
    </div>
</div>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="dept-stats">
    <div class="dept-stat-card animate-in">
        <div class="stat-icon primary"><i class="bi bi-building"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalDepartamentos) ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="dept-stat-card animate-in">
        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAtivos) ?></div>
            <div class="stat-label">Ativos</div>
        </div>
    </div>
    <div class="dept-stat-card animate-in">
        <div class="stat-icon danger"><i class="bi bi-x-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalInativos) ?></div>
            <div class="stat-label">Inativos</div>
        </div>
    </div>
    <div class="dept-stat-card animate-in">
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
<div class="dept-table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="bi bi-building"></i> Departamento</th>
                    <th><i class="bi bi-person-badge"></i> Gestor</th>
                    <th><i class="bi bi-people"></i> Funcionários</th>
                    <th><i class="bi bi-circle"></i> Status</th>
                    <th class="text-end"><i class="bi bi-gear"></i> Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departamentos)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <span class="empty-icon"><i class="bi bi-building-slash"></i></span>
                                <h6>Nenhum departamento cadastrado</h6>
                                <p>Clique em <strong>"Novo Departamento"</strong> para começar.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($departamentos as $d): ?>
                    <tr>
                        <td>
                            <div class="dept-info">
                                <div class="dept-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <div class="dept-name"><?= e($d['nome']) ?></div>
                                    <div class="dept-meta">
                                        <i class="bi bi-calendar3"></i> Criado em <?= formatarData($d['criado_em'] ?? date('Y-m-d')) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($d['gestor_nome'])): ?>
                                <div class="gestor-info">
                                    <span class="gestor-avatar"><?= e(strtoupper(substr($d['gestor_nome'], 0, 1))) ?></span>
                                    <span class="gestor-nome"><?= e($d['gestor_nome']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:0.8rem;">
                                    <i class="bi bi-person-slash me-1"></i> Sem gestor
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="count-badge">
                                <i class="bi bi-people"></i> <?= $d['total_funcionarios'] ?? 0 ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-status <?= $d['ativo'] ? 'ativo' : 'inativo' ?>">
                                <i class="bi <?= $d['ativo'] ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                                <?= $d['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="visualizar.php?id=<?= $d['id'] ?>" class="action-btn edit" title="Visualizar" style="background:var(--rh-primary);border-color:var(--rh-primary);color:#fff;">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar.php?id=<?= $d['id'] ?>" class="action-btn edit" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($departamentoModel->podeExcluir($d['id'])): ?>
                                    <a href="excluir.php?id=<?= $d['id'] ?>" class="action-btn delete js-confirm" 
                                       data-confirm-msg="Excluir o departamento <strong><?= e($d['nome']) ?></strong>?" 
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