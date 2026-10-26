<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações - Usuários';
$erros = [];
$sucesso = false;

// Dados padrão
$config = [
    'auto_cadastro' => 0,
    'politica_senha' => 'medio',
    'expira_senha' => 90,
    'max_tentativas' => 5,
    'min_caracteres' => 8,
    'exigir_maiuscula' => 1,
    'exigir_numero' => 1,
    'exigir_especial' => 0,
    'notificar_novo_usuario' => 1,
    'confirmar_email' => 1,
    'sessao_maxima' => 1,
    'ip_restrito' => 0,
    'horario_restrito' => 0,
    'horario_inicio' => '08:00',
    'horario_fim' => '18:00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $config = [
        'auto_cadastro' => isset($_POST['auto_cadastro']) ? 1 : 0,
        'politica_senha' => $_POST['politica_senha'] ?? 'medio',
        'expira_senha' => (int) ($_POST['expira_senha'] ?? 90),
        'max_tentativas' => (int) ($_POST['max_tentativas'] ?? 5),
        'min_caracteres' => (int) ($_POST['min_caracteres'] ?? 8),
        'exigir_maiuscula' => isset($_POST['exigir_maiuscula']) ? 1 : 0,
        'exigir_numero' => isset($_POST['exigir_numero']) ? 1 : 0,
        'exigir_especial' => isset($_POST['exigir_especial']) ? 1 : 0,
        'notificar_novo_usuario' => isset($_POST['notificar_novo_usuario']) ? 1 : 0,
        'confirmar_email' => isset($_POST['confirmar_email']) ? 1 : 0,
        'sessao_maxima' => isset($_POST['sessao_maxima']) ? 1 : 0,
        'ip_restrito' => isset($_POST['ip_restrito']) ? 1 : 0,
        'horario_restrito' => isset($_POST['horario_restrito']) ? 1 : 0,
        'horario_inicio' => $_POST['horario_inicio'] ?? '08:00',
        'horario_fim' => $_POST['horario_fim'] ?? '18:00',
    ];
    
    // Validações
    if ($config['expira_senha'] < 0) {
        $erros[] = 'Tempo de expiração da senha deve ser maior ou igual a 0.';
    }
    
    if ($config['max_tentativas'] < 1) {
        $erros[] = 'Número máximo de tentativas deve ser pelo menos 1.';
    }
    
    if ($config['min_caracteres'] < 4) {
        $erros[] = 'Mínimo de caracteres deve ser pelo menos 4.';
    }
    
    if ($config['horario_inicio'] >= $config['horario_fim']) {
        $erros[] = 'Horário de início deve ser menor que o horário de fim.';
    }
    
    if (empty($erros)) {
        $sucesso = true;
        definirMensagem('success', 'Configurações de usuários atualizadas com sucesso!');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   USUÁRIOS - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DA PÁGINA ----- */
.page-header-premium {
    background: linear-gradient(135deg, #059669, #10b981);
    border-radius: var(--rh-radius);
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    position: relative;
    overflow: hidden;
}

.page-header-premium::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.page-header-premium::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -10%;
    width: 250px;
    height: 250px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}

.page-header-premium .header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.page-header-premium .header-left .header-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.1);
}

.page-header-premium .header-left .header-title h5 {
    color: #fff;
    font-weight: 700;
    margin: 0;
    font-size: 1.2rem;
}

.page-header-premium .header-left .header-title p {
    color: rgba(255,255,255,0.7);
    margin: 0;
    font-size: 0.8rem;
}

.page-header-premium .btn-back {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
    text-decoration: none;
}

.page-header-premium .btn-back:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
    transform: translateY(-2px);
}

/* ----- CONTAINER ----- */
.config-form-container {
    max-width: 900px;
    margin: 0 auto;
}

