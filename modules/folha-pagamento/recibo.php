<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR, PERFIL_FUNCIONARIO]);

$id = (int) ($_GET['id'] ?? 0);
$folhaModel = new FolhaPagamento();
$folha = $folhaModel->buscarPorId($id);

if (!$folha) {
    die('Registro não encontrado.');
}

// Verificar permissão: funcionário só vê o próprio
if (isset($_SESSION['funcionario_id']) && $_SESSION['perfil'] === PERFIL_FUNCIONARIO) {
    if ($folha['funcionario_id'] != $_SESSION['funcionario_id']) {
        die('Acesso negado.');
    }
}

$pageTitle = 'Recibo - ' . $folha['funcionario_nome'];

require __DIR__ . '/../../includes/header.php';
?>

<style>
.recibo-container {
    max-width: 800px;
    margin: 0 auto;
}

.recibo {
    background: var(--rh-card-bg);
    border: 2px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 2rem;
    box-shadow: var(--rh-shadow);
}

.recibo-header {
    text-align: center;
    border-bottom: 3px double var(--rh-primary);
    padding-bottom: 1.5rem;
    margin-bottom: 1.5rem;
}

.recibo-header .company-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--rh-primary);
}

.recibo-header .document-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-top: 0.25rem;
}

.recibo-header .document-subtitle {
    font-size: 0.8rem;
    color: var(--rh-text-muted);
}

.recibo-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem 2rem;
    margin-bottom: 1.5rem;
}

.recibo-grid .item {
    display: flex;
    justify-content: space-between;
    padding: 0.3rem 0;
    border-bottom: 1px dashed var(--rh-border);
}

.recibo-grid .item .label {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
}

.recibo-grid .item .value {
    font-weight: 500;
    color: var(--bs-body-color);
}

.recibo-section {
    margin-bottom: 1rem;
}

.recibo-section .section-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.5rem;
    padding-bottom: 0.25rem;
    border-bottom: 1px solid var(--rh-border);
}

.recibo-total {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--rh-primary);
}

.recibo-total .total-item {
    text-align: center;
    padding: 0.5rem;
    background: var(--bs-tertiary-bg);
    border-radius: 8px;
}

.recibo-total .total-item .total-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    color: var(--rh-text-muted);
}

.recibo-total .total-item .total-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--bs-body-color);
}

.recibo-total .total-item .total-value.positive {
    color: var(--rh-accent);
}

.recibo-total .total-item .total-value.negative {
    color: var(--rh-danger);
}

.recibo-footer {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--rh-border);
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: var(--rh-text-muted);
}

.recibo-footer .signature {
    text-align: center;
}

.recibo-footer .signature .line {
    margin-top: 1rem;
    width: 150px;
    border-top: 1px solid var(--rh-border);
}

.recibo-status {
    display: inline-block;
    padding: 0.2rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.8rem;
}

.recibo-status.pago {
    background: var(--rh-success-bg);
    color: var(--rh-success-text);
}

.recibo-status.pendente {
    background: var(--rh-warning-bg);
    color: var(--rh-warning-text);
}

@media print {
    .no-print { display: none !important; }
    .recibo-container { max-width: 100%; }
    .recibo { border: none !important; box-shadow: none !important; }
}

@media (max-width: 576px) {
    .recibo-grid { grid-template-columns: 1fr; gap: 0.25rem; }
    .recibo-total { grid-template-columns: 1fr; gap: 0.5rem; }
    .recibo { padding: 1rem; }
}
</style>

