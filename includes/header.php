<?php
/**
 * header.php - Início do layout comum (HTML head + sidebar + topbar)
 * Espera que a página que o inclui já tenha definido:
 *   $pageTitle (string) - título da página
 * E que bootstrap.php + exigirLogin() já tenham sido executados.
 */

$notificacaoModel = new Notificacao();
$notificacaoModel->gerarNotificacoesAutomaticas();
$naoLidas = $notificacaoModel->contarNaoLidas(usuarioAtualId());

// Determina o tema
$temaSalvo = $_SESSION['usuario_tema'] ?? 'claro';
$temaLocalStorage = $_COOKIE['rh_tema'] ?? 'light';
$tema = ($temaSalvo === 'escuro' || $temaLocalStorage === 'dark') ? 'dark' : 'light';

// Saudação baseada na hora
$hora = (int) date('H');
$saudacao = $hora < 12 ? '🌅 Bom dia' : ($hora < 18 ? '☀️ Boa tarde' : '🌙 Boa noite');

// Nome do usuário
$nomeUsuario = usuarioAtualNome();
$perfilUsuario = nomePerfil(usuarioAtualPerfil());

// Data atual formatada
$dataAtual = date('d/m/Y');
$diaSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'][(int) date('w')];
$horaAtual = date('H:i:s');