/* ----- CARD PREMIUM ----- */
.config-form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.config-form-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.config-form-card .card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--rh-border);
    background: var(--bs-tertiary-bg);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.config-form-card .card-header .header-icon-sm {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #059669, #10b981);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.config-form-card .card-header h6 {
    margin: 0;
    font-weight: 600;
    color: var(--bs-body-color);
}

.config-form-card .card-header .badge-status {
    margin-left: auto;
    font-size: 0.55rem;
    padding: 0.2rem 0.7rem;
}

.config-form-card .card-body {
    padding: 1.75rem;
}

/* ----- FORMULÁRIO ----- */
.config-form-card .form-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.config-form-card .form-label .required {
    color: var(--rh-danger);
    margin-left: 2px;
}

.config-form-card .form-control,
.config-form-card .form-select {
    border-radius: 10px;
    padding: 0.55rem 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
    font-size: 0.9rem;
}

.config-form-card .form-control:focus,
.config-form-card .form-select:focus {
    background: var(--rh-card-bg);
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
    outline: none;
}

.config-form-card .form-control.is-invalid {
    border-color: var(--rh-danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.config-form-card .form-control::placeholder {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
}

.config-form-card .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    margin-top: 0.2rem;
}

.config-form-card .form-text i {
    margin-right: 4px;
}

/* ----- GRID ----- */
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
}

/* ----- SECTION DIVIDER ----- */
.section-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.5rem 0 1rem;
}

.section-divider .line {
    flex: 1;
    height: 1px;
    background: var(--rh-border);
}

.section-divider .label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    white-space: nowrap;
}

.section-divider .label i {
    margin-right: 6px;
}

/* ----- INFO CARD ----- */
.info-card {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: rgba(16, 185, 129, 0.06);
    border-left: 3px solid #10b981;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.info-card i {
    color: #10b981;
    font-size: 1.1rem;
    margin-top: 0.1rem;
}

.info-card .info-text {
    font-size: 0.8rem;
    color: var(--rh-text-secondary);
}

.info-card .info-text strong {
    color: var(--bs-body-color);
}

/* ----- TOGGLE SWITCH ----- */
.switch-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.6rem 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: 10px;
    border: 1px solid var(--rh-border);
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
}

.switch-container:hover {
    border-color: #10b981;
}

.switch-container .switch-label {
    flex: 1;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.switch-container .switch-label small {
    display: block;
    font-size: 0.7rem;
    color: var(--rh-text-muted);
    font-weight: 400;
}

.switch {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.switch .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--rh-border);
    transition: 0.3s;
    border-radius: 34px;
}

.switch .slider::before {
    content: '';
    position: absolute;
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background: #fff;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.switch input:checked + .slider {
    background: #10b981;
}

.switch input:checked + .slider::before {
    transform: translateX(20px);
}

/* ----- BOTÕES ----- */
.form-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--rh-border);
}

.form-actions .btn {
    border-radius: 10px;
    padding: 0.5rem 1.8rem;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, #059669, #10b981);
    border: none;
    color: #fff;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
}

.form-actions .btn-secondary {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
}

.form-actions .btn-secondary:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--bs-secondary-color);
}

.form-actions .btn-reset {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-danger);
}

.form-actions .btn-reset:hover {
    background: var(--rh-danger);
    border-color: var(--rh-danger);
    color: #fff;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .page-header-premium {
        padding: 1.25rem;
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .page-header-premium .header-left {
        flex-direction: column;
        text-align: center;
    }
    
    .config-form-card .card-body {
        padding: 1.25rem;
    }
    
    .grid-2,
    .grid-3 {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        text-align: center;
    }
    
    .switch-container {
        flex-wrap: wrap;
    }
}

@media (max-width: 575.98px) {
    .page-header-premium .header-left .header-title h5 {
        font-size: 1rem;
    }
    
    .config-form-card .card-body {
        padding: 1rem;
    }
}

