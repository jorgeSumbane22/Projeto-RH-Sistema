<?php
/**
 * framework.php
 * Micro-framework de testes, sem dependências (sem Composer/PHPUnit), coerente
 * com a filosofia "framework-free" do projeto. Suficiente para testar a
 * camada de Model, que é a parte com lógica de negócio mais crítica.
 *
 * Uso em um arquivo de teste (tests/casos/*.php):
 *   teste('descrição do caso', function () {
 *       afirmarIgual(4, 2 + 2);
 *   });
 */

$GLOBALS['__testes'] = [];

function teste(string $nome, callable $fn): void
{
    $GLOBALS['__testes'][] = ['nome' => $nome, 'fn' => $fn];
}

function afirmarIgual($esperado, $atual, string $msg = ''): void
{
    if ($esperado != $atual) {
        throw new Exception(sprintf(
            'Esperado [%s] mas obteve [%s]. %s',
            var_export($esperado, true),
            var_export($atual, true),
            $msg
        ));
    }
}

function afirmarProximo(float $esperado, float $atual, float $tolerancia = 0.01, string $msg = ''): void
{
    if (abs($esperado - $atual) > $tolerancia) {
        throw new Exception("Esperado ~$esperado (±$tolerancia) mas obteve $atual. $msg");
    }
}

function afirmarVerdadeiro($valor, string $msg = ''): void
{
    if (!$valor) {
        throw new Exception('Esperado valor verdadeiro (truthy). ' . $msg);
    }
}

function afirmarNulo($valor, string $msg = ''): void
{
    if ($valor !== null) {
        throw new Exception('Esperado null. ' . $msg);
    }
}
