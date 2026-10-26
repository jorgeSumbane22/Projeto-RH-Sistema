<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$presencaModel = new Presenca();
$presenca = $presencaModel->buscarPorId($id);

if (!$presenca) {
    definirMensagem('danger', 'Registro não encontrado.');
    redirecionar('/modules/presenca/index.php');
}

/**
 * Função para formatar hora com segurança
 */
function formatarHoraSeguro($hora) {
    if (empty($hora) || $hora === null) {
        return '--:--';
    }
    return date('H:i', strtotime($hora));
}

/**
 * Função para calcular horas trabalhadas com segurança
 */
function calcularHorasTrabalhadasSeguro($presenca) {
    $total = 0;
    
    // Verifica se entrada e saída existem
    if (!empty($presenca['hora_entrada']) && !empty($presenca['hora_saida'])) {
        $entrada = strtotime($presenca['hora_entrada']);
        $saida = strtotime($presenca['hora_saida']);
        $total = ($saida - $entrada) / 3600;
        
        // Verifica se almoço existe (usando isset ou array_key_exists)
        if (isset($presenca['hora_saida_almoco']) && !empty($presenca['hora_saida_almoco']) && 
            isset($presenca['hora_retorno']) && !empty($presenca['hora_retorno'])) {
            $almoco = strtotime($presenca['hora_retorno']) - strtotime($presenca['hora_saida_almoco']);
            $total -= $almoco / 3600;
        }
    }
    return max(0, $total);
}

// Calcular horas trabalhadas
$totalHoras = calcularHorasTrabalhadasSeguro($presenca);

// Valores seguros para exibição
$horaEntrada = isset($presenca['hora_entrada']) ? formatarHoraSeguro($presenca['hora_entrada']) : '--:--';
$horaSaidaAlmoco = isset($presenca['hora_saida_almoco']) ? formatarHoraSeguro($presenca['hora_saida_almoco']) : '--:--';
$horaRetorno = isset($presenca['hora_retorno']) ? formatarHoraSeguro($presenca['hora_retorno']) : '--:--';
$horaSaida = isset($presenca['hora_saida']) ? formatarHoraSeguro($presenca['hora_saida']) : '--:--';
$horasExtras = isset($presenca['horas_extras']) ? (float) $presenca['horas_extras'] : 0;
$status = isset($presenca['status']) ? $presenca['status'] : 'falta';
$observacao = isset($presenca['observacao']) ? $presenca['observacao'] : '';
$funcionarioNome = isset($presenca['funcionario_nome']) ? $presenca['funcionario_nome'] : 'Desconhecido';
$data = isset($presenca['data']) ? $presenca['data'] : date('Y-m-d');

$pageTitle = 'Visualizar Presença';
require __DIR__ . '/../../includes/header.php';
?>

<style>
.visualizar-container {
    max-width: 600px;
    margin: 0 auto;
}

.visualizar-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.5rem;
    box-shadow: var(--rh-shadow);
    transition: all 0.3s ease;
}

.visualizar-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.visualizar-card .card-header {
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--rh-border);
    margin-bottom: 1.5rem;
}

.visualizar-card .card-header h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin: 0;
}

.visualizar-card .card-header h5 i {
    color: var(--rh-accent);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem 1.5rem;
}

.info-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.info-item.full {
    grid-column: 1 / -1;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item .info-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
}

.info-item .info-label i {
    margin-right: 4px;
}

.info-item .info-value {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--rh-text-primary);
}

.info-item .info-value .badge-status {
    font-size: 0.7rem;
    padding: 0.2rem 0.6rem;
}

.btn-group-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--rh-border);
}

.btn-group-actions .btn {
    border-radius: 10px;
    padding: 0.4rem 1.2rem;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.btn-group-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.btn-group-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21,94,117,0.3);
}

.btn-group-actions .btn-secondary {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
}

.btn-group-actions .btn-secondary:hover {
    background: var(--bs-tertiary-bg);
}

