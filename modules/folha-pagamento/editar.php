<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$id = (int) ($_GET['id'] ?? 0);
$folhaModel = new FolhaPagamento();
$folha = $folhaModel->buscarPorId($id);

if (!$folha) {
    definirMensagem('danger', 'Registro de folha não encontrado.');
    redirecionar('/modules/folha-pagamento/index.php');
}

$pageTitle = 'Ajustar Folha - ' . $folha['funcionario_nome'];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $bonus = (float) ($_POST['bonus'] ?? 0);
    $descontos = (float) ($_POST['descontos'] ?? 0);
    $horasExtras = (float) ($_POST['horas_extras'] ?? 0);
    $faltas = (float) ($_POST['faltas'] ?? 0);
    $salarioBase = (float) ($_POST['salario_base'] ?? 0);

    if ($bonus < 0 || $descontos < 0 || $horasExtras < 0 || $faltas < 0 || $salarioBase < 0) {
        $erros[] = 'Valores não podem ser negativos.';
    }
    
    if ($salarioBase <= 0) {
        $erros[] = 'Salário base deve ser maior que zero.';
    }

    if (empty($erros)) {
        $dados = [
            'salario_base' => $salarioBase,
            'horas_extras_valor' => $horasExtras,
            'bonus' => $bonus,
            'faltas_desconto' => $faltas,
            'descontos' => $descontos
        ];
        
        $folhaModel->atualizarFolha($id, $dados);
        registrarAuditoria('folha_ajustada', "ID $id - bonus:$bonus descontos:$descontos");
        definirMensagem('success', 'Folha atualizada com sucesso.');
        redirecionar('/modules/folha-pagamento/index.php?mes=' . urlencode($folha['mes_referencia']));
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
.form-container {
    max-width: 650px;
    margin: 0 auto;
}

.form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.form-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.form-card .form-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--rh-border);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: var(--bs-tertiary-bg);
}

.form-card .form-card-header h5 {
    font-weight: 700;
    margin: 0;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.form-card .form-card-header h5 i {
    color: var(--rh-primary);
    margin-right: 6px;
}

.form-card .form-card-body {
    padding: 1.5rem;
}

.employee-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: 10px;
    margin-bottom: 1.25rem;
    border-left: 4px solid var(--rh-primary);
}

.employee-card .avatar-lg {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    text-transform: uppercase;
}

.employee-card .emp-name {
    font-weight: 600;
    color: var(--bs-body-color);
    font-size: 0.95rem;
}

.employee-card .emp-details {
    font-size: 0.75rem;
    color: var(--rh-text-muted);
}

.employee-card .emp-details i {
    margin-right: 4px;
}

.info-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    background: var(--bs-tertiary-bg);
    border-left: 4px solid var(--rh-info);
    margin-bottom: 1.25rem;
}

.info-box i {
    color: var(--rh-info);
    font-size: 1.1rem;
}

.info-box span {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.info-box span strong {
    color: var(--bs-body-color);
}

.form-floating-custom {
    margin-bottom: 1.25rem;
}

.form-floating-custom .form-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
    display: block;
}

.form-floating-custom .form-label i {
    margin-right: 6px;
    font-size: 0.7rem;
}

.form-floating-custom .form-control {
    border-radius: 10px;
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
}

.form-floating-custom .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
    outline: none;
}

.form-floating-custom .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    margin-top: 4px;
}

.form-floating-custom .form-text i {
    margin-right: 4px;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--rh-border);
}

.form-actions .btn {
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.3);
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

@media (max-width: 767.98px) {
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    .form-card .form-card-body {
        padding: 1rem;
    }
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 575.98px) {
    .employee-card {
        flex-wrap: wrap;
    }
    .employee-card .emp-details {
        width: 100%;
        margin-left: 52px;
    }
}
</style>

<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-pencil-square" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Ajustar Folha de Pagamento</h5>
        </div>
        <div class="form-card-body">
            <!-- Employee Info -->
            <div class="employee-card">
                <span class="avatar-lg"><?= e(strtoupper(substr($folha['funcionario_nome'], 0, 1))) ?></span>
                <div>
                    <div class="emp-name"><?= e($folha['funcionario_nome']) ?></div>
                    <div class="emp-details">
                        <i class="bi bi-briefcase"></i> <?= e($folha['cargo_titulo'] ?? '-') ?>
                        <span class="mx-1">·</span>
                        <i class="bi bi-calendar3"></i> <?= e($folha['mes_referencia']) ?>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="bi bi-info-circle"></i>
                <span>
                    Valores atuais: <strong>Líquido</strong> <?= dashMoney($folha['salario_liquido']) ?> ·
                    <strong>Bônus</strong> <?= dashMoney($folha['bonus']) ?> ·
                    <strong>Descontos</strong> <?= dashMoney($folha['descontos']) ?>
                </span>
            </div>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
                        <i class="bi bi-x-circle me-2"></i>
                        <strong>Por favor, corrija os seguintes erros:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <div class="form-floating-custom">
                    <label class="form-label"><i class="bi bi-cash-coin"></i> Salário Base (MT)</label>
                    <input type="number" step="0.01" min="0" name="salario_base" class="form-control" 
                           value="<?= e((string) $folha['salario_base']) ?>" placeholder="0.00" required>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Salário base do funcionário
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-floating-custom">
                        <label class="form-label"><i class="bi bi-clock"></i> Horas Extras (MT)</label>
                        <input type="number" step="0.01" min="0" name="horas_extras" class="form-control" 
                               value="<?= e((string) $folha['horas_extras_valor']) ?>" placeholder="0.00">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Valor total de horas extras
                        </div>
                    </div>
                    <div class="form-floating-custom">
                        <label class="form-label"><i class="bi bi-gift"></i> Bônus (MT)</label>
                        <input type="number" step="0.01" min="0" name="bonus" class="form-control" 
                               value="<?= e((string) $folha['bonus']) ?>" placeholder="0.00">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Bônus adicionais
                        </div>
                    </div>
                    <div class="form-floating-custom">
                        <label class="form-label"><i class="bi bi-x-circle"></i> Faltas (MT)</label>
                        <input type="number" step="0.01" min="0" name="faltas" class="form-control" 
                               value="<?= e((string) $folha['faltas_desconto']) ?>" placeholder="0.00">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Desconto por faltas
                        </div>
                    </div>
                    <div class="form-floating-custom">
                        <label class="form-label"><i class="bi bi-scissors"></i> Descontos (MT)</label>
                        <input type="number" step="0.01" min="0" name="descontos" class="form-control" 
                               value="<?= e((string) $folha['descontos']) ?>" placeholder="0.00">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> INSS, IRRF, benefícios, etc.
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar Alterações
                    </button>
                    <a href="index.php?mes=<?= e($folha['mes_referencia']) ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>