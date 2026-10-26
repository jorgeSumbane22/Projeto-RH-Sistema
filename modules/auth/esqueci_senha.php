<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (estaLogado()) {
    redirecionar('/modules/dashboard/index.php');
}

$mensagem = null;
$linkDev = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $email = trim($_POST['email'] ?? '');

    $usuarioModel = new Usuario();
    $usuario = $usuarioModel->buscarPorEmail($email);

    $mensagem = 'Se o e-mail informado existir em nossa base, um link de redefinição foi gerado.';

    if ($usuario && $usuario['ativo']) {
        $resetModel = new PasswordReset();
        $resetModel->invalidarTokensAnteriores((int) $usuario['id']);
        $token = $resetModel->gerarToken((int) $usuario['id']);
        registrarAuditoria('senha_reset_solicitado', "Usuário {$usuario['id']}");

        if (APP_ENV === 'development') {
            $linkDev = BASE_URL . '/modules/auth/redefinir_senha.php?token=' . $token;
        }
    }
}

require __DIR__ . '/../../includes/head_publico.php';
?>

<style>
    /* ============================================================
       ESQUECI SENHA - DESIGN PREMIUM
       ============================================================ */

    .forgot-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0a2635 0%, #10495B 40%, #1C6E8C 100%);
        padding: 1.5rem;
        position: relative;
    }

    .forgot-wrapper::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 600px;
        height: 600px;
        background: radial-gradient(ellipse, rgba(20, 184, 166, 0.06) 0%, transparent 70%);
        border-radius: 50%;
        animation: float-bg 20s ease-in-out infinite;
    }

    .forgot-wrapper::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(ellipse, rgba(245, 158, 11, 0.04) 0%, transparent 70%);
        border-radius: 50%;
        animation: float-bg 15s ease-in-out infinite reverse;
    }

    @keyframes float-bg {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
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

    .forgot-card {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 420px;
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 20px;
        box-shadow: 0 32px 64px -12px rgba(0,0,0,0.4);
        animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        overflow: hidden;
    }

    .forgot-card .card-body {
        padding: 2.5rem 2rem;
    }

    /* ----- HEADER ----- */
    .forgot-header {
        text-align: center;
        margin-bottom: 1.75rem;
    }

    .forgot-header .icon-wrapper {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #fff;
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.25);
        margin-bottom: 0.75rem;
        transition: transform 0.3s ease;
    }

    .forgot-header .icon-wrapper:hover {
        transform: scale(1.05);
    }

    .forgot-header h4 {
        color: #fff;
        font-weight: 700;
        font-size: 1.3rem;
        margin: 0;
        letter-spacing: -0.3px;
    }

    .forgot-header p {
        color: rgba(255,255,255,0.5);
        font-size: 0.85rem;
        margin: 0.2rem 0 0;
    }

    /* ----- FORMULÁRIO ----- */
    .forgot-form .form-group {
        margin-bottom: 1.25rem;
    }

    .forgot-form .form-label {
        color: rgba(255,255,255,0.6);
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.3rem;
        display: block;
    }

    .forgot-form .form-label i {
        margin-right: 0.3rem;
        opacity: 0.6;
    }

    .forgot-form .input-group-custom {
        position: relative;
    }

    .forgot-form .form-control {
        background: rgba(255,255,255,0.05);
        border: 1.5px solid rgba(255,255,255,0.06);
        border-radius: 12px;
        padding: 0.7rem 1rem;
        padding-right: 2.8rem;
        color: #fff;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        height: 48px;
        width: 100%;
    }

    .forgot-form .form-control::placeholder {
        color: rgba(255,255,255,0.2);
        font-size: 0.85rem;
    }

    .forgot-form .form-control:focus {
        background: rgba(255,255,255,0.07);
        border-color: rgba(245, 158, 11, 0.3);
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.06);
        color: #fff;
        outline: none;
    }

    .forgot-form .form-control:focus ~ .input-icon {
        color: #f59e0b;
    }

    .forgot-form .input-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255,255,255,0.15);
        font-size: 1.1rem;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    /* ----- BOTÃO ----- */
    .btn-forgot {
        width: 100%;
        padding: 0.75rem;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        color: #fff;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2);
        transition: all 0.3s ease;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .btn-forgot:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 28px rgba(245, 158, 11, 0.3);
    }

    .btn-forgot:active {
        transform: translateY(0px);
    }

    .btn-forgot i {
        font-size: 1.1rem;
    }

    /* ----- ALERTAS ----- */
    .alert-premium {
        padding: 0.6rem 1rem;
        border-radius: 12px;
        border: 1px solid rgba(16, 185, 129, 0.15);
        background: rgba(16, 185, 129, 0.06);
        color: rgba(255,255,255,0.8);
        font-size: 0.8rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        backdrop-filter: blur(8px);
    }

    .alert-premium i {
        font-size: 1rem;
        color: #10b981;
        margin-top: 0.1rem;
        flex-shrink: 0;
    }

    .alert-premium.warning {
        border-color: rgba(245, 158, 11, 0.15);
        background: rgba(245, 158, 11, 0.06);
    }

    .alert-premium.warning i {
        color: #f59e0b;
    }

    .alert-premium.warning a {
        color: #f59e0b;
        word-break: break-all;
        text-decoration: underline;
        transition: color 0.3s ease;
    }

    .alert-premium.warning a:hover {
        color: #fbbf24;
    }

    .alert-premium .alert-content {
        flex: 1;
        line-height: 1.5;
    }

    /* ----- LINK VOLTAR ----- */
    .forgot-footer {
        display: flex;
        justify-content: center;
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255,255,255,0.04);
    }

    .forgot-footer a {
        color: rgba(255,255,255,0.35);
        font-size: 0.75rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .forgot-footer a:hover {
        color: #f59e0b;
        text-decoration: none;
    }

    /* ----- BOTÃO OUTLINE ----- */
    .btn-outline-premium {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.06);
        color: rgba(255,255,255,0.6);
        border-radius: 12px;
        padding: 0.7rem;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        width: 100%;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
        margin-top: 0.75rem;
    }

    .btn-outline-premium:hover {
        background: rgba(255,255,255,0.04);
        border-color: rgba(255,255,255,0.12);
        color: #fff;
        text-decoration: none;
    }

    /* ----- RESPONSIVE ----- */
    @media (max-width: 575.98px) {
        .forgot-card .card-body {
            padding: 1.75rem 1.25rem;
        }

        .forgot-header .icon-wrapper {
            width: 54px;
            height: 54px;
            font-size: 1.6rem;
        }

        .forgot-header h4 {
            font-size: 1.15rem;
        }

        .forgot-form .form-control {
            height: 44px;
            padding: 0.6rem 0.9rem;
            padding-right: 2.6rem;
            font-size: 0.9rem;
        }

        .btn-forgot {
            height: 44px;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 400px) {
        .forgot-card .card-body {
            padding: 1.25rem 0.9rem;
        }
    }
</style>

<!-- ============================================================
     ESQUECI MINHA SENHA
     ============================================================ -->
<div class="forgot-wrapper">
    <div class="forgot-card">
        <div class="card-body">
            <!-- Header -->
            <div class="forgot-header">
                <div class="icon-wrapper">
                    <i class="bi bi-key-fill"></i>
                </div>
                <h4>Esqueci minha senha</h4>
                <p>Informe seu e-mail cadastrado</p>
            </div>

            <!-- Mensagem de sucesso -->
            <?php if ($mensagem): ?>
                <div class="alert-premium">
                    <i class="bi bi-check-circle-fill"></i>
                    <div class="alert-content"><?= e($mensagem) ?></div>
                </div>
                
                <?php if ($linkDev): ?>
                    <div class="alert-premium warning">
                        <i class="bi bi-code-square"></i>
                        <div class="alert-content">
                            <strong>Modo desenvolvimento</strong><br>
                            <a href="<?= e($linkDev) ?>"><?= e($linkDev) ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <a href="login.php" class="btn-outline-premium">
                    <i class="bi bi-arrow-left"></i> Voltar ao login
                </a>
            <?php else: ?>
                <!-- Formulário -->
                <form method="POST" class="forgot-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-envelope"></i> E-mail
                        </label>
                        <div class="input-group-custom">
                            <input type="email" name="email" class="form-control" required autofocus 
                                   placeholder="seu@email.com" value="<?= e($_POST['email'] ?? '') ?>">
                            <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        </div>
                    </div>

                    <button type="submit" class="btn-forgot">
                        <i class="bi bi-send"></i> Enviar link de redefinição
                    </button>
                </form>

                <!-- Link voltar -->
                <div class="forgot-footer">
                    <a href="login.php">
                        <i class="bi bi-arrow-left"></i> Voltar ao login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/foot_publico.php'; ?>