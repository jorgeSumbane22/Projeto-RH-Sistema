<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$pageTitle = 'Meu Perfil';
$usuarioModel = new Usuario();
$usuario = $usuarioModel->buscarPorId(usuarioAtualId());
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $nome = trim($_POST['nome'] ?? '');
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';
    $senhaAtual = $_POST['senha_atual'] ?? '';

    if ($nome === '') $erros[] = 'Informe seu nome.';

    if (!empty($novaSenha) || !empty($confirmacao)) {
        if (empty($senhaAtual) || !password_verify($senhaAtual, $usuario['senha_hash'])) {
            $erros[] = 'Senha atual incorreta.';
        }
        if (strlen($novaSenha) < 6) {
            $erros[] = 'A nova senha deve ter ao menos 6 caracteres.';
        }
        if ($novaSenha !== $confirmacao) {
            $erros[] = 'A confirmação não coincide com a nova senha.';
        }
    }

    if (empty($erros)) {
        $usuarioModel->atualizarProprioPerfil(usuarioAtualId(), $nome, $novaSenha ?: null);
        $_SESSION['usuario_nome'] = $nome;
        registrarAuditoria('perfil_atualizado', 'Usuário ' . usuarioAtualId());
        definirMensagem('success', 'Perfil atualizado com sucesso.');
        redirecionar('/modules/perfil/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   PERFIL - DESIGN PREMIUM
   ============================================================ */

.perfil-container {
    max-width: 580px;
    margin: 0 auto;
}

.perfil-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.perfil-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.perfil-card .perfil-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--rh-border);
    display: flex;
    align-items: center;
    gap: 1.25rem;
    background: var(--bs-tertiary-bg);
}

.perfil-card .perfil-header .perfil-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.8rem;
    flex-shrink: 0;
    text-transform: uppercase;
    box-shadow: 0 4px 16px rgba(21,94,117,0.3);
}

.perfil-card .perfil-header .perfil-info h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin: 0;
}

.perfil-card .perfil-header .perfil-info .perfil-email {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.perfil-card .perfil-header .perfil-info .perfil-perfil {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
    background: rgba(21,94,117,0.12);
    color: var(--rh-primary);
    margin-top: 0.2rem;
}

.perfil-card .perfil-body {
    padding: 2rem;
}

.perfil-card .form-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.perfil-card .form-label i {
    margin-right: 4px;
}

.perfil-card .form-control {
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--rh-text-primary);
}

.perfil-card .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
}

.perfil-card .form-control.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.12);
}

.perfil-card .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
}

.perfil-card .form-text i {
    margin-right: 4px;
}

.perfil-card .divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.5rem 0;
}

.perfil-card .divider::before,
.perfil-card .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--rh-border);
}

.perfil-card .divider span {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.perfil-card .divider span i {
    color: var(--rh-accent);
}

.perfil-card .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    border-radius: 10px;
    padding: 0.5rem 2rem;
    font-weight: 600;
    font-size: 0.85rem;
    color: #fff;
    transition: all 0.3s ease;
}

.perfil-card .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.3);
}

.perfil-card .btn-secondary {
    border-radius: 10px;
    padding: 0.5rem 2rem;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
}

.perfil-card .btn-secondary:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--bs-secondary-color);
}

/* ----- Security Info ----- */
.perfil-security-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    background: var(--bs-tertiary-bg);
    margin-bottom: 1rem;
}

.perfil-security-info i {
    color: var(--rh-accent);
    font-size: 1.1rem;
}

.perfil-security-info span {
    font-size: 0.8rem;
    color: var(--rh-text-muted);
}

/* ----- Badge Tema ----- */
.perfil-tema-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.7rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    margin-top: 0.3rem;
}

.perfil-tema-badge i {
    font-size: 0.7rem;
}

/* ----- Responsive ----- */
@media (max-width: 767.98px) {
    .perfil-card .perfil-header {
        flex-direction: column;
        text-align: center;
        padding: 1.25rem;
    }
    .perfil-card .perfil-body {
        padding: 1.25rem;
    }
    .perfil-card .btn-primary,
    .perfil-card .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 575.98px) {
    .perfil-card .perfil-header .perfil-avatar {
        width: 56px;
        height: 56px;
        font-size: 1.4rem;
    }
}
</style>

<!-- ============================================================
     PERFIL CONTAINER
     ============================================================ -->
<div class="perfil-container">
    <div class="perfil-card">
        <!-- Header -->
        <div class="perfil-header">
            <div class="perfil-avatar">
                <?= e(strtoupper(substr($usuario['nome'], 0, 1))) ?>
            </div>
            <div class="perfil-info">
                <h5><?= e($usuario['nome']) ?></h5>
                <div class="perfil-email">
                    <i class="bi bi-envelope"></i> <?= e($usuario['email']) ?>
                </div>
                <div class="perfil-perfil">
                    <i class="bi bi-shield-check"></i>
                    <?= e(nomePerfil($usuario['perfil'])) ?>
                </div>
                <div class="perfil-tema-badge">
                    <i class="bi <?= ($_SESSION['usuario_tema'] ?? 'claro') === 'escuro' ? 'bi-moon' : 'bi-sun' ?>"></i>
                    Tema: <?= ucfirst($_SESSION['usuario_tema'] ?? 'Claro') ?>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="perfil-body">
            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
                        <i class="bi bi-x-circle me-2"></i>
                        <strong>Por favor, corrija os seguintes erros:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <!-- Dados Pessoais -->
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-person"></i> Nome completo</label>
                    <input type="text" name="nome" class="form-control" required value="<?= e($usuario['nome']) ?>">
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Seu nome de exibição no sistema
                    </div>
                </div>

                <!-- Divider -->
                <div class="divider">
                    <span><i class="bi bi-lock"></i> Segurança</span>
                </div>

                <!-- Informação de segurança -->
                <div class="perfil-security-info">
                    <i class="bi bi-shield-check"></i>
                    <span>Para alterar a senha, informe a senha atual e a nova senha.</span>
                </div>

                <!-- Senha Atual -->
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-key"></i> Senha atual</label>
                    <input type="password" name="senha_atual" class="form-control" autocomplete="current-password" placeholder="Digite sua senha atual">
                </div>

                <!-- Nova Senha e Confirmação -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-key-fill"></i> Nova senha</label>
                        <input type="password" name="nova_senha" class="form-control" minlength="6" autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-check-circle"></i> Confirmar nova senha</label>
                        <input type="password" name="confirmacao" class="form-control" minlength="6" autocomplete="new-password" placeholder="Confirme a nova senha">
                    </div>
                </div>

                <div class="form-text mb-3" style="font-size:0.7rem;">
                    <i class="bi bi-info-circle"></i> Deixe os campos de senha em branco para manter a senha atual.
                </div>

                <!-- Botões -->
                <div class="d-flex gap-2 flex-wrap mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar alterações
                    </button>
                    <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>