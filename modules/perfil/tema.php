<?php
/**
 * tema.php
 * Endpoint chamado via fetch() pelo JS para persistir a preferência de tema
 * (claro/escuro) do usuário logado no banco, além do localStorage do navegador.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $tema = ($_POST['tema'] ?? '') === 'dark' ? 'escuro' : 'claro';
    $usuarioModel = new Usuario();
    $usuarioModel->atualizarTema(usuarioAtualId(), $tema);
    $_SESSION['usuario_tema'] = $tema;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);