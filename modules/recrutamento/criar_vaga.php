<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$pageTitle = 'Nova Vaga';
$vagaModel = new Vaga();
$departamentoModel = new Departamento();
$departamentos = $departamentoModel->listar(true);
$erros = [];
$vaga = $_POST ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'departamento_id' => $_POST['departamento_id'] ?? '',
        'descricao' => trim($_POST['descricao'] ?? ''),
        'requisitos' => trim($_POST['requisitos'] ?? ''),
    ];
    if ($dados['titulo'] === '') $erros[] = 'Informe o título da vaga.';
    if ($dados['departamento_id'] === '') $erros[] = 'Selecione o departamento.';

    if (empty($erros)) {
        $id = $vagaModel->criar($dados, usuarioAtualId());
        registrarAuditoria('vaga_criada', "ID $id - {$dados['titulo']}");
        definirMensagem('success', 'Vaga publicada com sucesso.');
        redirecionar('/modules/recrutamento/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
.form-container {
    max-width: 700px;
    margin: 0 auto;
}

.form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.form-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.form-card .form-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--rh-border);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.form-card .form-card-header h5 {
    font-weight: 700;
    margin: 0;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.form-card .form-card-header h5 i {
    color: var(--rh-primary);
    margin-right: 6px;
}

.form-card .form-card-body {
    padding: 1.5rem;
}

.page-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.page-header-custom h5 {
    font-weight: 700;
    color: var(--rh-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-header-custom h5 i {
    color: var(--rh-accent);
}

.page-header-custom .btn-back {
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
    transition: all 0.3s ease;
    text-decoration: none;
}

.page-header-custom .btn-back:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--rh-primary);
    color: var(--rh-primary);
}

.form-floating-custom {
    margin-bottom: 1.25rem;
}

.form-floating-custom .form-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
    display: block;
}

.form-floating-custom .form-label i {
    margin-right: 6px;
    font-size: 0.7rem;
}

.form-floating-custom .form-label .required {
    color: #ef4444;
    margin-left: 4px;
}

.form-floating-custom .form-control,
.form-floating-custom .form-select {
    border-radius: 10px;
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
}

.form-floating-custom .form-control:focus,
.form-floating-custom .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
    outline: none;
}

.form-floating-custom .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    margin-top: 4px;
}

.form-floating-custom .form-text i {
    margin-right: 4px;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--rh-border);
}

.form-actions .btn {
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.3);
}

.form-actions .btn-secondary {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
}

.form-actions .btn-secondary:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--bs-secondary-color);
}

@media (max-width: 767.98px) {
    .page-header-custom {
        flex-direction: column;
        align-items: stretch;
    }
    .page-header-custom .btn-back {
        text-align: center;
    }
    .form-card .form-card-body {
        padding: 1rem;
    }
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="page-header-custom">
    <h5>
        <i class="bi bi-briefcase-plus"></i> Nova Vaga
    </h5>
    <a href="index.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Voltar à lista
    </a>
</div>

<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-briefcase" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Publicar nova vaga</h5>
        </div>
        <div class="form-card-body">
            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
                        <i class="bi bi-x-circle me-2"></i>
                        <strong>Por favor, corrija os seguintes erros:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-tag"></i> Título da vaga
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="titulo" class="form-control" required value="<?= e($vaga['titulo'] ?? '') ?>" placeholder="Ex: Desenvolvedor PHP Sênior">
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Nome da posição que está sendo oferecida
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-building"></i> Departamento
                        <span class="required">*</span>
                    </label>
                    <select name="departamento_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($departamentos as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($vaga['departamento_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Departamento onde a vaga está alocada
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-align-left"></i> Descrição
                    </label>
                    <textarea name="descricao" class="form-control" rows="4" placeholder="Descreva as atividades e responsabilidades da vaga..."><?= e($vaga['descricao'] ?? '') ?></textarea>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Descreva as principais atividades e responsabilidades
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-list-check"></i> Requisitos
                    </label>
                    <textarea name="requisitos" class="form-control" rows="4" placeholder="Liste os requisitos necessários para a vaga..."><?= e($vaga['requisitos'] ?? '') ?></textarea>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Requisitos técnicos, formação e experiência necessárias
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Publicar vaga
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>