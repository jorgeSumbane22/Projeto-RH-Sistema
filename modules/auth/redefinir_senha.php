<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$resetModel = new PasswordReset();
$dadosToken = $resetModel->validarToken($token);

$erros = [];
$sucesso = false;

if (!$dadosToken) {
    $erros[] = 'Link inválido ou expirado. Solicite uma nova redefinição.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $novaSenha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';

    if (strlen($novaSenha) < 6) {
        $erros[] = 'A senha deve ter ao menos 6 caracteres.';
    } elseif ($novaSenha !== $confirmacao) {
        $erros[] = 'As senhas não coincidem.';
    }

    if (empty($erros)) {
        $usuarioModel = new Usuario();
        $usuarioModel->redefinirSenha((int) $dadosToken['usuario_id'], $novaSenha);
        $resetModel->marcarComoUsado((int) $dadosToken['id']);
        registrarAuditoria('senha_redefinida', "Usuário {$dadosToken['usuario_id']}");
        $sucesso = true;
    }
}

require __DIR__ . '/../../includes/head_publico.php';
?>

<style>
    .reset-bg {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0a2635 0%, #10495B 40%, #1C6E8C 100%);
        position: relative;
        overflow: hidden;
        padding: 1.5rem;
    }

    .reset-bg::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 800px;
        height: 800px;
        background: radial-gradient(ellipse, rgba(20, 184, 166, 0.08) 0%, transparent 70%);
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

    .reset-card {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 420px;
        background: rgba(255,255,255,0.06);
        backdrop-filter: blur(24px);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        box-shadow: 0 32px 64px -12px rgba(0,0,0,0.4);
        animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        overflow: hidden;
    }

    .reset-card .card-body { padding: 2.5rem 2.5rem 2rem; position: relative; z-index: 1; }

    .reset-header { text-align: center; margin-bottom: 2rem; }

    .reset-header .icon {
        width: 72px; height: 72px; border-radius: 20px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 2.2rem; color: #fff;
        box-shadow: 0 8px 32px rgba(139, 92, 246, 0.3);
        margin-bottom: 0.75rem;
    }

    .reset-header h4 { color: #fff; font-weight: 700; font-size: 1.4rem; margin: 0; }
    .reset-header p { color: rgba(255,255,255,0.5); font-size: 0.85rem; margin: 0.2rem 0 0; }

    .reset-form .form-control {
        background: rgba(255,255,255,0.06);
        border: 1.5px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 0.7rem 1rem;
        color: #fff;
        font-size: 0.95rem;
        height: 50px;
        transition: all 0.3s ease;
    }

    .reset-form .form-control::placeholder { color: rgba(255,255,255,0.25); }
    .reset-form .form-control:focus {
        background: rgba(255,255,255,0.08);
        border-color: rgba(139, 92, 246, 0.4);
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.08);
        color: #fff;
        outline: none;
    }

    .btn-reset {
        width: 100%; padding: 0.8rem; border: none; border-radius: 12px;
        font-weight: 700; font-size: 0.95rem; color: #fff;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.25);
        transition: all 0.3s ease;
        height: 50px;
        display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    }

    .btn-reset:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(139, 92, 246, 0.35); }

    .btn-reset-success {
        width: 100%; padding: 0.8rem; border: none; border-radius: 12px;
        font-weight: 700; font-size: 0.95rem; color: #fff;
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.25);
        transition: all 0.3s ease;
        height: 50px;
        display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        text-decoration: none;
    }

    .btn-reset-success:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(16, 185, 129, 0.35); color: #fff; }

    .alert-premium {
        padding: 0.6rem 1rem; border-radius: 12px;
        border: 1px solid rgba(239, 68, 68, 0.2);
        background: rgba(239, 68, 68, 0.08);
        color: rgba(255,255,255,0.85);
        font-size: 0.8rem; margin-bottom: 1.25rem;
        display: flex; align-items: center; gap: 0.6rem;
        backdrop-filter: blur(8px);
    }

    .alert-premium i { font-size: 1.1rem; color: #ef4444; }
    .alert-premium.success { border-color: rgba(16, 185, 129, 0.2); background: rgba(16, 185, 129, 0.08); }
    .alert-premium.success i { color: #10b981; }

    .btn-theme-toggle {
        position: fixed; top: 1.5rem; right: 1.5rem; z-index: 10;
        width: 40px; height: 40px; border-radius: 12px;
        background: rgba(255,255,255,0.06); backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,0.06);
        color: rgba(255,255,255,0.5);
        display: flex; align-items: center; justify-content: center;
        transition: all 0.3s ease; cursor: pointer;
    }

    .btn-theme-toggle:hover { background: rgba(255,255,255,0.1); color: #fff; transform: rotate(30deg); }

    @media (max-width: 575.98px) {
        .reset-card .card-body { padding: 1.75rem 1.5rem 1.5rem; }
        .reset-header .icon { width: 60px; height: 60px; font-size: 1.8rem; }
        .reset-header h4 { font-size: 1.2rem; }
        .reset-form .form-control { height: 44px; font-size: 0.9rem; }
        .btn-reset, .btn-reset-success { height: 44px; font-size: 0.85rem; }
        .btn-theme-toggle { top: 1rem; right: 1rem; width: 36px; height: 36px; }
    }
</style>

<div class="reset-bg">
    <button type="button" class="btn-theme-toggle js-toggle-tema" title="Alternar tema">
        <i class="bi bi-moon-stars"></i>
    </button>

    <div class="reset-card">
        <div class="card-body">
            <div class="reset-header">
                <div class="icon"><i class="bi bi-shield-lock-fill"></i></div>
                <h4>Redefinir senha</h4>
                <?php if ($dadosToken): ?>
                    <p>Olá, <?= e($dadosToken['nome']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($sucesso): ?>
                <div class="alert-premium success">
                    <i class="bi bi-check-circle-fill"></i>
                    Senha redefinida com sucesso!
                </div>
                <a href="login.php" class="btn-reset-success">
                    <i class="bi bi-box-arrow-in-right"></i> Ir para o login
                </a>
            <?php else: ?>
                <?php if (!empty($erros)): ?>
                    <div class="alert-premium">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul class="mb-0" style="padding-left:1rem;margin:0;">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($dadosToken): ?>
                    <form method="POST" class="reset-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="token" value="<?= e($token) ?>">

                        <div class="mb-3">
                            <label class="form-label" style="color:rgba(255,255,255,0.7);font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">
                                <i class="bi bi-lock"></i> Nova senha
                            </label>
                            <input type="password" name="senha" class="form-control" required minlength="6" placeholder="••••••••">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="color:rgba(255,255,255,0.7);font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">
                                <i class="bi bi-check-circle"></i> Confirme a nova senha
                            </label>
                            <input type="password" name="confirmacao" class="form-control" required minlength="6" placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn-reset">
                            <i class="bi bi-check-lg"></i> Redefinir senha
                        </button>
                    </form>
                <?php else: ?>
                    <a href="esqueci_senha.php" class="btn-reset" style="background:linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="bi bi-send"></i> Solicitar novo link
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/foot_publico.php'; ?>