<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$dashboards = [
    PERFIL_SUPER_ADMIN => 'super_admin.php',
    PERFIL_ADMIN_RH => 'administrador_rh.php',
    PERFIL_GESTOR => 'gestor.php',
    PERFIL_FUNCIONARIO => 'funcionario.php',
    PERFIL_AUDITOR => 'auditor.php',
];

$perfil = usuarioAtualPerfil();
if (!isset($dashboards[$perfil])) {
    http_response_code(403);
    require __DIR__ . '/../auth/acesso_negado.php';
    exit;
}

require __DIR__ . '/' . $dashboards[$perfil];
