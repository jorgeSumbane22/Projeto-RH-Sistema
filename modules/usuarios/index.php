<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Usuários e Acessos';
$usuarioModel = new Usuario();
$usuarios = $usuarioModel->listar();

// Estatísticas
$totalUsuarios = count($usuarios);
$ativos = count(array_filter($usuarios, fn($u) => $u['ativo']));
$inativos = $totalUsuarios - $ativos;

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* Usuários Premium */
.users-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.users-header-left h5 {
    font-weight: 700;
    color: var(--rh-primary);
    margin-bottom: 0.25rem;
}

.users-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.users-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.users-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
}

.users-stats .stat-item .stat-value {
    font-weight: 700;
    font-size: 1.1rem;
}

.users-stats .stat-item .stat-label {
    color: var(--rh-text-muted);
}

.users-table {
    border-radius: var(--rh-radius);
    overflow: hidden;
}

.users-table .table {
    margin-bottom: 0;
}

.users-table .table thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.85rem 1rem;
    border-bottom: 2px solid var(--rh-border);
}

.users-table .table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    border-bottom-color: var(--rh-border);
}

.users-table .table tbody tr {
    transition: all 0.2s ease;
}

.users-table .table tbody tr:hover {
    background: rgba(21, 94, 117, 0.04);
}

.user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    color: #fff;
    flex-shrink: 0;
}

.user-avatar.primary { background: var(--rh-primary); }
.user-avatar.success { background: var(--rh-accent); }
.user-avatar.warning { background: #f59e0b; }
.user-avatar.danger { background: #ef4444; }
.user-avatar.purple { background: #8b5cf6; }
.user-avatar.pink { background: #ec4899; }

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-info .user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.user-info .user-email {
    font-size: 0.75rem;
    color: var(--rh-text-muted);
}

.badge-profile {
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.03em;
}

.badge-profile.super_admin { background: #8b5cf6; color: #fff; }
.badge-profile.administrador_rh { background: #155e75; color: #fff; }
.badge-profile.gestor { background: #0e7490; color: #fff; }
.badge-profile.funcionario { background: #14b8a6; color: #fff; }
.badge-profile.auditor { background: #f59e0b; color: #1a1a2e; }

.btn-action {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    border: 1px solid var(--rh-border);
    color: var(--bs-body-color);
    background: transparent;
    text-decoration: none;
}

.btn-action:hover {
    transform: translateY(-2px);
}

.btn-action.edit:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.3);
}

.btn-action.delete:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-action.view:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
}

.last-login {
    font-size: 0.75rem;
    color: var(--rh-text-muted);
}

.last-login .bi {
    font-size: 0.65rem;
}

.empty-state {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state .empty-icon {
    font-size: 3rem;
    color: var(--rh-border);
    margin-bottom: 1rem;
}

.empty-state h6 {
    color: var(--bs-body-color);
    font-weight: 600;
}

.empty-state p {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
}

@media (max-width: 767.98px) {
    .users-header {
        flex-direction: column;
        align-items: stretch;
    }
    .users-stats {
        gap: 1rem;
    }
    .user-info .user-email {
        display: none;
    }
    .users-table .table thead th:not(:first-child):not(:nth-child(2)) {
        display: none;
    }
    .users-table .table tbody td:not(:first-child):not(:nth-child(2)) {
        display: none;
    }
}
</style>

<!-- Header -->
<div class="users-header">
    <div class="users-header-left">
        <h5><i class="bi bi-shield-lock me-2" style="color:var(--rh-primary);"></i>Usuários e Acessos</h5>
        <p>Gerencie contas de acesso ao sistema e permissões</p>
    </div>
    <div>
        <a href="criar.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Novo Usuário
        </a>
    </div>
</div>

<!-- Stats -->
<div class="users-stats mb-4">
    <div class="stat-item">
        <span class="stat-value" style="color:var(--rh-primary);"><?= $totalUsuarios ?></span>
        <span class="stat-label">Total</span>
    </div>
    <div class="stat-item">
        <span class="stat-value" style="color:var(--rh-accent);"><?= $ativos ?></span>
        <span class="stat-label">Ativos</span>
    </div>
    <div class="stat-item">
        <span class="stat-value" style="color:var(--rh-text-muted);"><?= $inativos ?></span>
        <span class="stat-label">Inativos</span>
    </div>
</div>

<!-- Table -->
<div class="card users-table">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th>Funcionário</th>
                    <th>Status</th>
                    <th>Último login</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-people"></i></div>
                                <h6>Nenhum usuário cadastrado</h6>
                                <p>Clique em "Novo Usuário" para criar o primeiro acesso.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: foreach ($usuarios as $u): 
                    $avatarColors = ['primary', 'success', 'warning', 'danger', 'purple', 'pink'];
                    $avatarColor = $avatarColors[$u['id'] % count($avatarColors)];
                ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <span class="user-avatar <?= $avatarColor ?>">
                                    <?= e(strtoupper(substr($u['nome'], 0, 2))) ?>
                                </span>
                                <div>
                                    <div class="user-name"><?= e($u['nome']) ?></div>
                                    <div class="user-email"><i class="bi bi-envelope me-1"></i><?= e($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge-profile <?= e($u['perfil']) ?>">
                                <i class="bi <?= $u['perfil'] === 'super_admin' ? 'bi-shield-check' : ($u['perfil'] === 'administrador_rh' ? 'bi-person-gear' : ($u['perfil'] === 'gestor' ? 'bi-person-badge' : ($u['perfil'] === 'auditor' ? 'bi-journal-text' : 'bi-person'))) ?> me-1"></i>
                                <?= e(nomePerfil($u['perfil'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($u['funcionario_nome'])): ?>
                                <span class="text-muted small">
                                    <i class="bi bi-person me-1"></i><?= e($u['funcionario_nome']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-dash me-1"></i>Não vinculado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['ativo']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="last-login">
                                <?php if ($u['ultimo_login']): ?>
                                    <i class="bi bi-clock me-1"></i>
                                    <?= e(formatarDataHora($u['ultimo_login'])) ?>
                                <?php else: ?>
                                    <i class="bi bi-dash me-1"></i>Nunca
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="editar.php?id=<?= $u['id'] ?>" class="btn-action edit" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id'] != usuarioAtualId()): ?>
                                    <a href="excluir.php?id=<?= $u['id'] ?>" class="btn-action delete js-confirm" 
                                       data-confirm-msg="Excluir o usuário <strong><?= e($u['nome']) ?></strong>? Esta ação não pode ser desfeita."
                                       title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-action" style="opacity:0.3;cursor:not-allowed;" title="Não pode excluir sua própria conta">
                                        <i class="bi bi-trash"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>