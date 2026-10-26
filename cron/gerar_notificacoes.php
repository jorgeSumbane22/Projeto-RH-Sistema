<?php
/**
 * gerar_notificacoes.php
 * Script standalone para ser agendado via cron (Linux/Mac) ou Agendador de
 * Tarefas do Windows (comum no XAMPP), rodando 1x por dia.
 *
 * Exemplo de agendamento:
 *   Linux/Mac (crontab -e):
 *     0 6 * * * /opt/lampp/bin/php /opt/lampp/htdocs/rh-sistema/cron/gerar_notificacoes.php
 *   Windows (Agendador de Tarefas): criar tarefa diária às 06:00 executando
 *     C:\xampp\php\php.exe C:\xampp\htdocs\rh-sistema\cron\gerar_notificacoes.php
 *
 * Observação: o sistema também dispara essa geração automaticamente a cada
 * carregamento de página (ver includes/header.php), como um fallback para
 * quem estiver usando o sistema apenas em localhost sem configurar um cron
 * de verdade. A função internamente já evita duplicar notificações no mesmo
 * dia, então rodar dos dois jeitos ao mesmo tempo é seguro.
 */

// Não inclui includes/bootstrap.php inteiro porque ele espera contexto de
// requisição HTTP (sessão, $_SERVER['REMOTE_ADDR'] etc.). Para CLI, incluímos
// só o necessário diretamente.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/impostos.php';

spl_autoload_register(function (string $classe): void {
    $caminho = __DIR__ . '/../classes/' . $classe . '.php';
    if (is_file($caminho)) {
        require_once $caminho;
    }
});

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Este script só pode ser executado via linha de comando (cron), não pelo navegador.');
}

$inicio = microtime(true);
$logFile = __DIR__ . '/../logs/notificacoes.log';

// Garante que o diretório de logs existe
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function escreverLog($mensagem) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $mensagem\n", FILE_APPEND);
}

escreverLog("Iniciando geração de notificações automáticas...");

try {
    $notificacaoModel = new Notificacao();
    $resultado = $notificacaoModel->gerarNotificacoesAutomaticas();
    
    $duracao = round(microtime(true) - $inicio, 3);
    $mensagem = "Concluído com sucesso em {$duracao}s. " . 
                "Notificações geradas: " . ($resultado['geradas'] ?? 0) . 
                ", Alertas: " . ($resultado['alertas'] ?? 0);
    
    echo '[' . date('Y-m-d H:i:s') . "] $mensagem\n";
    escreverLog($mensagem);
    
    // Verifica se há alertas críticos
    if (($resultado['alertas'] ?? 0) > 0) {
        escreverLog("ALERTA: " . ($resultado['alertas'] ?? 0) . " situações críticas detectadas.");
    }
    
    exit(0);
} catch (Throwable $e) {
    $erro = 'Erro ao gerar notificações: ' . $e->getMessage();
    fwrite(STDERR, $erro . "\n");
    escreverLog("ERRO: " . $erro);
    exit(1);
}