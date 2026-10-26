<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$id = (int) ($_GET['id'] ?? 0);
$funcionarioModel = new Funcionario();
$cargoModel = new Cargo();
$departamentoModel = new Departamento();

$funcionario = $funcionarioModel->buscarPorId($id);
if (!$funcionario) {
    definirMensagem('danger', 'Funcionário não encontrado.');
    redirecionar('/modules/funcionarios/index.php');
}

$pageTitle = 'Editar Funcionário';
$cargos = $cargoModel->listar();
$departamentos = $departamentoModel->listar(true);
$erros = [];

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
        'status'          => $_POST['status'] ?? 'ativo',
    ];

    if ($dados['nome'] === '') $erros[] = 'Informe o nome.';
    if ($dados['cpf'] === '') $erros[] = 'Informe o CPF/BI.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if ($dados['data_admissao'] === '') $erros[] = 'Informe a data de admissão.';
    if (!is_numeric($dados['salario']) || (float) $dados['salario'] < 0) $erros[] = 'Salário inválido.';

    if (empty($erros) && $funcionarioModel->existeCpfOuEmail($dados['cpf'], $dados['email'], $id)) {
        $erros[] = 'Já existe outro funcionário com este CPF/BI ou e-mail.';
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
        $funcionarioModel->atualizar($id, $dados);
        registrarAuditoria('funcionario_atualizado', "ID $id - {$dados['nome']}");
        definirMensagem('success', 'Dados atualizados com sucesso.');
        redirecionar('/modules/funcionarios/visualizar.php?id=' . $id);
    }
    $funcionario = array_merge($funcionario, $dados);
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

.form-card .form-card-header .badge-status {
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: auto;
}

.form-card .form-card-header .badge-status.ativo { background: #d1fae5; color: #065f46; }
.form-card .form-card-header .badge-status.ferias { background: #fef3c7; color: #92400e; }
.form-card .form-card-header .badge-status.inativo { background: #fee2e2; color: #991b1b; }
.form-card .form-card-header .badge-status.licenca { background: #dbeafe; color: #1e40af; }

[data-bs-theme="dark"] .form-card .form-card-header .badge-status.ativo { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .form-card .form-card-header .badge-status.ferias { background: #92400e; color: #fef3c7; }
[data-bs-theme="dark"] .form-card .form-card-header .badge-status.inativo { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .form-card .form-card-header .badge-status.licenca { background: #1e40af; color: #dbeafe; }

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
    .form-card .form-card-header .badge-status {
        margin-left: 0;
    }
    .form-card .form-card-body {
        padding: 1rem;
    }
}
</style>

<div class="page-header-custom">
    <h5>
        <i class="bi bi-pencil-square"></i> Editar Funcionário
    </h5>
    <a href="visualizar.php?id=<?= $id ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i> Voltar ao perfil
    </a>
</div>

<div class="form-card">
    <div class="form-card-header">
        <i class="bi bi-person" style="color:var(--rh-primary);font-size:1.2rem;"></i>
        <h5><?= e($funcionario['nome']) ?></h5>
        <span class="badge-status <?= $funcionario['status'] ?? 'ativo' ?>">
            <i class="bi <?= $funcionario['status'] === 'ativo' ? 'bi-check-circle' : ($funcionario['status'] === 'ferias' ? 'bi-sun' : 'bi-x-circle') ?>"></i>
            <?= ucfirst($funcionario['status'] ?? 'Ativo') ?>
        </span>
    </div>
    <div class="form-card-body">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>