// Contar funcionários ativos (para exibir no header)
$funcionarioModel = new Funcionario();
$totalAtivos = $funcionarioModel->contarPorStatus('ativo');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="<?= $tema ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Sistema de RH') ?> · Sistema de RH</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos customizados -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/responsive.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/print.css" rel="stylesheet" media="print">
    
    <script>
        window.RH_BASE_URL = <?= json_encode(BASE_URL) ?>;
        // Aplica o tema salvo antes do carregamento para evitar flash
        (function() {
            var tema = localStorage.getItem('rh_tema');
            if (!tema) {
                tema = <?= json_encode($tema) ?>;
            }
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
    
    <style>
        /* ============================================================
           HEADER PREMIUM - ESTILOS ADICIONAIS
           ============================================================ */
        
        /* Topbar melhorado */
        .rh-topbar-premium {
            min-height: 72px;
            padding: 0.6rem 1.5rem;
            background: var(--rh-topbar-bg);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--rh-border);
            box-shadow: 0 2px 16px rgba(15,41,64,.04);
            color: var(--rh-text-primary);
            position: sticky;
            top: 0;
            z-index: 1020;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        /* Lado esquerdo - Saudação e título */
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }
        
        .topbar-left .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--rh-text-primary);
            font-size: 1.5rem;
            padding: 0.25rem;
            cursor: pointer;
        }
        
        .topbar-left .menu-toggle:hover {
            color: var(--rh-primary);
        }
        
        .topbar-left .greeting-wrapper {
            display: flex;
            flex-direction: column;
        }
        
        .topbar-left .greeting {
            font-weight: 700;
            font-size: 1rem;
            color: var(--rh-text-primary);
            line-height: 1.2;
        }
        
        .topbar-left .greeting .user-name {
            color: var(--rh-primary);
        }
        
        .topbar-left .greeting-sub {
            font-size: 0.7rem;
            color: var(--rh-text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .topbar-left .greeting-sub .separator {
            color: var(--rh-border);
        }
        
        .topbar-left .page-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--rh-text-primary);
            margin: 0;
            padding-left: 1rem;
            border-left: 2px solid var(--rh-border);
        }
        
        /* Lado direito - Status e ações */
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        /* Status do sistema */
        .system-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.7rem;
            border-radius: 50px;
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--rh-border);
        }
        
        .system-status .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--rh-success);
            animation: pulse-dot 2s infinite;
        }
        
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        
        .system-status .status-label {
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--rh-text-muted);
        }
        
        .system-status .status-time {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--rh-text-primary);
            font-variant-numeric: tabular-nums;
        }
        
        /* Badge de funcionários */
        .employees-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.7rem;
            border-radius: 50px;
            background: var(--rh-primary);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .employees-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(21, 94, 117, 0.3);
            color: #fff;
        }
        
        .employees-badge i {
            font-size: 0.65rem;
        }
        
        /* Botão de tema */
        .btn-theme-toggle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid var(--rh-border);
            background: var(--rh-card-bg);
            color: var(--rh-text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-theme-toggle:hover {
            background: var(--rh-primary);
            border-color: var(--rh-primary);
            color: #fff;
            transform: rotate(30deg);
        }
        
        /* Notificações */
        .btn-notifications {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid var(--rh-border);
            background: var(--rh-card-bg);
            color: var(--rh-text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-notifications:hover {
            background: var(--bs-tertiary-bg);
            border-color: var(--rh-primary);
        }
        
        .btn-notifications .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--rh-danger);
            color: #fff;
            font-size: 0.55rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid var(--rh-card-bg);
        }
        
        /* Dropdown usuário */
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.2rem 0.8rem 0.2rem 0.2rem;
            border-radius: 50px;
            border: 1px solid var(--rh-border);
            background: var(--rh-card-bg);
            color: var(--rh-text-primary);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }
        
        .user-dropdown-toggle:hover {
            background: var(--bs-tertiary-bg);
            border-color: var(--rh-primary);
            color: var(--rh-text-primary);
        }
        
        .user-dropdown-toggle .user-name-display {
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .user-dropdown-toggle .user-perfil {
            font-size: 0.6rem;
            color: var(--rh-text-muted);
            font-weight: 500;
        }
        
        .user-dropdown-toggle .avatar-circle {
            width: 32px;
            height: 32px;
            font-size: 0.7rem;
        }
        
        /* Dropdown menu */
        .dropdown-menu-premium {
            border: 1px solid var(--rh-border);
            border-radius: 12px;
            box-shadow: var(--rh-shadow-hover);
            padding: 0.5rem;
            min-width: 220px;
        }
        
        .dropdown-menu-premium .dropdown-item {
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-menu-premium .dropdown-item:hover {
            background: var(--bs-tertiary-bg);
        }
        
        .dropdown-menu-premium .dropdown-item i {
            width: 1.2rem;
            text-align: center;
        }
        
        .dropdown-menu-premium .dropdown-divider {
            border-color: var(--rh-border);
        }
        
        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 1199.98px) {
            .topbar-left .page-title {
                font-size: 0.95rem;
                padding-left: 0.75rem;
            }
        }
        
        @media (max-width: 991.98px) {
            .topbar-left .menu-toggle {
                display: block;
            }
            .topbar-left .page-title {
                font-size: 0.85rem;
                padding-left: 0.5rem;
            }
            .system-status .status-label {
                display: none;
            }
            .topbar-left .greeting-sub .separator {
                display: none;
            }
        }
        
        @media (max-width: 767.98px) {
            .rh-topbar-premium {
                padding: 0.5rem 1rem;
                min-height: 60px;
            }
            .topbar-left .greeting {
                font-size: 0.85rem;
            }
            .topbar-left .page-title {
                font-size: 0.75rem;
                padding-left: 0.5rem;
                display: none;
            }
            .system-status {
                padding: 0.2rem 0.5rem;
            }
            .system-status .status-time {
                font-size: 0.55rem;
            }
            .employees-badge {
                font-size: 0.6rem;
                padding: 0.15rem 0.5rem;
            }
            .employees-badge span {
                display: none;
            }
            .user-dropdown-toggle .user-name-display {
                font-size: 0.75rem;
                display: none;
            }
            .user-dropdown-toggle .user-perfil {
                display: none;
            }
            .user-dropdown-toggle .avatar-circle {
                width: 28px;
                height: 28px;
                font-size: 0.6rem;
            }
            .topbar-right {
                gap: 0.4rem;
            }
            .btn-theme-toggle,
            .btn-notifications {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
            }
            .topbar-left .greeting {
                font-size: 0.75rem;
            }
            .topbar-left .greeting-sub {
                font-size: 0.6rem;
            }
            .topbar-left .greeting-sub span:not(:first-child) {
                display: none;
            }
        }
        
        @media (max-width: 575.98px) {
            .topbar-left .greeting-wrapper {
                max-width: 120px;
            }
            .topbar-left .greeting-sub {
                font-size: 0.55rem;
            }
            .topbar-right .system-status {
                display: none;
            }
            .topbar-right .employees-badge {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="rh-content flex-grow-1">
        <!-- Topbar Premium -->
        <div class="rh-topbar-premium">
            <input type="hidden" id="csrfGlobal" value="<?= e(csrfToken()) ?>">
            
            <!-- Lado Esquerdo -->
            <div class="topbar-left">
                <button id="btnToggleSidebar" class="menu-toggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="greeting-wrapper">
                    <div class="greeting">
                        <?= $saudacao ?>, <span class="user-name"><?= e($nomeUsuario) ?></span>
                    </div>
                    <div class="greeting-sub">
                        <span><i class="bi bi-calendar3"></i> <?= $diaSemana ?>, <?= $dataAtual ?></span>
                        <span class="separator">|</span>
                        <span><i class="bi bi-clock"></i> <span id="relogioHeader"><?= $horaAtual ?></span></span>
                        <span class="separator">|</span>
                        <span><i class="bi bi-person-badge"></i> <?= e($perfilUsuario) ?></span>
                    </div>
                </div>
                
                <div class="page-title d-none d-lg-block">
                    <?= e($pageTitle ?? 'Dashboard') ?>
                </div>
            </div>
            
            <!-- Lado Direito -->
            <div class="topbar-right">
                <!-- Status do Sistema -->
                <div class="system-status">
                    <span class="status-dot"></span>
                    <span class="status-label">Sistema</span>
                    <span class="status-time" id="statusTime">Online</span>
                </div>
                
                <!-- Total Funcionários -->
                <a href="<?= BASE_URL ?>/modules/funcionarios/index.php" class="employees-badge" title="Ver todos os funcionários">
                    <i class="bi bi-people"></i>
                    <span><?= number_format($totalAtivos) ?> ativos</span>
                </a>
                
                <!-- Alternar tema -->
                <button type="button" class="btn-theme-toggle js-toggle-tema" title="Alternar tema">
                    <i class="bi <?= $tema === 'dark' ? 'bi-sun' : 'bi-moon-stars' ?>"></i>
                </button>
                
                <!-- Notificações -->
                <div class="dropdown">
                    <button class="btn-notifications" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($naoLidas > 0): ?>
                            <span class="notification-badge"><?= $naoLidas > 9 ? '9+' : $naoLidas ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-premium p-0" style="width: 340px;">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="border-color:var(--rh-border);">
                            <strong class="small">Notificações</strong>
                            <a href="<?= BASE_URL ?>/modules/notificacoes/index.php" class="small text-decoration-none" style="color:var(--rh-primary);">
                                Ver todas <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                        <div style="max-height: 340px; overflow-y: auto;">
                            <?php
                            $listaRecente = $notificacaoModel->listarPorUsuario(usuarioAtualId(), 5);
                            if (empty($listaRecente)):
                            ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                                    <span class="small">Nenhuma notificação</span>
                                </div>
                            <?php else: foreach ($listaRecente as $n): ?>
                                <div class="dropdown-item-text border-bottom py-2 px-3 <?= !$n['lida'] ? 'bg-light' : '' ?>" style="border-color:var(--rh-border);">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="small fw-semibold"><?= e($n['titulo']) ?></div>
                                        <?php if (!$n['lida']): ?>
                                            <span class="badge bg-primary rounded-pill" style="font-size:0.5rem;">Nova</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted"><?= e($n['mensagem']) ?></div>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-clock"></i> <?= formatarDataHora($n['criado_em']) ?>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Usuário -->
                <div class="dropdown">
                    <a href="#" class="user-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-circle"><?= e(strtoupper(substr($nomeUsuario, 0, 1))) ?></span>
                        <div class="d-none d-sm-block">
                            <div class="user-name-display"><?= e($nomeUsuario) ?></div>
                            <div class="user-perfil"><?= e($perfilUsuario) ?></div>
                        </div>
                        <i class="bi bi-chevron-down" style="font-size:0.6rem;color:var(--rh-text-muted);"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-premium">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                <i class="bi bi-person"></i> <?= e($nomeUsuario) ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/modules/perfil/index.php">
                                <i class="bi bi-person-gear me-2"></i>Meu Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/modules/dashboard/index.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/modules/auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <main class="p-4">
            <!-- Container para mensagens flash -->
            <div id="message-container">
                <?php exibirMensagem(); ?>
            </div>