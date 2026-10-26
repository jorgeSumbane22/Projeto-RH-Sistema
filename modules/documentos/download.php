<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$documentoModel = new Documento();
$documento = $documentoModel->buscarPorId($id);

if (!$documento) {
    http_response_code(404);
    die('
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><title>Documento não encontrado</title></head>
        <body style="font-family:sans-serif;text-align:center;padding:50px;background:#f8f9fa;">
            <div style="max-width:400px;margin:auto;background:white;padding:40px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.1);">
                <i class="bi bi-x-circle" style="font-size:3rem;color:#dc3545;"></i>
                <h3 style="margin:1rem 0 0.5rem;">Documento não encontrado</h3>
                <p style="color:#6c757d;">O documento solicitado não existe ou foi removido.</p>
                <a href="index.php" style="display:inline-block;margin-top:1rem;padding:0.5rem 2rem;background:var(--rh-primary);color:white;text-decoration:none;border-radius:8px;">Voltar</a>
            </div>
        </body>
        </html>
    ');
}

// Controle de acesso
if (usuarioAtualPerfil() === PERFIL_FUNCIONARIO && $documento['funcionario_id'] != usuarioAtualFuncionarioId()) {
    http_response_code(403);
    die('Acesso negado.');
}
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $funcionarioModel = new Funcionario();
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    $alvo = $funcionarioModel->buscarPorId($documento['funcionario_id']);
    if (!$alvo || !$meuFuncionario || $alvo['departamento_id'] != $meuFuncionario['departamento_id']) {
        http_response_code(403);
        die('Acesso negado.');
    }
}

$baseDocumentos = realpath(UPLOADS_DOCUMENTOS);
$caminho = $baseDocumentos ? realpath($baseDocumentos . DIRECTORY_SEPARATOR . $documento['caminho_arquivo']) : false;
if (!$baseDocumentos || !$caminho || !is_file($caminho)
    || !str_starts_with($caminho, $baseDocumentos . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    die('Arquivo não encontrado no servidor.');
}

registrarAuditoria('documento_baixado', "ID $id");

$nomeDownload = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($documento['nome_original'])) ?: 'documento';
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $nomeDownload . '"');
header('Content-Length: ' . filesize($caminho));
readfile($caminho);
exit;