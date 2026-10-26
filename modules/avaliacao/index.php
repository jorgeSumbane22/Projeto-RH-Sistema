<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR]);

$pageTitle = 'Avaliações de Desempenho';
$avaliacaoModel = new Avaliacao();
$funcionarioModel = new Funcionario();

$filtros = [];
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    $filtros['departamento_id'] = $meuFuncionario['departamento_id'] ?? 0;
}

$avaliacoes = $avaliacaoModel->listar($filtros);

// Estatísticas
$totalAvaliacoes = count($avaliacoes);
$notaMedia = $totalAvaliacoes > 0 ? array_sum(array_column($avaliacoes, 'nota_final')) / $totalAvaliacoes : 0;
$aprovadas = count(array_filter($avaliacoes, function($a) { return $a['nota_final'] >= 4; }));
$melhorNota = $totalAvaliacoes > 0 ? max(array_column($avaliacoes, 'nota_final')) : 0;
$piorNota = $totalAvaliacoes > 0 ? min(array_column($avaliacoes, 'nota_final')) : 0;

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   AVALIAÇÕES - DESIGN PREMIUM
   ============================================================ */

/* ----- STATS CARDS ----- */
.avaliacao-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.avaliacao-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.avaliacao-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.avaliacao-stat-card .stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.avaliacao-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.avaliacao-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.avaliacao-stat-card .stat-icon.purple {
    background: rgba(139, 92, 246, 0.12);
    color: #8b5cf6;
}
.avaliacao-stat-card .stat-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}

.avaliacao-stat-card .stat-info .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--bs-body-color);
    line-height: 1.2;
}

.avaliacao-stat-card .stat-info .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- HEADER ----- */
.avaliacao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.avaliacao-header-left h5 {
    font-weight: 700;
    color: var(--rh-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.avaliacao-header-left h5 i {
    color: var(--rh-accent);
}

.avaliacao-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.avaliacao-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
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
    padding: 0.6rem 1rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-card-premium .table thead th i {
    margin-right: 4px;
    font-size: 0.65rem;
}

.table-card-premium .table tbody td {
    padding: 0.5rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.85rem;
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

/* ----- BADGE NOTA ----- */
.badge-nota {
    padding: 0.25rem 0.7rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.badge-nota.excelente {
    background: #d1fae5;
    color: #065f46;
}
.badge-nota.bom {
    background: #dbeafe;
    color: #1e40af;
}
.badge-nota.regular {
    background: #fef3c7;
    color: #92400e;
}
.badge-nota.insuficiente {
    background: #fee2e2;
    color: #991b1b;
}

[data-bs-theme="dark"] .badge-nota.excelente {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}
[data-bs-theme="dark"] .badge-nota.bom {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
}
[data-bs-theme="dark"] .badge-nota.regular {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
}
[data-bs-theme="dark"] .badge-nota.insuficiente {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    padding: 2.5rem 1rem;
    text-align: center;
}

.empty-state-premium .empty-icon {
    font-size: 3.5rem;
    color: var(--rh-border);
    display: block;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 991.98px) {
    .avaliacao-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .avaliacao-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .avaliacao-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .avaliacao-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .avaliacao-stat-card {
        padding: 0.75rem 1rem;
    }
    .avaliacao-stat-card .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .avaliacao-stat-card .stat-info .stat-value {
        font-size: 1.1rem;
    }
    .table-card-premium .table thead th {
        font-size: 0.5rem;
        padding: 0.4rem 0.5rem;
    }
    .table-card-premium .table tbody td {
        padding: 0.4rem 0.5rem;
        font-size: 0.75rem;
    }
    .badge-nota {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
    }
}

@media (max-width: 575.98px) {
    .avaliacao-stats {
        grid-template-columns: 1fr;
    }
    .avaliacao-header-left h5 {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="avaliacao-stats">
    <div class="avaliacao-stat-card">
        <div class="stat-icon primary"><i class="bi bi-star"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAvaliacoes) ?></div>
            <div class="stat-label">Total Avaliações</div>
        </div>
    </div>
    <div class="avaliacao-stat-card">
        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($aprovadas) ?></div>
            <div class="stat-label">Excelentes (≥4.0)</div>
        </div>
    </div>
    <div class="avaliacao-stat-card">
        <div class="stat-icon purple"><i class="bi bi-bar-chart"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($notaMedia, 2) ?></div>
            <div class="stat-label">Média Geral</div>
        </div>
    </div>
    <div class="avaliacao-stat-card">
        <div class="stat-icon warning"><i class="bi bi-trophy"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($melhorNota, 2) ?></div>
            <div class="stat-label">Melhor Nota</div>
        </div>
    </div>
</div>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="avaliacao-header">
    <div class="avaliacao-header-left">
        <h5>
            <i class="bi bi-star-fill"></i> Avaliações de Desempenho
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($totalAvaliacoes) ?> registos
            </span>
        </h5>
        <p>Gerencie avaliações de desempenho dos colaboradores</p>
    </div>
    <div class="avaliacao-header-right">
        <a href="criar.php" class="btn btn-primary btn-sm" style="border-radius:10px;padding:0.5rem 1.2rem;">
            <i class="bi bi-plus-lg me-1"></i> Nova Avaliação
        </a>
    </div>
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
                    <th><i class="bi bi-calendar3"></i> Período</th>
                    <th><i class="bi bi-star"></i> Nota final</th>
                    <th><i class="bi bi-person-badge"></i> Avaliador</th>
                    <th><i class="bi bi-calendar-check"></i> Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($avaliacoes)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state-premium">
                                <span class="empty-icon"><i class="bi bi-star-slash"></i></span>
                                <p>Nenhuma avaliação registrada.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($avaliacoes as $a): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm"><?= e(strtoupper(substr($a['funcionario_nome'], 0, 1))) ?></span>
                                <span style="font-weight:500;"><?= e($a['funcionario_nome']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight:500;">
                                <i class="bi bi-calendar3" style="color:var(--rh-accent);"></i>
                                <?= e($a['periodo']) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $nota = $a['nota_final'];
                            if ($nota >= 4.5) {
                                $classe = 'excelente';
                                $label = 'Excelente';
                                $icone = 'bi-star-fill';
                            } elseif ($nota >= 3.5) {
                                $classe = 'bom';
                                $label = 'Bom';
                                $icone = 'bi-star-half';
                            } elseif ($nota >= 2.5) {
                                $classe = 'regular';
                                $label = 'Regular';
                                $icone = 'bi-circle';
                            } else {
                                $classe = 'insuficiente';
                                $label = 'Insuficiente';
                                $icone = 'bi-x-circle';
                            }
                            ?>
                            <span class="badge-nota <?= $classe ?>">
                                <i class="bi <?= $icone ?>"></i>
                                <?= number_format($nota, 2) ?> / 5.00
                            </span>
                        </td>
                        <td style="color:var(--rh-text-muted);font-size:0.8rem;">
                            <?= e($a['avaliador_nome'] ?? '-') ?>
                        </td>
                        <td style="color:var(--rh-text-muted);font-size:0.8rem;">
                            <i class="bi bi-calendar3" style="color:var(--rh-accent);"></i>
                            <?= formatarData($a['data_avaliacao']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>