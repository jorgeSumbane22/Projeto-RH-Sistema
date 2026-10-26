<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$vagaId = (int) ($_GET['id'] ?? 0);
$vagaModel = new Vaga();
$candidatoModel = new Candidato();
$entrevistaModel = new Entrevista();

$vaga = $vagaModel->buscarPorId($vagaId);
if (!$vaga) {
    definirMensagem('danger', 'Vaga não encontrada.');
    redirecionar('/modules/recrutamento/index.php');
}

$pageTitle = 'Candidatos — ' . $vaga['titulo'];
$erros = [];

// Cadastro de novo candidato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'novo_candidato') {
    validarCsrf();
    $dados = [
        'vaga_id'  => $vagaId,
        'nome'     => trim($_POST['nome'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
    ];
    if ($dados['nome'] === '') $erros[] = 'Informe o nome do candidato.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';

    $curriculoNome = null;
    if (!empty($_FILES['curriculo']['name'])) {
        $erroUpload = null;
        $curriculoNome = fazerUploadArquivo($_FILES['curriculo'], UPLOADS_CURRICULOS, $erroUpload);
        if ($erroUpload) $erros[] = $erroUpload;
    }

    if (empty($erros)) {
        $dados['curriculo_arquivo'] = $curriculoNome;
        $candidatoModel->criar($dados);
        registrarAuditoria('candidato_criado', "Vaga $vagaId - {$dados['nome']}");
        definirMensagem('success', 'Candidato adicionado com sucesso.');
        redirecionar('/modules/recrutamento/vaga.php?id=' . $vagaId);
    }
}

// Atualização rápida de status do candidato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'status_candidato') {
    validarCsrf();
    $candidatoModel->atualizarStatus((int) $_POST['candidato_id'], $_POST['status']);
    definirMensagem('success', 'Status do candidato atualizado.');
    redirecionar('/modules/recrutamento/vaga.php?id=' . $vagaId);
}

$candidatos = $candidatoModel->listarPorVaga($vagaId);

// Estatísticas
$totalCandidatos = count($candidatos);
$statusCounts = [];
foreach ($candidatos as $c) {
    $statusCounts[$c['status']] = ($statusCounts[$c['status']] ?? 0) + 1;
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   VAGA - CANDIDATOS - DESIGN PREMIUM
   ============================================================ */

.page-header-vaga {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin: 1rem 0 1.5rem;
}

.page-header-vaga .vaga-title h5 {
    font-weight: 700;
    margin: 0;
    color: var(--bs-body-color);
}

.page-header-vaga .vaga-title .vaga-meta {
    font-size: 0.8rem;
    color: var(--rh-text-muted);
}

.page-header-vaga .vaga-title .vaga-meta i {
    margin-right: 4px;
}

.page-header-vaga .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    border-radius: 10px;
    padding: 0.4rem 1.2rem;
    font-weight: 500;
    font-size: 0.8rem;
    color: #fff;
    transition: all 0.3s ease;
}

.page-header-vaga .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.3);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--rh-text-muted);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.back-link:hover {
    color: var(--rh-primary);
}

.stats-candidatos {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.stats-candidatos .stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--rh-text-muted);
}

.stats-candidatos .stat-item .number {
    font-weight: 700;
    color: var(--bs-body-color);
}

