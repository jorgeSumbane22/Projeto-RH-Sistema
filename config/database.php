<?php
/**
 * database.php
 * Define as credenciais de conexão com o MySQL/MariaDB.
 * A classe Database (em /classes/Database.php) usa estas constantes.
 *
 * IMPORTANTE: em um ambiente real, mova estes valores para variáveis de
 * ambiente (.env) fora do controle de versão. Mantemos aqui como
 * constantes simples para facilitar a execução em localhost/XAMPP.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'rh_sistema');
define('DB_USER', 'root');
define('DB_PASS', '');       // no XAMPP padrão, o usuário root não tem senha
define('DB_CHARSET', 'utf8mb4');
