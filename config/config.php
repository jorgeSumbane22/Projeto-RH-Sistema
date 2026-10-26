<?php
/**
 * config.php
 * Configurações globais da aplicação: caminhos, sessão e constantes.
 * Este é o PRIMEIRO arquivo carregado por qualquer página do sistema.
 */

// ---------------------------------------------------------------------
// Configuração de sessão segura (deve ocorrer ANTES de session_start())
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    session_start();
}

// ---------------------------------------------------------------------
// Exibição de erros (DESATIVE em produção)
// ---------------------------------------------------------------------
define('APP_ENV', getenv('RH_APP_ENV') ?: 'development'); // 'development' | 'production'

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ---------------------------------------------------------------------
// Caminhos e URLs base
// ---------------------------------------------------------------------
define('BASE_PATH', dirname(__DIR__));                 // caminho absoluto no servidor
define('BASE_URL', '/rh-sistema');                      // ajuste se a pasta tiver outro nome no htdocs

define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('UPLOADS_DOCUMENTOS', UPLOADS_PATH . '/documentos');
define('UPLOADS_CURRICULOS', UPLOADS_PATH . '/curriculos');
define('UPLOADS_FOTOS', UPLOADS_PATH . '/fotos');

// ---------------------------------------------------------------------
// Constantes de negócio
// ---------------------------------------------------------------------
define('DIAS_FERIAS_ANUAL', 30);           // dias de férias por ano trabalhado
define('LIMITE_UPLOAD_MB', 5);              // tamanho máximo de upload em MB
define('EXTENSOES_PERMITIDAS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// ============================================================
// PERFIS DE ACESSO DO SISTEMA (CORRIGIDO)
// ============================================================
define('PERFIL_SUPER_ADMIN', 'super_admin');
define('PERFIL_ADMIN_RH', 'administrador_rh');
define('PERFIL_RH', 'rh');                    // CORRIGIDO: agora é um perfil real
define('PERFIL_GESTOR', 'gestor');
define('PERFIL_FUNCIONARIO', 'funcionario');
define('PERFIL_AUDITOR', 'auditor');

// Alias para compatibilidade com módulos existentes
define('PERFIL_ADMIN', PERFIL_ADMIN_RH);

// ============================================================
// PERFIS DO SISTEMA - LISTA COMPLETA
// ============================================================
define('PERFIS_SISTEMA', [
    PERFIL_SUPER_ADMIN,
    PERFIL_ADMIN_RH,
    PERFIL_RH,           // <--- AGORA INCLUÍDO
    PERFIL_GESTOR,
    PERFIL_FUNCIONARIO,
    PERFIL_AUDITOR,
]);

// ============================================================
// NOMES AMIGÁVEIS DOS PERFIS
// ============================================================
define('NOMES_PERFIS', [
    PERFIL_SUPER_ADMIN => 'Super Admin',
    PERFIL_ADMIN_RH => 'Administrador RH',
    PERFIL_RH => 'Recursos Humanos',     // <--- ADICIONADO
    PERFIL_GESTOR => 'Gestor',
    PERFIL_FUNCIONARIO => 'Funcionário',
    PERFIL_AUDITOR => 'Auditor',
]);

date_default_timezone_set('America/Sao_Paulo');