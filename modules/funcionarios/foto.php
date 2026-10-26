<?php
/**
 * foto.php - Serve a foto do funcionário para qualquer usuário autenticado
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$funcionarioModel = new Funcionario();
$funcionario = $funcionarioModel->buscarPorId($id);

if (!$funcionario || empty($funcionario['foto'])) {
    http_response_code(404);
    exit;
}

$caminho = UPLOADS_FOTOS . '/' . $funcionario['foto'];
if (!is_file($caminho)) {
    http_response_code(404);
    exit;
}

$extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
$mime = $extensao === 'png' ? 'image/png' : 'image/jpeg';

header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=3600');
readfile($caminho);
exit;