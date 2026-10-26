<?php
/**
 * bootstrap.php
 * Ponto único de inicialização. Toda página do sistema deve começar com:
 *     require_once __DIR__ . '/../../includes/bootstrap.php';
 * (ajuste o número de "../" conforme a profundidade da pasta do módulo)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/impostos.php';

// Autoload simples: cada classe Model.php em /classes é carregada sob demanda,
// evitando um require manual para cada uma (DRY) e mantendo o baixo acoplamento.
spl_autoload_register(function (string $classe): void {
    $caminho = __DIR__ . '/../classes/' . $classe . '.php';
    if (is_file($caminho)) {
        require_once $caminho;
    }
});

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