.stats-candidatos .stat-item .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.dot-inscrito { background: #6c757d; }
.dot-triagem { background: #0ea5e9; }
.dot-entrevista { background: #f59e0b; }
.dot-aprovado { background: #10b981; }
.dot-rejeitado { background: #ef4444; }

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

.badge-status-candidato {
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.badge-status-candidato.inscrito { background: #e9ecef; color: #495057; }
.badge-status-candidato.triagem { background: #d1ecf1; color: #0c5460; }
.badge-status-candidato.entrevista { background: #fef3c7; color: #92400e; }
.badge-status-candidato.aprovado { background: #d1fae5; color: #065f46; }
.badge-status-candidato.rejeitado { background: #fee2e2; color: #991b1b; }

[data-bs-theme="dark"] .badge-status-candidato.inscrito { background: #3d3d55; color: #8a8aa0; }
[data-bs-theme="dark"] .badge-status-candidato.triagem { background: #1a3a4a; color: #58a6ff; }
[data-bs-theme="dark"] .badge-status-candidato.entrevista { background: #4a3a1a; color: #fcd34d; }
[data-bs-theme="dark"] .badge-status-candidato.aprovado { background: #1a4a3a; color: #6ee7b7; }
[data-bs-theme="dark"] .badge-status-candidato.rejeitado { background: #4a1a1a; color: #fca5a5; }

.btn-sm-outline {
    padding: 0.2rem 0.6rem;
    border-radius: 8px;
    font-size: 0.7rem;
    transition: all 0.3s ease;
    border: 1px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-sm-outline:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.25);
}

.empty-state-premium {
    padding: 2rem 1rem;
    text-align: center;
}

.empty-state-premium .empty-icon {
    font-size: 3rem;
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

/* ----- MODAL ----- */
.modal-content {
    border-radius: var(--rh-radius);
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    background: var(--rh-card-bg);
}

.modal-header {
    border-bottom: 1px solid var(--rh-border);
    padding: 1rem 1.5rem;
}

.modal-header .modal-title {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid var(--rh-border);
    padding: 1rem 1.5rem;
}

.modal .form-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.modal .form-label i {
    margin-right: 4px;
}

.modal .form-label .required {
    color: #ef4444;
    margin-left: 4px;
}

.modal .form-control {
    border-radius: 10px;
    padding: 0.4rem 0.75rem;
    font-size: 0.85rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
}

.modal .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
}

.modal .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    border-radius: 10px;
    padding: 0.4rem 1.2rem;
    font-weight: 600;
    font-size: 0.85rem;
    color: #fff;
    transition: all 0.3s ease;
}

.modal .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21, 94, 117, 0.3);
}

@media (max-width: 767.98px) {
    .page-header-vaga {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .page-header-vaga .btn-primary {
        width: 100%;
        text-align: center;
    }
    .stats-candidatos {
        gap: 0.5rem;
    }
    .stats-candidatos .stat-item {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
    }
    .table-card-premium .table thead th {
        font-size: 0.5rem;
        padding: 0.4rem 0.5rem;
    }
    .table-card-premium .table tbody td {
        padding: 0.4rem 0.5rem;
        font-size: 0.75rem;
    }
    .btn-sm-outline {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
    }
}
</style>

<!-- ============================================================
     BACK LINK
     ============================================================ -->
<a href="index.php" class="back-link">
    <i class="bi bi-arrow-left"></i> Voltar às vagas
</a>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="page-header-vaga">
    <div class="vaga-title">
        <h5><?= e($vaga['titulo']) ?></h5>
        <div class="vaga-meta">
            <i class="bi bi-building"></i> <?= e($vaga['departamento_nome']) ?>
            <span class="mx-2">·</span>
            <i class="bi bi-people"></i> <?= $totalCandidatos ?> candidatos
            <span class="mx-2">·</span>
            <span class="badge-status <?= $vaga['status'] ?? 'aberta' ?>" style="font-size:0.65rem;">
                <i class="bi <?= ($vaga['status'] ?? 'aberta') === 'aberta' ? 'bi-check-circle' : (($vaga['status'] ?? '') === 'pausada' ? 'bi-pause-circle' : 'bi-x-circle') ?>"></i>
                <?= ucfirst($vaga['status'] ?? 'Aberta') ?>
            </span>
        </div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCandidato">
        <i class="bi bi-person-plus me-1"></i> Adicionar Candidato
    </button>
</div>

<!-- ============================================================
     STATS
     ============================================================ -->
<div class="stats-candidatos">
    <span class="stat-item">
        <i class="bi bi-people"></i> Total: <span class="number"><?= $totalCandidatos ?></span>
    </span>
    <?php foreach (['inscrito', 'triagem', 'entrevista', 'aprovado', 'rejeitado'] as $st): ?>
        <?php if (isset($statusCounts[$st]) && $statusCounts[$st] > 0): ?>
            <span class="stat-item">
                <span class="dot dot-<?= $st ?>"></span>
                <?= ucfirst($st) ?>: <span class="number"><?= $statusCounts[$st] ?></span>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- ============================================================
     TABELA
     ============================================================ -->
<div class="table-card-premium">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="bi bi-person"></i> Nome</th>
                    <th><i class="bi bi-envelope"></i> Contato</th>
                    <th><i class="bi bi-circle"></i> Status</th>
                    <th><i class="bi bi-file"></i> Currículo</th>
                    <th class="text-end"><i class="bi bi-gear"></i> Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($candidatos)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state-premium">
                                <span class="empty-icon"><i class="bi bi-people"></i></span>
                                <p>Nenhum candidato inscrito ainda.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($candidatos as $c): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm"><?= e(strtoupper(substr($c['nome'], 0, 1))) ?></span>
                                <span style="font-weight:500;"><?= e($c['nome']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:0.8rem;">
                                <div><i class="bi bi-envelope" style="color:var(--rh-text-muted);width:16px;"></i> <?= e($c['email']) ?></div>
                                <?php if ($c['telefone']): ?>
                                    <div><i class="bi bi-telephone" style="color:var(--rh-text-muted);width:16px;"></i> <?= e($c['telefone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <form method="POST" class="d-flex align-items-center gap-1">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="acao" value="status_candidato">
                                <input type="hidden" name="candidato_id" value="<?= $c['id'] ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:120px;border-radius:8px;font-size:0.7rem;padding:0.2rem 0.5rem;background:var(--bs-tertiary-bg);border:1.5px solid var(--rh-border);color:var(--bs-body-color);">
                                    <?php foreach (['inscrito','triagem','entrevista','aprovado','rejeitado'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $c['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <?php if ($c['curriculo_arquivo']): ?>
                                <a href="curriculo.php?candidato_id=<?= $c['id'] ?>" class="btn-sm-outline">
                                    <i class="bi bi-download"></i> Baixar
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="entrevista.php?candidato_id=<?= $c['id'] ?>" class="btn-sm-outline">
                                <i class="bi bi-calendar-plus"></i> Entrevista
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     MODAL
     ============================================================ -->
<div class="modal fade" id="modalCandidato" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="acao" value="novo_candidato">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-person-plus" style="color:var(--rh-primary);"></i> Adicionar Candidato</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($erros)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
                            <i class="bi bi-x-circle me-2"></i>
                            <strong>Erros:</strong>
                            <ul class="mb-0 mt-1">
                                <?php foreach ($erros as $erro): ?>
                                    <li><?= e($erro) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person"></i> Nome <span class="required">*</span></label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope"></i> E-mail <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-telephone"></i> Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(84) 91234-5678">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-file"></i> Currículo (PDF/DOC)</label>
                        <input type="file" name="curriculo" class="form-control" accept=".pdf,.doc,.docx">
                        <div style="font-size:0.65rem;color:var(--rh-text-muted);margin-top:4px;">
                            <i class="bi bi-info-circle"></i> Formatos permitidos: PDF, DOC, DOCX
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>