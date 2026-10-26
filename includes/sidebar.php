<?php
/**
 * sidebar.php
 * Menu de navegação lateral premium com design moderno e profissional
 * Cada item só aparece para os perfis autorizados
 */
$rotaAtual = $_SERVER['SCRIPT_NAME'];
function linkAtivo(string $trecho, string $rotaAtual): string
{
    return str_contains($rotaAtual, $trecho) ? 'active' : '';
}

// Obter informações do usuário para o perfil
$nomeUsuario = usuarioAtualNome();
$perfilUsuario = usuarioAtualPerfil();
$perfilLabel = nomePerfil($perfilUsuario);
$avatarInicial = strtoupper(substr($nomeUsuario, 0, 1));

// Cores para cada perfil
$perfilCores = [
    'super_admin' => '#8b5cf6',
    'administrador_rh' => '#155e75',
    'gestor' => '#0e7490',
    'funcionario' => '#14b8a6',
    'auditor' => '#f59e0b',
];
$corPerfil = $perfilCores[$perfilUsuario] ?? '#155e75';
?>
<nav class="rh-sidebar">
    <!-- ============================================================
         BRAND / LOGO
         ============================================================ -->
    <div class="brand">
        <div class="brand-icon">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="brand-text">
            <span class="brand-title">RH System</span>
            <span class="brand-version">v2.0.1</span>
        </div>
    </div>

    <!-- ============================================================
         PERFIL DO USUÁRIO
         ============================================================ -->
    <div class="sidebar-user">
        <div class="user-avatar" style="background:<?= $corPerfil ?>;">
            <?= $avatarInicial ?>
            <span class="user-status"></span>
        </div>
        <div class="user-info">
            <div class="user-name"><?= e($nomeUsuario) ?></div>
            <div class="user-role">
                <span class="role-badge" style="background:<?= $corPerfil ?>20;color:<?= $corPerfil ?>;">
                    <i class="bi bi-shield-check"></i> <?= e($perfilLabel) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ============================================================
         MENU DE NAVEGAÇÃO
         ============================================================ -->
    <div class="nav flex-column py-2">
        <!-- Dashboard -->
        <a class="nav-link <?= linkAtivo('/dashboard/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/dashboard/index.php">
            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
            <span class="nav-text">Dashboard</span>
        </a>

        <!-- PESSOAS -->
        <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR])): ?>
        <div class="nav-section-title">Pessoas</div>
        
        <!-- Funcionários -->
        <a class="nav-link <?= linkAtivo('/funcionarios/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/funcionarios/index.php">
            <span class="nav-icon"><i class="bi bi-people"></i></span>
            <span class="nav-text">Funcionários</span>
            <?php 
            try {
                $totalFuncionarios = (new Funcionario())->contarTotal();
                if ($totalFuncionarios > 0): ?>
                    <span class="nav-badge"><?= number_format($totalFuncionarios) ?></span>
                <?php endif;
            } catch (Exception $e) {}
            ?>
        </a>
        <?php endif; ?>

        <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH])): ?>
        <!-- Departamentos -->
        <a class="nav-link <?= linkAtivo('/departamentos/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/departamentos/index.php">
            <span class="nav-icon"><i class="bi bi-diagram-3"></i></span>
            <span class="nav-text">Departamentos</span>
        </a>
        
        <!-- Cargos -->
        <a class="nav-link <?= linkAtivo('/cargos/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/cargos/index.php">
            <span class="nav-icon"><i class="bi bi-briefcase"></i></span>
            <span class="nav-text">Cargos</span>
        </a>
        <?php endif; ?>

        <!-- ROTINA -->
        <?php if (usuarioAtualPerfil() !== PERFIL_AUDITOR): ?>
        <div class="nav-section-title">Rotina</div>
        
        <!-- Presença / Ponto -->
        <a class="nav-link <?= linkAtivo('/presenca/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/presenca/index.php">
            <span class="nav-icon"><i class="bi bi-clock-history"></i></span>
            <span class="nav-text">Presença / Ponto</span>
        </a>
        
        <!-- Férias e Licenças -->
        <a class="nav-link <?= linkAtivo('/ferias/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/ferias/index.php">
            <span class="nav-icon"><i class="bi bi-umbrella"></i></span>
            <span class="nav-text">Férias e Licenças</span>
            <?php 
            try {
                $feriasPendentes = (new Ferias())->contarPendentes();
                if ($feriasPendentes > 0): ?>
                    <span class="nav-badge warning"><?= $feriasPendentes ?></span>
                <?php endif;
            } catch (Exception $e) {}
            ?>
        </a>
        <?php endif; ?>

        <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH])): ?>
        <!-- Folha de Pagamento -->
        <a class="nav-link <?= linkAtivo('/folha-pagamento/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/folha-pagamento/index.php">
            <span class="nav-icon"><i class="bi bi-cash-stack"></i></span>
            <span class="nav-text">Folha de Pagamento</span>
        </a>
        <?php endif; ?>

        <!-- RECRUTAMENTO -->
        <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH])): ?>
        <div class="nav-section-title">Recrutamento</div>
        
        <!-- Vagas e Candidatos -->
        <a class="nav-link <?= linkAtivo('/recrutamento/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/recrutamento/index.php">
            <span class="nav-icon"><i class="bi bi-person-plus"></i></span>
            <span class="nav-text">Vagas e Candidatos</span>
            <?php 
            try {
                $stmt = Database::getConnection()->query("SELECT COUNT(*) FROM vagas WHERE status = 'aberta'");
                $vagasAbertas = (int) $stmt->fetchColumn();
                if ($vagasAbertas > 0): ?>
                    <span class="nav-badge success"><?= $vagasAbertas ?></span>
                <?php endif;
            } catch (Exception $e) {}
            ?>
        </a>
        <?php endif; ?>

        <!-- DESEMPENHO -->
        <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR])): ?>
        <div class="nav-section-title">Desempenho</div>
        
        <!-- Avaliações -->
        <a class="nav-link <?= linkAtivo('/avaliacao/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/avaliacao/index.php">
            <span class="nav-icon"><i class="bi bi-star"></i></span>
            <span class="nav-text">Avaliações</span>
            <?php 
            try {
                $stmt = Database::getConnection()->query("SELECT COUNT(*) FROM funcionarios f WHERE f.status = 'ativo' AND NOT EXISTS (SELECT 1 FROM avaliacoes_desempenho a WHERE a.funcionario_id = f.id AND YEAR(a.data_avaliacao) = YEAR(CURDATE()))");
                $avaliacoesPendentes = (int) $stmt->fetchColumn();
                if ($avaliacoesPendentes > 0): ?>
                    <span class="nav-badge warning"><?= $avaliacoesPendentes ?></span>
                <?php endif;
            } catch (Exception $e) {}
            ?>
        </a>
        <?php endif; ?>

        <!-- DOCUMENTOS -->
        <?php if (usuarioAtualPerfil() !== PERFIL_AUDITOR): ?>
        <div class="nav-section-title">Documentos</div>
        
        <!-- Documentos -->
        <a class="nav-link <?= linkAtivo('/documentos/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/documentos/index.php">
            <span class="nav-icon"><i class="bi bi-file-earmark-text"></i></span>
            <span class="nav-text">Documentos</span>
        </a>
        <?php endif; ?>

        <!-- ANÁLISE -->
        <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR])): ?>
        <div class="nav-section-title">Análise</div>
        
        <!-- Relatórios -->
        <a class="nav-link <?= linkAtivo('/relatorios/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/relatorios/index.php">
            <span class="nav-icon"><i class="bi bi-bar-chart"></i></span>
            <span class="nav-text">Relatórios</span>
        </a>
        <?php endif; ?>

        <!-- SISTEMA -->
        <?php if (usuarioPodeGerirUtilizadores()): ?>
        <div class="nav-section-title">Sistema</div>
        
        <!-- Usuários e Acessos -->
        <a class="nav-link <?= linkAtivo('/usuarios/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/usuarios/index.php">
            <span class="nav-icon"><i class="bi bi-shield-lock"></i></span>
            <span class="nav-text">Usuários e Acessos</span>
        </a>
        <?php endif; ?>
        
        <?php if (usuarioTemPerfil([PERFIL_SUPER_ADMIN])): ?>
        <!-- Permissões -->
        <a class="nav-link <?= linkAtivo('/usuarios/permissoes', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/usuarios/permissoes.php">
            <span class="nav-icon"><i class="bi bi-shield-check"></i></span>
            <span class="nav-text">Permissões</span>
        </a>
        <?php endif; ?>
        
        <?php if (usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_AUDITOR, PERFIL_SUPER_ADMIN])): ?>
        <!-- Log de Auditoria -->
        <a class="nav-link <?= linkAtivo('/auditoria/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/auditoria/index.php">
            <span class="nav-icon"><i class="bi bi-journal-text"></i></span>
            <span class="nav-text">Log de Auditoria</span>
        </a>
        <?php endif; ?>
        
        <?php if (usuarioTemPerfil([PERFIL_SUPER_ADMIN])): ?>
        <!-- Backup -->
        <a class="nav-link <?= linkAtivo('/backup/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/backup/index.php">
            <span class="nav-icon"><i class="bi bi-cloud-arrow-down"></i></span>
            <span class="nav-text">Backup</span>
        </a>
        <?php endif; ?>
        
        <!-- CONFIGURAÇÕES (apenas Super Admin) -->
        <?php if (usuarioTemPerfil([PERFIL_SUPER_ADMIN])): ?>
        <div class="nav-section-title">Configurações</div>
        
        <!-- Configurações do Sistema -->
        <a class="nav-link <?= linkAtivo('/configuracoes/', $rotaAtual) ?>" href="<?= BASE_URL ?>/modules/configuracoes/index.php">
            <span class="nav-icon"><i class="bi bi-gear"></i></span>
            <span class="nav-text">Configurações</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         FOOTER DO SIDEBAR
         ============================================================ -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-content">
            <span class="footer-text">
                <i class="bi bi-circle-fill"></i> Sistema Online
            </span>
            <span class="footer-version">v2.0.1</span>
        </div>
    </div>
