<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);

// Controle de acesso
if (usuarioAtualPerfil() === PERFIL_FUNCIONARIO && $id !== usuarioAtualFuncionarioId()) {
    exigirPerfil([]);
}
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $funcionarioModelCheck = new Funcionario();
    $meuFuncionario = $funcionarioModelCheck->buscarPorId(usuarioAtualFuncionarioId());
    $alvo = $funcionarioModelCheck->buscarPorId($id);
    if (!$alvo || !$meuFuncionario || $alvo['departamento_id'] != $meuFuncionario['departamento_id']) {
        exigirPerfil([]);
    }
}

$funcionarioModel = new Funcionario();
$documentoModel = new Documento();
$avaliacaoModel = new Avaliacao();
$feriasModel = new Ferias();
$presencaModel = new Presenca();
$folhaModel = new FolhaPagamento();
$logModel = new LogAuditoria();

$funcionario = $funcionarioModel->buscarPorId($id);
if (!$funcionario) {
    definirMensagem('danger', 'Funcionário não encontrado.');
    redirecionar('/modules/funcionarios/index.php');
}

$pageTitle = $funcionario['nome'];
$documentos = $documentoModel->listarPorFuncionario($id);
$avaliacoes = $avaliacaoModel->listar(['funcionario_id' => $id]);
$ferias = $feriasModel->listar(['funcionario_id' => $id]);
$presencas = $presencaModel->listarPorFuncionario($id, date('Y-m-01'), date('Y-m-t'));
$folha = $folhaModel->historicoPorFuncionario($id);
$logs = $logModel->listar(['funcionario_id' => $id, 'limite' => 20]);

// Estatísticas
$totalPresencas = count($presencas);
$presencasMes = count(array_filter($presencas, fn($p) => $p['status'] === 'presente'));
$faltasMes = count(array_filter($presencas, fn($p) => $p['status'] === 'falta'));
$atrasosMes = count(array_filter($presencas, fn($p) => $p['status'] === 'atraso'));
$totalFerias = array_sum(array_column($ferias, 'dias'));
$avaliacaoMedia = !empty($avaliacoes) ? array_sum(array_column($avaliacoes, 'nota_final')) / count($avaliacoes) : 0;

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   PERFIL FUNCIONÁRIO - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DO PERFIL ----- */
.profile-header-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.profile-header-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--rh-primary), var(--rh-accent));
}

.profile-header-premium:hover {
    box-shadow: var(--rh-shadow-hover);
}

.profile-header-premium .profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    border: 3px solid var(--rh-border);
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(21,94,117,0.2);
}

.profile-header-premium .profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-header-premium .profile-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
}

.profile-header-premium .profile-role {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.profile-header-premium .profile-role i {
    margin-right: 4px;
    color: var(--rh-primary);
}

.profile-header-premium .profile-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.profile-header-premium .profile-actions .btn {
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.profile-header-premium .profile-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.profile-header-premium .profile-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21,94,117,0.3);
}

.profile-header-premium .profile-actions .btn-outline-danger:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(239,68,68,0.3);
}

