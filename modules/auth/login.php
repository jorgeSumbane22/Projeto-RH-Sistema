<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Se já estiver logado, vai direto para o dashboard
if (estaLogado()) {
    redirecionar('/modules/dashboard/index.php');
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } else {
        $usuarioModel = new Usuario();

        if ($usuarioModel->estaBloqueado($email)) {
            $minutos = $usuarioModel->minutosRestantesBloqueio($email);
            $erro = "Conta temporariamente bloqueada por excesso de tentativas. Tente novamente em {$minutos} minuto(s).";
        } else {
            $usuario = $usuarioModel->autenticar($email, $senha);

            if ($usuario) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];
                $_SESSION['usuario_funcionario_id'] = $usuario['funcionario_id'];
                $_SESSION['usuario_tema'] = $usuario['tema_preferido'];

                $usuarioModel->resetarTentativas($usuario['id']);
                $usuarioModel->atualizarUltimoLogin($usuario['id']);
                registrarAuditoria('login', 'Login bem-sucedido');

                redirecionar('/modules/dashboard/index.php');
            } else {
                $usuarioModel->registrarTentativaFalha($email);
                $erro = 'E-mail ou senha inválidos.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar · Sistema de RH</title>
    <script>
        (function () {
            document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('rh_tema') || 'light');
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        /* ============================================================
           LOGIN PREMIUM - COM BACKGROUND DE GRUPO DE PESSOAS
           ============================================================ */

        /* ----- BACKGROUND COM IMAGEM ----- */
        .login-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(10, 38, 53, 0.85) 0%, rgba(16, 73, 91, 0.75) 40%, rgba(28, 110, 140, 0.65) 100%),
                        url('https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            overflow: hidden;
            padding: 1.5rem;
        }

        /* ----- OVERLAY GRADIENTE DINÂMICO ----- */
        .login-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(10, 38, 53, 0.7) 0%, rgba(16, 73, 91, 0.5) 50%, rgba(20, 184, 166, 0.2) 100%);
            z-index: 1;
            pointer-events: none;
        }

        /* ----- ORNAMENTOS DECORATIVOS ----- */
        .login-bg .ornament {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .login-bg .ornament-1 {
            top: 10%;
            right: 5%;
            width: 300px;
            height: 300px;
            background: radial-gradient(ellipse, rgba(20, 184, 166, 0.06) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .login-bg .ornament-2 {
            bottom: 15%;
            left: 5%;
            width: 250px;
            height: 250px;
            background: radial-gradient(ellipse, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-15px) scale(1.05); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* ----- CARD DE LOGIN PREMIUM ----- */
        .login-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 24px;
            box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.04);
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 30% 20%, rgba(20, 184, 166, 0.03) 0%, transparent 60%);
            pointer-events: none;
        }

        .login-card .card-body {
            padding: 2.5rem 2.5rem 2rem;
            position: relative;
            z-index: 1;
        }

        /* ----- HEADER ----- */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .logo-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #fff;
            box-shadow: 0 8px 32px rgba(20, 184, 166, 0.25);
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }

        .login-header .logo-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 40px rgba(20, 184, 166, 0.35);
        }

        .login-header h4 {
            color: #fff;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            margin: 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .login-header .subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin: 0.2rem 0 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* ----- FORMULÁRIO ----- */
        .login-form .form-group {
            margin-bottom: 1.25rem;
        }

        .login-form .form-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .login-form .form-label i {
            font-size: 0.8rem;
            opacity: 0.6;
        }

        .login-form .input-group-custom {
            position: relative;
        }

        .login-form .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 0.7rem 1rem;
            padding-right: 2.8rem;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            height: 50px;
            width: 100%;
            backdrop-filter: blur(8px);
        }

        .login-form .form-control::placeholder {
            color: rgba(255, 255, 255, 0.2);
            font-size: 0.85rem;
        }

        .login-form .form-control:focus {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(20, 184, 166, 0.3);
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.06);
            color: #fff;
            outline: none;
        }

        .login-form .form-control:focus ~ .input-icon {
            color: #14b8a6;
        }

        .login-form .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.15);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        /* ----- BOTÃO TOGGLE SENHA ----- */
        .login-form .input-icon.toggle-password {
            pointer-events: auto;
            cursor: pointer;
            z-index: 3;
        }

        .login-form .input-icon.toggle-password:hover {
            color: #14b8a6 !important;
        }

        /* ----- BOTÃO ----- */
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            box-shadow: 0 4px 20px rgba(20, 184, 166, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.08), transparent);
            animation: shimmer 3s infinite;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(20, 184, 166, 0.3);
        }

        .btn-login:active {
            transform: translateY(0px);
        }

        .btn-login i {
            font-size: 1.1rem;
        }

        /* ----- FOOTER LINKS ----- */
        .login-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .login-footer a {
            color: rgba(255, 255, 255, 0.35);
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .login-footer a:hover {
            color: #14b8a6;
            text-decoration: none;
        }

        /* ----- ALERTA ----- */
        .alert-premium {
            padding: 0.6rem 1rem;
            border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, 0.15);
            background: rgba(239, 68, 68, 0.06);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.8rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            backdrop-filter: blur(8px);
        }

        .alert-premium i {
            font-size: 1.1rem;
            color: #ef4444;
        }

        /* ----- BOTÃO TEMA ----- */
        .btn-theme-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 10;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.04);
            color: rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-theme-toggle:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            transform: rotate(30deg);
        }

        .btn-theme-toggle i {
            font-size: 1rem;
        }

        /* ----- DARK MODE ----- */
        [data-bs-theme="dark"] .login-bg {
            background: linear-gradient(135deg, rgba(10, 38, 53, 0.9) 0%, rgba(16, 73, 91, 0.8) 40%, rgba(28, 110, 140, 0.7) 100%),
                        url('https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        [data-bs-theme="dark"] .login-card {
            background: rgba(13, 23, 32, 0.7);
            backdrop-filter: blur(24px);
            border-color: rgba(255, 255, 255, 0.03);
        }

        [data-bs-theme="dark"] .login-form .form-control {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.03);
        }

        [data-bs-theme="dark"] .login-form .form-control:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(20, 184, 166, 0.25);
        }

        [data-bs-theme="dark"] .login-header h4 {
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        /* ----- RESPONSIVE ----- */
        @media (max-width: 575.98px) {
            .login-card .card-body {
                padding: 1.75rem 1.5rem 1.5rem;
            }

            .login-header .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
            }

            .login-header h4 {
                font-size: 1.2rem;
            }

            .login-form .form-control {
                height: 44px;
                padding: 0.6rem 0.9rem;
                padding-right: 2.6rem;
                font-size: 0.9rem;
            }

            .btn-login {
                height: 44px;
                font-size: 0.85rem;
            }

            .login-footer {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .btn-theme-toggle {
                top: 1rem;
                right: 1rem;
                width: 36px;
                height: 36px;
            }

            .login-bg {
                background-position: 30% center;
            }
        }

        @media (max-width: 400px) {
            .login-card .card-body {
                padding: 1.25rem 1rem 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-bg">
        <!-- Ornamentos Decorativos -->
        <div class="ornament ornament-1"></div>
        <div class="ornament ornament-2"></div>

        <!-- Botão Tema -->
        <button type="button" class="btn-theme-toggle js-toggle-tema" title="Alternar tema">
            <i class="bi bi-moon-stars"></i>
        </button>

        <!-- Card de Login -->
        <div class="login-card">
            <div class="card-body">
                <!-- Header -->
                <div class="login-header">
                    <div class="logo-icon"><i class="bi bi-people-fill"></i></div>
                    <h4>Sistema de RH</h4>
                    <p class="subtitle">Acesse com suas credenciais</p>
                </div>

                <!-- Mensagem de Erro -->
                <?php if ($erro): ?>
                    <div class="alert-premium">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?= e($erro) ?>
                    </div>
                <?php endif; ?>

                <!-- Formulário -->
                <form method="POST" class="login-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-envelope"></i> E-mail</label>
                        <div class="input-group-custom">
                            <input type="email" name="email" class="form-control" 
                                   placeholder="seu@email.com" required autofocus 
                                   value="<?= e($_POST['email'] ?? '') ?>">
                            <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-lock"></i> Senha</label>
                        <div class="input-group-custom">
                            <input type="password" name="senha" id="senha" class="form-control" 
                                   placeholder="••••••••" required>
                            <span class="input-icon toggle-password" id="toggleSenha">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                </form>

                <!-- Links Rodapé -->
                <div class="login-footer">
                    <a href="esqueci_senha.php"><i class="bi bi-question-circle"></i> Esqueci minha senha</a>
                    <a href="<?= BASE_URL ?>/public/vagas.php"><i class="bi bi-briefcase"></i> Ver vagas</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/scripts.js"></script>
    <script>
        // Toggle mostrar/ocultar senha
        document.getElementById('toggleSenha').addEventListener('click', function () {
            const inputSenha = document.getElementById('senha');
            const icon = this.querySelector('i');
            
            if (inputSenha.type === 'password') {
                inputSenha.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                inputSenha.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    </script>
</body>
</html>