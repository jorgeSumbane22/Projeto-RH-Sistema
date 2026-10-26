<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (estaLogado()) {
    registrarAuditoria('logout', 'Logout realizado');
}

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/modules/auth/login.php');
exit;