<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$pageTitle = 'Notificações';
$notificacaoModel = new Notificacao();

// Filtros
$filtroStatus = $_GET['status'] ?? 'todas';
$porPagina = 15;
$paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));

// Buscar notificações
if ($filtroStatus === 'nao_lidas') {
    $notificacoes = $notificacaoModel->listarNaoLidas(usuarioAtualId(), $porPagina, $paginaAtual);
    $total = $notificacaoModel->contarNaoLidas(usuarioAtualId());
} else {
    $notificacoes = $notificacaoModel->listarPorUsuario(usuarioAtualId(), $porPagina, $paginaAtual);
    $total = $notificacaoModel->contarPorUsuario(usuarioAtualId());
}

$totalNaoLidas = $notificacaoModel->contarNaoLidas(usuarioAtualId());

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   NOTIFICAÇÕES - DESIGN PREMIUM
   ============================================================ */

.notificacoes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.notificacoes-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notificacoes-header-left h5 i {
    color: var(--rh-accent);
}

.notificacoes-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.notificacoes-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

/* ----- Stats ----- */
.notificacoes-stats {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.notificacoes-stats .stat-badge {
    padding: 0.3rem 1rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    text-decoration: none;
    transition: all 0.3s ease;
}

.notificacoes-stats .stat-badge:hover {
    background: var(--rh-primary);
    color: #fff;
}

.notificacoes-stats .stat-badge.active {
    background: var(--rh-primary);
    color: #fff;
}

.notificacoes-stats .stat-badge .count {
    background: rgba(255,255,255,0.2);
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    margin-left: 4px;
    font-size: 0.65rem;
}

.notificacoes-stats .stat-badge:hover .count {
    background: rgba(255,255,255,0.3);
}

/* ----- Action Buttons ----- */
.action-btn-group {
    display: flex;
    gap: 0.5rem;
}

.action-btn-group .btn {
    border-radius: 10px;
    padding: 0.3rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-btn-group .btn-outline-primary:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
}

/* ----- Notificação Card ----- */
.notification-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
    position: relative;
}

.notification-card:hover {
    box-shadow: var(--rh-shadow-hover);
    border-color: var(--rh-primary);
}

.notification-card.nao-lida {
    border-left: 4px solid var(--rh-primary);
    background: rgba(21, 94, 117, 0.03);
}

.notification-card .notif-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.notification-card .notif-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--rh-text-primary);
    margin: 0;
}

.notification-card .notif-title .badge-nova {
    font-size: 0.55rem;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    background: var(--rh-primary);
    color: #fff;
    margin-left: 0.5rem;
    font-weight: 600;
}

.notification-card .notif-mensagem {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
    margin: 0.25rem 0 0.5rem;
    line-height: 1.5;
}

.notification-card .notif-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--rh-border);
}

.notification-card .notif-footer .notif-data {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.notification-card .notif-footer .notif-data i {
    margin-right: 4px;
}

.notification-card .notif-footer .notif-actions {
    display: flex;
    gap: 0.5rem;
}

.notification-card .notif-footer .notif-actions .btn {
    border-radius: 8px;
    padding: 0.15rem 0.6rem;
    font-size: 0.65rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.notification-card .notif-footer .notif-actions .btn-outline-success:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
    color: #fff;
}

.notification-card .notif-footer .notif-actions .btn-outline-danger:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
}

