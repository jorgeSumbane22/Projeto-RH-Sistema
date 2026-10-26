<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// ============================================================
// PERMISSÕES
// ============================================================
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN]);

$id = (int) ($_GET['id'] ?? 0);
$cargoModel = new Cargo();
$funcionarioModel = new Funcionario();
$departamentoModel = new Departamento();
$folhaModel = new FolhaPagamento();
$avaliacaoModel = new Avaliacao();

$cargo = $cargoModel->buscarPorId($id);
if (!$cargo) {
    definirMensagem('danger', 'Cargo não encontrado.');
    redirecionar('/modules/cargos/index.php');
}

// Dados do cargo
$departamento = $departamentoModel->buscarPorId($cargo['departamento_id']);
$funcionarios = $funcionarioModel->listar(['cargo_id' => $id, 'status' => 'ativo']);
$funcionariosTotal = $funcionarioModel->listar(['cargo_id' => $id]);

// Estatísticas
$totalFuncionarios = count($funcionariosTotal);
$totalAtivos = count(array_filter($funcionariosTotal, fn($f) => $f['status'] === 'ativo'));
$totalInativos = count(array_filter($funcionariosTotal, fn($f) => $f['status'] === 'inativo'));
$totalFerias = count(array_filter($funcionariosTotal, fn($f) => $f['status'] === 'ferias'));

// Salário médio
$salarioMedio = 0;
if ($totalFuncionarios > 0) {
    $somaSalarios = array_sum(array_column($funcionariosTotal, 'salario'));
    $salarioMedio = $somaSalarios / $totalFuncionarios;
}

// Desempenho médio do cargo
$mediaDesempenho = (float) dashScalar(
    'SELECT AVG(a.nota_final) FROM avaliacoes_desempenho a 
     JOIN funcionarios f ON f.id = a.funcionario_id 
     WHERE f.cargo_id = :cargo AND a.nota_final IS NOT NULL',
    [':cargo' => $id]
);

// Ranking de desempenho do cargo
$ranking = dashRows(
    'SELECT f.nome, MAX(a.nota_final) as nota 
     FROM avaliacoes_desempenho a 
     JOIN funcionarios f ON f.id = a.funcionario_id 
     WHERE f.cargo_id = :cargo AND a.nota_final IS NOT NULL 
     GROUP BY f.id, f.nome 
     ORDER BY nota DESC LIMIT 5',
    [':cargo' => $id]
);

// Histórico de funcionários no cargo
$historicoFuncionarios = dashRows(
    'SELECT f.id, f.nome, f.data_admissao, f.status, 
            f.salario, f.data_demissao
     FROM funcionarios f
     WHERE f.cargo_id = :cargo
     ORDER BY f.data_admissao DESC
     LIMIT 10',
    [':cargo' => $id]
);

// Histórico salarial do cargo (últimos meses)
$historicoSalarial = dashRows(
    'SELECT fp.mes_referencia, 
            AVG(fp.salario_base) as media_base,
            AVG(fp.salario_liquido) as media_liquido,
            COUNT(fp.id) as total_registros
     FROM folha_pagamento fp
     JOIN funcionarios f ON f.id = fp.funcionario_id
     WHERE f.cargo_id = :cargo
     GROUP BY fp.mes_referencia
     ORDER BY fp.mes_referencia DESC
     LIMIT 6',
    [':cargo' => $id]
);

$pageTitle = $cargo['titulo'] . ' - Cargo';
require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   VISUALIZAR CARGO - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER ----- */
.cargo-profile-header {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.cargo-profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--rh-primary), var(--rh-accent));
}

.cargo-profile-header:hover {
    box-shadow: var(--rh-shadow-hover);
}

.cargo-profile-header .cargo-icon {
    width: 64px;
    height: 64px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #fff;
    flex-shrink: 0;
}

.cargo-profile-header .cargo-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
}

.cargo-profile-header .cargo-meta {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.cargo-profile-header .cargo-meta i {
    margin-right: 4px;
}

.cargo-profile-header .cargo-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.cargo-profile-header .cargo-actions .btn {
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.cargo-profile-header .cargo-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.cargo-profile-header .cargo-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21,94,117,0.3);
}

