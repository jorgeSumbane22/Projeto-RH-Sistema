<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$id = (int) ($_GET['id'] ?? 0);
$cargoModel = new Cargo();

if (!$cargoModel->podeExcluir($id)) {
    definirMensagem('danger', 'Não é possível excluir: há funcionários vinculados a este cargo.');
} else {
    $cargoModel->excluir($id);
    registrarAuditoria('cargo_excluido', "ID $id");
    definirMensagem('success', 'Cargo excluído com sucesso.');
}
redirecionar('/modules/cargos/index.php');