</nav>

<style>
/* ============================================================
   SIDEBAR PREMIUM - DESIGN MODERNO E PROFISSIONAL
   ============================================================ */

/* ----- BRAND MELHORADO ----- */
.rh-sidebar .brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem 1.15rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.rh-sidebar .brand .brand-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #14b8a6, #0d9488);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    box-shadow: 0 4px 12px rgba(20,184,166,0.25);
}

.rh-sidebar .brand .brand-title {
    font-weight: 700;
    font-size: 1.05rem;
    color: #fff;
    letter-spacing: -0.3px;
}

.rh-sidebar .brand .brand-version {
    font-size: 0.5rem;
    color: rgba(255,255,255,0.35);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-top: 1px;
}

/* ----- PERFIL DO USUÁRIO ----- */
.sidebar-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.9rem 1.15rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    transition: all 0.3s ease;
}

.sidebar-user:hover {
    background: rgba(255,255,255,0.03);
}

.sidebar-user .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    color: #fff;
    position: relative;
    flex-shrink: 0;
}

.sidebar-user .user-avatar .user-status {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #10b981;
    border: 2px solid #0b1a24;
    animation: pulse-dot 2s infinite;
}

.sidebar-user .user-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-user .role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.1rem 0.5rem;
    border-radius: 50px;
    font-size: 0.55rem;
    font-weight: 600;
    letter-spacing: 0.03em;
}

