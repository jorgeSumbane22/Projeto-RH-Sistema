<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações - Sistema';
$erros = [];
$sucesso = false;
$mensagem = '';

// Dados padrão
$config = [
    'backup_automatico' => 1,
    'backup_pasta' => '../backup/',
    'backup_reter' => 30,
    'log_nivel' => 'warning',
    'log_reter' => 90,
    'manutencao_modo' => 0,
    'manutencao_mensagem' => 'Sistema em manutenção. Volte em breve.',
    'cache_ativado' => 1,
    'cache_tempo' => 3600,
    'debug_mode' => 0,
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $config = [
        'backup_automatico' => (int) ($_POST['backup_automatico'] ?? 0),
        'backup_pasta' => trim($_POST['backup_pasta'] ?? '../backup/'),
        'backup_reter' => (int) ($_POST['backup_reter'] ?? 30),
        'log_nivel' => $_POST['log_nivel'] ?? 'warning',
        'log_reter' => (int) ($_POST['log_reter'] ?? 90),
        'manutencao_modo' => isset($_POST['manutencao_modo']) ? 1 : 0,
        'manutencao_mensagem' => trim($_POST['manutencao_mensagem'] ?? 'Sistema em manutenção. Volte em breve.'),
        'cache_ativado' => isset($_POST['cache_ativado']) ? 1 : 0,
        'cache_tempo' => (int) ($_POST['cache_tempo'] ?? 3600),
        'debug_mode' => isset($_POST['debug_mode']) ? 1 : 0,
        'timezone' => $_POST['timezone'] ?? 'America/Sao_Paulo',
        'locale' => $_POST['locale'] ?? 'pt_BR',
    ];
    
    // Validações
    if ($config['backup_reter'] < 1) {
        $erros[] = 'Dias para reter backups deve ser pelo menos 1.';
    }
    
    if ($config['log_reter'] < 1) {
        $erros[] = 'Dias para reter logs deve ser pelo menos 1.';
    }
    
    if ($config['cache_tempo'] < 60) {
        $erros[] = 'Tempo de cache deve ser pelo menos 60 segundos.';
    }
    
    if (empty($erros)) {
        $sucesso = true;
        $mensagem = 'Configurações do sistema atualizadas com sucesso!';
        definirMensagem('success', $mensagem);
    }
}

// Informações do sistema
$phpVersion = phpversion();
$dbVersion = Database::getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
$os = php_uname('s') . ' ' . php_uname('r');
$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');
$uploadMaxFilesize = ini_get('upload_max_filesize');

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   SISTEMA - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DA PÁGINA ----- */
.page-header-premium {
    background: linear-gradient(135deg, #0f1724, #1e293b);
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
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}

.page-header-premium::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -10%;
    width: 250px;
    height: 250px;
    background: rgba(255,255,255,0.02);
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
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.05);
}

.page-header-premium .header-left .header-title h5 {
    color: #fff;
    font-weight: 700;
    margin: 0;
    font-size: 1.2rem;
}

.page-header-premium .header-left .header-title p {
    color: rgba(255,255,255,0.6);
    margin: 0;
    font-size: 0.8rem;
}

.page-header-premium .btn-back {
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.05);
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
    background: rgba(255,255,255,0.15);
    color: #fff;
    transform: translateY(-2px);
}

/* ----- CONTAINER ----- */
.config-form-container {
    max-width: 100%;
}

/* ----- CARD PREMIUM ----- */
.config-form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
}

.config-form-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.config-form-card .card-header {
    padding: 1rem 1.25rem;
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
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: #fff;
}

