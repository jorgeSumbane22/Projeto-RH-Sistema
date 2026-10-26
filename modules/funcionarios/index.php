<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// ============================================================
// PERMISSÕES ATUALIZADAS
// ============================================================
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Funcionários';

$funcionarioModel = new Funcionario();
$departamentoModel = new Departamento();

$filtros = [
    'nome'            => trim($_GET['nome'] ?? ''),
    'departamento_id' => $_GET['departamento_id'] ?? '',
    'status'          => $_GET['status'] ?? '',
];

// Gestor só pode ver funcionários do próprio departamento
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    if ($meuFuncionario) {
        $filtros['departamento_id'] = $meuFuncionario['departamento_id'] ?? 0;
    }
}

$paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 15;
$resultado = $funcionarioModel->listarPaginado(array_filter($filtros), $porPagina, $paginaAtual);
$funcionarios = $resultado['registros'];
$paginacao = paginar($resultado['total'], $paginaAtual, $porPagina);
$departamentos = $departamentoModel->listar(true);

// Estatísticas
$totalAtivos = $funcionarioModel->contarPorStatus('ativo');
$totalFerias = $funcionarioModel->contarPorStatus('ferias');
$totalInativos = $funcionarioModel->contarPorStatus('inativo');

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   FUNCIONÁRIOS - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER ----- */
.funcionarios-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.funcionarios-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.funcionarios-header-left h5 i {
    color: var(--rh-accent);
}

.funcionarios-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

.funcionarios-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* ----- STATS CARDS ----- */
.funcionarios-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.funcionarios-stats .stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    min-width: 120px;
}

.funcionarios-stats .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--rh-shadow-hover);
}

.funcionarios-stats .stat-card .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.funcionarios-stats .stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.funcionarios-stats .stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.funcionarios-stats .stat-card .stat-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.funcionarios-stats .stat-card .stat-icon.danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

.funcionarios-stats .stat-card .stat-info .stat-value {
    font-weight: 700;
    font-size: 1.2rem;
    line-height: 1.2;
    color: var(--rh-text-primary);
}

.funcionarios-stats .stat-card .stat-info .stat-label {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* ----- FILTER CARD ----- */
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
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    margin-bottom: 0.3rem;
}

