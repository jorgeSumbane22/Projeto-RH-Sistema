<?php
/** Testes da tabela progressiva de descontos (config/impostos.php). */

teste('calcularDescontoProgressivo não desconta nada de salário zero', function () {
    afirmarIgual(0.0, calcularDescontoProgressivo(0));
});

teste('calcularDescontoProgressivo aplica só a primeira faixa para salários baixos', function () {
    // 1000 * 7.5% = 75.00 (está inteiramente dentro da primeira faixa, até 1518.00)
    afirmarProximo(75.00, calcularDescontoProgressivo(1000));
});

teste('calcularDescontoProgressivo é progressivo (aumenta menos que proporcionalmente no início)', function () {
    $descontoBaixo = calcularDescontoProgressivo(1000);
    $descontoAlto = calcularDescontoProgressivo(10000);
    // a alíquota efetiva (desconto/salário) deve ser maior para salários mais altos
    afirmarVerdadeiro(($descontoAlto / 10000) > ($descontoBaixo / 1000));
});

teste('calcularDescontoProgressivo nunca desconta mais que o próprio salário', function () {
    $desconto = calcularDescontoProgressivo(5000);
    afirmarVerdadeiro($desconto < 5000);
});