/* ----- BADGE STATUS ----- */
.badge-status-cargo {
    padding: 0.25rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.badge-status-cargo.ativo { background: #d1fae5; color: #065f46; }
.badge-status-cargo.inativo { background: #fee2e2; color: #991b1b; }

[data-bs-theme="dark"] .badge-status-cargo.ativo { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-status-cargo.inativo { background: #991b1b; color: #fee2e2; }

/* ----- STATS CARDS ----- */
.cargo-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.cargo-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.cargo-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--rh-shadow-hover);
}

.cargo-stat-card .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.cargo-stat-card .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.cargo-stat-card .stat-value.success { color: var(--rh-accent); }
.cargo-stat-card .stat-value.danger { color: #ef4444; }
.cargo-stat-card .stat-value.warning { color: #f59e0b; }
.cargo-stat-card .stat-value.info { color: #3b82f6; }
.cargo-stat-card .stat-value.primary { color: var(--rh-primary); }
.cargo-stat-card .stat-value.purple { color: #8b5cf6; }

/* ----- CARDS ----- */
.cargo-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
    height: 100%;
}

.cargo-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.cargo-card .card-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cargo-card .card-title i {
    color: var(--rh-accent);
}

/* ----- INFO GRID ----- */
.cargo-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem 1.5rem;
}

.cargo-info-grid .info-item {
    padding: 0.3rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.cargo-info-grid .info-item:last-child {
    border-bottom: none;
}

.cargo-info-grid .info-item .info-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    display: block;
}

.cargo-info-grid .info-item .info-value {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rh-text-primary);
}

/* ----- TABELA ----- */
.table-cargo {
    margin-bottom: 0;
}

.table-cargo thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 600;
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.5rem 0.75rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-cargo tbody td {
    padding: 0.4rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.82rem;
    color: var(--rh-text-primary);
}

.table-cargo tbody tr {
    transition: all 0.2s ease;
}

.table-cargo tbody tr:hover {
    background: rgba(21,94,117,0.03);
}

.table-cargo tbody tr:last-child td {
    border-bottom: none;
}

/* ----- LISTA ----- */
.cargo-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.cargo-list-item:last-child {
    border-bottom: none;
}

.cargo-list-item .item-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cargo-list-item .item-left .item-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.55rem;
    text-transform: uppercase;
    flex-shrink: 0;
}

.cargo-list-item .item-left .item-text {
    font-size: 0.8rem;
    color: var(--rh-text-primary);
}

.cargo-list-item .item-left .item-text .item-sub {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
}

.cargo-list-item .item-right {
    font-size: 0.65rem;
    font-weight: 600;
}

/* ----- EMPTY STATE ----- */
.empty-state-cargo {
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.empty-state-cargo .empty-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.3rem;
    opacity: 0.4;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .cargo-profile-header {
        padding: 1rem;
    }
    .cargo-profile-header .cargo-icon {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }
    .cargo-profile-header .cargo-name {
        font-size: 1.1rem;
    }
    .cargo-profile-header .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.75rem;
    }
    .cargo-profile-header .d-flex .d-flex {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }
    .cargo-profile-header .cargo-actions {
        width: 100%;
    }
    .cargo-profile-header .cargo-actions .btn {
        flex: 1;
        text-align: center;
    }
    .cargo-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .cargo-info-grid {
        grid-template-columns: 1fr;
    }
    .cargo-card {
        padding: 1rem;
    }
    .table-cargo thead th,
    .table-cargo tbody td {
        padding: 0.3rem 0.4rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 575.98px) {
    .cargo-stats-grid {
        grid-template-columns: 1fr;
    }
    .cargo-stat-card .stat-value {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     HEADER DO CARGO
     ============================================================ -->
<div class="cargo-profile-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="cargo-icon">
                <i class="bi bi-briefcase"></i>
            </div>
            <div>
                <div class="cargo-name"><?= e($cargo['titulo']) ?></div>
                <div class="cargo-meta">
                    <i class="bi bi-building"></i> <?= e($departamento['nome'] ?? 'Departamento não definido') ?>
                    <span class="mx-1">·</span>
                    <span class="badge-status-cargo <?= $cargo['ativo'] ? 'ativo' : 'inativo' ?>">
                        <i class="bi <?= $cargo['ativo'] ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                        <?= $cargo['ativo'] ? 'Ativo' : 'Inativo' ?>
                    </span>
                </div>
                <?php if (!empty($cargo['descricao'])): ?>
                    <div class="mt-1 text-muted small"><?= e($cargo['descricao']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="cargo-actions">
            <?php if (usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN])): ?>
                <a href="editar.php?id=<?= $id ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="cargo-stats-grid">
    <div class="cargo-stat-card">
        <div class="stat-value primary"><?= number_format($totalFuncionarios) ?></div>
        <div class="stat-label">Total Funcionários</div>
    </div>
    <div class="cargo-stat-card">
        <div class="stat-value success"><?= number_format($totalAtivos) ?></div>
        <div class="stat-label">Ativos</div>
    </div>
    <div class="cargo-stat-card">
        <div class="stat-value warning"><?= number_format($totalFerias) ?></div>
        <div class="stat-label">Em Férias</div>
    </div>
    <div class="cargo-stat-card">
        <div class="stat-value purple"><?= number_format($mediaDesempenho, 2) ?></div>
        <div class="stat-label">Média Desempenho</div>
    </div>
    <div class="cargo-stat-card">
        <div class="stat-value info"><?= dashMoney($cargo['salario_base'] ?? 0) ?></div>
        <div class="stat-label">Salário Base</div>
    </div>
    <div class="cargo-stat-card">
        <div class="stat-value success"><?= dashMoney($salarioMedio) ?></div>
        <div class="stat-label">Salário Médio</div>
    </div>
</div>

<!-- ============================================================
     INFORMAÇÕES DO CARGO
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="cargo-card">
            <div class="card-title"><i class="bi bi-info-circle"></i> Informações do Cargo</div>
            <div class="cargo-info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-tag"></i> Nome</span>
                    <span class="info-value"><?= e($cargo['titulo']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-building"></i> Departamento</span>
                    <span class="info-value"><?= e($departamento['nome'] ?? 'Não definido') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-cash-coin"></i> Salário Base</span>
                    <span class="info-value"><?= dashMoney($cargo['salario_base'] ?? 0) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-currency-exchange"></i> Salário Médio</span>
                    <span class="info-value"><?= dashMoney($salarioMedio) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-people"></i> Total Funcionários</span>
                    <span class="info-value"><?= number_format($totalFuncionarios) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-circle"></i> Status</span>
                    <span class="info-value">
                        <span class="badge-status-cargo <?= $cargo['ativo'] ? 'ativo' : 'inativo' ?>" style="font-size:0.7rem;">
                            <i class="bi <?= $cargo['ativo'] ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                            <?= $cargo['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </span>
                </div>
                <?php if (!empty($cargo['descricao'])): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label"><i class="bi bi-align-left"></i> Descrição</span>
                        <span class="info-value"><?= e($cargo['descricao']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="cargo-card">
            <div class="card-title"><i class="bi bi-star"></i> Destaques do Cargo</div>
            <?php if (empty($ranking)): ?>
                <div class="empty-state-cargo">
                    <span class="empty-icon"><i class="bi bi-star-slash"></i></span>
                    <div>Sem avaliações registradas</div>
                </div>
            <?php else: ?>
                <?php foreach ($ranking as $i => $r): 
                    $medalhas = ['🥇', '🥈', '🥉', '4º', '5º'];
                    $medalha = $medalhas[$i] ?? ($i + 1 . 'º');
                ?>
                    <div class="cargo-list-item">
                        <div class="item-left">
                            <span class="item-avatar"><?= e(strtoupper(substr($r['nome'], 0, 1))) ?></span>
                            <div class="item-text">
                                <?= e($r['nome']) ?>
                                <div class="item-sub"><?= $medalha ?> lugar</div>
                            </div>
                        </div>
                        <span class="badge bg-<?= $i === 0 ? 'warning' : ($i === 1 ? 'secondary' : ($i === 2 ? 'info' : 'light')) ?>">
                            <?= number_format((float) $r['nota'], 2) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     HISTÓRICO SALARIAL
     ============================================================ -->
<div class="cargo-card mb-4">
    <div class="card-title">
        <i class="bi bi-cash-stack"></i> Histórico Salarial
        <span class="badge bg-primary rounded-pill ms-auto" style="font-size:0.55rem;">Últimos 6 meses</span>
    </div>
    <?php if (empty($historicoSalarial)): ?>
        <div class="empty-state-cargo">
            <span class="empty-icon"><i class="bi bi-cash-slash"></i></span>
            <div>Nenhum registro salarial disponível</div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-cargo table">
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Média Salário Base</th>
                        <th>Média Salário Líquido</th>
                        <th>Registros</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historicoSalarial as $h): ?>
                        <tr>
                            <td><strong><?= e($h['mes_referencia']) ?></strong></td>
                            <td><?= dashMoney($h['media_base']) ?></td>
                            <td><?= dashMoney($h['media_liquido']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= $h['total_registros'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     FUNCIONÁRIOS DO CARGO
     ============================================================ -->
<div class="cargo-card mb-4">
    <div class="card-title">
        <i class="bi bi-people"></i> Funcionários deste Cargo
        <span class="badge bg-primary rounded-pill ms-auto" style="font-size:0.55rem;"><?= number_format($totalFuncionarios) ?></span>
    </div>
    <?php if (empty($funcionariosTotal)): ?>
        <div class="empty-state-cargo">
            <span class="empty-icon"><i class="bi bi-people-slash"></i></span>
            <div>Nenhum funcionário vinculado a este cargo</div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-cargo table">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Departamento</th>
                        <th>Admissão</th>
                        <th>Salário</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionariosTotal as $f): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="item-avatar" style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--rh-primary),var(--rh-accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.55rem;text-transform:uppercase;flex-shrink:0;">
                                        <?= e(strtoupper(substr($f['nome'], 0, 1))) ?>
                                    </span>
                                    <span style="font-weight:500;"><?= e($f['nome']) ?></span>
                                </div>
                            </td>
                            <td><?= e($f['departamento_nome'] ?? '-') ?></td>
                            <td><?= formatarData($f['data_admissao'] ?? '') ?></td>
                            <td><?= dashMoney($f['salario'] ?? 0) ?></td>
                            <td>
                                <span class="badge-status-premium <?= $f['status'] ?? 'ativo' ?>" style="font-size:0.6rem;padding:0.15rem 0.5rem;">
                                    <i class="bi <?= $f['status'] === 'ativo' ? 'bi-check-circle' : ($f['status'] === 'ferias' ? 'bi-sun' : ($f['status'] === 'inativo' ? 'bi-x-circle' : 'bi-clock')) ?>"></i>
                                    <?= ucfirst($f['status'] ?? 'Ativo') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/modules/funcionarios/visualizar.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     HISTÓRICO DE FUNCIONÁRIOS (ÚLTIMOS)
     ============================================================ -->
<div class="cargo-card mb-4">
    <div class="card-title">
        <i class="bi bi-clock-history"></i> Histórico de Funcionários
        <span class="badge bg-light text-dark ms-auto" style="font-size:0.55rem;">Últimos 10</span>
    </div>
    <?php if (empty($historicoFuncionarios)): ?>
        <div class="empty-state-cargo">
            <span class="empty-icon"><i class="bi bi-clock-slash"></i></span>
            <div>Nenhum histórico disponível</div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-cargo table">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Admissão</th>
                        <th>Salário</th>
                        <th>Desligamento</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historicoFuncionarios as $h): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="item-avatar" style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--rh-primary),var(--rh-accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.55rem;text-transform:uppercase;flex-shrink:0;">
                                        <?= e(strtoupper(substr($h['nome'], 0, 1))) ?>
                                    </span>
                                    <span style="font-weight:500;"><?= e($h['nome']) ?></span>
                                </div>
                            </td>
                            <td><?= formatarData($h['data_admissao'] ?? '') ?></td>
                            <td><?= dashMoney($h['salario'] ?? 0) ?></td>
                            <td><?= $h['data_demissao'] ? formatarData($h['data_demissao']) : '-' ?></td>
                            <td>
                                <span class="badge-status-premium <?= $h['status'] ?? 'ativo' ?>" style="font-size:0.6rem;padding:0.15rem 0.5rem;">
                                    <i class="bi <?= $h['status'] === 'ativo' ? 'bi-check-circle' : ($h['status'] === 'ferias' ? 'bi-sun' : ($h['status'] === 'inativo' ? 'bi-x-circle' : 'bi-clock')) ?>"></i>
                                    <?= ucfirst($h['status'] ?? 'Ativo') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/modules/funcionarios/visualizar.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>