@media (max-width: 575.98px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    .visualizar-card {
        padding: 1rem;
    }
    .btn-group-actions .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="visualizar-container">
    <div class="visualizar-card">
        <!-- Header -->
        <div class="card-header">
            <h5>
                <i class="bi bi-clock-history me-2"></i> Detalhes da Presença
            </h5>
        </div>

        <!-- Informações -->
        <div class="info-grid">
            <!-- Funcionário -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-person"></i> Funcionário</span>
                <span class="info-value"><?= e($funcionarioNome) ?></span>
            </div>
            
            <!-- Data -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-calendar3"></i> Data</span>
                <span class="info-value"><?= formatarData($data) ?></span>
            </div>
            
            <!-- Entrada -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-arrow-right"></i> Entrada</span>
                <span class="info-value"><?= $horaEntrada ?></span>
            </div>
            
            <!-- Saída Almoço -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-arrow-left"></i> Saída Almoço</span>
                <span class="info-value"><?= $horaSaidaAlmoco ?></span>
            </div>
            
            <!-- Retorno -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-arrow-right"></i> Retorno</span>
                <span class="info-value"><?= $horaRetorno ?></span>
            </div>
            
            <!-- Saída -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-arrow-left"></i> Saída</span>
                <span class="info-value"><?= $horaSaida ?></span>
            </div>
            
            <!-- Total Horas -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-clock"></i> Total Horas</span>
                <span class="info-value"><?= number_format($totalHoras, 1) ?>h</span>
            </div>
            
            <!-- Horas Extras -->
            <div class="info-item">
                <span class="info-label"><i class="bi bi-clock-history"></i> Horas Extras</span>
                <span class="info-value">
                    <?php if ($horasExtras > 0): ?>
                        <span class="badge bg-warning text-dark">+<?= number_format($horasExtras, 1) ?>h</span>
                    <?php else: ?>
                        <span class="text-muted">0h</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <!-- Status -->
            <div class="info-item full">
                <span class="info-label"><i class="bi bi-circle"></i> Status</span>
                <span class="info-value">
                    <?php 
                    $statusLabels = [
                        'presente' => ['label' => '✅ Presente', 'class' => 'presente'],
                        'falta' => ['label' => '❌ Falta', 'class' => 'falta'],
                        'atraso' => ['label' => '⏰ Atraso', 'class' => 'atraso'],
                        'justificado' => ['label' => '📋 Justificado', 'class' => 'justificado'],
                    ];
                    $statusInfo = $statusLabels[$status] ?? ['label' => ucfirst($status), 'class' => 'falta'];
                    ?>
                    <span class="badge-status badge-status-presenca <?= $statusInfo['class'] ?>">
                        <?= $statusInfo['label'] ?>
                    </span>
                </span>
            </div>
            
            <!-- Observação -->
            <?php if (!empty($observacao)): ?>
                <div class="info-item full">
                    <span class="info-label"><i class="bi bi-chat"></i> Observação</span>
                    <span class="info-value"><?= e($observacao) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botões -->
        <div class="btn-group-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
            <?php if (usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN])): ?>
                <a href="editar.php?id=<?= $id ?>" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Badge Status CSS adicional -->
<style>
.badge-status-presenca.presente { background: #d1fae5; color: #065f46; }
.badge-status-presenca.falta { background: #fee2e2; color: #991b1b; }
.badge-status-presenca.atraso { background: #fef3c7; color: #92400e; }
.badge-status-presenca.justificado { background: #dbeafe; color: #1e40af; }

[data-bs-theme="dark"] .badge-status-presenca.presente { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .badge-status-presenca.falta { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .badge-status-presenca.atraso { background: #92400e; color: #fef3c7; }
[data-bs-theme="dark"] .badge-status-presenca.justificado { background: #1e40af; color: #dbeafe; }
</style>

<?php require __DIR__ . '/../../includes/footer.php'; ?>