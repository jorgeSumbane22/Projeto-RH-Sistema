<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN, PERFIL_GESTOR]);

$id = (int) ($_GET['id'] ?? 0);
$departamentoModel = new Departamento();
$funcionarioModel = new Funcionario();
$cargoModel = new Cargo();
$presencaModel = new Presenca();
$avaliacaoModel = new Avaliacao();
$folhaModel = new FolhaPagamento();

$departamento = $departamentoModel->buscarPorId($id);
if (!$departamento) {
    definirMensagem('danger', 'Departamento não encontrado.');
    redirecionar('/modules/departamentos/index.php');
}

// Verificar permissão do gestor
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    if (!$meuFuncionario || $meuFuncionario['departamento_id'] != $id) {
        http_response_code(403);
        require __DIR__ . '/../auth/acesso_negado.php';
        exit;
    }
}

// Dados do departamento
$funcionarios = $funcionarioModel->listar(['departamento_id' => $id, 'status' => 'ativo']);
$funcionariosTotal = $funcionarioModel->listar(['departamento_id' => $id]);

// Estatísticas
$totalFuncionarios = count($funcionariosTotal);
$totalAtivos = count(array_filter($funcionariosTotal, fn($f) => $f['status'] === 'ativo'));
$totalInativos = count(array_filter($funcionariosTotal, fn($f) => $f['status'] === 'inativo'));
$totalFerias = count(array_filter($funcionariosTotal, fn($f) => $f['status'] === 'ferias'));
$totalLicencas = count(array_filter($funcionariosTotal, fn($f) => $f['status'] === 'licenca'));

// Cargos do departamento (CORRIGIDO)
try {
    $cargos = $cargoModel->listarPorDepartamento($id);
} catch (Exception $e) {
    $cargos = [];
}

// Desempenho médio do departamento
$mediaDesempenho = (float) dashScalar(
    'SELECT AVG(a.nota_final) FROM avaliacoes_desempenho a 
     JOIN funcionarios f ON f.id = a.funcionario_id 
     WHERE f.departamento_id = :dept AND a.nota_final IS NOT NULL',
    [':dept' => $id]
);

// Ranking de desempenho do departamento
$ranking = dashRows(
    'SELECT f.nome, MAX(a.nota_final) as nota 
     FROM avaliacoes_desempenho a 
     JOIN funcionarios f ON f.id = a.funcionario_id 
     WHERE f.departamento_id = :dept AND a.nota_final IS NOT NULL 
     GROUP BY f.id, f.nome 
     ORDER BY nota DESC LIMIT 5',
    [':dept' => $id]
);

// Presenças do mês
$mesAtual = date('Y-m');
$inicioMes = $mesAtual . '-01';
$fimMes = date('Y-m-t', strtotime($inicioMes));

$presencasMes = (int) dashScalar(
    'SELECT COUNT(*) FROM presencas p 
     JOIN funcionarios f ON f.id = p.funcionario_id 
     WHERE f.departamento_id = :dept 
     AND p.data BETWEEN :inicio AND :fim 
     AND p.status = "presente"',
    [':dept' => $id, ':inicio' => $inicioMes, ':fim' => $fimMes]
);

$faltasMes = (int) dashScalar(
    'SELECT COUNT(*) FROM presencas p 
     JOIN funcionarios f ON f.id = p.funcionario_id 
     WHERE f.departamento_id = :dept 
     AND p.data BETWEEN :inicio AND :fim 
     AND p.status = "falta"',
    [':dept' => $id, ':inicio' => $inicioMes, ':fim' => $fimMes]
);

$atrasosMes = (int) dashScalar(
    'SELECT COUNT(*) FROM presencas p 
     JOIN funcionarios f ON f.id = p.funcionario_id 
     WHERE f.departamento_id = :dept 
     AND p.data BETWEEN :inicio AND :fim 
     AND p.status = "atraso"',
    [':dept' => $id, ':inicio' => $inicioMes, ':fim' => $fimMes]
);

// Folha salarial do mês
$folhaTotal = (float) dashScalar(
    'SELECT COALESCE(SUM(fp.salario_liquido), 0) FROM folha_pagamento fp 
     JOIN funcionarios f ON f.id = fp.funcionario_id 
     WHERE f.departamento_id = :dept AND fp.mes_referencia = :mes',
    [':dept' => $id, ':mes' => $mesAtual]
);