/* ----- DARK MODE ----- */
[data-bs-theme="dark"] .page-header-premium {
    background: linear-gradient(135deg, #065f46, #059669);
}

[data-bs-theme="dark"] .info-card {
    background: rgba(16, 185, 129, 0.1);
}

[data-bs-theme="dark"] .switch .slider {
    background: var(--rh-border);
}

[data-bs-theme="dark"] .switch input:checked + .slider {
    background: #10b981;
}

/* ----- ANIMAÇÕES ----- */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.config-form-card {
    animation: fadeInUp 0.5s ease forwards;
}
</style>

<!-- ============================================================
     HEADER DA PÁGINA
     ============================================================ -->
<div class="page-header-premium">
    <div class="header-left">
        <div class="header-icon">
            <i class="bi bi-people"></i>
        </div>
        <div class="header-title">
            <h5>Configurações de Usuários</h5>
            <p>Configure políticas de cadastro, autenticação e permissões de acesso</p>
        </div>
    </div>
    <a href="index.php" class="btn-back">
        <i class="bi bi-arrow-left me-1"></i> Voltar
    </a>
</div>

<!-- ============================================================
     FORMULÁRIO
     ============================================================ -->
<div class="config-form-container">
    <div class="config-form-card">
        <div class="card-header">
            <div class="header-icon-sm"><i class="bi bi-sliders"></i></div>
            <h6>Políticas de Usuários</h6>
            <span class="badge-status ready ms-auto">
                <i class="bi bi-check-circle me-1"></i> Configurando
            </span>
        </div>
        <div class="card-body">
            <!-- Mensagens -->
            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #10b981;">
                    <i class="bi bi-check-circle me-2"></i> 
                    <strong>Sucesso!</strong> Configurações de usuários atualizadas com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
                    <i class="bi bi-x-circle me-2"></i>
                    <strong>Por favor, corrija os seguintes erros:</strong>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($erros as $erro): ?>
                            <li><?= e($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Info -->
            <div class="info-card">
                <i class="bi bi-info-circle"></i>
                <div class="info-text">
                    <strong>Políticas de usuários:</strong> Configure as regras de cadastro, autenticação 
                    e segurança para todos os usuários do sistema. Estas configurações afetam a experiência 
                    de todos os perfis.
                </div>
            </div>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <!-- Cadastro -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-person-plus"></i> Cadastro de Usuários</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <div class="switch-container">
                        <div class="switch-label">
                            Permitir auto-cadastro
                            <small>Usuários podem se cadastrar sem aprovação administrativa</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="auto_cadastro" <?= $config['auto_cadastro'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="switch-container">
                        <div class="switch-label">
                            Confirmar e-mail
                            <small>Exige confirmação de e-mail para ativação da conta</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="confirmar_email" <?= $config['confirmar_email'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="switch-container">
                        <div class="switch-label">
                            Notificar novo usuário
                            <small>Envia e-mail de boas-vindas ao novo usuário</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notificar_novo_usuario" <?= $config['notificar_novo_usuario'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Política de Senhas -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-key"></i> Política de Senhas</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nível de segurança da senha</label>
                    <select name="politica_senha" class="form-select">
                        <option value="basico" <?= $config['politica_senha'] == 'basico' ? 'selected' : '' ?>>Básico (mínimo 8 caracteres)</option>
                        <option value="medio" <?= $config['politica_senha'] == 'medio' ? 'selected' : '' ?>>Médio (8 caracteres, maiúsculas e números)</option>
                        <option value="forte" <?= $config['politica_senha'] == 'forte' ? 'selected' : '' ?>>Forte (12 caracteres, maiúsculas, números e especiais)</option>
                    </select>
                    <div class="form-text"><i class="bi bi-info-circle"></i> Define a complexidade mínima exigida para senhas</div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Mínimo de caracteres</label>
                        <input type="number" name="min_caracteres" class="form-control" 
                               value="<?= e($config['min_caracteres']) ?>" min="4">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiração da senha (dias)</label>
                        <input type="number" name="expira_senha" class="form-control" 
                               value="<?= e($config['expira_senha']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> 0 = nunca expira</div>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="exigir_maiuscula" id="exigir_maiuscula" <?= $config['exigir_maiuscula'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="exigir_maiuscula">
                                Letras maiúsculas
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="exigir_numero" id="exigir_numero" <?= $config['exigir_numero'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="exigir_numero">
                                Números
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="exigir_especial" id="exigir_especial" <?= $config['exigir_especial'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="exigir_especial">
                                Caracteres especiais (@, #, $, etc)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Autenticação -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-shield-lock"></i> Autenticação</span>
                    <span class="line"></span>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Máximo de tentativas de login</label>
                        <input type="number" name="max_tentativas" class="form-control" 
                               value="<?= e($config['max_tentativas']) ?>" min="1">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Número de tentativas antes do bloqueio</div>
                    </div>
                    <div class="mb-3">
                        <div class="switch-container" style="margin-bottom:0;">
                            <div class="switch-label">
                                Sessão única
                                <small>Permite apenas uma sessão ativa por usuário</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="sessao_maxima" <?= $config['sessao_maxima'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Restrições de Acesso -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-geo-alt"></i> Restrições de Acesso</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <div class="switch-container">
                        <div class="switch-label">
                            IP Restrito
                            <small>Permite acesso apenas de IPs autorizados</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="ip_restrito" <?= $config['ip_restrito'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="switch-container">
                        <div class="switch-label">
                            Horário Restrito
                            <small>Permite acesso apenas em horários específicos</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="horario_restrito" <?= $config['horario_restrito'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <?php if ($config['horario_restrito']): ?>
                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Horário de Início</label>
                        <input type="time" name="horario_inicio" class="form-control" 
                               value="<?= e($config['horario_inicio']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Horário de Fim</label>
                        <input type="time" name="horario_fim" class="form-control" 
                               value="<?= e($config['horario_fim']) ?>">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ações -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar Configurações
                    </button>
                    <button type="reset" class="btn btn-reset">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
                    </button>
                    <a href="<?= BASE_URL ?>/modules/usuarios/index.php" class="btn btn-secondary">
                        <i class="bi bi-box-arrow-right me-1"></i> Gerenciar Usuários
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo das configurações -->
    <div class="mt-4 p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--rh-border);">
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-person-plus me-1"></i> Auto-cadastro
                </div>
                <div style="font-size:1rem;font-weight:600;color:#10b981;">
                    <?= $config['auto_cadastro'] ? 'Ativado' : 'Desativado' ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-key me-1"></i> Política de Senha
                </div>
                <div style="font-size:1rem;font-weight:600;color:#10b981;">
                    <?= ucfirst($config['politica_senha']) ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-clock me-1"></i> Expiração
                </div>
                <div style="font-size:1rem;font-weight:600;color:#10b981;">
                    <?= $config['expira_senha'] > 0 ? $config['expira_senha'] . ' dias' : 'Nunca' ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-check-circle me-1"></i> Status
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:var(--rh-success);">✓ Configurado</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/esconder campos de horário
    const horarioCheck = document.querySelector('input[name="horario_restrito"]');
    const horarioFields = document.querySelector('.grid-2:has(input[name="horario_inicio"])')?.parentElement;
    
    if (horarioCheck && horarioFields) {
        horarioCheck.addEventListener('change', function() {
            if (this.checked) {
                horarioFields.style.display = 'block';
            } else {
                horarioFields.style.display = 'none';
            }
        });
        
        // Estado inicial
        if (!horarioCheck.checked) {
            horarioFields.style.display = 'none';
        }
    }

    // Restaurar valores padrão
    document.querySelector('button[type="reset"]')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Deseja restaurar todos os valores para o padrão?')) {
            location.reload();
        }
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>