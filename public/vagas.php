<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$vagaModel = new Vaga();
$vagas = $vagaModel->listar('aberta');
$pageTitle = 'Vagas Abertas';

// Buscar departamentos para filtro
$departamentoModel = new Departamento();
$departamentos = $departamentoModel->listar(true);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vagas Abertas · Sistema de RH</title>
    <script>
        (function() {
            var tema = localStorage.getItem('rh_tema') || 'light';
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        /* ============================================================
           VAGAS PÚBLICAS - DESIGN PREMIUM
           ============================================================ */
        
        :root {
            --rh-primary: #155e75;
            --rh-primary-light: #0e7490;
            --rh-accent: #14b8a6;
            --rh-radius: 16px;
            --rh-shadow: 0 8px 32px rgba(15,41,64,0.08);
            --rh-shadow-hover: 0 16px 56px rgba(15,41,64,0.14);
        }
        
        body {
            background: var(--rh-bg);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        /* ===== NAVBAR ===== */
        .navbar-premium {
            background: linear-gradient(135deg, var(--rh-primary) 0%, #0B3242 100%);
            padding: 0.8rem 0;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .navbar-premium .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -0.3px;
        }
        
        .navbar-premium .navbar-brand i {
            color: var(--rh-accent);
        }
        
        .navbar-premium .btn-outline-light {
            border-color: rgba(255,255,255,0.25);
            color: rgba(255,255,255,0.85);
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
        }
        
        .navbar-premium .btn-outline-light:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.4);
            color: #fff;
            transform: translateY(-1px);
        }
        
        /* ===== HERO ===== */
        .hero-section {
            background: linear-gradient(135deg, var(--rh-primary) 0%, #0B3242 100%);
            padding: 3rem 0 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(ellipse, rgba(20,184,166,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .hero-section .container {
            position: relative;
            z-index: 1;
        }
        
        .hero-section h1 {
            color: #fff;
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
        }
        
        .hero-section h1 i {
            color: var(--rh-accent);
        }
        
        .hero-section p {
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        .hero-section .badge-count {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* ===== FILTER ===== */
        .filter-section {
            margin-bottom: 2rem;
        }
        
        .filter-section .filter-card {
            background: var(--rh-card-bg);
            border: 1px solid var(--rh-border);
            border-radius: var(--rh-radius);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--rh-shadow);
            transition: all 0.3s ease;
        }
        
        .filter-section .filter-card:hover {
            box-shadow: var(--rh-shadow-hover);
        }
        
        .filter-section .form-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--rh-text-muted);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .filter-section .form-control,
        .filter-section .form-select {
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 0.45rem 0.875rem;
            background: var(--bs-tertiary-bg);
            border: 1.5px solid var(--rh-border);
            transition: all 0.2s ease;
            color: var(--rh-text-primary);
        }
        
        .filter-section .form-control:focus,
        .filter-section .form-select:focus {
            background: var(--rh-card-bg);
            border-color: var(--rh-primary);
            box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
        }
        
        .filter-section .btn-filter {
            border-radius: 10px;
            padding: 0.45rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
            border: none;
            color: #fff;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .filter-section .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(21,94,117,0.25);
        }
        
        /* ===== VAGA CARD ===== */
        .vaga-card-premium {
            background: var(--rh-card-bg);
            border: 1px solid var(--rh-border);
            border-radius: var(--rh-radius);
            padding: 1.5rem;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: var(--rh-shadow);
        }
        
        .vaga-card-premium:hover {
            transform: translateY(-6px);
            box-shadow: var(--rh-shadow-hover);
            border-color: var(--rh-primary);
        }
        
        .vaga-card-premium .vaga-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .vaga-card-premium .vaga-header h6 {
            font-weight: 700;
            font-size: 1rem;
            color: var(--rh-text-primary);
            margin: 0;
            flex: 1;
            margin-right: 0.5rem;
        }
        
        .vaga-card-premium .vaga-header .badge-departamento {
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            font-size: 0.6rem;
            font-weight: 600;
            background: rgba(20,184,166,0.12);
            color: var(--rh-accent);
            white-space: nowrap;
        }
        
        .vaga-card-premium .vaga-meta {
            font-size: 0.75rem;
            color: var(--rh-text-muted);
            margin-bottom: 0.75rem;
        }
        
        .vaga-card-premium .vaga-meta i {
            margin-right: 4px;
        }
        
        .vaga-card-premium .vaga-descricao {
            font-size: 0.82rem;
            color: var(--rh-text-muted);
            line-height: 1.5;
            flex: 1;
            margin-bottom: 1rem;
        }
        
        .vaga-card-premium .vaga-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--rh-border);
        }
        
        .vaga-card-premium .vaga-footer .btn-candidatar {
            border-radius: 10px;
            padding: 0.4rem 1.2rem;
            font-weight: 600;
            font-size: 0.8rem;
            background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
            border: none;
            color: #fff;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .vaga-card-premium .vaga-footer .btn-candidatar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(21,94,117,0.3);
            color: #fff;
        }
        
        .vaga-card-premium .vaga-footer .btn-candidatar i {
            margin-right: 4px;
        }
        
        .vaga-card-premium .vaga-footer .vaga-status {
            font-size: 0.65rem;
            color: var(--rh-accent);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state-premium {
            padding: 4rem 1rem;
            text-align: center;
            grid-column: 1 / -1;
        }
        
        .empty-state-premium .empty-icon {
            font-size: 4rem;
            color: var(--rh-border);
            display: block;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state-premium h5 {
            font-weight: 600;
            color: var(--rh-text-primary);
            margin-bottom: 0.25rem;
        }
        
        .empty-state-premium p {
            color: var(--rh-text-muted);
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        /* ===== FOOTER ===== */
        .footer-premium {
            background: var(--rh-card-bg);
            border-top: 1px solid var(--rh-border);
            padding: 1.5rem 0;
            margin-top: 3rem;
            color: var(--rh-text-muted);
            font-size: 0.8rem;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 767.98px) {
            .hero-section h1 {
                font-size: 1.6rem;
            }
            .hero-section {
                padding: 2rem 0 1.5rem;
            }
            .navbar-premium .navbar-brand {
                font-size: 1rem;
            }
            .filter-section .btn-filter {
                width: 100%;
            }
            .vaga-card-premium {
                padding: 1.25rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-section h1 {
                font-size: 1.3rem;
            }
            .hero-section p {
                font-size: 0.85rem;
            }
            .navbar-premium .btn-outline-light {
                font-size: 0.7rem;
                padding: 0.25rem 0.6rem;
            }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar-premium">
    <div class="container">
        <span class="navbar-brand">
            <i class="bi bi-people-fill"></i> Sistema de RH — Vagas Abertas
        </span>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-outline-light js-toggle-tema" title="Alternar tema">
                <i class="bi bi-moon-stars"></i>
            </button>
            <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn btn-outline-light">
                <i class="bi bi-box-arrow-in-right"></i> Área do colaborador
            </a>
        </div>
    </div>
</nav>

<!-- ===== HERO ===== -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1>
                    <i class="bi bi-briefcase"></i> Oportunidades
                </h1>
                <p>Encontre a vaga perfeita para o seu perfil e candidate-se agora mesmo.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge-count">
                    <i class="bi bi-list me-1"></i> <?= count($vagas) ?> vagas abertas
                </span>
            </div>
        </div>
    </div>
</section>

<!-- ===== FILTER ===== -->
<div class="container filter-section">
    <div class="filter-card">
        <form method="GET" id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-search"></i> Buscar vaga</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Ex: Desenvolvedor, Analista..." value="<?= e($_GET['busca'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-building"></i> Departamento</label>
                <select id="deptFilter" class="form-select">
                    <option value="">Todos os departamentos</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($_GET['departamento'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                            <?= e($d['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" id="applyFilter" class="btn-filter">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
            </div>
            <div class="col-md-2">
                <button type="reset" id="clearFilter" class="btn btn-outline-secondary w-100" style="border-radius:10px;padding:0.45rem 1.5rem;font-weight:600;font-size:0.85rem;border-color:var(--rh-border);color:var(--rh-text-muted);">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Limpar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== VAGAS ===== -->
<div class="container">
    <div class="row g-3">
        <?php if (empty($vagas)): ?>
            <div class="col-12">
                <div class="empty-state-premium">
                    <span class="empty-icon"><i class="bi bi-briefcase-slash"></i></span>
                    <h5>Nenhuma vaga disponível</h5>
                    <p>Não há vagas abertas no momento. Volte em breve para novas oportunidades.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php foreach ($vagas as $v): ?>
            <div class="col-md-6 col-lg-4 vaga-item" 
                 data-departamento="<?= $v['departamento_id'] ?>" 
                 data-titulo="<?= e(strtolower($v['titulo'])) ?>">
                <div class="vaga-card-premium">
                    <div class="vaga-header">
                        <h6><?= e($v['titulo']) ?></h6>
                        <span class="badge-departamento">
                            <i class="bi bi-building"></i> <?= e($v['departamento_nome']) ?>
                        </span>
                    </div>
                    <div class="vaga-meta">
                        <i class="bi bi-clock"></i> Publicada em <?= formatarData($v['data_publicacao']) ?>
                    </div>
                    <div class="vaga-descricao">
                        <?= e(truncarTexto($v['descricao'] ?? '', 140)) ?>
                    </div>
                    <div class="vaga-footer">
                        <a href="candidatar.php?vaga_id=<?= $v['id'] ?>" class="btn-candidatar">
                            <i class="bi bi-send"></i> Candidatar-se
                        </a>
                        <span class="vaga-status">
                            <i class="bi bi-circle-fill" style="font-size:0.4rem;"></i> Aberta
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="footer-premium">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <span>© <?= date('Y') ?> Sistema de RH — Todos os direitos reservados</span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted small">
                    <i class="bi bi-shield-check"></i> Portal de candidaturas
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/scripts.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const deptFilter = document.getElementById('deptFilter');
    const applyBtn = document.getElementById('applyFilter');
    const clearBtn = document.getElementById('clearFilter');
    const items = document.querySelectorAll('.vaga-item');

    function filterVagas() {
        const search = searchInput.value.toLowerCase().trim();
        const dept = deptFilter.value;

        items.forEach(function(item) {
            const titulo = item.dataset.titulo || '';
            const departamento = item.dataset.departamento || '';
            
            const matchSearch = search === '' || titulo.includes(search);
            const matchDept = dept === '' || departamento === dept;
            
            if (matchSearch && matchDept) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    applyBtn.addEventListener('click', filterVagas);
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') filterVagas();
    });
    deptFilter.addEventListener('change', filterVagas);

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        deptFilter.value = '';
        filterVagas();
    });

    // Inicializa filtro
    filterVagas();
});
</script>

</body>
</html>