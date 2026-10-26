<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$pageTitle = 'Recrutamento e Seleção';
$vagaModel = new Vaga();
$status = $_GET['status'] ?? '';
$vagas = $vagaModel->listar($status ?: null);

// Estatísticas
$totalVagas = count($vagas);
$totalAbertas = count(array_filter($vagas, function($v) { return $v['status'] === 'aberta'; }));
$totalPausadas = count(array_filter($vagas, function($v) { return $v['status'] === 'pausada'; }));
$totalFechadas = count(array_filter($vagas, function($v) { return $v['status'] === 'fechada'; }));
$totalCandidatos = array_sum(array_column($vagas, 'total_candidatos'));

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   RECRUTAMENTO - DESIGN PREMIUM
   ============================================================ */

/* ----- STATS CARDS ----- */
.recrutamento-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.recrutamento-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.recrutamento-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.recrutamento-stat-card .stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.recrutamento-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.recrutamento-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.recrutamento-stat-card .stat-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.recrutamento-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.recrutamento-stat-card .stat-info .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--bs-body-color);
    line-height: 1.2;
}

.recrutamento-stat-card .stat-info .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- HEADER ----- */
.recrutamento-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.recrutamento-header-left h5 {
    font-weight: 700;
    color: var(--rh-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.recrutamento-header-left h5 i {
    color: var(--rh-accent);
}

.recrutamento-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.recrutamento-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
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

.filter-card-premium .form-select {
    border-radius: 8px;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

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

/* ----- VAGA CARD ----- */
.vaga-card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.vaga-card-premium:hover {
    transform: translateY(-4px);
    box-shadow: var(--rh-shadow-hover);
}

.vaga-card-premium .vaga-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.vaga-card-premium .vaga-header h6 {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin: 0;
    flex: 1;
    margin-right: 0.5rem;
}

.vaga-card-premium .vaga-meta {
    font-size: 0.75rem;
    color: var(--rh-text-muted);
    margin-bottom: 0.5rem;
}

.vaga-card-premium .vaga-meta i {
    margin-right: 4px;
}

.vaga-card-premium .vaga-descricao {
    font-size: 0.82rem;
    color: var(--rh-text-muted);
    line-height: 1.5;
    flex: 1;
    margin-bottom: 1rem;
}

.vaga-card-premium .vaga-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--rh-border);
}

.vaga-card-premium .vaga-footer .candidatos-badge {
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.7rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.vaga-card-premium .vaga-footer .btn-group-actions {
    display: flex;
    gap: 0.3rem;
}

.vaga-card-premium .vaga-footer .btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.3rem 0.7rem;
    border-radius: 8px;
    border: 1px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
    font-size: 0.7rem;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
}

.vaga-card-premium .vaga-footer .btn-ghost:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.25);
}

.vaga-card-premium .vaga-footer .btn-ghost.edit:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
}

/* ----- BADGE STATUS ----- */
.badge-status {
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    white-space: nowrap;
}

.badge-status.aberta {
    background: #d1fae5;
    color: #065f46;
}
.badge-status.pausada {
    background: #fef3c7;
    color: #92400e;
}
.badge-status.fechada {
    background: #fee2e2;
    color: #991b1b;
}

[data-bs-theme="dark"] .badge-status.aberta {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}
[data-bs-theme="dark"] .badge-status.pausada {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
}
[data-bs-theme="dark"] .badge-status.fechada {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    padding: 3rem 1rem;
    text-align: center;
    grid-column: 1 / -1;
}

