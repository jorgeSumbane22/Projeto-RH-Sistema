<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$id = (int) ($_GET['id'] ?? 0);
$departamentoModel = new Departamento();

if (!$departamentoModel->podeExcluir($id)) {
    definirMensagem('danger', 'Não é possível excluir: há funcionários vinculados a este departamento.');
} else {
    $departamentoModel->excluir($id);
    registrarAuditoria('departamento_excluido', "ID $id");
    definirMensagem('success', 'Departamento excluído com sucesso.');
}
redirecionar('/modules/departamentos/index.php');