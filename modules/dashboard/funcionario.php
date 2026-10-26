<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_FUNCIONARIO]);
require_once __DIR__ . '/_helpers.php';

// ============================================================
// DADOS DO FUNCIONÁRIO
// ============================================================
$id = usuarioAtualFuncionarioId();
$funcionarioModel = new Funcionario();
$presencaModel = new Presenca();
$feriasModel = new Ferias();
$avaliacaoModel = new Avaliacao();
$folhaModel = new FolhaPagamento();

$funcionario = $id ? $funcionarioModel->buscarPorId($id) : null;
$presenca = $id ? $presencaModel->buscarDoDia($id, date('Y-m-d')) : null;
$historicoPresenca = $id ? $presencaModel->listarPorFuncionario($id, date('Y-m-01'), date('Y-m-t')) : [];
$ferias = $id ? $feriasModel->listar(['funcionario_id' => $id]) : [];
$avaliacoes = $id ? $avaliacaoModel->listar(['funcionario_id' => $id]) : [];
$folha = $id ? $folhaModel->historicoPorFuncionario($id) : [];
$documentos = $id ? dashRows('SELECT id, tipo, nome_original, criado_em FROM documentos WHERE funcionario_id = :id ORDER BY criado_em DESC', [':id' => $id]) : [];
$notificacoes = dashRows('SELECT titulo, mensagem, criado_em FROM notificacoes WHERE usuario_id = :uid ORDER BY criado_em DESC LIMIT 5', [':uid' => usuarioAtualId()]);

// Estatísticas
$diasUsados = array_sum(array_map('intval', array_column(array_filter($ferias, static fn(array $f): bool => $f['status'] === 'aprovado'), 'dias')));
$horasExtras = array_sum(array_map('floatval', array_column($historicoPresenca, 'horas_extras')));
$horasTrabalhadas = array_sum(array_map(static function(array $p): float {
    return $p['hora_entrada'] && $p['hora_saida'] ? (strtotime($p['hora_saida']) - strtotime($p['hora_entrada'])) / 3600 : 0;
}, $historicoPresenca));
$ultimaAvaliacao = !empty($avaliacoes) ? $avaliacoes[0]['nota_final'] ?? null : null;
$feriasPendentes = count(array_filter($ferias, fn($f) => $f['status'] === 'pendente'));
$feriasAprovadas = count(array_filter($ferias, fn($f) => $f['status'] === 'aprovado'));

// ============================================================
// HEADER (usando o header do sistema)
// ============================================================
$pageTitle = 'Dashboard Funcionário';
require_once __DIR__ . '/../../includes/header.php';
exibirMensagem();
?>

<style>
/* ============================================================
   DASHBOARD FUNCIONÁRIO - DESIGN PREMIUM
   ============================================================ */

/* ----- Stats Cards ----- */
.funcionario-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 991.98px) {
    .funcionario-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .funcionario-stats-grid {
        grid-template-columns: 1fr;
    }
}

.kpi-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.2rem 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--kpi-color, var(--rh-primary));
    opacity: 0.8;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--rh-shadow-hover);
}

.kpi-card .kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.kpi-card .kpi-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
}

.kpi-card .kpi-label i {
    margin-right: 4px;
}

.kpi-card .kpi-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--rh-text-primary);
    line-height: 1.2;
    margin: 4px 0 2px;
    letter-spacing: -1px;
}