.filter-card-premium .form-control,
.filter-card-premium .form-select {
    border-radius: 10px;
    border-color: var(--rh-border);
    padding: 0.55rem 1rem;
    font-size: 0.9rem;
    background: var(--bs-tertiary-bg);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.filter-card-premium .form-control:focus,
.filter-card-premium .form-select:focus {
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
    background: var(--rh-card-bg);
}

.filter-card-premium .btn-filter {
    border-radius: 10px;
    padding: 0.55rem 1.5rem;
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
    padding: 0.55rem 1.5rem;
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
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.85rem 1rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-card-premium .table tbody td {
    padding: 0.7rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
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

/* ----- USER AVATAR ----- */
.user-avatar-func {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    color: #fff;
    flex-shrink: 0;
}

.user-avatar-func.primary { background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light)); }
.user-avatar-func.success { background: linear-gradient(135deg, var(--rh-accent), #0d9488); }
.user-avatar-func.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.user-avatar-func.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
.user-avatar-func.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.user-avatar-func.pink { background: linear-gradient(135deg, #ec4899, #db2777); }

.user-info-func {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-info-func .user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rh-text-primary);
}

.user-info-func .user-name a {
    color: var(--rh-text-primary);
    text-decoration: none;
    transition: color 0.2s ease;
}

.user-info-func .user-name a:hover {
    color: var(--rh-primary);
}

.user-info-func .user-detail {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

/* ----- BADGE STATUS PREMIUM ----- */
.badge-status-premium {
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.badge-status-premium i {
    font-size: 0.65rem;
}

.badge-status-premium.ativo {
    background: #d1fae5;
    color: #065f46;
}
.badge-status-premium.ferias {
    background: #fef3c7;
    color: #92400e;
}
.badge-status-premium.licenca {
    background: #dbeafe;
    color: #1e40af;
}
.badge-status-premium.inativo {
    background: #fee2e2;
    color: #991b1b;
}
.badge-status-premium.desligado {
    background: #f3f4f6;
    color: #4b5563;
}

[data-bs-theme="dark"] .badge-status-premium.ativo {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}
[data-bs-theme="dark"] .badge-status-premium.ferias {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
}
[data-bs-theme="dark"] .badge-status-premium.licenca {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
}
[data-bs-theme="dark"] .badge-status-premium.inativo {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}
[data-bs-theme="dark"] .badge-status-premium.desligado {
    background: rgba(107, 114, 128, 0.2);
    color: #d1d5db;
}

/* ----- ACTION BUTTONS ----- */
.action-btn-premium {
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

.action-btn-premium:hover {
    transform: translateY(-2px);
}

.action-btn-premium.view:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.3);
}

.action-btn-premium.edit:hover {
    background: var(--rh-accent);
    border-color: var(--rh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
}

.action-btn-premium.delete:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.action-btn-premium.terminate:hover {
    background: #f59e0b;
    border-color: #f59e0b;
    color: #fff;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state-premium .empty-icon {
    font-size: 3.5rem;
    color: var(--rh-border);
    margin-bottom: 1rem;
    display: block;
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

/* ----- RESPONSIVE ----- */
@media (max-width: 991.98px) {
    .funcionarios-stats .stat-card {
        min-width: 80px;
        padding: 0.6rem 1rem;
    }
    .funcionarios-stats .stat-card .stat-info .stat-value {
        font-size: 1rem;
    }
}

@media (max-width: 767.98px) {
    .funcionarios-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .funcionarios-header-right {
        flex-direction: column;
        align-items: stretch;
    }
    .funcionarios-header-right .btn {
        width: 100%;
        justify-content: center;
    }
    .funcionarios-stats {
        gap: 0.75rem;
    }
    .funcionarios-stats .stat-card {
        flex: 1;
        min-width: 60px;
        padding: 0.5rem 0.75rem;
    }
    .funcionarios-stats .stat-card .stat-icon {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }
    .funcionarios-stats .stat-card .stat-info .stat-value {
        font-size: 0.9rem;
    }
    .funcionarios-stats .stat-card .stat-info .stat-label {
        font-size: 0.6rem;
    }
    .filter-card-premium {
        padding: 1rem;
    }
    .table-card-premium .table thead th {
        font-size: 0.6rem;
        padding: 0.5rem 0.6rem;
    }
    .table-card-premium .table tbody td {
        padding: 0.5rem 0.6rem;
        font-size: 0.8rem;
    }
    .user-info-func .user-name {
        font-size: 0.8rem;
    }
    .user-info-func .user-detail {
        display: none;
    }
    .badge-status-premium {
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
    }
    .action-btn-premium {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
}

@media (max-width: 575.98px) {
    .funcionarios-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="funcionarios-header">
    <div class="funcionarios-header-left">
        <h5>
            <i class="bi bi-people-fill"></i> Funcionários
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($resultado['total']) ?>
            </span>
        </h5>
        <p>Gerencie todos os colaboradores da organização</p>
    </div>
    <div class="funcionarios-header-right">
        <?php if (usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN])): ?>
            <a href="criar.php" class="btn btn-primary btn-sm" style="border-radius:10px;padding:0.5rem 1.2rem;">
                <i class="bi bi-plus-lg me-1"></i> Novo Funcionário
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="funcionarios-stats">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="bi bi-person-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAtivos) ?></div>
            <div class="stat-label">Ativos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="bi bi-sun"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalFerias) ?></div>
            <div class="stat-label">Em férias</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="bi bi-person-x"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalInativos) ?></div>
            <div class="stat-label">Inativos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="bi bi-building"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format(count($departamentos)) ?></div>
            <div class="stat-label">Departamentos</div>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTER CARD
     ============================================================ -->
<div class="filter-card-premium">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label"><i class="bi bi-search me-1"></i> Buscar</label>
            <input type="text" name="nome" class="form-control" placeholder="Nome do funcionário..." value="<?= e($filtros['nome']) ?>">
        </div>
        <?php if (usuarioAtualPerfil() !== PERFIL_GESTOR): ?>
        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-diagram-3 me-1"></i> Departamento</label>
            <select name="departamento_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($departamentos as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filtros['departamento_id'] == $d['id'] ? 'selected' : '' ?>>
                        <?= e($d['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-circle me-1"></i> Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="ativo" <?= $filtros['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="ferias" <?= $filtros['status'] === 'ferias' ? 'selected' : '' ?>>Em férias</option>
                <option value="licenca" <?= $filtros['status'] === 'licenca' ? 'selected' : '' ?>>Em licença</option>
                <option value="inativo" <?= $filtros['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                <option value="desligado" <?= $filtros['status'] === 'desligado' ? 'selected' : '' ?>>Desligado</option>
            </select>
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
                    <th style="min-width:200px;">Funcionário</th>
                    <th>Cargo</th>
                    <th>Departamento</th>
                    <th>Admissão</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width:120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($funcionarios)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state-premium">
                                <span class="empty-icon"><i class="bi bi-people"></i></span>
                                <h6>Nenhum funcionário encontrado</h6>
                                <p>Tente ajustar os filtros de busca ou <a href="criar.php" style="color:var(--rh-primary);">cadastre um novo funcionário</a>.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($funcionarios as $f): 
                    $avatarColors = ['primary', 'success', 'warning', 'danger', 'purple', 'pink'];
                    $avatarColor = $avatarColors[$f['id'] % count($avatarColors)];
                ?>
                    <tr>
                        <td>
                            <div class="user-info-func">
                                <span class="user-avatar-func <?= $avatarColor ?>">
                                    <?= e(strtoupper(substr($f['nome'], 0, 2))) ?>
                                </span>
                                <div>
                                    <div class="user-name">
                                        <a href="visualizar.php?id=<?= $f['id'] ?>"><?= e($f['nome']) ?></a>
                                    </div>
                                    <div class="user-detail">
                                        <i class="bi bi-envelope me-1"></i><?= e($f['email'] ?? 'Sem e-mail') ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($f['cargo_titulo'] ?? '-') ?></td>
                        <td><?= e($f['departamento_nome'] ?? '-') ?></td>
                        <td><?= $f['data_admissao'] ? formatarData($f['data_admissao']) : '-' ?></td>
                        <td>
                            <span class="badge-status-premium <?= $f['status'] ?? 'ativo' ?>">
                                <i class="bi <?= $f['status'] === 'ativo' ? 'bi-check-circle' : ($f['status'] === 'ferias' ? 'bi-sun' : ($f['status'] === 'inativo' ? 'bi-x-circle' : 'bi-clock')) ?>"></i>
                                <?= ucfirst($f['status'] ?? 'Ativo') ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="visualizar.php?id=<?= $f['id'] ?>" class="action-btn-premium view" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN])): ?>
                                    <a href="editar.php?id=<?= $f['id'] ?>" class="action-btn-premium edit" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($f['status'] !== 'desligado' && $f['status'] !== 'inativo'): ?>
                                        <a href="desligar.php?id=<?= $f['id'] ?>" class="action-btn-premium terminate js-confirm" 
                                           data-confirm-msg="Deseja realmente desligar <strong><?= e($f['nome']) ?></strong>?" 
                                           title="Desligar">
                                            <i class="bi bi-person-x"></i>
                                        </a>
                                    <?php endif; ?>
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
     PAGINAÇÃO
     ============================================================ -->
<?php if ($resultado['total'] > $porPagina): ?>
    <div class="mt-4">
        <?php require __DIR__ . '/../../includes/paginacao.php'; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>