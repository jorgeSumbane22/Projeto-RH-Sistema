<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$notificacaoModel = new Notificacao();

if ($id > 0) {
    $notificacaoModel->excluir($id);
}

redirecionar('/modules/notificacoes/index.php');