.sidebar-user .role-badge i {
    font-size: 0.5rem;
}

/* ----- NAV SECTION TITLE ----- */
.rh-sidebar .nav-section-title {
    color: rgba(255,255,255,0.3);
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 0.8rem 1.15rem 0.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rh-sidebar .nav-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.05);
}

/* ----- NAV LINKS ----- */
.rh-sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 0.85rem;
    margin: 1px 0.65rem;
    border-radius: 8px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    transition: all 0.25s ease;
    position: relative;
}

.rh-sidebar .nav-link .nav-icon {
    width: 20px;
    text-align: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.25s ease;
}

.rh-sidebar .nav-link .nav-text {
    font-size: 0.82rem;
    font-weight: 500;
    flex: 1;
}

.rh-sidebar .nav-link .nav-badge {
    font-size: 0.5rem;
    font-weight: 700;
    padding: 0.1rem 0.4rem;
    border-radius: 50px;
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.6);
    min-width: 18px;
    text-align: center;
}

.rh-sidebar .nav-link .nav-badge.warning {
    background: rgba(245,158,11,0.2);
    color: #fcd34d;
}

.rh-sidebar .nav-link .nav-badge.success {
    background: rgba(20,184,166,0.2);
    color: #6ee7b7;
}

.rh-sidebar .nav-link .nav-badge.danger {
    background: rgba(239,68,68,0.2);
    color: #fca5a5;
}

.rh-sidebar .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.06);
    text-decoration: none;
}

.rh-sidebar .nav-link:hover .nav-icon {
    transform: scale(1.05);
}

.rh-sidebar .nav-link.active {
    color: #fff;
    background: rgba(255,255,255,0.08);
    box-shadow: inset 3px 0 0 #14b8a6;
}

.rh-sidebar .nav-link.active .nav-icon {
    color: #14b8a6;
}

/* ----- FOOTER ----- */
.sidebar-footer {
    padding: 0.6rem 1.15rem;
    border-top: 1px solid rgba(255,255,255,0.06);
    margin-top: auto;
}

.sidebar-footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.6rem;
    color: rgba(255,255,255,0.25);
}

.sidebar-footer-content .footer-text {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.sidebar-footer-content .footer-text i {
    font-size: 0.4rem;
    color: #10b981;
}

.sidebar-footer-content .footer-version {
    font-weight: 600;
}

/* ----- ANIMAÇÕES ----- */
@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

/* ----- SCROLLBAR ----- */
.rh-sidebar::-webkit-scrollbar {
    width: 3px;
}

.rh-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.rh-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
}

.rh-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.2);
}

/* ============================================================
   RESPONSIVE - MANTENDO SEU CSS
   ============================================================ */
@media (max-width: 991.98px) {
    .rh-sidebar .brand .brand-title {
        font-size: 0.95rem;
    }
    
    .sidebar-user .user-name {
        font-size: 0.8rem;
    }
    
    .rh-sidebar .nav-link .nav-text {
        font-size: 0.78rem;
    }
}

@media (max-width: 575.98px) {
    .rh-sidebar .brand .brand-title {
        font-size: 0.85rem;
    }
    
    .sidebar-user .user-name {
        font-size: 0.75rem;
    }
    
    .rh-sidebar .nav-link .nav-text {
        font-size: 0.75rem;
    }
}

/* ============================================================
   DARK MODE - JÁ SUPORTADO PELO SEU CSS
   ============================================================ */
[data-bs-theme="dark"] .rh-sidebar .brand .brand-version {
    color: rgba(255,255,255,0.25);
}

[data-bs-theme="dark"] .sidebar-footer-content {
    color: rgba(255,255,255,0.15);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('btnToggleSidebar');
    const sidebar = document.querySelector('.rh-sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
});
</script>