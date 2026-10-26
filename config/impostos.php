<?php
/**
 * impostos.php
 * Tabela progressiva de desconto sobre o salário bruto, usada para pré-preencher
 * o campo "Descontos" ao gerar a folha de pagamento.
 *
 * IMPORTANTE: os valores abaixo são ILUSTRATIVOS (inspirados em uma tabela de
 * INSS/IRRF simplificada) e devem ser ajustados pelo contador/RH da empresa
 * conforme a legislação vigente no país/ano de uso. O valor calculado aqui é
 * apenas um ponto de partida — pode ser editado manualmente por funcionário em
 * "Folha de Pagamento" → "Ajustar" antes de confirmar o pagamento.
 */

/**
 * Calcula o desconto total (impostos/contribuições) sobre um salário bruto,
 * aplicando uma tabela progressiva por faixa (cada faixa tributa só a
 * parte do salário que cai dentro dela, como no IRRF/INSS reais).
 */
function calcularDescontoProgressivo(float $salarioBruto): float
{
    // Faixas: [limite_superior, alíquota]. A última faixa (PHP_FLOAT_MAX) cobre o restante.
    $faixas = [
        [1518.00, 0.075],
        [2793.88, 0.09],
        [4190.83, 0.12],
        [8157.41, 0.14],
        [PHP_FLOAT_MAX, 0.14], // acima do teto, mantém a última alíquota (simplificação)
    ];

    $desconto = 0.0;
    $faixaAnterior = 0.0;

    foreach ($faixas as [$limite, $aliquota]) {
        if ($salarioBruto <= $faixaAnterior) {
            break;
        }
        $baseDaFaixa = min($salarioBruto, $limite) - $faixaAnterior;
        $desconto += $baseDaFaixa * $aliquota;
        $faixaAnterior = $limite;
    }

    return round($desconto, 2);
}