<div class="recibo-container">
    <div class="recibo">
        <!-- Header -->
        <div class="recibo-header">
            <div class="company-name">🏢 RH Sistema</div>
            <div class="document-title">RECIBO DE VENCIMENTO</div>
            <div class="document-subtitle">Documento comprovativo de pagamento</div>
        </div>

        <!-- Dados do Funcionário -->
        <div class="recibo-grid">
            <div class="item">
                <span class="label">Funcionário</span>
                <span class="value"><?= e($folha['funcionario_nome']) ?></span>
            </div>
            <div class="item">
                <span class="label">Cargo</span>
                <span class="value"><?= e($folha['cargo_titulo'] ?? '-') ?></span>
            </div>
            <div class="item">
                <span class="label">Departamento</span>
                <span class="value"><?= e($folha['departamento_nome'] ?? '-') ?></span>
            </div>
            <div class="item">
                <span class="label">Mês</span>
                <span class="value"><?= formatarData($folha['mes_referencia'] . '-01') ?></span>
            </div>
            <div class="item">
                <span class="label">Status</span>
                <span class="value">
                    <span class="recibo-status <?= $folha['status'] ?>">
                        <?= strtoupper($folha['status']) ?>
                    </span>
                </span>
            </div>
            <div class="item">
                <span class="label">Data Pagamento</span>
                <span class="value"><?= $folha['data_pagamento'] ? formatarData($folha['data_pagamento']) : '—' ?></span>
            </div>
        </div>

        <!-- Vencimentos -->
        <div class="recibo-section">
            <div class="section-title"><i class="bi bi-arrow-up-circle me-1"></i> Vencimentos</div>
            <div class="recibo-grid">
                <div class="item">
                    <span class="label">Vencimento Base</span>
                    <span class="value"><?= dashMoney($folha['salario_base']) ?></span>
                </div>
                <div class="item">
                    <span class="label">Horas Extras</span>
                    <span class="value positive">+ <?= dashMoney($folha['horas_extras_valor']) ?></span>
                </div>
                <div class="item">
                    <span class="label">Bónus</span>
                    <span class="value positive">+ <?= dashMoney($folha['bonus']) ?></span>
                </div>
                <div class="item">
                    <span class="label">Subsídios</span>
                    <span class="value positive">+ <?= dashMoney(0) ?></span>
                </div>
            </div>
        </div>

        <!-- Descontos -->
        <div class="recibo-section">
            <div class="section-title"><i class="bi bi-arrow-down-circle me-1"></i> Descontos</div>
            <div class="recibo-grid">
                <div class="item">
                    <span class="label">Faltas</span>
                    <span class="value negative">- <?= dashMoney($folha['faltas_desconto']) ?></span>
                </div>
                <div class="item">
                    <span class="label">Descontos Diversos</span>
                    <span class="value negative">- <?= dashMoney($folha['descontos']) ?></span>
                </div>
                <div class="item">
                    <span class="label">INSS</span>
                    <span class="value negative">- <?= dashMoney(0) ?></span>
                </div>
                <div class="item">
                    <span class="label">IRPS</span>
                    <span class="value negative">- <?= dashMoney(0) ?></span>
                </div>
            </div>
        </div>

        <!-- Totais -->
        <div class="recibo-total">
            <div class="total-item">
                <div class="total-label">Salário Bruto</div>
                <div class="total-value positive">
                    <?= dashMoney($folha['salario_base'] + $folha['horas_extras_valor'] + $folha['bonus']) ?>
                </div>
            </div>
            <div class="total-item">
                <div class="total-label">Total Descontos</div>
                <div class="total-value negative">
                    - <?= dashMoney($folha['faltas_desconto'] + $folha['descontos']) ?>
                </div>
            </div>
            <div class="total-item" style="background:var(--rh-primary);">
                <div class="total-label" style="color:rgba(255,255,255,0.7);">Salário Líquido</div>
                <div class="total-value" style="color:#fff;font-size:1.5rem;">
                    <?= dashMoney($folha['salario_liquido']) ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="recibo-footer">
            <div>
                <i class="bi bi-qr-code me-1"></i> ID: #<?= str_pad($folha['id'], 6, '0', STR_PAD_LEFT) ?>
            </div>
            <div class="signature">
                <div>___________________________________</div>
                <div style="font-size:0.65rem;color:var(--rh-text-muted);">Assinatura do Responsável</div>
            </div>
            <div>
                <i class="bi bi-clock me-1"></i> <?= date('d/m/Y H:i') ?>
            </div>
        </div>
    </div>

    <!-- Ações -->
    <div class="no-print text-center mt-4">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
        <a href="index.php?mes=<?= e($folha['mes_referencia']) ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>