<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN, PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR]);
header('Location: ' . BASE_URL . '/modules/funcionarios/imprimir.php'); exit;
