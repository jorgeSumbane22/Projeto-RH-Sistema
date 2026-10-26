<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações - Segurança';
$erros = [];
$sucesso = false;

// Dados padrão
$config = [
    'two_factor' => 0,
    'sessao' => 7200,
    'log_atividades' => 1,
    'ips_permitidos' => '',
    'politica_senha' => 'medio',
    'min_caracteres' => 8,
    'exigir_maiuscula' => 1,
    'exigir_numero' => 1,
    'exigir_especial' => 0,
    'bloqueio_tentativas' => 5,
    'tempo_bloqueio' => 30,
    'recaptcha' => 0,
    'recaptcha_site_key' => '',
    'recaptcha_secret_key' => '',
    'csrf_protecao' => 1,
    'session_segura' => 1,
    'http_only' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $config = [
        'two_factor' => (int) ($_POST['two_factor'] ?? 0),
        'sessao' => (int) ($_POST['sessao'] ?? 7200),
        'log_atividades' => isset($_POST['log_atividades']) ? 1 : 0,
        'ips_permitidos' => trim($_POST['ips_permitidos'] ?? ''),
        'politica_senha' => $_POST['politica_senha'] ?? 'medio',
        'min_caracteres' => (int) ($_POST['min_caracteres'] ?? 8),
        'exigir_maiuscula' => isset($_POST['exigir_maiuscula']) ? 1 : 0,
        'exigir_numero' => isset($_POST['exigir_numero']) ? 1 : 0,
        'exigir_especial' => isset($_POST['exigir_especial']) ? 1 : 0,
        'bloqueio_tentativas' => (int) ($_POST['bloqueio_tentativas'] ?? 5),
        'tempo_bloqueio' => (int) ($_POST['tempo_bloqueio'] ?? 30),
        'recaptcha' => isset($_POST['recaptcha']) ? 1 : 0,
        'recaptcha_site_key' => trim($_POST['recaptcha_site_key'] ?? ''),
        'recaptcha_secret_key' => trim($_POST['recaptcha_secret_key'] ?? ''),
        'csrf_protecao' => isset($_POST['csrf_protecao']) ? 1 : 0,
        'session_segura' => isset($_POST['session_segura']) ? 1 : 0,
        'http_only' => isset($_POST['http_only']) ? 1 : 0,
    ];
    
    // Validações
    if ($config['min_caracteres'] < 4) {
        $erros[] = 'O mínimo de caracteres deve ser pelo menos 4.';
    }
    
    if ($config['bloqueio_tentativas'] < 1) {
        $erros[] = 'O número de tentativas deve ser pelo menos 1.';
    }
    
    if ($config['tempo_bloqueio'] < 1) {
        $erros[] = 'O tempo de bloqueio deve ser pelo menos 1 minuto.';
    }
    
    if (empty($erros)) {
        $sucesso = true;
        definirMensagem('success', 'Configurações de segurança atualizadas com sucesso!');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   SEGURANÇA - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DA PÁGINA ----- */
.page-header-premium {
    background: linear-gradient(135deg, #dc2626, #ef4444);
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
    background: linear-gradient(135deg, #dc2626, #ef4444);
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
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
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
    background: rgba(220, 38, 38, 0.06);
    border-left: 3px solid #dc2626;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.info-card i {
    color: #dc2626;
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
    border-color: #dc2626;
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
    background: #dc2626;
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
    background: linear-gradient(135deg, #dc2626, #ef4444);
    border: none;
    color: #fff;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35);
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
    background: linear-gradient(135deg, #991b1b, #dc2626);
}

[data-bs-theme="dark"] .info-card {
    background: rgba(220, 38, 38, 0.1);
}

[data-bs-theme="dark"] .switch .slider {
    background: var(--rh-border);
}

[data-bs-theme="dark"] .switch input:checked + .slider {
    background: #dc2626;
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

/* ----- SECURITY LEVEL INDICATOR ----- */
.security-level {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
}

.security-level.alto {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.security-level.medio {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.security-level.baixo {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}
</style>

<!-- ============================================================
     HEADER DA PÁGINA
     ============================================================ -->
<div class="page-header-premium">
    <div class="header-left">
        <div class="header-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div class="header-title">
            <h5>Configurações de Segurança</h5>
            <p>Configure as políticas de segurança e proteção do sistema</p>
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
            <div class="header-icon-sm"><i class="bi bi-shield-check"></i></div>
            <h6>Políticas de Segurança</h6>
            <span class="badge-status ready ms-auto">
                <i class="bi bi-check-circle me-1"></i> Configurando
            </span>
        </div>
        <div class="card-body">
            <!-- Mensagens -->
            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #10b981;">
                    <i class="bi bi-check-circle me-2"></i> 
                    <strong>Sucesso!</strong> Configurações de segurança atualizadas com sucesso!
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
                <i class="bi bi-exclamation-triangle"></i>
                <div class="info-text">
                    <strong>Atenção:</strong> As configurações de segurança afetam todo o sistema. 
                    Altere com cuidado e sempre teste após as alterações.
                </div>
            </div>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <!-- Autenticação -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-person-lock"></i> Autenticação</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Autenticação em dois fatores (2FA)</label>
                    <select name="two_factor" class="form-select">
                        <option value="0" <?= $config['two_factor'] == 0 ? 'selected' : '' ?>>Desativado</option>
                        <option value="1" <?= $config['two_factor'] == 1 ? 'selected' : '' ?>>Ativado para todos</option>
                        <option value="2" <?= $config['two_factor'] == 2 ? 'selected' : '' ?>>Opcional (usuário pode ativar)</option>
                    </select>
                    <div class="form-text"><i class="bi bi-info-circle"></i> Adiciona uma camada extra de segurança com código via e-mail ou aplicativo</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tempo de sessão</label>
                    <select name="sessao" class="form-select">
                        <option value="1800" <?= $config['sessao'] == 1800 ? 'selected' : '' ?>>30 minutos</option>
                        <option value="3600" <?= $config['sessao'] == 3600 ? 'selected' : '' ?>>1 hora</option>
                        <option value="7200" <?= $config['sessao'] == 7200 ? 'selected' : '' ?>>2 horas</option>
                        <option value="14400" <?= $config['sessao'] == 14400 ? 'selected' : '' ?>>4 horas</option>
                        <option value="28800" <?= $config['sessao'] == 28800 ? 'selected' : '' ?>>8 horas</option>
                        <option value="86400" <?= $config['sessao'] == 86400 ? 'selected' : '' ?>>24 horas</option>
                    </select>
                    <div class="form-text"><i class="bi bi-info-circle"></i> Tempo máximo de inatividade antes da sessão expirar</div>
                </div>

                <!-- Política de Senhas -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-key"></i> Política de Senhas</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nível de segurança da senha</label>
                    <select name="politica_senha" class="form-select">
                        <option value="basico" <?= $config['politica_senha'] == 'basico' ? 'selected' : '' ?>>Básico (8 caracteres)</option>
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
                        <label class="form-label">Tentativas antes do bloqueio</label>
                        <input type="number" name="bloqueio_tentativas" class="form-control" 
                               value="<?= e($config['bloqueio_tentativas']) ?>" min="1">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Número de tentativas de login antes do bloqueio</div>
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

                <div class="mb-3">
                    <label class="form-label">Tempo de bloqueio (minutos)</label>
                    <input type="number" name="tempo_bloqueio" class="form-control" 
                           value="<?= e($config['tempo_bloqueio']) ?>" min="1">
                    <div class="form-text"><i class="bi bi-info-circle"></i> Tempo que o usuário fica bloqueado após exceder tentativas</div>
                </div>

                <!-- Proteção Avançada -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-shield"></i> Proteção Avançada</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Log de atividades</label>
                    <select name="log_atividades" class="form-select">
                        <option value="1" <?= $config['log_atividades'] ? 'selected' : '' ?>>Ativo - Registrar todas as ações</option>
                        <option value="0" <?= !$config['log_atividades'] ? 'selected' : '' ?>>Inativo - Não registrar ações</option>
                    </select>
                    <div class="form-text"><i class="bi bi-info-circle"></i> Registra todas as ações dos usuários para auditoria</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">IPs permitidos (opcional)</label>
                    <input type="text" name="ips_permitidos" class="form-control" 
                           placeholder="192.168.1.1, 10.0.0.1, 172.16.0.1" 
                           value="<?= e($config['ips_permitidos']) ?>">
                    <div class="form-text"><i class="bi bi-info-circle"></i> Separe por vírgula. Deixe em branco para permitir todos os IPs.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">reCAPTCHA</label>
                    <div class="switch-container">
                        <div class="switch-label">
                            Ativar reCAPTCHA
                            <small>Adiciona verificação de captcha no login para prevenir ataques automatizados</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="recaptcha" <?= $config['recaptcha'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <?php if ($config['recaptcha']): ?>
                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Site Key</label>
                        <input type="text" name="recaptcha_site_key" class="form-control" 
                               value="<?= e($config['recaptcha_site_key']) ?>" placeholder="Chave pública do site">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Secret Key</label>
                        <input type="password" name="recaptcha_secret_key" class="form-control" 
                               value="<?= e($config['recaptcha_secret_key']) ?>" placeholder="Chave secreta">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Segurança de Sessão -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-session"></i> Segurança de Sessão</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <div class="switch-container">
                        <div class="switch-label">
                            Proteção CSRF
                            <small>Previne ataques de falsificação de requisição entre sites</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="csrf_protecao" <?= $config['csrf_protecao'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            Sessão Segura
                            <small>Cookies de sessão apenas em conexões HTTPS</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="session_segura" <?= $config['session_segura'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            HTTP Only
                            <small>Cookies inacessíveis via JavaScript (proteção XSS)</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="http_only" <?= $config['http_only'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Ações -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar Configurações
                    </button>
                    <button type="reset" class="btn btn-reset">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
                    </button>
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
                    <i class="bi bi-shield-check me-1"></i> 2FA
                </div>
                <div style="font-size:1rem;font-weight:600;color:#dc2626;">
                    <?php 
                        $status2FA = ['Desativado', 'Ativado para todos', 'Opcional'];
                        echo $status2FA[$config['two_factor']] ?? 'Desativado';
                    ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-clock me-1"></i> Sessão
                </div>
                <div style="font-size:1rem;font-weight:600;color:#dc2626;">
                    <?= floor($config['sessao'] / 3600) ?> horas
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-key me-1"></i> Senha
                </div>
                <div style="font-size:1rem;font-weight:600;color:#dc2626;">
                    <?= ucfirst($config['politica_senha']) ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-check-circle me-1"></i> Status
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:var(--rh-success);">✓ Seguro</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/esconder campos reCAPTCHA
    const recaptchaCheck = document.querySelector('input[name="recaptcha"]');
    const recaptchaFields = document.querySelector('.grid-2')?.parentElement;
    
    if (recaptchaCheck && recaptchaFields) {
        recaptchaCheck.addEventListener('change', function() {
            if (this.checked) {
                recaptchaFields.style.display = 'block';
            } else {
                recaptchaFields.style.display = 'none';
            }
        });
        
        // Estado inicial
        if (!recaptchaCheck.checked) {
            recaptchaFields.style.display = 'none';
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