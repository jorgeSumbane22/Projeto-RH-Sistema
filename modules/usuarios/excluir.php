<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_SUPER_ADMIN]);

$id = (int) ($_GET['id'] ?? 0);

if ($id === usuarioAtualId()) {
    definirMensagem('danger', 'Você não pode excluir a própria conta.');
    redirecionar('/modules/usuarios/index.php');
}

$usuarioModel = new Usuario();
$alvo = $usuarioModel->buscarPorId($id);

if (!$alvo) {
    definirMensagem('danger', 'Usuário não encontrado.');
    redirecionar('/modules/usuarios/index.php');
}

if ($alvo['perfil'] === PERFIL_SUPER_ADMIN && usuarioAtualPerfil() !== PERFIL_SUPER_ADMIN) {
    definirMensagem('danger', 'Apenas o Super Admin pode excluir uma conta Super Admin.');
    redirecionar('/modules/usuarios/index.php');
}

$usuarioModel->excluir($id);
registrarAuditoria('usuario_excluido', "ID $id - {$alvo['email']}");
definirMensagem('success', 'Usuário excluído com sucesso.');
redirecionar('/modules/usuarios/index.php');