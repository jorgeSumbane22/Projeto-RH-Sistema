<?php
// Incluído diretamente por exigirPerfil() quando o usuário não tem permissão.
if (!defined('BASE_URL')) { exit; }
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado · Sistema de RH</title>
    <script>
        (function () {
            document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('rh_tema') || 'light');
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .access-denied-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a2635 0%, #10495B 40%, #1C6E8C 100%);
            position: relative;
            overflow: hidden;
            padding: 1.5rem;
        }

        .access-denied-bg::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 800px;
            height: 800px;
            background: radial-gradient(ellipse, rgba(239, 68, 68, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        .denied-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 480px;
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            box-shadow: 0 32px 64px -12px rgba(0,0,0,0.4);
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            overflow: hidden;
            text-align: center;
            padding: 3rem 2.5rem;
        }

        .denied-card .status-icon {
            font-size: 5rem;
            color: #ef4444;
            margin-bottom: 1rem;
            animation: pulse-ring 2s ease-in-out infinite;
        }

        .denied-card h1 {
            color: #fff;
            font-weight: 800;
            font-size: 3.5rem;
            margin: 0;
            letter-spacing: -2px;
        }

        .denied-card h1 .text-danger {
            color: #ef4444;
        }

        .denied-card h4 {
            color: #fff;
            font-weight: 600;
            font-size: 1.3rem;
            margin: 0.5rem 0;
        }

        .denied-card p {
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            margin: 0.5rem 0 1.5rem;
        }

        .btn-denied {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.8rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #fff;
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            box-shadow: 0 4px 20px rgba(20, 184, 166, 0.25);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-denied:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(20, 184, 166, 0.35);
            color: #fff;
            text-decoration: none;
        }

        .btn-denied-secondary {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .btn-denied-secondary:hover {
            background: rgba(255,255,255,0.1);
            box-shadow: none;
        }

        .denied-card .divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            margin: 1.5rem auto;
        }

        .btn-theme-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 10;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-theme-toggle:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            transform: rotate(30deg);
        }

        @media (max-width: 575.98px) {
            .denied-card { padding: 2rem 1.5rem; }
            .denied-card .status-icon { font-size: 4rem; }
            .denied-card h1 { font-size: 2.8rem; }
            .denied-card h4 { font-size: 1.1rem; }
            .btn-denied { width: 100%; justify-content: center; }
            .btn-theme-toggle { top: 1rem; right: 1rem; width: 36px; height: 36px; }
        }
    </style>
</head>
<body>
    <div class="access-denied-bg">
        <button type="button" class="btn-theme-toggle js-toggle-tema" title="Alternar tema">
            <i class="bi bi-moon-stars"></i>
        </button>

        <div class="denied-card">
            <div class="status-icon">
                <i class="bi bi-shield-exclamation"></i>
            </div>
            <h1>403</h1>
            <h4>Acesso Negado</h4>
            <p>Você não tem permissão para acessar esta página.</p>

            <div class="divider"></div>

            <div style="display:flex;flex-wrap:wrap;gap:0.75rem;justify-content:center;">
                <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="btn-denied">
                    <i class="bi bi-house-fill"></i> Dashboard
                </a>
                <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="btn-denied btn-denied-secondary">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle tema
        document.querySelector('.js-toggle-tema')?.addEventListener('click', function() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('rh_tema', newTheme);
            this.querySelector('i').className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars';
        });
    </script>
</body>
</html>