.kpi-card .kpi-sub {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.kpi-card .kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.kpi-card:hover .kpi-icon {
    transform: scale(1.08) rotate(-4deg);
}

.kpi-card .kpi-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.kpi-card .kpi-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.kpi-card .kpi-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.kpi-card .kpi-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.kpi-card .kpi-icon.danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

/* ----- Welcome Banner ----- */
.welcome-banner-funcionario {
    background: linear-gradient(135deg, var(--rh-primary) 0%, #0B3242 100%);
    border-radius: var(--rh-radius);
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.welcome-banner-funcionario::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(20, 184, 166, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.welcome-banner-funcionario h4 {
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #fff;
}

.welcome-banner-funcionario p {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
    font-size: 0.9rem;
}

.welcome-banner-funcionario .badge-status {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    padding: 0.4rem 1.2rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ----- Cards ----- */
.card-custom {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
    height: 100%;
}

.card-custom:hover {
    box-shadow: var(--rh-shadow-hover);
}

.card-custom h6 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-custom h6 i {
    color: var(--rh-accent);
}

/* ----- Profile Info ----- */
.profile-info dl {
    margin-bottom: 0;
}

.profile-info dt {
    color: var(--rh-text-muted);
    font-weight: 600;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.profile-info dd {
    color: var(--rh-text-primary);
    font-weight: 500;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

/* ----- Attendance ----- */
.attendance-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    text-align: center;
}

.attendance-grid .attendance-item {
    padding: 0.5rem;
    border-radius: 8px;
    background: var(--bs-tertiary-bg);
}

.attendance-grid .attendance-item .value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    display: block;
}

.attendance-grid .attendance-item .label {
    font-size: 0.6rem;
    text-transform: uppercase;
    color: var(--rh-text-muted);
    letter-spacing: 0.05em;
}

.attendance-grid .attendance-item .value.presente { color: var(--rh-accent); }
.attendance-grid .attendance-item .value.ausente { color: #ef4444; }
.attendance-grid .attendance-item .value.atraso { color: #f59e0b; }

/* ----- Tables ----- */
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
    padding: 0.4rem 0.5rem;
    border-bottom: 2px solid var(--rh-border);
}

.table-custom tbody td {
    padding: 0.4rem 0.5rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    font-size: 0.8rem;
    color: var(--rh-text-primary);
}

.table-custom tbody tr:hover {
    background: rgba(21, 94, 117, 0.03);
}

.table-custom tbody tr:last-child td {
    border-bottom: none;
}

/* ----- Document Item ----- */
.doc-item {
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--rh-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.doc-item:last-child {
    border-bottom: none;
}

.doc-item .doc-name {
    font-size: 0.8rem;
    color: var(--rh-text-primary);
}

.doc-item .doc-name i {
    color: var(--rh-accent);
    margin-right: 6px;
}

.doc-item .doc-type {
    font-size: 0.65rem;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
}

/* ----- Notification Item ----- */
.notification-item {
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item .notif-title {
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--rh-text-primary);
}

.notification-item .notif-text {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.notification-item .notif-time {
    font-size: 0.6rem;
    color: var(--rh-text-muted);
}

/* ----- Quick Actions ----- */
.quick-actions-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.quick-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    color: var(--rh-text-primary);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    background: var(--rh-card-bg);
}

.quick-action:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.25);
    text-decoration: none;
}

.quick-action i {
    font-size: 0.9rem;
}

/* ----- Empty State ----- */
.rh-state {
    border: 1px dashed var(--rh-border);
    border-radius: 10px;
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--rh-text-muted);
}

.rh-state i {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.3rem;
    opacity: 0.5;
}

/* ----- Responsive ----- */
@media (max-width: 767.98px) {
    .kpi-card {
        padding: 1rem;
    }
    .kpi-card .kpi-value {
        font-size: 1.3rem;
    }
    .kpi-card .kpi-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .card-custom {
        padding: 1rem;
    }
    .quick-action {
        width: 100%;
        justify-content: center;
    }
    .welcome-banner-funcionario {
        padding: 1rem 1.25rem;
    }
    .welcome-banner-funcionario h4 {
        font-size: 1.1rem;
    }
    .attendance-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 575.98px) {
    .funcionario-stats-grid {
        grid-template-columns: 1fr;
    }
    .profile-info dd {
        font-size: 0.8rem;
    }
    .attendance-grid .attendance-item .value {
        font-size: 1rem;
    }
}
</style>

<!-- ============================================================
     WELCOME BANNER
     ============================================================ -->
<div class="welcome-banner-funcionario d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4><i class="bi bi-person me-2"></i> Olá, <?= e($funcionario['nome'] ?? 'Funcionário') ?> 👋</h4>
        <p>Acompanhe seus dados pessoais e profissionais</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="badge-status">
            <i class="bi <?= ($funcionario['status'] ?? 'inativo') === 'ativo' ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
            <?= ucfirst($funcionario['status'] ?? 'Não vinculado') ?>
        </span>
        <span class="badge-status">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?>
        </span>
    </div>
</div>

<!-- ============================================================
     KPI CARDS
     ============================================================ -->
<div class="funcionario-stats-grid">
    <div class="kpi-card" style="--kpi-color: var(--rh-accent);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-person-check"></i> Estado</div>
                <div class="kpi-value"><?= e($funcionario ? ucfirst($funcionario['status']) : 'Não vinculado') ?></div>
                <div class="kpi-sub">Status atual</div>
            </div>
            <div class="kpi-icon success"><i class="bi bi-person-check"></i></div>
        </div>
    </div>

    <div class="kpi-card" style="--kpi-color: var(--rh-primary);">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-clock"></i> Horas no mês</div>
                <div class="kpi-value"><?= number_format($horasTrabalhadas, 1) ?>h</div>
                <div class="kpi-sub">Total trabalhadas</div>
            </div>
            <div class="kpi-icon primary"><i class="bi bi-clock"></i></div>
        </div>
    </div>

    <div class="kpi-card" style="--kpi-color: #f59e0b;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-airplane"></i> Férias usadas</div>
                <div class="kpi-value"><?= number_format($diasUsados) ?>d</div>
                <div class="kpi-sub">Dias aprovados</div>
            </div>
            <div class="kpi-icon warning"><i class="bi bi-airplane"></i></div>
        </div>
    </div>

    <div class="kpi-card" style="--kpi-color: #3b82f6;">
        <div class="kpi-top">
            <div>
                <div class="kpi-label"><i class="bi bi-star"></i> Desempenho</div>
                <div class="kpi-value"><?= $ultimaAvaliacao !== null ? number_format($ultimaAvaliacao, 2) : '—' ?></div>
                <div class="kpi-sub">Última avaliação</div>
            </div>
            <div class="kpi-icon info"><i class="bi bi-star"></i></div>
        </div>
    </div>
</div>

<!-- ============================================================
     PERFIL E PRESENÇA
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-person-badge"></i> Meu perfil</h6>
            <?php if (!$funcionario): ?>
                <?php dashEmpty('A sua conta não está vinculada a um funcionário.'); ?>
            <?php else: ?>
                <div class="profile-info">
                    <dl class="row small mb-0">
                        <dt class="col-5">Nome</dt>
                        <dd class="col-7"><?= e($funcionario['nome']) ?></dd>
                        <dt class="col-5">Cargo</dt>
                        <dd class="col-7"><?= e($funcionario['cargo_titulo'] ?? '-') ?></dd>
                        <dt class="col-5">Departamento</dt>
                        <dd class="col-7"><?= e($funcionario['departamento_nome'] ?? '-') ?></dd>
                        <dt class="col-5">Admissão</dt>
                        <dd class="col-7"><?= formatarData($funcionario['data_admissao'] ?? '') ?></dd>
                        <dt class="col-5">Salário base</dt>
                        <dd class="col-7"><?= dashMoney($funcionario['salario'] ?? 0) ?></dd>
                    </dl>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-calendar-check"></i> Minha assiduidade hoje</h6>
            <div class="attendance-grid">
                <div class="attendance-item">
                    <span class="value <?= !empty($presenca['hora_entrada']) ? 'presente' : 'ausente' ?>">
                        <?= e(!empty($presenca['hora_entrada']) ? date('H:i', strtotime($presenca['hora_entrada'])) : '—') ?>
                    </span>
                    <span class="label">Entrada</span>
                </div>
                <div class="attendance-item">
                    <span class="value <?= !empty($presenca['hora_saida']) ? 'presente' : 'ausente' ?>">
                        <?= e(!empty($presenca['hora_saida']) ? date('H:i', strtotime($presenca['hora_saida'])) : '—') ?>
                    </span>
                    <span class="label">Saída</span>
                </div>
                <div class="attendance-item">
                    <span class="value <?= e($presenca['status'] ?? 'ausente') ?>">
                        <?= e(ucfirst($presenca['status'] ?? 'sem registo')) ?>
                    </span>
                    <span class="label">Estado</span>
                </div>
            </div>
            <p class="small text-muted mt-3 mb-0">
                <i class="bi bi-clock-history me-1"></i> Horas extras no mês: <?= number_format($horasExtras, 2) ?>h
            </p>
        </div>
    </div>
</div>

<!-- ============================================================
     SALÁRIO E FÉRIAS
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-cash-stack"></i> Meu salário e recibos</h6>
            <?php if (!$folha): ?>
                <?php dashEmpty('Ainda não existem recibos disponíveis.'); ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Valor líquido</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($folha, 0, 6) as $f): ?>
                                <tr>
                                    <td><?= e($f['mes_referencia']) ?></td>
                                    <td><?= dashMoney($f['salario_liquido']) ?></td>
                                    <td><?= badgeStatus($f['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-umbrella"></i> Minhas férias</h6>
            <?php if (!$ferias): ?>
                <?php dashEmpty('Ainda não existem pedidos de férias.'); ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Dias</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($ferias, 0, 5) as $f): ?>
                                <tr>
                                    <td><?= formatarData($f['data_inicio']) ?> a <?= formatarData($f['data_fim']) ?></td>
                                    <td><?= (int) $f['dias'] ?></td>
                                    <td><?= badgeStatus($f['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     DOCUMENTOS E NOTIFICAÇÕES
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-file-earmark"></i> Meus documentos</h6>
            <?php if (!$documentos): ?>
                <?php dashEmpty('Ainda não existem documentos disponíveis.'); ?>
            <?php else: ?>
                <?php foreach ($documentos as $d): ?>
                    <div class="doc-item">
                        <span class="doc-name">
                            <i class="bi bi-file-earmark-text"></i> <?= e($d['nome_original']) ?>
                        </span>
                        <span class="doc-type"><?= e($d['tipo']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-custom">
            <h6><i class="bi bi-bell"></i> Notificações</h6>
            <?php if (!$notificacoes): ?>
                <?php dashEmpty('Não existem novas notificações.'); ?>
            <?php else: ?>
                <?php foreach ($notificacoes as $n): ?>
                    <div class="notification-item">
                        <div class="notif-title"><?= e($n['titulo']) ?></div>
                        <div class="notif-text"><?= e($n['mensagem']) ?></div>
                        <div class="notif-time"><i class="bi bi-clock me-1"></i> <?= formatarDataHora($n['criado_em']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     AÇÕES RÁPIDAS
     ============================================================ -->
<div class="card-custom mb-4">
    <h6><i class="bi bi-lightning-charge-fill text-warning"></i> Ações rápidas</h6>
    <div class="quick-actions-grid">
        <a class="quick-action" href="<?= BASE_URL ?>/modules/ferias/solicitar.php">
            <i class="bi bi-airplane"></i> Solicitar férias
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/folha-pagamento/index.php">
            <i class="bi bi-receipt"></i> Consultar recibo
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/presenca/index.php">
            <i class="bi bi-clock-history"></i> Consultar presença
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/perfil/index.php">
            <i class="bi bi-person-gear"></i> Atualizar perfil
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/notificacoes/index.php">
            <i class="bi bi-chat-dots"></i> Contactar RH
        </a>
        <a class="quick-action" href="<?= BASE_URL ?>/modules/documentos/index.php">
            <i class="bi bi-file-earmark"></i> Meus documentos
        </a>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>