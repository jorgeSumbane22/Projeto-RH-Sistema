<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN]);

$id = (int) ($_GET['id'] ?? 0);
$presencaModel = new Presenca();

$presencaModel->excluir($id);
registrarAuditoria('presenca_excluida', "ID $id");
definirMensagem('success', 'Registro excluído com sucesso.');
redirecionar('/modules/presenca/index.php');