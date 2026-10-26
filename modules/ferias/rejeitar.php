<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_SUPER_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('/modules/ferias/index.php');
}
validarCsrf();

$id = (int) ($_POST['id'] ?? 0);
$status = 'rejeitado';

$feriasModel = new Ferias();
$feriasModel->atualizarStatus($id, $status);

$registro = $feriasModel->buscarPorId($id);
if ($registro) {
    $stmt = Database::getConnection()->prepare('SELECT id FROM usuarios WHERE funcionario_id = :fid');
    $stmt->execute([':fid' => $registro['funcionario_id']]);
    $usuarioDestino = $stmt->fetchColumn();
    if ($usuarioDestino) {
        criarNotificacao(
            (int) $usuarioDestino,
            'sistema',
            'Solicitação Rejeitada ❌',
            'Sua solicitação de ' . str_replace('_', ' ', $registro['tipo']) . ' foi rejeitada.'
        );
    }
}

registrarAuditoria('ferias_rejeitada', "ID $id - " . ($registro['funcionario_nome'] ?? ''));
definirMensagem('success', 'Solicitação rejeitada com sucesso.');
redirecionar('/modules/ferias/index.php');