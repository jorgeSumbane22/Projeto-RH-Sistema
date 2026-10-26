<?php
/**
 * Database.php
 *
 * Responsabilidade única: fornecer uma instância PDO reutilizável (padrão
 * Singleton) para toda a aplicação, evitando abrir múltiplas conexões
 * por requisição.
 */

require_once __DIR__ . '/../config/database.php';

class Database
{
    private static ?PDO $instance = null;

    // Construtor privado: impede "new Database()" fora da classe
    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // usa prepared statements nativos (mais seguro)
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);

                // Sincroniza o fuso horário da SESSÃO MySQL com o fuso configurado no PHP
                // (config/config.php → date_default_timezone_set). Sem isso, funções como
                // NOW()/CURRENT_TIMESTAMP no MySQL podem usar um fuso diferente do PHP
                // (tipicamente UTC no servidor de banco), fazendo comparações entre datas
                // calculadas em PHP e datas geradas no MySQL derivarem — por exemplo, um
                // token de redefinição de senha "expirando" antes mesmo de ser usado.
                $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
                self::$instance->exec("SET time_zone = '{$offset}'");
            } catch (PDOException $e) {
                // Nunca exponha credenciais ou detalhes internos ao usuário final
                error_log('Erro de conexão com o banco: ' . $e->getMessage());
                die('Não foi possível conectar ao banco de dados. Verifique se o MySQL está em execução no XAMPP e se o banco "rh_sistema" foi importado (veja database/schema.sql).');
            }
        }

        return self::$instance;
    }
}
