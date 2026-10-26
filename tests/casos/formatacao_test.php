<?php
/** Testes das funções puras de formatação (includes/functions.php). */

teste('formatarMoeda formata valores em Real corretamente', function () {
    afirmarIgual('R$ 1.234,50', formatarMoeda(1234.50));
    afirmarIgual('R$ 0,00', formatarMoeda(0));
    afirmarIgual('R$ 0,00', formatarMoeda(null));
});

teste('formatarData converte Y-m-d para d/m/Y', function () {
    afirmarIgual('25/12/2026', formatarData('2026-12-25'));
    afirmarIgual('-', formatarData(null));
    afirmarIgual('-', formatarData(''));
});

teste('calcularIdade calcula corretamente a partir da data de nascimento', function () {
    $dataNascimento = date('Y-m-d', strtotime('-30 years -1 day'));
    afirmarIgual(30, calcularIdade($dataNascimento));
    afirmarNulo(calcularIdade(null));
});

teste('e() escapa HTML para prevenir XSS', function () {
    afirmarIgual('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
});

teste('paginar calcula total de páginas corretamente', function () {
    $p = paginar(45, 1, 15);
    afirmarIgual(3, $p['total_paginas']);
    afirmarIgual(1, $p['pagina_atual']);

    // página além do total deve ser ajustada para a última página válida
    $p2 = paginar(45, 99, 15);
    afirmarIgual(3, $p2['pagina_atual']);
});
