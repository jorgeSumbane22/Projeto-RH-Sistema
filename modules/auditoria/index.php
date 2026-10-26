<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_AUDITOR]);

$pageTitle = 'Log de Auditoria';
$logModel = new LogAuditoria();

$filtros = [
    'acao'        => trim($_GET['acao'] ?? ''),
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim'    => $_GET['data_fim'] ?? '',
];
$paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 20;

$resultado = $logModel->listar(array_filter($filtros), $porPagina, $paginaAtual);
$paginacao = paginar($resultado['total'], $paginaAtual, $porPagina);

// Estatísticas
$totalRegistros = $resultado['total'];
$totalAcoes = count(array_unique(array_column($resultado['registros'], 'acao')));
$ultimoRegistro = !empty($resultado['registros']) ? $resultado['registros'][0]['criado_em'] : null;

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   AUDITORIA - DESIGN PREMIUM
   ============================================================ */

/* ----- Header ----- */
.auditoria-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.auditoria-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.auditoria-header-left h5 i {
    color: var(--rh-accent);
}

.auditoria-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.auditoria-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* ----- CORREÇÃO: BADGE ÚLTIMA AÇÃO EM DARK MODE ----- */
.auditoria-header-right .badge.bg-light {
    background: var(--bs-tertiary-bg) !important;
    color: var(--rh-text-primary) !important;
    border: 1px solid var(--rh-border);
}

[data-bs-theme="dark"] .auditoria-header-right .badge.bg-light {
    background: #2a3a45 !important;
    color: #e8edf3 !important;
    border: 1px solid #3d5563;
}

[data-bs-theme="dark"] .auditoria-header-right .badge.bg-light .text-dark {
    color: #e8edf3 !important;
}

[data-bs-theme="dark"] .auditoria-header-right .badge.bg-light i {
    color: #94a3b8 !important;
}

/* ----- Stats Cards ----- */
.auditoria-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.auditoria-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.auditoria-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.auditoria-stat-card .stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.auditoria-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.auditoria-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.auditoria-stat-card .stat-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.auditoria-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}

.auditoria-stat-card .stat-info .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.auditoria-stat-card .stat-info .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- Filter Card ----- */
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

.filter-card-premium .form-label i {
    margin-right: 4px;
}

.filter-card-premium .form-control {
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.45rem 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.filter-card-premium .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
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
    width: 100%;
}

.filter-card-premium .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.25);
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
    width: 100%;
}

.filter-card-premium .btn-clear:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--bs-secondary-color);
}

/* ----- Table Card ----- */
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
    font-weight: 700;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-card-premium .table thead th i {
    margin-right: 6px;
    font-size: 0.7rem;
}

