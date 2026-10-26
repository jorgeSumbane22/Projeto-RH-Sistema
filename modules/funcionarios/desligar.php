<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$id = (int) ($_GET['id'] ?? 0);
$funcionarioModel = new Funcionario();
$funcionario = $funcionarioModel->buscarPorId($id);

if (!$funcionario) {
    definirMensagem('danger', 'Funcionário não encontrado.');
    redirecionar('/modules/funcionarios/index.php');
}

$funcionarioModel->desligar($id, date('Y-m-d'));
registrarAuditoria('funcionario_desligado', "ID $id - {$funcionario['nome']}");
definirMensagem('success', 'Funcionário desligado com sucesso.');
redirecionar('/modules/funcionarios/visualizar.php?id=' . $id);