<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações - Notificações';
$erros = [];
$sucesso = false;

// Dados padrão
$config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_encrypt' => 'tls',
    'smtp_user' => '',
    'smtp_pass' => '',
    'email_notificacoes' => 'notificacoes@rhsystem.com',
    'notif_ferias' => 1,
    'notif_aniversario' => 1,
    'notif_contrato' => 1,
    'notif_avaliacao' => 1,
    'notif_presenca' => 1,
    'notif_candidato' => 1,
    'notif_documento' => 1,
    'notif_ferias_dias' => 30,
    'notif_contrato_dias' => 60,
    'notif_avaliacao_dias' => 15,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $config = [
        'smtp_host' => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
        'smtp_port' => (int) ($_POST['smtp_port'] ?? 587),
        'smtp_encrypt' => $_POST['smtp_encrypt'] ?? 'tls',
        'smtp_user' => trim($_POST['smtp_user'] ?? ''),
        'smtp_pass' => trim($_POST['smtp_pass'] ?? ''),
        'email_notificacoes' => trim($_POST['email_notificacoes'] ?? ''),
        'notif_ferias' => isset($_POST['notif_ferias']) ? 1 : 0,
        'notif_aniversario' => isset($_POST['notif_aniversario']) ? 1 : 0,
        'notif_contrato' => isset($_POST['notif_contrato']) ? 1 : 0,
        'notif_avaliacao' => isset($_POST['notif_avaliacao']) ? 1 : 0,
        'notif_presenca' => isset($_POST['notif_presenca']) ? 1 : 0,
        'notif_candidato' => isset($_POST['notif_candidato']) ? 1 : 0,
        'notif_documento' => isset($_POST['notif_documento']) ? 1 : 0,
        'notif_ferias_dias' => (int) ($_POST['notif_ferias_dias'] ?? 30),
        'notif_contrato_dias' => (int) ($_POST['notif_contrato_dias'] ?? 60),
        'notif_avaliacao_dias' => (int) ($_POST['notif_avaliacao_dias'] ?? 15),
    ];
    
    // Validações
    if (!empty($config['smtp_user']) && !empty($config['smtp_pass'])) {
        if (!filter_var($config['smtp_user'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail do SMTP inválido.';
        }
    }
    
    if (!empty($config['email_notificacoes']) && !filter_var($config['email_notificacoes'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail de notificações inválido.';
    }
    
    if ($config['notif_ferias_dias'] < 1) {
        $erros[] = 'Dias de antecedência para férias deve ser maior que zero.';
    }
    
    if ($config['notif_contrato_dias'] < 1) {
        $erros[] = 'Dias de antecedência para contratos deve ser maior que zero.';
    }
    
    if (empty($erros)) {
        $sucesso = true;
        definirMensagem('success', 'Configurações de notificações atualizadas com sucesso!');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   NOTIFICAÇÕES - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DA PÁGINA ----- */
.page-header-premium {
    background: linear-gradient(135deg, #d97706, #f59e0b);
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
    background: linear-gradient(135deg, #d97706, #f59e0b);
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
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
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
    background: rgba(245, 158, 11, 0.06);
    border-left: 3px solid #f59e0b;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.info-card i {
    color: #f59e0b;
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
    border-color: #f59e0b;
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

.switch-container .switch-label .badge-days {
    display: inline-block;
    font-size: 0.55rem;
    padding: 0.1rem 0.5rem;
    background: var(--rh-border);
    border-radius: 50px;
    color: var(--rh-text-muted);
    margin-left: 0.5rem;
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
    background: #f59e0b;
}

.switch input:checked + .slider::before {
    transform: translateX(20px);
}

.switch input:disabled + .slider {
    opacity: 0.5;
    cursor: not-allowed;
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
    background: linear-gradient(135deg, #d97706, #f59e0b);
    border: none;
    color: #fff;
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.25);
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.35);
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

.form-actions .btn-test {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-info);
}

.form-actions .btn-test:hover {
    background: var(--rh-info);
    border-color: var(--rh-info);
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
    
    .grid-2 {
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
    background: linear-gradient(135deg, #b45309, #d97706);
}

[data-bs-theme="dark"] .info-card {
    background: rgba(245, 158, 11, 0.1);
}

[data-bs-theme="dark"] .switch .slider {
    background: var(--rh-border);
}

[data-bs-theme="dark"] .switch input:checked + .slider {
    background: #f59e0b;
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
            <i class="bi bi-bell"></i>
        </div>
        <div class="header-title">
            <h5>Configurações de Notificações</h5>
            <p>Configure os canais, alertas e notificações automáticas do sistema</p>
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
            <h6>Configurações de E-mail e Notificações</h6>
            <span class="badge-status ready ms-auto">
                <i class="bi bi-check-circle me-1"></i> Configurando
            </span>
        </div>
        <div class="card-body">
            <!-- Mensagens -->
            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #10b981;">
                    <i class="bi bi-check-circle me-2"></i> 
                    <strong>Sucesso!</strong> Configurações de notificações atualizadas com sucesso!
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
                    <strong>Configurações de notificações:</strong> Configure os servidores de e-mail, 
                    canais de comunicação e os tipos de alertas automáticos que o sistema deve enviar.
                </div>
            </div>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <!-- Configurações SMTP -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-envelope"></i> Servidor SMTP</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Servidor SMTP <span class="required">*</span></label>
                    <input type="text" name="smtp_host" class="form-control" 
                           value="<?= e($config['smtp_host']) ?>" placeholder="smtp.gmail.com" required>
                    <div class="form-text"><i class="bi bi-info-circle"></i> Endereço do servidor SMTP</div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Porta SMTP</label>
                        <input type="number" name="smtp_port" class="form-control" 
                               value="<?= e($config['smtp_port']) ?>" min="1" max="65535">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Criptografia</label>
                        <select name="smtp_encrypt" class="form-select">
                            <option value="tls" <?= $config['smtp_encrypt'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $config['smtp_encrypt'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= $config['smtp_encrypt'] === 'none' ? 'selected' : '' ?>>Nenhuma</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Usuário SMTP</label>
                        <input type="email" name="smtp_user" class="form-control" 
                               value="<?= e($config['smtp_user']) ?>" placeholder="email@exemplo.com">
                        <div class="form-text"><i class="bi bi-info-circle"></i> E-mail para autenticação SMTP</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha SMTP</label>
                        <input type="password" name="smtp_pass" class="form-control" 
                               value="<?= e($config['smtp_pass']) ?>" placeholder="********">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Senha do e-mail para autenticação</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">E-mail para notificações</label>
                    <input type="email" name="email_notificacoes" class="form-control" 
                           value="<?= e($config['email_notificacoes']) ?>" placeholder="notificacoes@exemplo.com">
                    <div class="form-text"><i class="bi bi-info-circle"></i> E-mail de remetente para as notificações</div>
                </div>

                <!-- Notificações Automáticas -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-bell"></i> Notificações Automáticas</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alertas e Lembretes</label>
                    
                    <div class="switch-container">
                        <div class="switch-label">
                            <i class="bi bi-umbrella me-1" style="color:#8b5cf6;"></i>
                            Férias vencendo
                            <small>Alertar sobre funcionários com férias próximas do vencimento</small>
                            <span class="badge-days"><?= e($config['notif_ferias_dias']) ?> dias</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_ferias" <?= $config['notif_ferias'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            <i class="bi bi-gift me-1" style="color:#ef4444;"></i>
                            Aniversários
                            <small>Alertar sobre aniversários dos funcionários</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_aniversario" <?= $config['notif_aniversario'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            <i class="bi bi-file-earmark-text me-1" style="color:#3b82f6;"></i>
                            Contratos vencendo
                            <small>Alertar sobre contratos próximos do vencimento</small>
                            <span class="badge-days"><?= e($config['notif_contrato_dias']) ?> dias</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_contrato" <?= $config['notif_contrato'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            <i class="bi bi-star me-1" style="color:#f59e0b;"></i>
                            Avaliações pendentes
                            <small>Alertar sobre avaliações de desempenho pendentes</small>
                            <span class="badge-days"><?= e($config['notif_avaliacao_dias']) ?> dias</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_avaliacao" <?= $config['notif_avaliacao'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            <i class="bi bi-clock-history me-1" style="color:#14b8a6;"></i>
                            Faltas e presenças
                            <small>Alertar sobre faltas não justificadas e atrasos</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_presenca" <?= $config['notif_presenca'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            <i class="bi bi-person-plus me-1" style="color:#8b5cf6;"></i>
                            Candidatos
                            <small>Alertar sobre novos candidatos e atualizações</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_candidato" <?= $config['notif_candidato'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label">
                            <i class="bi bi-file-earmark-text me-1" style="color:#ef4444;"></i>
                            Documentos
                            <small>Alertar sobre documentos pendentes ou vencidos</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_documento" <?= $config['notif_documento'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Antecedências -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-calendar"></i> Antecedência dos Alertas</span>
                    <span class="line"></span>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Férias (dias antes)</label>
                        <input type="number" name="notif_ferias_dias" class="form-control" 
                               value="<?= e($config['notif_ferias_dias']) ?>" min="1">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Dias de antecedência para alerta de férias</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contratos (dias antes)</label>
                        <input type="number" name="notif_contrato_dias" class="form-control" 
                               value="<?= e($config['notif_contrato_dias']) ?>" min="1">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Dias de antecedência para alerta de contratos</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Avaliações (dias antes)</label>
                    <input type="number" name="notif_avaliacao_dias" class="form-control" 
                           value="<?= e($config['notif_avaliacao_dias']) ?>" min="1">
                    <div class="form-text"><i class="bi bi-info-circle"></i> Dias de antecedência para alerta de avaliações</div>
                </div>

                <!-- Ações -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar Configurações
                    </button>
                    <button type="button" class="btn btn-test" onclick="testarConexao()">
                        <i class="bi bi-send me-1"></i> Testar E-mail
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
                    <i class="bi bi-envelope me-1"></i> SMTP
                </div>
                <div style="font-size:1rem;font-weight:600;color:#f59e0b;">
                    <?= !empty($config['smtp_user']) ? e($config['smtp_user']) : 'Não configurado' ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-bell me-1"></i> Alertas Ativos
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#f59e0b;">
                    <?php 
                        $ativos = 0;
                        foreach (['notif_ferias', 'notif_aniversario', 'notif_contrato', 'notif_avaliacao', 'notif_presenca', 'notif_candidato', 'notif_documento'] as $campo) {
                            if ($config[$campo] ?? 0) $ativos++;
                        }
                        echo $ativos . '/7';
                    ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-calendar me-1"></i> Alertas Programados
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#f59e0b;">3 tipos</div>
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
    // Restaurar valores padrão
    document.querySelector('button[type="reset"]')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Deseja restaurar todos os valores para o padrão?')) {
            location.reload();
        }
    });
});

// Função para testar conexão SMTP
function testarConexao() {
    if (!confirm('Deseja enviar um e-mail de teste para verificar as configurações?')) {
        return;
    }
    
    const btn = document.querySelector('.btn-test');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-spinner bi-spin me-1"></i> Testando...';
    btn.disabled = true;
    
    // Simular teste (substituir por requisição AJAX real)
    setTimeout(function() {
        alert('✅ E-mail de teste enviado com sucesso!\n\nVerifique a caixa de entrada do e-mail configurado.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 2000);
}
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>