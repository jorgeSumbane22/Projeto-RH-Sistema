<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$feriasModel = new Ferias();
$ferias = $feriasModel->buscarPorId($id);

if (!$ferias) {
    definirMensagem('danger', 'Solicitação não encontrada.');
    redirecionar('/modules/ferias/index.php');
}

// Apenas o próprio funcionário pode cancelar
if ($ferias['funcionario_id'] != usuarioAtualFuncionarioId()) {
    http_response_code(403);
    require __DIR__ . '/../auth/acesso_negado.php';
    exit;
}

if ($ferias['status'] !== 'pendente') {
    definirMensagem('danger', 'Apenas solicitações pendentes podem ser canceladas.');
    redirecionar('/modules/ferias/index.php');
}

$feriasModel->atualizarStatus($id, 'cancelado');
registrarAuditoria('ferias_cancelada', "ID $id - " . ($ferias['funcionario_nome'] ?? ''));
definirMensagem('success', 'Solicitação cancelada com sucesso.');
redirecionar('/modules/ferias/index.php');