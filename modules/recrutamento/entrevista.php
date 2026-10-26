<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$candidatoId = (int) ($_GET['candidato_id'] ?? $_POST['candidato_id'] ?? 0);
$candidatoModel = new Candidato();
$entrevistaModel = new Entrevista();

$candidato = $candidatoModel->buscarPorId($candidatoId);
if (!$candidato) {
    definirMensagem('danger', 'Candidato não encontrado.');
    redirecionar('/modules/recrutamento/index.php');
}

$pageTitle = 'Entrevistas — ' . $candidato['nome'];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'agendar') {
    validarCsrf();
    $dados = [
        'candidato_id'     => $candidatoId,
        'entrevistador_id' => usuarioAtualId(),
        'data_hora'        => $_POST['data_hora'] ?? '',
        'local_ou_link'    => trim($_POST['local_ou_link'] ?? ''),
        'observacoes'      => trim($_POST['observacoes'] ?? ''),
    ];
    if (empty($dados['data_hora'])) $erros[] = 'Informe data e hora da entrevista.';

    if (empty($erros)) {
        $entrevistaModel->criar($dados);
        registrarAuditoria('entrevista_agendada', "Candidato $candidatoId");
        definirMensagem('success', 'Entrevista agendada com sucesso.');
        redirecionar('/modules/recrutamento/entrevista.php?candidato_id=' . $candidatoId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'resultado') {
    validarCsrf();
    $entrevistaModel->registrarResultado((int) $_POST['entrevista_id'], $_POST['resultado'], trim($_POST['observacoes'] ?? ''));
    if ($_POST['resultado'] === 'aprovado') {
        $candidatoModel->atualizarStatus($candidatoId, 'aprovado');
    } elseif ($_POST['resultado'] === 'reprovado') {
        $candidatoModel->atualizarStatus($candidatoId, 'rejeitado');
    }
    definirMensagem('success', 'Resultado da entrevista registrado.');
    redirecionar('/modules/recrutamento/entrevista.php?candidato_id=' . $candidatoId);
}

$entrevistas = $entrevistaModel->listarPorCandidato($candidatoId);

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   ENTREVISTAS - DESIGN PREMIUM
   ============================================================ */

.page-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--rh-text-muted);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.page-back:hover {
    color: var(--rh-primary);
}

.page-title {
    margin: 0.5rem 0 1.5rem;
}

.page-title h5 {
    font-weight: 700;
    color: var(--bs-body-color);
    margin: 0;
}

.page-title .subtitle {
    font-size: 0.8rem;
    color: var(--rh-text-muted);
}

.entrevista-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
}

@media (max-width: 767.98px) {
    .entrevista-grid {
        grid-template-columns: 1fr;
    }
}

.card-form {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
}

.card-form:hover {
    box-shadow: var(--rh-shadow-hover);
}

.card-form h6 {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 1rem;
}

.card-form h6 i {
    color: var(--rh-primary);
    margin-right: 6px;
}

.card-form .form-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.card-form .form-label i {
    margin-right: 4px;
}

.card-form .form-label .required {
    color: #ef4444;
    margin-left: 4px;
}

.card-form .form-control {
    border-radius: 10px;
    padding: 0.4rem 0.75rem;
    font-size: 0.85rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
}

.card-form .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
}

.card-form .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    border-radius: 10px;
    padding: 0.4rem 1.2rem;
    font-weight: 600;
    font-size: 0.85rem;
    color: #fff;
    transition: all 0.3s ease;
    width: 100%;
}

.card-form .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21, 94, 117, 0.3);
}

.card-historico {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.25rem;
    transition: all 0.3s ease;
}

.card-historico:hover {
    box-shadow: var(--rh-shadow-hover);
}

.card-historico h6 {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 1rem;
}

.card-historico h6 i {
    color: var(--rh-primary);
    margin-right: 6px;
}

.entrevista-item {
    border: 1px solid var(--rh-border);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
}

.entrevista-item:hover {
    border-color: var(--rh-primary);
    background: var(--bs-tertiary-bg);
}

.entrevista-item:last-child {
    margin-bottom: 0;
}

.entrevista-item .entrevista-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.entrevista-item .entrevista-header .data-hora {
    font-weight: 700;
    color: var(--bs-body-color);
    font-size: 0.9rem;
}

.entrevista-item .entrevista-header .data-hora i {
    margin-right: 4px;
    color: var(--rh-primary);
}

.entrevista-item .entrevista-detalhes {
    font-size: 0.8rem;
    color: var(--rh-text-muted);
}

.entrevista-item .entrevista-detalhes i {
    margin-right: 4px;
    width: 16px;
}

.entrevista-item .entrevista-obs {
    font-size: 0.8rem;
    color: var(--rh-text-muted);
    margin-top: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border-radius: 8px;
}

.badge-resultado {
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
}

.badge-resultado.pendente {
    background: #fef3c7;
    color: #92400e;
}
.badge-resultado.aprovado {
    background: #d1fae5;
    color: #065f46;
}
.badge-resultado.reprovado {
    background: #fee2e2;
    color: #991b1b;
}