/* ----- BADGE STATUS ----- */
.badge-status-profile {
    padding: 0.25rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.badge-status-profile.ativo { background: #d1fae5; color: #065f46; }
.badge-status-profile.ferias { background: #fef3c7; color: #92400e; }
.badge-status-profile.licenca { background: #dbeafe; color: #1e40af; }
.badge-status-profile.inativo { background: #fee2e2; color: #991b1b; }
.badge-status-profile.desligado { background: #f3f4f6; color: #4b5563; }

[data-bs-theme="dark"] .badge-status-profile.ativo { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-status-profile.ferias { background: #92400e; color: #fef3c7; }
[data-bs-theme="dark"] .badge-status-profile.licenca { background: #1e40af; color: #dbeafe; }
[data-bs-theme="dark"] .badge-status-profile.inativo { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .badge-status-profile.desligado { background: #374151; color: #e5e7eb; }

/* ----- STATS CARDS ----- */
.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.profile-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.profile-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--rh-shadow-hover);
}

.profile-stat-card .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.profile-stat-card .stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.profile-stat-card .stat-value.success { color: var(--rh-accent); }
.profile-stat-card .stat-value.danger { color: #ef4444; }
.profile-stat-card .stat-value.warning { color: #f59e0b; }
.profile-stat-card .stat-value.info { color: #3b82f6; }
.profile-stat-card .stat-value.primary { color: var(--rh-primary); }

/* ----- TABS ----- */
.nav-tabs-premium {
    border-bottom: 2px solid var(--rh-border);
    gap: 0.25rem;
    margin-bottom: 1.5rem;
    flex-wrap: nowrap;
    overflow-x: auto;
}

.nav-tabs-premium .nav-link {
    border: none;
    border-radius: 10px 10px 0 0;
    padding: 0.6rem 1.2rem;
    font-weight: 600;
    font-size: 0.78rem;
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
    background: transparent;
    white-space: nowrap;
}

.nav-tabs-premium .nav-link i {
    margin-right: 6px;
    font-size: 0.85rem;
}

.nav-tabs-premium .nav-link:hover {
    color: var(--rh-primary);
    background: var(--bs-tertiary-bg);
}

.nav-tabs-premium .nav-link.active {
    color: var(--rh-primary);
    background: transparent;
    border-bottom: 3px solid var(--rh-primary);
}

.nav-tabs-premium .nav-link .badge-count {
    font-size: 0.55rem;
    padding: 0.1rem 0.4rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    margin-left: 4px;
}

.nav-tabs-premium .nav-link.active .badge-count {
    background: var(--rh-primary);
    color: #fff;
}

/* ----- TAB CONTENT ----- */
.tab-content .tab-pane {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-content .card-custom {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
}

.tab-content .card-custom:hover {
    box-shadow: var(--rh-shadow-hover);
}

.tab-content .card-custom .card-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-content .card-custom .card-title i {
    color: var(--rh-accent);
}

/* ----- INFO GRID ----- */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.75rem 1.5rem;
}

.info-grid .info-item {
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.info-grid .info-item:last-child {
    border-bottom: none;
}

.info-grid .info-item .info-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    display: block;
    margin-bottom: 1px;
}

.info-grid .info-item .info-label i {
    margin-right: 4px;
    font-size: 0.7rem;
}

.info-grid .info-item .info-value {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rh-text-primary);
}

/* ----- TABLES ----- */
.table-custom {
    margin-bottom: 0;
}

.table-custom thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 600;
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.5rem 0.75rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-custom tbody td {
    padding: 0.4rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.82rem;
    color: var(--rh-text-primary);
}

.table-custom tbody tr {
    transition: all 0.2s ease;
}

.table-custom tbody tr:hover {
    background: rgba(21,94,117,0.03);
}

.table-custom tbody tr:last-child td {
    border-bottom: none;
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    padding: 2rem 1rem;
    text-align: center;
}

.empty-state-premium .empty-icon {
    font-size: 2.5rem;
    color: var(--rh-border);
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin-bottom: 0;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .profile-header-premium {
        padding: 1rem;
    }
    .profile-header-premium .profile-avatar {
        width: 60px;
        height: 60px;
        font-size: 1.6rem;
    }
    .profile-header-premium .profile-name {
        font-size: 1.1rem;
    }
    .profile-header-premium .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.75rem;
    }
    .profile-header-premium .d-flex .d-flex {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }
    .profile-header-premium .profile-actions {
        width: 100%;
    }
    .profile-header-premium .profile-actions .btn {
        flex: 1;
        text-align: center;
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
    .nav-tabs-premium {
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 0.1rem;
    }
    .nav-tabs-premium .nav-link {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
    }
    .nav-tabs-premium .nav-link .badge-count {
        display: none;
    }
    .info-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    .profile-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .table-custom thead th,
    .table-custom tbody td {
        padding: 0.3rem 0.4rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 575.98px) {
    .profile-stats {
        grid-template-columns: 1fr 1fr;
    }
    .profile-stat-card .stat-value {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     HEADER DO PERFIL
     ============================================================ -->
<div class="profile-header-premium">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="profile-avatar">
                <?php if (!empty($funcionario['foto'])): ?>
                    <img src="foto.php?id=<?= $id ?>" alt="Foto de <?= e($funcionario['nome']) ?>">
                <?php else: ?>
                    <?= e(strtoupper(substr($funcionario['nome'], 0, 1))) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="profile-name"><?= e($funcionario['nome']) ?></div>
                <div class="profile-role">
                    <i class="bi bi-briefcase"></i> <?= e($funcionario['cargo_titulo'] ?? 'Cargo não definido') ?>
                    <span class="mx-1">·</span>
                    <i class="bi bi-diagram-3"></i> <?= e($funcionario['departamento_nome'] ?? 'Departamento não definido') ?>
                </div>
                <div class="mt-1">
                    <span class="badge-status-profile <?= $funcionario['status'] ?? 'ativo' ?>">
                        <i class="bi <?= $funcionario['status'] === 'ativo' ? 'bi-check-circle' : ($funcionario['status'] === 'ferias' ? 'bi-sun' : ($funcionario['status'] === 'inativo' ? 'bi-person-x' : 'bi-clock')) ?>"></i>
                        <?= ucfirst($funcionario['status'] ?? 'Ativo') ?>
                    </span>
                    <span class="ms-2 text-muted small">
                        <i class="bi bi-clock"></i> Membro desde <?= formatarData($funcionario['data_admissao'] ?? '') ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="profile-actions">
            <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH])): ?>
                <a href="editar.php?id=<?= $id ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <?php if ($funcionario['status'] !== 'inativo' && $funcionario['status'] !== 'desligado'): ?>
                    <a href="desligar.php?id=<?= $id ?>" class="btn btn-outline-danger js-confirm"
                       data-confirm-msg="Confirma o desligamento deste funcionário?">
                        <i class="bi bi-person-x"></i> Desligar
                    </a>
                <?php endif; ?>
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
<div class="profile-stats">
    <div class="profile-stat-card">
        <div class="stat-value primary"><?= number_format($totalPresencas) ?></div>
        <div class="stat-label">Total Presenças</div>
    </div>
    <div class="profile-stat-card">
        <div class="stat-value success"><?= number_format($presencasMes) ?></div>
        <div class="stat-label">Presente</div>
    </div>
    <div class="profile-stat-card">
        <div class="stat-value danger"><?= number_format($faltasMes) ?></div>
        <div class="stat-label">Faltas</div>
    </div>
    <div class="profile-stat-card">
        <div class="stat-value warning"><?= number_format($atrasosMes) ?></div>
        <div class="stat-label">Atrasos</div>
    </div>
    <div class="profile-stat-card">
        <div class="stat-value info"><?= number_format($totalFerias) ?></div>
        <div class="stat-label">Dias Férias</div>
    </div>
    <div class="profile-stat-card">
        <div class="stat-value primary"><?= number_format($avaliacaoMedia, 1) ?></div>
        <div class="stat-label">Média Avaliações</div>
    </div>
</div>

<!-- ============================================================
     TABS PREMIUM
     ============================================================ -->
<ul class="nav nav-tabs-premium" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-dados">
            <i class="bi bi-person"></i> Dados
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-profissional">
            <i class="bi bi-briefcase"></i> Profissional
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-salario">
            <i class="bi bi-cash-stack"></i> Salário
            <span class="badge-count"><?= count($folha) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ferias">
            <i class="bi bi-umbrella"></i> Férias
            <span class="badge-count"><?= count($ferias) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-presenca">
            <i class="bi bi-clock-history"></i> Presenças
            <span class="badge-count"><?= count($presencas) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-avaliacoes">
            <i class="bi bi-star"></i> Avaliações
            <span class="badge-count"><?= count($avaliacoes) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documentos">
            <i class="bi bi-file-earmark"></i> Documentos
            <span class="badge-count"><?= count($documentos) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-logs">
            <i class="bi bi-journal-text"></i> Histórico
            <span class="badge-count"><?= count($logs) ?></span>
        </button>
    </li>
</ul>

<!-- ============================================================
     TAB CONTENT
     ============================================================ -->
<div class="tab-content">

    <!-- ===== TAB: DADOS PESSOAIS ===== -->
    <div class="tab-pane fade show active" id="tab-dados">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-person"></i> Dados Pessoais</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-id-card"></i> CPF/BI</span>
                    <span class="info-value"><?= e($funcionario['cpf'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-card-text"></i> RG</span>
                    <span class="info-value"><?= e($funcionario['rg'] ?: '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar3"></i> Data de nascimento</span>
                    <span class="info-value">
                        <?= formatarData($funcionario['data_nascimento']) ?>
                        <?php if ($idade = calcularIdade($funcionario['data_nascimento'])): ?>
                            <span class="text-muted small">(<?= $idade ?> anos)</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-gender-ambiguous"></i> Género</span>
                    <span class="info-value"><?= e($funcionario['genero'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-envelope"></i> E-mail</span>
                    <span class="info-value"><?= e($funcionario['email'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-telephone"></i> Telefone</span>
                    <span class="info-value"><?= e($funcionario['telefone'] ?: '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-geo-alt"></i> Endereço</span>
                    <span class="info-value"><?= e($funcionario['endereco'] ?: '-') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TAB: DADOS PROFISSIONAIS ===== -->
    <div class="tab-pane fade" id="tab-profissional">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-briefcase"></i> Dados Profissionais</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-briefcase"></i> Cargo</span>
                    <span class="info-value"><?= e($funcionario['cargo_titulo'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-diagram-3"></i> Departamento</span>
                    <span class="info-value"><?= e($funcionario['departamento_nome'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar-check"></i> Data de admissão</span>
                    <span class="info-value"><?= formatarData($funcionario['data_admissao'] ?? '') ?></span>
                </div>
                <?php if ($funcionario['data_demissao']): ?>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar-x"></i> Data de desligamento</span>
                    <span class="info-value"><?= formatarData($funcionario['data_demissao']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH])): ?>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-cash-coin"></i> Salário base</span>
                    <span class="info-value"><?= dashMoney($funcionario['salario'] ?? 0) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== TAB: SALÁRIO ===== -->
    <div class="tab-pane fade" id="tab-salario">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-cash-stack"></i> Histórico Salarial</div>
            <?php if (empty($folha)): ?>
                <div class="empty-state-premium">
                    <span class="empty-icon"><i class="bi bi-cash-slash"></i></span>
                    <p>Nenhum registro salarial encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Salário Base</th>
                                <th>Bônus</th>
                                <th>Descontos</th>
                                <th>Líquido</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($folha as $f): ?>
                                <tr>
                                    <td><?= e($f['mes_referencia']) ?></td>
                                    <td><?= dashMoney($f['salario_base']) ?></td>
                                    <td class="text-success">+<?= dashMoney($f['bonus']) ?></td>
                                    <td class="text-danger">-<?= dashMoney($f['descontos']) ?></td>
                                    <td><strong><?= dashMoney($f['salario_liquido']) ?></strong></td>
                                    <td><?= badgeStatus($f['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TAB: FÉRIAS ===== -->
    <div class="tab-pane fade" id="tab-ferias">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-umbrella"></i> Férias e Licenças</div>
            <?php if (empty($ferias)): ?>
                <div class="empty-state-premium">
                    <span class="empty-icon"><i class="bi bi-umbrella-slash"></i></span>
                    <p>Nenhum registro de férias encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Período</th>
                                <th>Dias</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ferias as $f): ?>
                                <tr>
                                    <td>
                                        <i class="bi <?= $f['tipo'] === 'ferias' ? 'bi-umbrella' : 'bi-heart-pulse' ?> me-1 text-primary"></i>
                                        <?= e(ucfirst(str_replace('_', ' ', $f['tipo'] ?? 'Férias'))) ?>
                                    </td>
                                    <td><?= formatarData($f['data_inicio']) ?> → <?= formatarData($f['data_fim']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= $f['dias'] ?> dias</span></td>
                                    <td><?= badgeStatus($f['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TAB: PRESENÇAS ===== -->
    <div class="tab-pane fade" id="tab-presenca">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-clock-history"></i> Registros de Presença</div>
            <?php if (empty($presencas)): ?>
                <div class="empty-state-premium">
                    <span class="empty-icon"><i class="bi bi-clock-slash"></i></span>
                    <p>Nenhum registro de presença neste mês.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>H. Extras</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presencas as $p): ?>
                                <tr>
                                    <td><?= formatarData($p['data']) ?></td>
                                    <td><?= !empty($p['hora_entrada']) ? date('H:i', strtotime($p['hora_entrada'])) : '--:--' ?></td>
                                    <td><?= !empty($p['hora_saida']) ? date('H:i', strtotime($p['hora_saida'])) : '--:--' ?></td>
                                    <td><?= ($p['horas_extras'] ?? 0) > 0 ? '<span class="badge bg-warning text-dark">+'.number_format($p['horas_extras'], 1).'h</span>' : '0h' ?></td>
                                    <td><?= badgeStatus($p['status'] ?? 'ausente') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TAB: AVALIAÇÕES ===== -->
    <div class="tab-pane fade" id="tab-avaliacoes">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-star"></i> Avaliações de Desempenho</div>
            <?php if (empty($avaliacoes)): ?>
                <div class="empty-state-premium">
                    <span class="empty-icon"><i class="bi bi-star-slash"></i></span>
                    <p>Nenhuma avaliação registrada.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Pontualidade</th>
                                <th>Qualidade</th>
                                <th>Equipe</th>
                                <th>Proatividade</th>
                                <th>Nota Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avaliacoes as $a): ?>
                                <tr>
                                    <td><?= e($a['periodo']) ?></td>
                                    <td><?= $a['pontualidade'] ?>/5</td>
                                    <td><?= $a['qualidade_trabalho'] ?>/5</td>
                                    <td><?= $a['trabalho_equipe'] ?>/5</td>
                                    <td><?= $a['proatividade'] ?>/5</td>
                                    <td>
                                        <span class="badge <?= $a['nota_final'] >= 4 ? 'bg-success' : ($a['nota_final'] >= 3 ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= number_format($a['nota_final'], 2) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TAB: DOCUMENTOS ===== -->
    <div class="tab-pane fade" id="tab-documentos">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-file-earmark"></i> Documentos</div>
            <?php if (empty($documentos)): ?>
                <div class="empty-state-premium">
                    <span class="empty-icon"><i class="bi bi-file-earmark-slash"></i></span>
                    <p>Nenhum documento cadastrado.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos as $doc): ?>
                                <tr>
                                    <td><i class="bi bi-file-earmark-text me-2 text-primary"></i><?= e($doc['nome_original']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= e(ucfirst($doc['tipo'])) ?></span></td>
                                    <td><?= formatarData($doc['criado_em']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/modules/documentos/download.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TAB: HISTÓRICO DE ALTERAÇÕES ===== -->
    <div class="tab-pane fade" id="tab-logs">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-journal-text"></i> Histórico de Alterações</div>
            <?php if (empty($logs)): ?>
                <div class="empty-state-premium">
                    <span class="empty-icon"><i class="bi bi-journal-slash"></i></span>
                    <p>Nenhuma alteração registrada.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Ação</th>
                                <th>Detalhes</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= formatarDataHora($log['criado_em']) ?></td>
                                    <td><span class="badge bg-primary"><?= e($log['acao']) ?></span></td>
                                    <td><?= e($log['detalhes'] ?? '-') ?></td>
                                    <td><?= e($log['usuario_nome'] ?? 'Sistema') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>