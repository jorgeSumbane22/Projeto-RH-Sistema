<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$id = (int) ($_GET['id'] ?? 0);
$mes = $_GET['mes'] ?? date('Y-m');
$folhaModel = new FolhaPagamento();

// Buscar dados para confirmação
$folha = $folhaModel->buscarPorId($id);
if (!$folha) {
    definirMensagem('danger', 'Registro não encontrado.');
    redirecionar('/modules/folha-pagamento/index.php');
}

if ($folha['status'] === 'pago') {
    definirMensagem('warning', 'Esta folha já foi paga.');
    redirecionar('/modules/folha-pagamento/index.php?mes=' . urlencode($mes));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $dataPagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
    $metodoPagamento = $_POST['metodo_pagamento'] ?? 'transferencia';
    $comprovante = trim($_POST['comprovante'] ?? '');
    
    $folhaModel->marcarComoPago($id, $dataPagamento, $metodoPagamento, $comprovante);
    registrarAuditoria('folha_paga', "ID $id - Funcionário {$folha['funcionario_nome']}");
    definirMensagem('success', 'Pagamento confirmado com sucesso.');
    redirecionar('/modules/folha-pagamento/index.php?mes=' . urlencode($mes));
}

$pageTitle = 'Confirmar Pagamento';
require __DIR__ . '/../../includes/header.php';
?>

<style>
.payment-container {
    max-width: 550px;
    margin: 0 auto;
}

.payment-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
}

.payment-card .payment-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--rh-border);
    background: var(--bs-tertiary-bg);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.payment-card .payment-header h5 {
    margin: 0;
    font-weight: 700;
    color: var(--bs-body-color);
}

.payment-card .payment-body {
    padding: 1.5rem;
}

.summary-box {
    background: var(--bs-tertiary-bg);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid var(--rh-success);
}

.summary-box .summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
    font-size: 0.9rem;
}

.summary-box .summary-item .label {
    color: var(--rh-text-muted);
}

.summary-box .summary-item .value {
    font-weight: 600;
}

.summary-box .summary-item .value.amount {
    color: var(--rh-primary);
    font-size: 1.2rem;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.form-actions .btn {
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
}

@media (max-width: 576px) {
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="payment-container">
    <div class="payment-card">
        <div class="payment-header">
            <i class="bi bi-check-circle" style="color:var(--rh-success);font-size:1.3rem;"></i>
            <h5>Confirmar Pagamento</h5>
        </div>
        <div class="payment-body">
            <!-- Summary -->
            <div class="summary-box">
                <div class="summary-item">
                    <span class="label">Funcionário</span>
                    <span class="value"><?= e($folha['funcionario_nome']) ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Cargo</span>
                    <span class="value"><?= e($folha['cargo_titulo'] ?? '-') ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Mês</span>
                    <span class="value"><?= formatarData($folha['mes_referencia'] . '-01') ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Salário Líquido</span>
                    <span class="value amount"><?= dashMoney($folha['salario_liquido']) ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Status Atual</span>
                    <span class="value">
                        <span class="badge-status pendente">
                            <i class="bi bi-clock"></i> Pendente
                        </span>
                    </span>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-calendar3 me-1"></i> Data de Pagamento</label>
                    <input type="date" name="data_pagamento" class="form-control" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-wallet me-1"></i> Método de Pagamento</label>
                    <select name="metodo_pagamento" class="form-select">
                        <option value="transferencia">Transferência Bancária</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="cheque">Cheque</option>
                        <option value="deposito">Depósito</option>
                        <option value="pix">PIX</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-receipt me-1"></i> Comprovante (opcional)</label>
                    <input type="text" name="comprovante" class="form-control" 
                           placeholder="Número do comprovante ou observação">
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Número de referência, código de transação, etc.
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Confirmar Pagamento
                    </button>
                    <a href="index.php?mes=<?= e($mes) ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>