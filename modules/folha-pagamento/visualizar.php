<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR]);

$id = (int) ($_GET['id'] ?? 0);
$folhaModel = new FolhaPagamento();
$folha = $folhaModel->buscarPorId($id);

if (!$folha) {
    definirMensagem('danger', 'Folha não encontrada.');
    redirecionar('/modules/folha-pagamento/index.php');
}

$pageTitle = 'Detalhes da Folha - ' . $folha['funcionario_nome'];

require __DIR__ . '/../../includes/header.php';
?>

<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.detail-item {
    padding: 0.75rem 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: 10px;
    border-left: 3px solid var(--rh-border);
}

.detail-item .label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.detail-item .value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-top: 0.15rem;
}

.detail-item .value.positive {
    color: var(--rh-accent);
}

.detail-item .value.negative {
    color: var(--rh-danger);
}

.detail-item.highlight {
    background: var(--rh-primary);
    border-left-color: var(--rh-accent);
}

.detail-item.highlight .label {
    color: rgba(255,255,255,0.7);
}

.detail-item.highlight .value {
    color: #fff;
    font-size: 1.2rem;
}

.view-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.view-actions .btn {
    border-radius: 8px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
}

@media (max-width: 576px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .no-print { display: none !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h5 class="mb-0">
        <i class="bi bi-file-earmark-text me-2" style="color:var(--rh-primary);"></i>
        Detalhes da Folha
    </h5>
    <div class="view-actions">
        <a href="editar.php?id=<?= $id ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="recibo.php?id=<?= $id ?>" class="btn btn-success btn-sm" target="_blank">
            <i class="bi bi-receipt"></i> Recibo
        </a>
        <a href="index.php?mes=<?= e($folha['mes_referencia']) ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Employee Info -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person me-2"></i> Dados do Funcionário
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar-circle" style="width:50px;height:50px;font-size:1.2rem;background:linear-gradient(135deg,var(--rh-primary),var(--rh-primary-light));color:#fff;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:700;text-transform:uppercase;">
                        <?= e(strtoupper(substr($folha['funcionario_nome'], 0, 1))) ?>
                    </span>
                    <div>
                        <h6 class="mb-0"><?= e($folha['funcionario_nome']) ?></h6>
                        <small class="text-muted"><?= e($folha['cargo_titulo'] ?? 'Sem cargo') ?></small>
                    </div>
                </div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="label">Departamento</div>
                        <div class="value"><?= e($folha['departamento_nome'] ?? '-') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Mês Referência</div>
                        <div class="value"><?= formatarData($folha['mes_referencia'] . '-01') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Status</div>
                        <div class="value">
                            <span class="badge-status <?= $folha['status'] ?>">
                                <i class="bi <?= $folha['status'] === 'pago' ? 'bi-check-circle' : 'bi-clock' ?>"></i>
                                <?= ucfirst($folha['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Data Pagamento</div>
                        <div class="value">
                            <?= $folha['data_pagamento'] ? formatarData($folha['data_pagamento']) : '—' ?>
                        </div>
                    </div>
                    <?php if (!empty($folha['metodo_pagamento'])): ?>
                    <div class="detail-item">
                        <div class="label">Método de Pagamento</div>
                        <div class="value"><?= ucfirst($folha['metodo_pagamento']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($folha['comprovante'])): ?>
                    <div class="detail-item">
                        <div class="label">Comprovante</div>
                        <div class="value" style="font-weight:400;font-size:0.85rem;"><?= e($folha['comprovante']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Info -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cash-coin me-2"></i> Valores
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="label">Salário Base</div>
                        <div class="value"><?= dashMoney($folha['salario_base']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Horas Extras</div>
                        <div class="value positive">+<?= dashMoney($folha['horas_extras_valor']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Bônus</div>
                        <div class="value positive">+<?= dashMoney($folha['bonus']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Faltas</div>
                        <div class="value negative">-<?= dashMoney($folha['faltas_desconto']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Descontos</div>
                        <div class="value negative">-<?= dashMoney($folha['descontos']) ?></div>
                    </div>
                    <div class="detail-item highlight">
                        <div class="label">Salário Líquido</div>
                        <div class="value"><?= dashMoney($folha['salario_liquido']) ?></div>
                    </div>
                </div>

                <hr>
                <div class="row text-center">
                    <div class="col-4">
                        <div style="font-size:0.65rem;color:var(--rh-text-muted);">Bruto</div>
                        <div style="font-weight:600;color:var(--rh-accent);">
                            <?= dashMoney($folha['salario_base'] + $folha['horas_extras_valor'] + $folha['bonus']) ?>
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:0.65rem;color:var(--rh-text-muted);">Descontos</div>
                        <div style="font-weight:600;color:var(--rh-danger);">
                            -<?= dashMoney($folha['faltas_desconto'] + $folha['descontos']) ?>
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:0.65rem;color:var(--rh-text-muted);">Líquido</div>
                        <div style="font-weight:700;color:var(--rh-primary);font-size:1.1rem;">
                            <?= dashMoney($folha['salario_liquido']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>