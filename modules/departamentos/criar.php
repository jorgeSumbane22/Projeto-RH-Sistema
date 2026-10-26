<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$pageTitle = 'Novo Departamento';
$departamentoModel = new Departamento();
$funcionarioModel = new Funcionario();
$funcionarios = $funcionarioModel->listar(['status' => 'ativo']);
$erros = [];
$departamento = $_POST ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'gestor_id' => $_POST['gestor_id'] ?? ''
    ];
    if ($dados['nome'] === '') $erros[] = 'Informe o nome do departamento.';

    if (empty($erros)) {
        $id = $departamentoModel->criar($dados);
        registrarAuditoria('departamento_criado', "ID $id - {$dados['nome']}");
        definirMensagem('success', 'Departamento cadastrado com sucesso.');
        redirecionar('/modules/departamentos/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
.form-container {
    max-width: 650px;
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

@media (max-width: 767.98px) {
    .page-header-custom {
        flex-direction: column;
        align-items: stretch;
    }
    .page-header-custom .btn-back {
        text-align: center;
    }
    .form-card .form-card-header {
        padding: 1rem;
    }
    .form-card .form-card-body {
        padding: 1rem;
    }
}
</style>

<div class="page-header-custom">
    <h5>
        <i class="bi bi-building-add"></i> Novo Departamento
    </h5>
    <a href="index.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Voltar à lista
    </a>
</div>

<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-building" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Cadastrar novo departamento</h5>
        </div>
        <div class="form-card-body">
            <?php require __DIR__ . '/_form.php'; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>