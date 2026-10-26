<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Novo Usuário';
$usuarioModel = new Usuario();
$funcionarioModel = new Funcionario();
$funcionarios = $funcionarioModel->listar(['status' => 'ativo']);
$erros = [];
$usuario = $_POST ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'perfil' => $_POST['perfil'] ?? 'funcionario',
        'funcionario_id' => $_POST['funcionario_id'] ?? '',
    ];
    
    if ($dados['nome'] === '') $erros[] = 'Informe o nome do usuário.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (strlen($dados['senha']) < 6) $erros[] = 'A senha deve ter ao menos 6 caracteres.';
    if (!in_array($dados['perfil'], PERFIS_SISTEMA, true)) $erros[] = 'Perfil de acesso inválido.';
    if ($dados['perfil'] === PERFIL_SUPER_ADMIN && usuarioAtualPerfil() !== PERFIL_SUPER_ADMIN) {
        $erros[] = 'Apenas o Super Admin pode criar outro Super Admin.';
    }
    if (empty($erros) && $usuarioModel->existeEmail($dados['email'])) $erros[] = 'Já existe um usuário com este e-mail.';

    if (empty($erros)) {
        $id = $usuarioModel->criar($dados);
        registrarAuditoria('usuario_criado', "ID $id - {$dados['email']}");
        definirMensagem('success', 'Usuário criado com sucesso.');
        redirecionar('/modules/usuarios/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="border-radius:var(--rh-radius);max-width:750px;margin:0 auto;">
    <div class="card-header bg-transparent border-0" style="padding:1.5rem 2rem 0 2rem;">
        <h5 class="mb-0" style="font-weight:700;color:var(--rh-primary);">
            <i class="bi bi-person-plus me-2"></i>Novo Usuário
        </h5>
        <p class="text-muted small mt-1" style="font-size:0.85rem;">
            Crie uma nova conta de acesso ao sistema.
        </p>
    </div>
    <div class="card-body" style="padding:1.5rem 2rem 2rem 2rem;">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>