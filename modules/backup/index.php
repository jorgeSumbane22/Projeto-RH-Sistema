<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Backup do sistema';
require __DIR__ . '/../../includes/header.php';

// Estatísticas do backup
$db = Database::getConnection();
$tabelas = ['departamentos', 'cargos', 'funcionarios', 'usuarios', 'documentos', 
            'presencas', 'ferias_licencas', 'folha_pagamento', 'vagas', 
            'candidatos', 'entrevistas', 'avaliacoes_desempenho', 'notificacoes', 'log_auditoria'];
$totalRegistros = 0;
foreach ($tabelas as $tabela) {
    $stmt = $db->query("SELECT COUNT(*) FROM `$tabela`");
    $totalRegistros += (int) $stmt->fetchColumn();
}

// Último backup (simulado - você pode armazenar no banco)
$ultimoBackup = date('d/m/Y H:i:s');
$tamanhoBackup = '2.4 MB';
?>

<style>
/* ============================================================
   BACKUP - DESIGN PREMIUM
   ============================================================ */

.backup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.backup-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.backup-header-left h5 i {
    color: var(--rh-accent);
}

.backup-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

/* ----- Stats Cards ----- */
.backup-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.backup-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.backup-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.backup-stat-card .stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.backup-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.backup-stat-card .stat-icon.success {
    background: rgba(20, 184, 166, 0.12);
    color: var(--rh-accent);
}
.backup-stat-card .stat-icon.warning {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.backup-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.backup-stat-card .stat-info .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.backup-stat-card .stat-info .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- Main Card ----- */
.backup-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 2rem;
    box-shadow: var(--rh-shadow);
    transition: all 0.3s ease;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.backup-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.backup-card .backup-icon {
    font-size: 4rem;
    color: var(--rh-accent);
    display: block;
    margin-bottom: 1rem;
}

.backup-card h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.5rem;
}

.backup-card .backup-description {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.backup-card .backup-info {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
    padding: 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.backup-card .backup-info .info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--rh-text-muted);
}

.backup-card .backup-info .info-item i {
    color: var(--rh-accent);
}

.backup-card .btn-backup {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    border-radius: 12px;
    padding: 0.8rem 3rem;
    font-weight: 600;
    font-size: 1rem;
    color: #fff;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
}

.backup-card .btn-backup:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(21, 94, 117, 0.35);
    color: #fff;
}

.backup-card .btn-backup i {
    font-size: 1.2rem;
}

.backup-card .btn-backup:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* ----- Alert ----- */
.backup-alert {
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.2);
    color: var(--rh-text-primary);
    text-align: left;
}

.backup-alert i {
    color: #f59e0b;
    font-size: 1.2rem;
    margin-top: 2px;
}

.backup-alert .alert-text {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.backup-alert .alert-text strong {
    color: var(--rh-text-primary);
}

/* ----- Tabelas List ----- */
.backup-tables-list {
    margin-top: 1.5rem;
    text-align: left;
}

.backup-tables-list .tables-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.backup-tables-list .table-tag {
    padding: 0.2rem 0.7rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.7rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.backup-tables-list .table-tag i {
    font-size: 0.6rem;
    color: var(--rh-accent);
}

/* ----- Responsive ----- */
@media (max-width: 767.98px) {
    .backup-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .backup-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .backup-stat-card {
        padding: 0.75rem 1rem;
    }
    .backup-stat-card .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .backup-stat-card .stat-info .stat-value {
        font-size: 1.1rem;
    }
    .backup-card {
        padding: 1.5rem;
    }
    .backup-card .backup-info {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    .backup-card .btn-backup {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 575.98px) {
    .backup-stats {
        grid-template-columns: 1fr;
    }
    .backup-header-left h5 {
        font-size: 1.1rem;
    }
    .backup-card {
        padding: 1.25rem;
    }
    .backup-card .backup-icon {
        font-size: 3rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="backup-header">
    <div class="backup-header-left">
        <h5>
            <i class="bi bi-cloud-arrow-down"></i> Backup do sistema
        </h5>
        <p>Exporte uma cópia JSON dos dados principais do RH-System</p>
    </div>
</div>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="backup-stats">
    <div class="backup-stat-card">
        <div class="stat-icon primary"><i class="bi bi-database"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalRegistros) ?></div>
            <div class="stat-label">Total Registos</div>
        </div>
    </div>
    <div class="backup-stat-card">
        <div class="stat-icon success"><i class="bi bi-table"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format(count($tabelas)) ?></div>
            <div class="stat-label">Tabelas</div>
        </div>
    </div>
    <div class="backup-stat-card">
        <div class="stat-icon warning"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $ultimoBackup ?></div>
            <div class="stat-label">Último Backup</div>
        </div>
    </div>
    <div class="backup-stat-card">
        <div class="stat-icon info"><i class="bi bi-file-earmark-zip"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $tamanhoBackup ?></div>
            <div class="stat-label">Tamanho Estimado</div>
        </div>
    </div>
</div>

<!-- ============================================================
     BACKUP CARD
     ============================================================ -->
<div class="backup-card">
    <span class="backup-icon"><i class="bi bi-cloud-arrow-down"></i></span>
    <h5>Exportar Backup Completo</h5>
    <p class="backup-description">
        O ficheiro não inclui palavras-passe em texto simples. 
        Guarde o ficheiro numa localização segura.
    </p>

    <!-- Alert -->
    <div class="backup-alert">
        <i class="bi bi-exclamation-triangle"></i>
        <div class="alert-text">
            <strong>Atenção:</strong> O backup contém dados sensíveis. 
            Guarde o ficheiro numa localização segura e teste regularmente a restauração.
        </div>
    </div>

    <!-- Info -->
    <div class="backup-info">
        <span class="info-item">
            <i class="bi bi-file-earmark-json"></i> Formato: JSON
        </span>
        <span class="info-item">
            <i class="bi bi-table"></i> <?= count($tabelas) ?> tabelas
        </span>
        <span class="info-item">
            <i class="bi bi-database"></i> <?= number_format($totalRegistros) ?> registos
        </span>
        <span class="info-item">
            <i class="bi bi-shield-check"></i> Dados anonimizados
        </span>
    </div>

    <!-- Botão -->
    <a class="btn-backup" href="<?= BASE_URL ?>/modules/backup/exportar.php" id="btnBackup">
        <i class="bi bi-download"></i> Descarregar Backup
    </a>

    <!-- Tabelas incluídas -->
    <div class="backup-tables-list">
        <div class="tables-grid">
            <?php foreach ($tabelas as $tabela): ?>
                <span class="table-tag">
                    <i class="bi bi-check-circle-fill"></i> <?= str_replace('_', ' ', $tabela) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnBackup = document.getElementById('btnBackup');
    
    btnBackup.addEventListener('click', function(e) {
        // Adiciona estado de carregamento
        const originalText = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> A gerar backup...';
        this.classList.add('disabled');
        this.style.pointerEvents = 'none';
        
        // Restaura após 2 segundos (o download já terá começado)
        setTimeout(() => {
            this.innerHTML = '<i class="bi bi-check-circle"></i> Backup gerado com sucesso!';
            this.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('disabled');
                this.style.pointerEvents = '';
                this.style.background = '';
            }, 3000);
        }, 2000);
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>