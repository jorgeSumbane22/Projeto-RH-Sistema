<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$candidatoId = (int) ($_GET['candidato_id'] ?? 0);
$candidatoModel = new Candidato();
$candidato = $candidatoModel->buscarPorId($candidatoId);

if (!$candidato || empty($candidato['curriculo_arquivo'])) {
    http_response_code(404);
    die('Currículo não encontrado.');
}

$caminho = UPLOADS_CURRICULOS . '/' . $candidato['curriculo_arquivo'];
if (!is_file($caminho)) {
    http_response_code(404);
    die('Arquivo não encontrado no servidor.');
}

registrarAuditoria('curriculo_baixado', "Candidato $candidatoId");

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="curriculo_' . $candidatoId . '.' . pathinfo($caminho, PATHINFO_EXTENSION) . '"');
header('Content-Length: ' . filesize($caminho));
readfile($caminho);
exit;