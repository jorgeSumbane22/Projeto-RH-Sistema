<?php
/**
 * paginacao.php
 * Partial reutilizável. Espera a variável $paginacao (retorno de paginar())
 * e usa a query string atual (exceto "pagina") para montar os links.
 */
if (($paginacao['total_paginas'] ?? 1) <= 1) {
    return;
}
$paramsAtuais = $_GET;
unset($paramsAtuais['pagina']);

function urlPagina(int $pagina, array $paramsAtuais): string
{
    $paramsAtuais['pagina'] = $pagina;
    return '?' . http_build_query($paramsAtuais);
}
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <li class="page-item <?= $paginacao['pagina_atual'] <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e(urlPagina($paginacao['pagina_atual'] - 1, $paramsAtuais)) ?>">Anterior</a>
        </li>
        <?php
        $inicio = max(1, $paginacao['pagina_atual'] - 2);
        $fim = min($paginacao['total_paginas'], $paginacao['pagina_atual'] + 2);
        if ($inicio > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= e(urlPagina(1, $paramsAtuais)) ?>">1</a></li>
            <?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $inicio; $p <= $fim; $p++): ?>
            <li class="page-item <?= $p === $paginacao['pagina_atual'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= e(urlPagina($p, $paramsAtuais)) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($fim < $paginacao['total_paginas']): ?>
            <?php if ($fim < $paginacao['total_paginas'] - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= e(urlPagina($paginacao['total_paginas'], $paramsAtuais)) ?>"><?= $paginacao['total_paginas'] ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $paginacao['pagina_atual'] >= $paginacao['total_paginas'] ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e(urlPagina($paginacao['pagina_atual'] + 1, $paramsAtuais)) ?>">Próxima</a>
        </li>
    </ul>
    <p class="text-center text-muted small mt-2"><?= $paginacao['total'] ?> registro(s) no total</p>
</nav>
