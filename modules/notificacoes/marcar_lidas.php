<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$notificacaoModel = new Notificacao();
$notificacaoModel->marcarTodasComoLidas(usuarioAtualId());

definirMensagem('success', 'Todas as notificações foram marcadas como lidas.');
redirecionar('/modules/notificacoes/index.php');