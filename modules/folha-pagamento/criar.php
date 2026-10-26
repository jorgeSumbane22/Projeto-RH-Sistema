<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$pageTitle = 'Criar Folha de Pagamento';
$folhaModel = new FolhaPagamento();
$funcionarioModel = new Funcionario();

$erros = [];

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $mesReferencia = $_POST['mes_referencia'] ?? date('Y-m');
    $funcionarioId = (int) ($_POST['funcionario_id'] ?? 0);
    $salarioBase = (float) ($_POST['salario_base'] ?? 0);
    $horasExtras = (float) ($_POST['horas_extras'] ?? 0);
    $bonus = (float) ($_POST['bonus'] ?? 0);
    $faltas = (float) ($_POST['faltas'] ?? 0);
    $descontos = (float) ($_POST['descontos'] ?? 0);
    
    // Validações
    if ($funcionarioId <= 0) {
        $erros[] = 'Selecione um funcionário.';
    }
    
    if ($salarioBase <= 0) {
        $erros[] = 'Salário base deve ser maior que zero.';
    }
    
    if ($bonus < 0 || $descontos < 0 || $horasExtras < 0 || $faltas < 0) {
        $erros[] = 'Valores não podem ser negativos.';
    }
    
    // Verificar se já existe folha para este funcionário/mês
    if (empty($erros) && $folhaModel->existeFolha($funcionarioId, $mesReferencia)) {
        $erros[] = 'Já existe uma folha para este funcionário neste mês.';
    }
    
    if (empty($erros)) {
        $salarioLiquido = $salarioBase + $horasExtras + $bonus - $faltas - $descontos;
        
        $dados = [
            'funcionario_id' => $funcionarioId,
            'mes_referencia' => $mesReferencia,
            'salario_base' => $salarioBase,
            'horas_extras_valor' => $horasExtras,
            'bonus' => $bonus,
            'faltas_desconto' => $faltas,
            'descontos' => $descontos,
            'salario_liquido' => $salarioLiquido,
            'status' => 'pendente'
        ];
        
        $id = $folhaModel->criar($dados);
        if ($id) {
            registrarAuditoria('folha_criada', "ID $id - Funcionário $funcionarioId");
            definirMensagem('success', 'Folha criada com sucesso!');
            redirecionar('/modules/folha-pagamento/index.php?mes=' . urlencode($mesReferencia));
        } else {
            $erros[] = 'Erro ao criar folha. Tente novamente.';
        }
    }
}

// Buscar funcionários ativos - USANDO O MÉTODO CORRETO
$funcionarios = $funcionarioModel->listarAtivos();
$mesReferencia = $_GET['mes'] ?? date('Y-m');

require __DIR__ . '/../../includes/header.php';
?>

<style>
.form-container {
    max-width: 700px;
    margin: 0 auto;
}

.form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
}

.form-card .form-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--rh-border);
    background: var(--bs-tertiary-bg);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.form-card .form-card-header h5 {
    margin: 0;
    font-weight: 700;
    color: var(--bs-body-color);
}

.form-card .form-card-header h5 i {
    color: var(--rh-primary);
    margin-right: 6px;
}

.form-card .form-card-body {
    padding: 1.5rem;
}

.form-section {
    margin-bottom: 1.5rem;
}

.form-section .section-title {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--rh-border);
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 576px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-plus-circle" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Criar Folha de Pagamento</h5>
        </div>
        <div class="form-card-body">
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
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

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="form-section">
                    <div class="section-title"><i class="bi bi-calendar3 me-1"></i> Período</div>
                    <div class="mb-3">
                        <label class="form-label">Mês de Referência</label>
                        <input type="month" name="mes_referencia" class="form-control" 
                               value="<?= e($mesReferencia) ?>" required>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title"><i class="bi bi-person me-1"></i> Funcionário</div>
                    <div class="mb-3">
                        <label class="form-label">Selecione o Funcionário</label>
                        <select name="funcionario_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($funcionarios as $func): ?>
                                <option value="<?= $func['id'] ?>">
                                    <?= e($func['nome']) ?> 
                                    <?php if (!empty($func['cargo_titulo'])): ?>
                                        - <?= e($func['cargo_titulo']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($funcionarios)): ?>
                            <div class="form-text text-danger">
                                <i class="bi bi-exclamation-triangle"></i> 
                                Nenhum funcionário ativo encontrado. Cadastre um funcionário primeiro.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title"><i class="bi bi-cash-coin me-1"></i> Valores</div>
                    <div class="grid-2">
                        <div class="mb-3">
                            <label class="form-label">Salário Base (MT) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="salario_base" class="form-control" 
                                   placeholder="0.00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Horas Extras (MT)</label>
                            <input type="number" step="0.01" min="0" name="horas_extras" class="form-control" 
                                   placeholder="0.00" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bônus (MT)</label>
                            <input type="number" step="0.01" min="0" name="bonus" class="form-control" 
                                   placeholder="0.00" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faltas (MT)</label>
                            <input type="number" step="0.01" min="0" name="faltas" class="form-control" 
                                   placeholder="0.00" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descontos Adicionais (MT)</label>
                        <input type="number" step="0.01" min="0" name="descontos" class="form-control" 
                               placeholder="0.00" value="0">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> INSS, IRRF, benefícios, adiantamentos, etc.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" <?= empty($funcionarios) ? 'disabled' : '' ?>>
                        <i class="bi bi-floppy me-1"></i> Criar
                    </button>
                    <a href="index.php?mes=<?= e($mesReferencia) ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>