[data-bs-theme="dark"] .badge-resultado.pendente {
    background: #92400e;
    color: #fef3c7;
}
[data-bs-theme="dark"] .badge-resultado.aprovado {
    background: #065f46;
    color: #d1fae5;
}
[data-bs-theme="dark"] .badge-resultado.reprovado {
    background: #991b1b;
    color: #fee2e2;
}

.btn-resultado {
    padding: 0.2rem 0.8rem;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
}

.btn-resultado.approve {
    background: #d1fae5;
    color: #065f46;
}

.btn-resultado.approve:hover {
    background: #10b981;
    color: #fff;
    transform: translateY(-2px);
}

.btn-resultado.reject {
    background: #fee2e2;
    color: #991b1b;
}

.btn-resultado.reject:hover {
    background: #ef4444;
    color: #fff;
    transform: translateY(-2px);
}

.empty-state-premium {
    text-align: center;
    padding: 1.5rem;
    color: var(--rh-text-muted);
}

.empty-state-premium .empty-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

@media (max-width: 767.98px) {
    .card-form {
        padding: 1rem;
    }
    .card-historico {
        padding: 1rem;
    }
    .entrevista-item .entrevista-header {
        flex-direction: column;
        align-items: stretch;
    }
    .entrevista-item .entrevista-header .badge-resultado {
        align-self: flex-start;
    }
}
</style>

<!-- ============================================================
     BACK LINK
     ============================================================ -->
<a href="vaga.php?id=<?= $candidato['vaga_id'] ?>" class="page-back">
    <i class="bi bi-arrow-left"></i> Voltar à vaga
</a>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="page-title">
    <h5>
        <i class="bi bi-chat-dots" style="color:var(--rh-primary);"></i>
        Entrevistas de <?= e($candidato['nome']) ?>
    </h5>
    <div class="subtitle">
        <i class="bi bi-briefcase"></i> <?= e($candidato['vaga_titulo'] ?? 'Vaga não definida') ?>
        <span class="mx-2">·</span>
        <i class="bi bi-envelope"></i> <?= e($candidato['email']) ?>
    </div>
</div>

<!-- ============================================================
     GRID
     ============================================================ -->
<div class="entrevista-grid">

    <!-- Agendar Entrevista -->
    <div class="card-form">
        <h6><i class="bi bi-calendar-plus"></i> Agendar nova entrevista</h6>
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="acao" value="agendar">
            <input type="hidden" name="candidato_id" value="<?= $candidatoId ?>">

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
                    <i class="bi bi-x-circle me-2"></i>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($erros as $erro): ?>
                            <li><?= e($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-calendar3"></i> Data e hora <span class="required">*</span></label>
                <input type="datetime-local" name="data_hora" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-geo-alt"></i> Local ou link</label>
                <input type="text" name="local_ou_link" class="form-control" placeholder="Sala 2 ou link de videochamada">
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-chat"></i> Observações</label>
                <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações sobre a entrevista..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-calendar-plus me-1"></i> Agendar
            </button>
        </form>
    </div>

    <!-- Histórico -->
    <div class="card-historico">
        <h6><i class="bi bi-clock-history"></i> Histórico de entrevistas</h6>

        <?php if (empty($entrevistas)): ?>
            <div class="empty-state-premium">
                <span class="empty-icon"><i class="bi bi-chat-slash"></i></span>
                <p>Nenhuma entrevista agendada.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($entrevistas as $e): ?>
            <div class="entrevista-item">
                <div class="entrevista-header">
                    <span class="data-hora">
                        <i class="bi bi-calendar3"></i> <?= formatarDataHora($e['data_hora']) ?>
                    </span>
                    <span class="badge-resultado <?= $e['resultado'] ?? 'pendente' ?>">
                        <i class="bi <?= ($e['resultado'] ?? 'pendente') === 'aprovado' ? 'bi-check-circle' : (($e['resultado'] ?? 'pendente') === 'reprovado' ? 'bi-x-circle' : 'bi-clock') ?>"></i>
                        <?= ucfirst($e['resultado'] ?? 'Pendente') ?>
                    </span>
                </div>
                <div class="entrevista-detalhes">
                    <div><i class="bi bi-geo-alt"></i> <?= e($e['local_ou_link'] ?: 'Local não informado') ?></div>
                    <div><i class="bi bi-person"></i> Entrevistador: <?= e($e['entrevistador_nome'] ?? '-') ?></div>
                </div>
                <?php if ($e['observacoes']): ?>
                    <div class="entrevista-obs">
                        <i class="bi bi-chat"></i> <?= e($e['observacoes']) ?>
                    </div>
                <?php endif; ?>

                <?php if (($e['resultado'] ?? 'pendente') === 'pendente'): ?>
                    <div class="mt-2 d-flex gap-2">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="acao" value="resultado">
                            <input type="hidden" name="candidato_id" value="<?= $candidatoId ?>">
                            <input type="hidden" name="entrevista_id" value="<?= $e['id'] ?>">
                            <button type="submit" name="resultado" value="aprovado" class="btn-resultado approve">
                                <i class="bi bi-check-circle"></i> Aprovar
                            </button>
                            <button type="submit" name="resultado" value="reprovado" class="btn-resultado reject">
                                <i class="bi bi-x-circle"></i> Reprovar
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>