.table-card-premium .table tbody td {
    padding: 0.6rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.85rem;
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

/* ----- Badge Ação ----- */
.badge-acao {
    padding: 0.25rem 0.7rem;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
}

.badge-acao i {
    font-size: 0.6rem;
}

.badge-acao.login { background: #dbeafe; color: #1e40af; }
.badge-acao.logout { background: #fee2e2; color: #991b1b; }
.badge-acao.criado { background: #d1fae5; color: #065f46; }
.badge-acao.atualizado { background: #fef3c7; color: #92400e; }
.badge-acao.excluido { background: #fee2e2; color: #991b1b; }
.badge-acao.gerado { background: #ede9fe; color: #5b21b6; }
.badge-acao.baixado { background: #dbeafe; color: #1e40af; }
.badge-acao.pago { background: #d1fae5; color: #065f46; }
.badge-acao.aprovado { background: #d1fae5; color: #065f46; }
.badge-acao.rejeitado { background: #fee2e2; color: #991b1b; }
.badge-acao.exportado { background: #fef3c7; color: #92400e; }

[data-bs-theme="dark"] .badge-acao.login { background: #1e40af; color: #dbeafe; }
[data-bs-theme="dark"] .badge-acao.logout { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .badge-acao.criado { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-acao.atualizado { background: #92400e; color: #fef3c7; }
[data-bs-theme="dark"] .badge-acao.excluido { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .badge-acao.gerado { background: #5b21b6; color: #ede9fe; }
[data-bs-theme="dark"] .badge-acao.baixado { background: #1e40af; color: #dbeafe; }
[data-bs-theme="dark"] .badge-acao.pago { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-acao.aprovado { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-acao.rejeitado { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .badge-acao.exportado { background: #92400e; color: #fef3c7; }

/* ----- Empty State ----- */
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

/* ----- Paginação ----- */
.pagination-wrap {
    margin-top: 1.5rem;
}

.pagination-wrap .pagination {
    margin-bottom: 0;
}

.pagination-wrap .page-link {
    border-color: var(--rh-border);
    color: var(--rh-text-primary);
    padding: 0.4rem 0.85rem;
    font-size: 0.8rem;
    border-radius: 8px;
    margin: 0 2px;
    transition: all 0.2s ease;
    background: var(--rh-card-bg);
}

.pagination-wrap .page-link:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
}

.pagination-wrap .page-item.active .page-link {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
}

/* ----- Responsive ----- */
@media (max-width: 991.98px) {
    .auditoria-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .auditoria-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .auditoria-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .auditoria-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .auditoria-stat-card {
        padding: 0.75rem 1rem;
    }
    .auditoria-stat-card .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .auditoria-stat-card .stat-info .stat-value {
        font-size: 1.1rem;
    }
    .filter-card-premium {
        padding: 1rem;
    }
    .table-card-premium .table thead th {
        font-size: 0.55rem;
        padding: 0.4rem 0.5rem;
    }
    .table-card-premium .table tbody td {
        padding: 0.4rem 0.5rem;
        font-size: 0.75rem;
    }
    .badge-acao {
        font-size: 0.55rem;
        padding: 0.15rem 0.4rem;
    }
}

@media (max-width: 575.98px) {
    .auditoria-stats {
        grid-template-columns: 1fr;
    }
    .auditoria-header-left h5 {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     HEADER - SEM DATA/HORA (usa as do header)
     ============================================================ -->
<div class="auditoria-header">
    <div class="auditoria-header-left">
        <h5>
            <i class="bi bi-journal-text"></i> Log de Auditoria
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($totalRegistros) ?> registos
            </span>
        </h5>
        <p>Histórico completo de todas as ações realizadas no sistema</p>
    </div>
    <div class="auditoria-header-right">
        <?php if ($ultimoRegistro): ?>
            <span class="badge bg-light text-dark" style="font-size:0.7rem;padding:0.4rem 0.8rem;">
                <i class="bi bi-clock me-1"></i> Última ação: <?= formatarDataHora($ultimoRegistro) ?>
            </span>
        <?php endif; ?>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;padding:0.4rem 1rem;">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
    </div>
</div>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="auditoria-stats">
    <div class="auditoria-stat-card">
        <div class="stat-icon primary"><i class="bi bi-list"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalRegistros) ?></div>
            <div class="stat-label">Total Registos</div>
        </div>
    </div>
    <div class="auditoria-stat-card">
        <div class="stat-icon info"><i class="bi bi-tags"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAcoes) ?></div>
            <div class="stat-label">Tipos de Ação</div>
        </div>
    </div>
    <div class="auditoria-stat-card">
        <div class="stat-icon warning"><i class="bi bi-calendar3"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $filtros['data_inicio'] && $filtros['data_fim'] ? number_format((strtotime($filtros['data_fim']) - strtotime($filtros['data_inicio'])) / 86400 + 1) : '-' ?></div>
            <div class="stat-label">Período (dias)</div>
        </div>
    </div>
    <div class="auditoria-stat-card">
        <div class="stat-icon success"><i class="bi bi-shield-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalRegistros > 0 ? '🔒' : '🟢' ?></div>
            <div class="stat-label">Status</div>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTER CARD
     ============================================================ -->
<div class="filter-card-premium">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label"><i class="bi bi-search"></i> Ação</label>
            <input type="text" name="acao" class="form-control" placeholder="Ex: login, funcionario_criado" value="<?= e($filtros['acao']) ?>">
            <div class="form-text" style="font-size:0.65rem;color:var(--rh-text-muted);">
                <i class="bi bi-info-circle"></i> Busca por ações específicas
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-calendar-start"></i> Data início</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= e($filtros['data_inicio']) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-calendar-end"></i> Data fim</label>
            <input type="date" name="data_fim" class="form-control" value="<?= e($filtros['data_fim']) ?>">
        </div>
        <div class="col-md-2">
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
     TABLE CARD
     ============================================================ -->
<div class="table-card-premium">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:160px;"><i class="bi bi-clock"></i> Data/Hora</th>
                    <th style="min-width:120px;"><i class="bi bi-person"></i> Usuário</th>
                    <th style="min-width:140px;"><i class="bi bi-tag"></i> Ação</th>
                    <th><i class="bi bi-info-circle"></i> Detalhes</th>
                    <th style="min-width:100px;"><i class="bi bi-laptop"></i> IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($resultado['registros'])): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state-premium">
                                <span class="empty-icon"><i class="bi bi-journal-slash"></i></span>
                                <p>Nenhum registro encontrado para os filtros selecionados.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($resultado['registros'] as $log): ?>
                    <tr>
                        <td>
                            <span style="font-weight:500;font-size:0.8rem;color:var(--rh-text-primary);">
                                <i class="bi bi-clock" style="color:var(--rh-primary);margin-right:4px;"></i>
                                <?= formatarDataHora($log['criado_em']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--rh-primary),var(--rh-primary-light));color:#fff;font-weight:700;font-size:0.55rem;flex-shrink:0;text-transform:uppercase;">
                                    <?= e(strtoupper(substr($log['usuario_nome'] ?? 'S', 0, 1))) ?>
                                </span>
                                <span style="font-size:0.8rem;font-weight:500;color:var(--rh-text-primary);"><?= e($log['usuario_nome'] ?? 'Sistema') ?></span>
                            </div>
                        </td>
                        <td>
                            <?php
                            $iconMap = [
                                'login' => 'bi-box-arrow-in-right',
                                'logout' => 'bi-box-arrow-right',
                                'criado' => 'bi-plus-circle',
                                'atualizado' => 'bi-pencil-square',
                                'excluido' => 'bi-trash',
                                'gerado' => 'bi-gear',
                                'baixado' => 'bi-download',
                                'pago' => 'bi-check-circle',
                                'aprovado' => 'bi-check-circle',
                                'rejeitado' => 'bi-x-circle',
                                'exportado' => 'bi-file-earmark-spreadsheet',
                            ];
                            $icon = 'bi-tag';
                            foreach ($iconMap as $key => $ic) {
                                if (strpos($log['acao'], $key) !== false) {
                                    $icon = $ic;
                                    break;
                                }
                            }
                            $classe = 'badge-acao';
                            foreach (['login','logout','criado','atualizado','excluido','gerado','baixado','pago','aprovado','rejeitado','exportado'] as $c) {
                                if (strpos($log['acao'], $c) !== false) {
                                    $classe .= ' ' . $c;
                                    break;
                                }
                            }
                            ?>
                            <span class="<?= $classe ?>">
                                <i class="bi <?= $icon ?>"></i>
                                <?= e($log['acao']) ?>
                            </span>
                        </td>
                        <td style="color:var(--rh-text-muted);font-size:0.8rem;">
                            <?= e($log['detalhes'] ?? '-') ?>
                        </td>
                        <td style="color:var(--rh-text-muted);font-size:0.75rem;font-family:monospace;">
                            <?= e($log['ip'] ?? '-') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     PAGINAÇÃO
     ============================================================ -->
<?php if ($resultado['total'] > $porPagina): ?>
    <div class="pagination-wrap">
        <?php require __DIR__ . '/../../includes/paginacao.php'; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>