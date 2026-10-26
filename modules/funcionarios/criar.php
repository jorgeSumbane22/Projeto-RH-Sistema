<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$pageTitle = 'Novo Funcionário';

$funcionarioModel = new Funcionario();
$cargoModel = new Cargo();
$departamentoModel = new Departamento();

$cargos = $cargoModel->listar();
$departamentos = $departamentoModel->listar(true);
$erros = [];
$funcionario = $_POST ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $dados = [
        'nome'            => trim($_POST['nome'] ?? ''),
        'cpf'             => trim($_POST['cpf'] ?? ''),
        'rg'              => trim($_POST['rg'] ?? ''),
        'data_nascimento' => $_POST['data_nascimento'] ?? '',
        'genero'          => $_POST['genero'] ?? '',
        'email'           => trim($_POST['email'] ?? ''),
        'telefone'        => trim($_POST['telefone'] ?? ''),
        'endereco'        => trim($_POST['endereco'] ?? ''),
        'data_admissao'   => $_POST['data_admissao'] ?? '',
        'cargo_id'        => $_POST['cargo_id'] ?? '',
        'departamento_id' => $_POST['departamento_id'] ?? '',
        'salario'         => $_POST['salario'] ?? '',
        'status'          => 'ativo',
    ];

    if ($dados['nome'] === '') $erros[] = 'Informe o nome.';
    if ($dados['cpf'] === '') $erros[] = 'Informe o CPF/BI.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if ($dados['data_admissao'] === '') $erros[] = 'Informe a data de admissão.';
    if ($dados['cargo_id'] === '') $erros[] = 'Selecione o cargo.';
    if ($dados['departamento_id'] === '') $erros[] = 'Selecione o departamento.';
    if (!is_numeric($dados['salario']) || (float) $dados['salario'] < 0) $erros[] = 'Salário inválido.';

    if (empty($erros) && $funcionarioModel->existeCpfOuEmail($dados['cpf'], $dados['email'])) {
        $erros[] = 'Já existe um funcionário com este CPF/BI ou e-mail.';
    }

    if (empty($erros) && !empty($_FILES['foto']['name'])) {
        $erroUpload = null;
        $nomeFoto = fazerUploadArquivo($_FILES['foto'], UPLOADS_FOTOS, $erroUpload);
        if ($erroUpload) {
            $erros[] = $erroUpload;
        } else {
            $dados['foto'] = $nomeFoto;
        }
    }

    if (empty($erros)) {
        $id = $funcionarioModel->criar($dados);
        registrarAuditoria('funcionario_criado', "ID $id - {$dados['nome']}");
        definirMensagem('success', 'Funcionário cadastrado com sucesso.');
        redirecionar('/modules/funcionarios/visualizar.php?id=' . $id);
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
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

.form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    transition: all 0.3s ease;
    overflow: hidden;
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
    font-size: 1rem;
    color: var(--bs-body-color);
}

.form-card .form-card-header h5 i {
    color: var(--rh-primary);
    margin-right: 6px;
}

.form-card .form-card-body {
    padding: 1.5rem;
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
        flex-wrap: wrap;
    }
    .form-card .form-card-body {
        padding: 1rem;
    }
}
</style>

<div class="page-header-custom">
    <h5>
        <i class="bi bi-person-plus"></i> Novo Funcionário
    </h5>
    <a href="index.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Voltar à lista
    </a>
</div>

<div class="form-card">
    <div class="form-card-header">
        <i class="bi bi-person" style="color:var(--rh-primary);font-size:1.2rem;"></i>
        <h5>Cadastrar novo colaborador</h5>
    </div>
    <div class="form-card-body">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>