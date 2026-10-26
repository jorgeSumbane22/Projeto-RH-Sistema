<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Sistema de RH') ?> · Sistema de RH</title>
    <!-- Aplica o tema salvo ANTES do CSS carregar, evitando "flash" de tela clara -->
    <script>
        (function () {
            var tema = localStorage.getItem('rh_tema') || 'light';
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center position-relative" style="min-height:100vh;">
    <button id="btnTemaPublico" class="btn btn-sm btn-light js-toggle-tema position-absolute top-0 end-0 m-3" title="Alternar tema">
        <i class="bi bi-moon-stars"></i>
    </button>
