<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_SUPER_ADMIN]);

$id = (int) ($_GET['id'] ?? 0);
$usuarioModel = new Usuario();
$funcionarioModel = new Funcionario();
$usuario = $usuarioModel->buscarPorId($id);

if (!$usuario) {
    definirMensagem('danger', 'Usuário não encontrado.');
    redirecionar('/modules/usuarios/index.php');
}

$pageTitle = 'Editar Usuário';
$funcionarios = $funcionarioModel->listar(['status' => 'ativo']);
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'perfil' => $_POST['perfil'] ?? 'funcionario',
        'funcionario_id' => $_POST['funcionario_id'] ?? '',
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
    ];
    
    if ($dados['nome'] === '') $erros[] = 'Informe o nome do usuário.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (!empty($dados['senha']) && strlen($dados['senha']) < 6) $erros[] = 'A senha deve ter ao menos 6 caracteres.';
    if (!in_array($dados['perfil'], PERFIS_SISTEMA, true)) $erros[] = 'Perfil de acesso inválido.';
    if ($dados['perfil'] === PERFIL_SUPER_ADMIN && usuarioAtualPerfil() !== PERFIL_SUPER_ADMIN) {
        $erros[] = 'Apenas o Super Admin pode atribuir o perfil Super Admin.';
    }
    if ($usuario['perfil'] === PERFIL_SUPER_ADMIN && $id === usuarioAtualId() && $dados['perfil'] !== PERFIL_SUPER_ADMIN) {
        $erros[] = 'O Super Admin não pode remover o próprio perfil.';
    }
    if (empty($erros) && $usuarioModel->existeEmail($dados['email'], $id)) $erros[] = 'Já existe outro usuário com este e-mail.';

    if (empty($erros)) {
        $usuarioModel->atualizar($id, $dados);
        registrarAuditoria('usuario_atualizado', "ID $id");
        definirMensagem('success', 'Usuário atualizado com sucesso.');
        redirecionar('/modules/usuarios/index.php');
    }
    $usuario = array_merge($usuario, $dados);
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="border-radius:var(--rh-radius);max-width:750px;margin:0 auto;">
    <div class="card-header bg-transparent border-0" style="padding:1.5rem 2rem 0 2rem;">
        <h5 class="mb-0" style="font-weight:700;color:var(--rh-primary);">
            <i class="bi bi-pencil-square me-2"></i>Editar Usuário
        </h5>
        <p class="text-muted small mt-1" style="font-size:0.85rem;">
            Edite as informações da conta de <strong><?= e($usuario['nome']) ?></strong>.
        </p>
    </div>
    <div class="card-body" style="padding:1.5rem 2rem 2rem 2rem;">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>