.empty-state-premium .empty-icon {
    font-size: 3.5rem;
    color: var(--rh-border);
    display: block;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.empty-state-premium h6 {
    font-weight: 600;
    color: var(--bs-body-color);
    margin-bottom: 0.25rem;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 991.98px) {
    .recrutamento-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .recrutamento-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .recrutamento-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .recrutamento-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .recrutamento-stat-card {
        padding: 0.75rem 1rem;
    }
    .recrutamento-stat-card .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .recrutamento-stat-card .stat-info .stat-value {
        font-size: 1.1rem;
    }
    .vaga-card-premium .vaga-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .vaga-card-premium .vaga-footer .btn-group-actions {
        justify-content: center;
    }
}

@media (max-width: 575.98px) {
    .recrutamento-stats {
        grid-template-columns: 1fr;
    }
    .recrutamento-header-left h5 {
        font-size: 1.1rem;
    }
    .vaga-card-premium {
        padding: 1rem;
    }
    .vaga-card-premium .vaga-header h6 {
        font-size: 0.85rem;
    }
    .vaga-card-premium .vaga-descricao {
        font-size: 0.75rem;
    }
}
</style>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="recrutamento-stats">
    <div class="recrutamento-stat-card">
        <div class="stat-icon primary"><i class="bi bi-briefcase"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalVagas) ?></div>
            <div class="stat-label">Total Vagas</div>
        </div>
    </div>
    <div class="recrutamento-stat-card">
        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAbertas) ?></div>
            <div class="stat-label">Abertas</div>
        </div>
    </div>
    <div class="recrutamento-stat-card">
        <div class="stat-icon warning"><i class="bi bi-pause-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalPausadas) ?></div>
            <div class="stat-label">Pausadas</div>
        </div>
    </div>
    <div class="recrutamento-stat-card">
        <div class="stat-icon info"><i class="bi bi-people"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalCandidatos) ?></div>
            <div class="stat-label">Total Candidatos</div>
        </div>
    </div>
</div>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="recrutamento-header">
    <div class="recrutamento-header-left">
        <h5>
            <i class="bi bi-people-fill"></i> Recrutamento e Seleção
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($totalVagas) ?> vagas
            </span>
        </h5>
        <p>Gerencie vagas, candidatos e processos seletivos</p>
    </div>
    <div class="recrutamento-header-right">
        <a href="criar_vaga.php" class="btn btn-primary btn-sm" style="border-radius:10px;padding:0.5rem 1.2rem;">
            <i class="bi bi-plus-lg me-1"></i> Nova Vaga
        </a>
    </div>
</div>

<!-- ============================================================
     FILTER CARD
     ============================================================ -->
<div class="filter-card-premium">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-funnel me-1"></i> Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="aberta" <?= $status === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                <option value="pausada" <?= $status === 'pausada' ? 'selected' : '' ?>>Pausada</option>
                <option value="fechada" <?= $status === 'fechada' ? 'selected' : '' ?>>Fechada</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn-filter w-100">
                <i class="bi bi-funnel me-1"></i> Filtrar
            </button>
        </div>
    </form>
</div>

<!-- ============================================================
     VAGAS CARDS
     ============================================================ -->
<div class="row g-3">
    <?php if (empty($vagas)): ?>
        <div class="col-12">
            <div class="empty-state-premium">
                <span class="empty-icon"><i class="bi bi-briefcase-slash"></i></span>
                <h6>Nenhuma vaga encontrada</h6>
                <p>Clique em <strong>"Nova Vaga"</strong> para começar o processo de recrutamento.</p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php foreach ($vagas as $v): ?>
        <div class="col-md-6 col-lg-4">
            <div class="vaga-card-premium">
                <div class="vaga-header">
                    <h6><?= e($v['titulo']) ?></h6>
                    <span class="badge-status <?= $v['status'] ?>">
                        <i class="bi <?= $v['status'] === 'aberta' ? 'bi-check-circle' : ($v['status'] === 'pausada' ? 'bi-pause-circle' : 'bi-x-circle') ?>"></i>
                        <?= ucfirst($v['status']) ?>
                    </span>
                </div>
                <div class="vaga-meta">
                    <i class="bi bi-building"></i> <?= e($v['departamento_nome']) ?>
                    <span class="mx-1">·</span>
                    <i class="bi bi-calendar3"></i> <?= formatarData($v['data_publicacao']) ?>
                </div>
                <div class="vaga-descricao">
                    <?= e(truncarTexto($v['descricao'] ?? '', 120)) ?>
                </div>
                <div class="vaga-footer">
                    <span class="candidatos-badge">
                        <i class="bi bi-person"></i> <?= $v['total_candidatos'] ?? 0 ?> candidato(s)
                    </span>
                    <div class="btn-group-actions">
                        <a href="vaga.php?id=<?= $v['id'] ?>" class="btn-ghost" title="Ver candidatos">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                        <a href="editar_vaga.php?id=<?= $v['id'] ?>" class="btn-ghost edit" title="Editar vaga">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>