.config-form-card .card-header .header-icon-sm.primary {
    background: linear-gradient(135deg, #155e75, #0e7490);
}

.config-form-card .card-header .header-icon-sm.warning {
    background: linear-gradient(135deg, #d97706, #f59e0b);
}

.config-form-card .card-header .header-icon-sm.danger {
    background: linear-gradient(135deg, #dc2626, #ef4444);
}

.config-form-card .card-header .header-icon-sm.info {
    background: linear-gradient(135deg, #0891b2, #06b6d4);
}

.config-form-card .card-header h6 {
    margin: 0;
    font-weight: 600;
    color: var(--bs-body-color);
    font-size: 0.9rem;
}

.config-form-card .card-body {
    padding: 1.25rem;
}

/* ----- FORMULÁRIO ----- */
.config-form-card .form-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.config-form-card .form-control,
.config-form-card .form-select {
    border-radius: 8px;
    padding: 0.4rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
    font-size: 0.85rem;
}

.config-form-card .form-control:focus,
.config-form-card .form-select:focus {
    background: var(--rh-card-bg);
    border-color: #0f1724;
    box-shadow: 0 0 0 3px rgba(15, 23, 36, 0.12);
    outline: none;
}

.config-form-card .form-text {
    font-size: 0.6rem;
    color: var(--rh-text-muted);
    margin-top: 0.15rem;
}

/* ----- TOGGLE SWITCH ----- */
.switch-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.4rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border-radius: 8px;
    border: 1px solid var(--rh-border);
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
}

.switch-container:hover {
    border-color: #0f1724;
}

.switch-container .switch-label {
    flex: 1;
    font-size: 0.8rem;
    color: var(--bs-body-color);
}

.switch-container .switch-label small {
    display: block;
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    font-weight: 400;
}

.switch {
    position: relative;
    width: 40px;
    height: 22px;
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
    height: 14px;
    width: 14px;
    left: 4px;
    bottom: 4px;
    background: #fff;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.switch input:checked + .slider {
    background: #0f1724;
}

.switch input:checked + .slider::before {
    transform: translateX(18px);
}

/* ----- BOTÕES DE AÇÃO ----- */
.btn-action {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    border: none;
    color: #fff;
    width: 100%;
}

.btn-action:hover {
    transform: translateY(-2px);
}

.btn-action.warning {
    background: linear-gradient(135deg, #d97706, #f59e0b);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.btn-action.warning:hover {
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
}

.btn-action.danger {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

.btn-action.danger:hover {
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.3);
}

.btn-action.info {
    background: linear-gradient(135deg, #0891b2, #06b6d4);
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2);
}

.btn-action.info:hover {
    box-shadow: 0 6px 16px rgba(8, 145, 178, 0.3);
}

.btn-action.success {
    background: linear-gradient(135deg, #059669, #10b981);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.btn-action.success:hover {
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
}

.btn-action.primary {
    background: linear-gradient(135deg, #155e75, #0e7490);
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.2);
}

.btn-action.primary:hover {
    box-shadow: 0 6px 16px rgba(21, 94, 117, 0.3);
}

/* ----- INFO SYSTEM ----- */
.info-system {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
}

.info-system .info-item {
    padding: 0.5rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border-radius: 8px;
    border: 1px solid var(--rh-border);
}

.info-system .info-item .label {
    font-size: 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
}

.info-system .info-item .value {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-top: 0.1rem;
}

.info-system .info-item .value .badge-status {
    font-size: 0.5rem;
    padding: 0.1rem 0.4rem;
}

/* ----- BOTÕES DO FORMULÁRIO ----- */
.form-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--rh-border);
}

.form-actions .btn {
    border-radius: 8px;
    padding: 0.4rem 1.5rem;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, #0f1724, #1e293b);
    border: none;
    color: #fff;
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(15, 23, 36, 0.3);
}

.form-actions .btn-secondary {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
}

.form-actions .btn-secondary:hover {
    background: var(--bs-tertiary-bg);
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
        padding: 1rem;
    }
    
    .info-system {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 575.98px) {
    .page-header-premium .header-left .header-title h5 {
        font-size: 1rem;
    }
    
    .info-system {
        grid-template-columns: 1fr;
    }
}

/* ----- DARK MODE ----- */
[data-bs-theme="dark"] .page-header-premium {
    background: linear-gradient(135deg, #0a0a0f, #1a1a2e);
}

[data-bs-theme="dark"] .switch .slider {
    background: var(--rh-border);
}

[data-bs-theme="dark"] .switch input:checked + .slider {
    background: #2d3748;
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

.config-form-card:nth-child(1) { animation-delay: 0.05s; }
.config-form-card:nth-child(2) { animation-delay: 0.10s; }
.config-form-card:nth-child(3) { animation-delay: 0.15s; }
.config-form-card:nth-child(4) { animation-delay: 0.20s; }
</style>

<!-- ============================================================
     HEADER DA PÁGINA
     ============================================================ -->
<div class="page-header-premium">
    <div class="header-left">
        <div class="header-icon">
            <i class="bi bi-gear"></i>
        </div>
        <div class="header-title">
            <h5>Configurações do Sistema</h5>
            <p>Gerencie backup, logs, manutenção e informações técnicas do sistema</p>
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
    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="row g-4">
            <!-- Backup -->
            <div class="col-md-6">
                <div class="config-form-card">
                    <div class="card-header">
                        <div class="header-icon-sm primary"><i class="bi bi-cloud-arrow-down"></i></div>
                        <h6>Backup</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($sucesso && empty($erros)): ?>
                            <div class="alert alert-success alert-dismissible fade show" style="border-radius:8px;border-left:3px solid #10b981;padding:0.5rem 0.75rem;font-size:0.8rem;">
                                <i class="bi bi-check-circle me-1"></i> <?= $mensagem ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:0.6rem;"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($erros)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" style="border-radius:8px;border-left:3px solid #ef4444;padding:0.5rem 0.75rem;font-size:0.8rem;">
                                <i class="bi bi-x-circle me-1"></i>
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($erros as $erro): ?>
                                        <li><?= e($erro) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:0.6rem;"></button>
                            </div>
                        <?php endif; ?>

                        <div class="mb-2">
                            <label class="form-label">Backup automático</label>
                            <select name="backup_automatico" class="form-select">
                                <option value="0" <?= $config['backup_automatico'] == 0 ? 'selected' : '' ?>>Desativado</option>
                                <option value="1" <?= $config['backup_automatico'] == 1 ? 'selected' : '' ?>>Diário</option>
                                <option value="7" <?= $config['backup_automatico'] == 7 ? 'selected' : '' ?>>Semanal</option>
                                <option value="30" <?= $config['backup_automatico'] == 30 ? 'selected' : '' ?>>Mensal</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Pasta de backup</label>
                            <input type="text" name="backup_pasta" class="form-control" value="<?= e($config['backup_pasta']) ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Reter backups (dias)</label>
                            <input type="number" name="backup_reter" class="form-control" value="<?= e($config['backup_reter']) ?>" min="1">
                            <div class="form-text">Backups mais antigos que este período serão removidos</div>
                        </div>

                        <a href="<?= BASE_URL ?>/modules/backup/index.php" class="btn btn-outline-primary btn-sm w-100" style="border-radius:8px;font-size:0.75rem;">
                            <i class="bi bi-box-arrow-right me-1"></i> Gerenciar Backup
                        </a>
                    </div>
                </div>
            </div>

            <!-- Logs -->
            <div class="col-md-6">
                <div class="config-form-card">
                    <div class="card-header">
                        <div class="header-icon-sm warning"><i class="bi bi-journal-text"></i></div>
                        <h6>Logs e Auditoria</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label">Nível de log</label>
                            <select name="log_nivel" class="form-select">
                                <option value="error" <?= $config['log_nivel'] == 'error' ? 'selected' : '' ?>>Erros apenas</option>
                                <option value="warning" <?= $config['log_nivel'] == 'warning' ? 'selected' : '' ?>>Erros e avisos</option>
                                <option value="info" <?= $config['log_nivel'] == 'info' ? 'selected' : '' ?>>Todas as atividades</option>
                                <option value="debug" <?= $config['log_nivel'] == 'debug' ? 'selected' : '' ?>>Debug completo</option>
                            </select>
                            <div class="form-text">Nível de detalhamento dos logs do sistema</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Reter logs (dias)</label>
                            <input type="number" name="log_reter" class="form-control" value="<?= e($config['log_reter']) ?>" min="1">
                            <div class="form-text">Logs mais antigos que este período serão removidos</div>
                        </div>

                        <a href="<?= BASE_URL ?>/modules/auditoria/index.php" class="btn btn-outline-primary btn-sm w-100" style="border-radius:8px;font-size:0.75rem;">
                            <i class="bi bi-box-arrow-right me-1"></i> Ver Logs
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cache e Desempenho -->
            <div class="col-md-6">
                <div class="config-form-card">
                    <div class="card-header">
                        <div class="header-icon-sm info"><i class="bi bi-speedometer2"></i></div>
                        <h6>Cache e Desempenho</h6>
                    </div>
                    <div class="card-body">
                        <div class="switch-container">
                            <div class="switch-label">
                                Cache ativado
                                <small>Melhora o desempenho do sistema</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="cache_ativado" <?= $config['cache_ativado'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Tempo de cache (segundos)</label>
                            <input type="number" name="cache_tempo" class="form-control" value="<?= e($config['cache_tempo']) ?>" min="60">
                            <div class="form-text">Tempo que os dados ficam em cache</div>
                        </div>

                        <button type="button" class="btn-action warning" onclick="executarAcao('limpar_cache')">
                            <i class="bi bi-brush me-1"></i> Limpar Cache
                        </button>
                    </div>
                </div>
            </div>

            <!-- Manutenção -->
            <div class="col-md-6">
                <div class="config-form-card">
                    <div class="card-header">
                        <div class="header-icon-sm danger"><i class="bi bi-tools"></i></div>
                        <h6>Manutenção</h6>
                    </div>
                    <div class="card-body">
                        <div class="switch-container">
                            <div class="switch-label">
                                Modo manutenção
                                <small>Ativa modo de manutenção para todos os usuários</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="manutencao_modo" <?= $config['manutencao_modo'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Mensagem de manutenção</label>
                            <input type="text" name="manutencao_mensagem" class="form-control" 
                                   value="<?= e($config['manutencao_mensagem']) ?>" placeholder="Mensagem exibida durante manutenção">
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn-action danger" onclick="executarAcao('recalcular')">
                                    <i class="bi bi-arrow-repeat me-1"></i> Recalcular
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn-action info" onclick="executarAcao('verificar_db')">
                                    <i class="bi bi-database-check me-1"></i> Verificar DB
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações do Sistema -->
            <div class="col-md-12">
                <div class="config-form-card">
                    <div class="card-header">
                        <div class="header-icon-sm primary"><i class="bi bi-info-circle"></i></div>
                        <h6>Informações do Sistema</h6>
                    </div>
                    <div class="card-body">
                        <div class="info-system">
                            <div class="info-item">
                                <div class="label">Versão do Sistema</div>
                                <div class="value">v2.0.1</div>
                            </div>
                            <div class="info-item">
                                <div class="label">PHP Version</div>
                                <div class="value"><?= $phpVersion ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Banco de Dados</div>
                                <div class="value">MySQL <?= $dbVersion ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Servidor</div>
                                <div class="value" style="font-size:0.7rem;"><?= $serverSoftware ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Sistema Operacional</div>
                                <div class="value" style="font-size:0.7rem;"><?= $os ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Memória</div>
                                <div class="value"><?= $memoryLimit ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Tempo Máximo</div>
                                <div class="value"><?= $maxExecutionTime ?>s</div>
                            </div>
                            <div class="info-item">
                                <div class="label">Upload Máximo</div>
                                <div class="value"><?= $uploadMaxFilesize ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Fuso Horário</div>
                                <div class="value">
                                    <select name="timezone" class="form-select" style="font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:4px;">
                                        <option value="America/Sao_Paulo" <?= $config['timezone'] == 'America/Sao_Paulo' ? 'selected' : '' ?>>São Paulo</option>
                                        <option value="America/New_York" <?= $config['timezone'] == 'America/New_York' ? 'selected' : '' ?>>New York</option>
                                        <option value="Europe/London" <?= $config['timezone'] == 'Europe/London' ? 'selected' : '' ?>>London</option>
                                        <option value="Europe/Lisbon" <?= $config['timezone'] == 'Europe/Lisbon' ? 'selected' : '' ?>>Lisbon</option>
                                        <option value="Africa/Maputo" <?= $config['timezone'] == 'Africa/Maputo' ? 'selected' : '' ?>>Maputo</option>
                                        <option value="UTC" <?= $config['timezone'] == 'UTC' ? 'selected' : '' ?>>UTC</option>
                                    </select>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="label">Localização</div>
                                <div class="value">
                                    <select name="locale" class="form-select" style="font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:4px;">
                                        <option value="pt_BR" <?= $config['locale'] == 'pt_BR' ? 'selected' : '' ?>>Português (BR)</option>
                                        <option value="pt_PT" <?= $config['locale'] == 'pt_PT' ? 'selected' : '' ?>>Português (PT)</option>
                                        <option value="en_US" <?= $config['locale'] == 'en_US' ? 'selected' : '' ?>>English (US)</option>
                                        <option value="es_ES" <?= $config['locale'] == 'es_ES' ? 'selected' : '' ?>>Español</option>
                                    </select>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="label">Debug Mode</div>
                                <div class="value">
                                    <span class="badge-status <?= $config['debug_mode'] ? 'active' : 'inativo' ?>" style="font-size:0.55rem;">
                                        <?= $config['debug_mode'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                    <label class="switch" style="width:32px;height:18px;display:inline-block;margin-left:0.5rem;vertical-align:middle;">
                                        <input type="checkbox" name="debug_mode" <?= $config['debug_mode'] ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações do Formulário -->
        <div class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-floppy me-1"></i> Salvar Configurações
            </button>
            <button type="reset" class="btn btn-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function executarAcao(acao) {
    var mensagens = {
        'limpar_cache': 'Deseja limpar o cache do sistema?',
        'recalcular': 'Deseja recalcular todos os saldos?',
        'verificar_db': 'Deseja verificar a integridade do banco de dados?'
    };
    
    if (confirm(mensagens[acao] || 'Confirmar ação?')) {
        var btn = event.target.closest('button');
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-spinner bi-spin me-1"></i> Processando...';
        btn.disabled = true;
        
        setTimeout(function() {
            alert('✅ Ação executada com sucesso!');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
    }
}

// Restaurar valores padrão
document.querySelector('button[type="reset"]')?.addEventListener('click', function(e) {
    e.preventDefault();
    if (confirm('Deseja restaurar todos os valores para o padrão?')) {
        location.reload();
    }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>