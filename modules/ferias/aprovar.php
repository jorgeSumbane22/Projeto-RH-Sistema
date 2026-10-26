<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('/modules/ferias/index.php');
}
validarCsrf();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!in_array($status, ['aprovado', 'rejeitado'], true)) {
    definirMensagem('danger', 'Ação inválida.');
    redirecionar('/modules/ferias/index.php');
}

$feriasModel = new Ferias();

// Atualizar status
$feriasModel->atualizarStatus($id, $status);

// Buscar registro para notificação
$registro = $feriasModel->buscarPorId($id);
if ($registro) {
    $stmt = Database::getConnection()->prepare('SELECT id FROM usuarios WHERE funcionario_id = :fid');
    $stmt->execute([':fid' => $registro['funcionario_id']]);
    $usuarioDestino = $stmt->fetchColumn();
    if ($usuarioDestino) {
        criarNotificacao(
            (int) $usuarioDestino,
            'sistema',
            'Solicitação ' . ($status === 'aprovado' ? 'Aprovada ✅' : 'Rejeitada ❌'),
            'Sua solicitação de ' . str_replace('_', ' ', $registro['tipo']) . ' foi ' . $status . '.'
        );
    }
}

// Registrar auditoria
registrarAuditoria(
    'ferias_' . $status,
    "ID $id - " . ($registro['funcionario_nome'] ?? '') . " - " . ($registro['tipo'] ?? '')
);

definirMensagem('success', 'Solicitação ' . ($status === 'aprovado' ? 'aprovada' : 'rejeitada') . ' com sucesso.');
redirecionar('/modules/ferias/index.php');