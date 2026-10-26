<?php
/**
 * executar.php
 * Executa toda a suíte de testes em tests/casos/*.php.
 * Uso: php tests/executar.php   (a partir da raiz do projeto)
 * Código de saída 0 = tudo passou; 1 = alguma falha (útil em CI/scripts).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/impostos.php';

spl_autoload_register(function (string $classe): void {
    $caminho = __DIR__ . '/../classes/' . $classe . '.php';
    if (is_file($caminho)) {
        require_once $caminho;
    }
});
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/framework.php';

foreach (glob(__DIR__ . '/casos/*.php') as $arquivo) {
    require $arquivo;
}

echo "\n=== Executando suíte de testes do Sistema de RH ===\n\n";

$total = 0;
$falhas = 0;

foreach ($GLOBALS['__testes'] as $t) {
    $total++;
    try {
        ($t['fn'])();
        echo "  [OK]    {$t['nome']}\n";
    } catch (Throwable $e) {
        $falhas++;
        echo "  [FALHA] {$t['nome']}\n          → " . $e->getMessage() . "\n";
    }
}

$passaram = $total - $falhas;
echo "\n---------------------------------------------\n";
echo "{$total} teste(s): {$passaram} passou(aram), {$falhas} falhou(aram).\n";

exit($falhas > 0 ? 1 : 0);