$pageTitle = $departamento['nome'] . ' - Departamento';
require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   VISUALIZAR DEPARTAMENTO - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER ----- */
.dept-profile-header {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.dept-profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--rh-primary), var(--rh-accent));
}

.dept-profile-header:hover {
    box-shadow: var(--rh-shadow-hover);
}

.dept-profile-header .dept-icon {
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

.dept-profile-header .dept-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
}

.dept-profile-header .dept-meta {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.dept-profile-header .dept-meta i {
    margin-right: 4px;
}

.dept-profile-header .dept-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.dept-profile-header .dept-actions .btn {
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.dept-profile-header .dept-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.dept-profile-header .dept-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21,94,117,0.3);
}

/* ----- BADGE STATUS ----- */
.badge-status-dept {
    padding: 0.25rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.badge-status-dept.ativo { background: #d1fae5; color: #065f46; }
.badge-status-dept.inativo { background: #fee2e2; color: #991b1b; }

[data-bs-theme="dark"] .badge-status-dept.ativo { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-status-dept.inativo { background: #991b1b; color: #fee2e2; }

/* ----- STATS CARDS ----- */
.dept-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.dept-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.dept-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--rh-shadow-hover);
}

.dept-stat-card .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.dept-stat-card .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.dept-stat-card .stat-value.success { color: var(--rh-accent); }
.dept-stat-card .stat-value.danger { color: #ef4444; }
.dept-stat-card .stat-value.warning { color: #f59e0b; }
.dept-stat-card .stat-value.info { color: #3b82f6; }
.dept-stat-card .stat-value.primary { color: var(--rh-primary); }
.dept-stat-card .stat-value.purple { color: #8b5cf6; }

/* ----- CARDS ----- */
.dept-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
    height: 100%;
}

.dept-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.dept-card .card-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dept-card .card-title i {
    color: var(--rh-accent);
}

/* ----- TABELA ----- */
.table-dept {
    margin-bottom: 0;
}

.table-dept thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 600;
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.5rem 0.75rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-dept tbody td {
    padding: 0.4rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.82rem;
    color: var(--rh-text-primary);
}

.table-dept tbody tr {
    transition: all 0.2s ease;
}

.table-dept tbody tr:hover {
    background: rgba(21,94,117,0.03);
}

.table-dept tbody tr:last-child td {
    border-bottom: none;
}

/* ----- LISTA ----- */
.dept-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.dept-list-item:last-child {
    border-bottom: none;
}

.dept-list-item .item-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dept-list-item .item-left .item-avatar {
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

.dept-list-item .item-left .item-text {
    font-size: 0.8rem;
    color: var(--rh-text-primary);
}

.dept-list-item .item-left .item-text .item-sub {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
}

.dept-list-item .item-right {
    font-size: 0.65rem;
    font-weight: 600;
}

/* ----- EMPTY STATE ----- */
.empty-state-dept {
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.empty-state-dept .empty-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.3rem;
    opacity: 0.4;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .dept-profile-header {
        padding: 1rem;
    }
    .dept-profile-header .dept-icon {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }
    .dept-profile-header .dept-name {
        font-size: 1.1rem;
    }
    .dept-profile-header .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.75rem;
    }
    .dept-profile-header .d-flex .d-flex {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }
    .dept-profile-header .dept-actions {
        width: 100%;
    }
    .dept-profile-header .dept-actions .btn {
        flex: 1;
        text-align: center;
    }
    .dept-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .dept-card {
        padding: 1rem;
    }
    .table-dept thead th,
    .table-dept tbody td {
        padding: 0.3rem 0.4rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 575.98px) {
    .dept-stats-grid {
        grid-template-columns: 1fr;
    }
    .dept-stat-card .stat-value {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     HEADER DO DEPARTAMENTO
     ============================================================ -->
<div class="dept-profile-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="dept-icon">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <div class="dept-name"><?= e($departamento['nome']) ?></div>
                <div class="dept-meta">
                    <i class="bi bi-calendar3"></i> Criado em <?= formatarData($departamento['criado_em'] ?? date('Y-m-d')) ?>
                    <span class="mx-1">·</span>
                    <span class="badge-status-dept <?= $departamento['ativo'] ? 'ativo' : 'inativo' ?>">
                        <i class="bi <?= $departamento['ativo'] ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                        <?= $departamento['ativo'] ? 'Ativo' : 'Inativo' ?>
                    </span>
                </div>
                <?php if (!empty($departamento['descricao'])): ?>
                    <div class="mt-1 text-muted small"><?= e($departamento['descricao']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="dept-actions">
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
<div class="dept-stats-grid">
    <div class="dept-stat-card">
        <div class="stat-value primary"><?= number_format($totalFuncionarios) ?></div>
        <div class="stat-label">Total</div>
    </div>
    <div class="dept-stat-card">
        <div class="stat-value success"><?= number_format($totalAtivos) ?></div>
        <div class="stat-label">Ativos</div>
    </div>
    <div class="dept-stat-card">
        <div class="stat-value warning"><?= number_format($totalFerias) ?></div>
        <div class="stat-label">Férias</div>
    </div>
    <div class="dept-stat-card">
        <div class="stat-value danger"><?= number_format($totalInativos) ?></div>
        <div class="stat-label">Inativos</div>
    </div>
    <div class="dept-stat-card">
        <div class="stat-value purple"><?= number_format($mediaDesempenho, 2) ?></div>
        <div class="stat-label">Média Desempenho</div>
    </div>
    <div class="dept-stat-card">
        <div class="stat-value info"><?= dashMoney($folhaTotal) ?></div>
        <div class="stat-label">Folha do Mês</div>
    </div>
</div>

<!-- ============================================================
     GRID PRINCIPAL
     ============================================================ -->
<div class="row g-3 mb-4">
    <!-- Gestor Responsável -->
    <div class="col-lg-4">
        <div class="dept-card">
            <div class="card-title"><i class="bi bi-person-badge"></i> Responsável</div>
            <?php if (!empty($departamento['gestor_nome'])): ?>
                <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:var(--bs-tertiary-bg);">
                    <span style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--rh-primary),var(--rh-accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.2rem;flex-shrink:0;text-transform:uppercase;">
                        <?= e(strtoupper(substr($departamento['gestor_nome'], 0, 1))) ?>
                    </span>
                    <div>
                        <div style="font-weight:600;font-size:1rem;color:var(--rh-text-primary);"><?= e($departamento['gestor_nome']) ?></div>
                        <div style="font-size:0.75rem;color:var(--rh-text-muted);"><i class="bi bi-person"></i> Gestor do departamento</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state-dept">
                    <span class="empty-icon"><i class="bi bi-person-slash"></i></span>
                    <div>Nenhum gestor definido</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cargos -->
    <div class="col-lg-4">
        <div class="dept-card">
            <div class="card-title"><i class="bi bi-briefcase"></i> Cargos</div>
            <?php if (empty($cargos)): ?>
                <div class="empty-state-dept">
                    <span class="empty-icon"><i class="bi bi-briefcase-slash"></i></span>
                    <div>Nenhum cargo vinculado</div>
                </div>
            <?php else: ?>
                <?php foreach ($cargos as $c): ?>
                    <div class="dept-list-item">
                        <div class="item-left">
                            <div class="item-text">
                                <?= e($c['titulo']) ?>
                                <div class="item-sub">
                                    <?= number_format($c['total_funcionarios'] ?? 0) ?> funcionários
                                </div>
                            </div>
                        </div>
                        <span class="badge bg-light text-dark"><?= dashMoney($c['salario_base'] ?? 0) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Funcionários em Destaque -->
    <div class="col-lg-4">
        <div class="dept-card">
            <div class="card-title"><i class="bi bi-star"></i> Destaques</div>
            <?php if (empty($ranking)): ?>
                <div class="empty-state-dept">
                    <span class="empty-icon"><i class="bi bi-star-slash"></i></span>
                    <div>Sem avaliações registradas</div>
                </div>
            <?php else: ?>
                <?php foreach ($ranking as $i => $r): 
                    $medalhas = ['🥇', '🥈', '🥉', '4º', '5º'];
                    $medalha = $medalhas[$i] ?? ($i + 1 . 'º');
                ?>
                    <div class="dept-list-item">
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
     PRESENÇAS E FUNCIONÁRIOS
     ============================================================ -->
<div class="row g-3 mb-4">
    <!-- Presenças -->
    <div class="col-lg-6">
        <div class="dept-card">
            <div class="card-title"><i class="bi bi-clock-history"></i> Presenças do Mês</div>
            <div class="row g-2">
                <div class="col-4">
                    <div class="p-2 rounded text-center" style="background:rgba(16,185,129,0.1);">
                        <div class="h4 mb-0" style="color:var(--rh-accent);"><?= number_format($presencasMes) ?></div>
                        <div class="small text-muted">Presentes</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded text-center" style="background:rgba(239,68,68,0.1);">
                        <div class="h4 mb-0" style="color:#ef4444;"><?= number_format($faltasMes) ?></div>
                        <div class="small text-muted">Faltas</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded text-center" style="background:rgba(245,158,11,0.1);">
                        <div class="h4 mb-0" style="color:#f59e0b;"><?= number_format($atrasosMes) ?></div>
                        <div class="small text-muted">Atrasos</div>
                    </div>
                </div>
            </div>
            <div class="mt-2 text-center small text-muted">
                <i class="bi bi-calendar3"></i> Referência: <?= formatarData($inicioMes) ?> a <?= formatarData($fimMes) ?>
            </div>
        </div>
    </div>

    <!-- Funcionários do Departamento -->
    <div class="col-lg-6">
        <div class="dept-card">
            <div class="card-title">
                <i class="bi bi-people"></i> Funcionários
                <span class="badge bg-primary rounded-pill ms-auto" style="font-size:0.55rem;"><?= number_format($totalFuncionarios) ?></span>
            </div>
            <?php if (empty($funcionarios)): ?>
                <div class="empty-state-dept">
                    <span class="empty-icon"><i class="bi bi-people-slash"></i></span>
                    <div>Nenhum funcionário ativo</div>
                </div>
            <?php else: ?>
                <div style="max-height:200px;overflow-y:auto;">
                    <?php foreach (array_slice($funcionarios, 0, 8) as $f): ?>
                        <div class="dept-list-item">
                            <div class="item-left">
                                <span class="item-avatar" style="background:linear-gradient(135deg, #155e75, #0e7490);"><?= e(strtoupper(substr($f['nome'], 0, 1))) ?></span>
                                <div class="item-text">
                                    <?= e($f['nome']) ?>
                                    <div class="item-sub"><?= e($f['cargo_titulo'] ?? 'Sem cargo') ?></div>
                                </div>
                            </div>
                            <span class="badge-status-premium <?= $f['status'] ?? 'ativo' ?>" style="font-size:0.55rem;padding:0.1rem 0.4rem;">
                                <i class="bi <?= $f['status'] === 'ativo' ? 'bi-check-circle' : ($f['status'] === 'ferias' ? 'bi-sun' : 'bi-x-circle') ?>"></i>
                                <?= ucfirst($f['status'] ?? 'Ativo') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($totalFuncionarios > 8): ?>
                    <div class="text-center mt-2">
                        <a href="<?= BASE_URL ?>/modules/funcionarios/index.php?departamento_id=<?= $id ?>" class="small text-decoration-none">
                            <i class="bi bi-arrow-right"></i> Ver todos os <?= number_format($totalFuncionarios) ?> funcionários
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     FUNCIONÁRIOS COMPLETOS (TABELA)
     ============================================================ -->
<div class="dept-card mb-4">
    <div class="card-title">
        <i class="bi bi-list"></i> Lista de Funcionários
        <span class="badge bg-primary rounded-pill ms-auto" style="font-size:0.55rem;"><?= number_format($totalFuncionarios) ?></span>
    </div>
    <?php if (empty($funcionariosTotal)): ?>
        <div class="empty-state-dept">
            <span class="empty-icon"><i class="bi bi-people-slash"></i></span>
            <div>Nenhum funcionário vinculado a este departamento</div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-dept table">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Cargo</th>
                        <th>Admissão</th>
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
                            <td><?= e($f['cargo_titulo'] ?? '-') ?></td>
                            <td><?= formatarData($f['data_admissao'] ?? '') ?></td>
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

<?php require __DIR__ . '/../../includes/footer.php'; ?>