<?php
/** Centralized permission matrix. Use canAccessModule() in controllers and menus. */
if (!defined('PERFIL_SUPER_ADMIN')) { require_once __DIR__ . '/config.php'; }

$PERMISSIONS = [
    PERFIL_SUPER_ADMIN => ['*'],
    PERFIL_ADMIN_RH => ['dashboard','funcionarios','usuarios','departamentos','cargos','presenca','ferias','folha','avaliacao','recrutamento','documentos','notificacoes','auditoria','relatorios','perfil'],
    PERFIL_RH => ['dashboard','funcionarios','departamentos','cargos','presenca','ferias','folha','avaliacao','recrutamento','documentos','notificacoes','relatorios','perfil'],
    PERFIL_GESTOR => ['dashboard','funcionarios','presenca','ferias','avaliacao','documentos','notificacoes','relatorios','perfil'],
    PERFIL_FUNCIONARIO => ['dashboard','presenca','ferias','folha','avaliacao','documentos','notificacoes','perfil'],
    PERFIL_AUDITOR => ['dashboard','auditoria','relatorios','perfil'],
];

function canAccessModule(string $module, ?string $perfil = null): bool {
    global $PERMISSIONS;
    $perfil = $perfil ?: (function_exists('usuarioAtualPerfil') ? usuarioAtualPerfil() : null);
    return $perfil && isset($PERMISSIONS[$perfil]) && (in_array('*', $PERMISSIONS[$perfil], true) || in_array($module, $PERMISSIONS[$perfil], true));
}

function requireModuleAccess(string $module): void {
    if (!canAccessModule($module)) { header('Location: ' . BASE_URL . '/modules/auth/acesso_negado.php'); exit; }
}