/* ----- Empty State ----- */
.empty-state-premium {
    padding: 3rem 1rem;
    text-align: center;
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
    color: var(--rh-text-primary);
    margin-bottom: 0.25rem;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* ----- Pagination ----- */
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
@media (max-width: 767.98px) {
    .notificacoes-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .notificacoes-header-right {
        flex-direction: column;
        align-items: stretch;
    }
    .notificacoes-stats {
        justify-content: center;
    }
    .action-btn-group {
        justify-content: center;
    }
    .notification-card {
        padding: 1rem;
    }
    .notification-card .notif-top {
        flex-direction: column;
        align-items: stretch;
    }
    .notification-card .notif-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .notification-card .notif-footer .notif-actions {
        justify-content: flex-end;
    }
}

@media (max-width: 575.98px) {
    .notificacoes-header-left h5 {
        font-size: 1.1rem;
    }
    .notificacoes-stats .stat-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.6rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="notificacoes-header">
    <div class="notificacoes-header-left">
        <h5>
            <i class="bi bi-bell"></i> Notificações
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($total) ?>
            </span>
        </h5>
        <p>Mantenha-se atualizado sobre eventos importantes</p>
    </div>
    <div class="notificacoes-header-right">
        <?php if ($totalNaoLidas > 0): ?>
            <a href="marcar_lidas.php" class="btn btn-outline-primary btn-sm" style="border-radius:10px;padding:0.3rem 1rem;">
                <i class="bi bi-check-all me-1"></i> Marcar todas como lidas
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     STATS
     ============================================================ -->
<div class="notificacoes-stats mb-4">
    <a href="?status=todas" class="stat-badge <?= $filtroStatus === 'todas' ? 'active' : '' ?>">
        <i class="bi bi-list"></i> Todas
        <span class="count"><?= $notificacaoModel->contarPorUsuario(usuarioAtualId()) ?></span>
    </a>
    <a href="?status=nao_lidas" class="stat-badge <?= $filtroStatus === 'nao_lidas' ? 'active' : '' ?>">
        <i class="bi bi-envelope"></i> Não lidas
        <span class="count"><?= $totalNaoLidas ?></span>
    </a>
</div>

<!-- ============================================================
     NOTIFICAÇÕES
     ============================================================ -->
<?php if (empty($notificacoes)): ?>
    <div class="empty-state-premium">
        <span class="empty-icon"><i class="bi bi-inbox"></i></span>
        <h6>Nenhuma notificação</h6>
        <p>
            <?= $filtroStatus === 'nao_lidas' ? 'Todas as suas notificações foram lidas. 🎉' : 'Você não tem notificações no momento.' ?>
        </p>
    </div>
<?php else: ?>
    <?php foreach ($notificacoes as $n): ?>
        <div class="notification-card <?= !$n['lida'] ? 'nao-lida' : '' ?>">
            <div class="notif-top">
                <div>
                    <div class="notif-title">
                        <?= e($n['titulo']) ?>
                        <?php if (!$n['lida']): ?>
                            <span class="badge-nova">Nova</span>
                        <?php endif; ?>
                    </div>
                    <div class="notif-mensagem">
                        <?= e($n['mensagem']) ?>
                    </div>
                </div>
                <?php if (!$n['lida']): ?>
                    <span class="badge bg-primary" style="font-size:0.55rem;padding:0.2rem 0.6rem;">
                        <i class="bi bi-envelope-fill me-1"></i> Não lida
                    </span>
                <?php endif; ?>
            </div>
            <div class="notif-footer">
                <span class="notif-data">
                    <i class="bi bi-clock"></i> <?= formatarDataHora($n['criado_em']) ?>
                </span>
                <div class="notif-actions">
                    <?php if (!$n['lida']): ?>
                        <a href="marcar_lida.php?id=<?= $n['id'] ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-check"></i> Marcar lida
                        </a>
                    <?php endif; ?>
                    <a href="excluir.php?id=<?= $n['id'] ?>" class="btn btn-outline-danger btn-sm js-confirm" 
                       data-confirm-msg="Excluir esta notificação?">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Paginação -->
    <?php if ($total > $porPagina): ?>
        <div class="pagination-wrap">
            <nav>
                <ul class="pagination justify-content-center">
                    <?php
                    $totalPaginas = ceil($total / $porPagina);
                    for ($i = 1; $i <= $totalPaginas; $i++):
                    ?>
                        <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">
                            <a class="page-link" href="?status=<?= $filtroStatus ?>&pagina=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>