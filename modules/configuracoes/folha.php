<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações - Folha de Pagamento';
$erros = [];
$sucesso = false;

// Dados padrão
$config = [
    'horas_mes' => 220,
    'hora_extra_mult' => 1.5,
    'desconto_falta' => 0,
    'inss' => 11,
    'irrf' => 7.5,
    'fgts' => 8,
    'decimo_terceiro' => 12,
    'salario_minimo' => 1412.00,
    'vale_transporte' => 6,
    'vale_refeicao' => 25.00,
    'plano_saude' => 150.00,
    'plano_odontologico' => 50.00,
    'inss_teto' => 7786.02,
    'irrf_isencao' => 1903.98,
    'irrf_dependente' => 189.59,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $config = [
        'horas_mes' => (float) ($_POST['horas_mes'] ?? 220),
        'hora_extra_mult' => (float) ($_POST['hora_extra_mult'] ?? 1.5),
        'desconto_falta' => (float) ($_POST['desconto_falta'] ?? 0),
        'inss' => (float) ($_POST['inss'] ?? 11),
        'irrf' => (float) ($_POST['irrf'] ?? 7.5),
        'fgts' => (float) ($_POST['fgts'] ?? 8),
        'decimo_terceiro' => (int) ($_POST['decimo_terceiro'] ?? 12),
        'salario_minimo' => (float) ($_POST['salario_minimo'] ?? 1412.00),
        'vale_transporte' => (float) ($_POST['vale_transporte'] ?? 6),
        'vale_refeicao' => (float) ($_POST['vale_refeicao'] ?? 25.00),
        'plano_saude' => (float) ($_POST['plano_saude'] ?? 150.00),
        'plano_odontologico' => (float) ($_POST['plano_odontologico'] ?? 50.00),
        'inss_teto' => (float) ($_POST['inss_teto'] ?? 7786.02),
        'irrf_isencao' => (float) ($_POST['irrf_isencao'] ?? 1903.98),
        'irrf_dependente' => (float) ($_POST['irrf_dependente'] ?? 189.59),
    ];
    
    // Validações
    if ($config['horas_mes'] < 1) {
        $erros[] = 'Horas por mês deve ser maior que zero.';
    }
    
    if ($config['hora_extra_mult'] < 1) {
        $erros[] = 'Multiplicador de hora extra deve ser maior que 1.';
    }
    
    if ($config['inss'] < 0 || $config['inss'] > 100) {
        $erros[] = 'INSS deve estar entre 0 e 100.';
    }
    
    if ($config['irrf'] < 0 || $config['irrf'] > 100) {
        $erros[] = 'IRRF deve estar entre 0 e 100.';
    }
    
    if (empty($erros)) {
        $sucesso = true;
        definirMensagem('success', 'Configurações da folha atualizadas com sucesso!');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   FOLHA DE PAGAMENTO - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DA PÁGINA ----- */
.page-header-premium {
    background: linear-gradient(135deg, #0891b2, #06b6d4);
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
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.page-header-premium::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -10%;
    width: 250px;
    height: 250px;
    background: rgba(255,255,255,0.03);
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
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.1);
}

.page-header-premium .header-left .header-title h5 {
    color: #fff;
    font-weight: 700;
    margin: 0;
    font-size: 1.2rem;
}

.page-header-premium .header-left .header-title p {
    color: rgba(255,255,255,0.7);
    margin: 0;
    font-size: 0.8rem;
}

.page-header-premium .btn-back {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
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
    background: rgba(255,255,255,0.25);
    color: #fff;
    transform: translateY(-2px);
}

/* ----- CONTAINER ----- */
.config-form-container {
    max-width: 900px;
    margin: 0 auto;
}

/* ----- CARD PREMIUM ----- */
.config-form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    box-shadow: var(--rh-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.config-form-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.config-form-card .card-header {
    padding: 1.25rem 1.5rem;
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
    background: linear-gradient(135deg, #0891b2, #06b6d4);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.config-form-card .card-header h6 {
    margin: 0;
    font-weight: 600;
    color: var(--bs-body-color);
}

.config-form-card .card-header .badge-status {
    margin-left: auto;
    font-size: 0.55rem;
    padding: 0.2rem 0.7rem;
}

.config-form-card .card-body {
    padding: 1.75rem;
}

/* ----- FORMULÁRIO ----- */
.config-form-card .form-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.config-form-card .form-label .required {
    color: var(--rh-danger);
    margin-left: 2px;
}

.config-form-card .form-control,
.config-form-card .form-select {
    border-radius: 10px;
    padding: 0.55rem 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
    font-size: 0.9rem;
}

.config-form-card .form-control:focus,
.config-form-card .form-select:focus {
    background: var(--rh-card-bg);
    border-color: #0891b2;
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.12);
    outline: none;
}

.config-form-card .form-control.is-invalid {
    border-color: var(--rh-danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.config-form-card .form-control::placeholder {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
}

.config-form-card .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    margin-top: 0.2rem;
}

.config-form-card .form-text i {
    margin-right: 4px;
}

/* ----- GRID ----- */
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
}

/* ----- SECTION DIVIDER ----- */
.section-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.5rem 0 1rem;
}

.section-divider .line {
    flex: 1;
    height: 1px;
    background: var(--rh-border);
}

.section-divider .label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    white-space: nowrap;
}

.section-divider .label i {
    margin-right: 6px;
}

/* ----- INFO CARD ----- */
.info-card {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: rgba(8, 145, 178, 0.06);
    border-left: 3px solid #0891b2;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.info-card i {
    color: #0891b2;
    font-size: 1.1rem;
    margin-top: 0.1rem;
}

.info-card .info-text {
    font-size: 0.8rem;
    color: var(--rh-text-secondary);
}

.info-card .info-text strong {
    color: var(--bs-body-color);
}

/* ----- BOTÕES ----- */
.form-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--rh-border);
}

.form-actions .btn {
    border-radius: 10px;
    padding: 0.5rem 1.8rem;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, #0891b2, #06b6d4);
    border: none;
    color: #fff;
    box-shadow: 0 4px 14px rgba(8, 145, 178, 0.25);
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(8, 145, 178, 0.35);
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

.form-actions .btn-reset {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-danger);
}

.form-actions .btn-reset:hover {
    background: var(--rh-danger);
    border-color: var(--rh-danger);
    color: #fff;
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
        padding: 1.25rem;
    }
    
    .grid-2,
    .grid-3 {
        grid-template-columns: 1fr;
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
    .page-header-premium .header-left .header-title h5 {
        font-size: 1rem;
    }
    
    .config-form-card .card-body {
        padding: 1rem;
    }
}

/* ----- DARK MODE ----- */
[data-bs-theme="dark"] .page-header-premium {
    background: linear-gradient(135deg, #0e7490, #0891b2);
}

[data-bs-theme="dark"] .info-card {
    background: rgba(8, 145, 178, 0.1);
}
</style>

<!-- ============================================================
     HEADER DA PÁGINA
     ============================================================ -->
<div class="page-header-premium">
    <div class="header-left">
        <div class="header-icon">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="header-title">
            <h5>Configurações da Folha de Pagamento</h5>
            <p>Configure os parâmetros de cálculo e impostos da folha de pagamento</p>
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
    <div class="config-form-card">
        <div class="card-header">
            <div class="header-icon-sm"><i class="bi bi-sliders"></i></div>
            <h6>Parâmetros da Folha de Pagamento</h6>
            <span class="badge-status ready ms-auto">
                <i class="bi bi-check-circle me-1"></i> Configurando
            </span>
        </div>
        <div class="card-body">
            <!-- Mensagens -->
            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #10b981;">
                    <i class="bi bi-check-circle me-2"></i> 
                    <strong>Sucesso!</strong> Configurações da folha atualizadas com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
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

            <!-- Info -->
            <div class="info-card">
                <i class="bi bi-info-circle"></i>
                <div class="info-text">
                    <strong>Parâmetros da folha:</strong> Configure as regras de cálculo para salários, 
                    horas extras, descontos, impostos e benefícios. Estas configurações afetam toda a 
                    folha de pagamento da organização.
                </div>
            </div>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <!-- Parâmetros Básicos -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-clock"></i> Parâmetros Básicos</span>
                    <span class="line"></span>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Horas por mês <span class="required">*</span></label>
                        <input type="number" name="horas_mes" class="form-control" 
                               value="<?= e($config['horas_mes']) ?>" min="1" required>
                        <div class="form-text"><i class="bi bi-info-circle"></i> Carga horária mensal padrão</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Multiplicador hora extra <span class="required">*</span></label>
                        <input type="number" step="0.01" name="hora_extra_mult" class="form-control" 
                               value="<?= e($config['hora_extra_mult']) ?>" min="1" required>
                        <div class="form-text"><i class="bi bi-info-circle"></i> Fator de multiplicação para horas extras (ex: 1.5 = 50% extra)</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Valor do desconto por falta (por dia)</label>
                    <input type="number" step="0.01" name="desconto_falta" class="form-control" 
                           value="<?= e($config['desconto_falta']) ?>" min="0">
                    <div class="form-text"><i class="bi bi-info-circle"></i> 
                        Deixe <strong>0</strong> para calcular automaticamente baseado no salário do funcionário
                    </div>
                </div>

                <!-- Salário Mínimo -->
                <div class="mb-3">
                    <label class="form-label">Salário Mínimo Nacional (MT)</label>
                    <input type="number" step="0.01" name="salario_minimo" class="form-control" 
                           value="<?= e($config['salario_minimo']) ?>" min="0">
                    <div class="form-text"><i class="bi bi-info-circle"></i> Valor do salário mínimo vigente</div>
                </div>

                <!-- Impostos e Encargos -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-percent"></i> Impostos e Encargos</span>
                    <span class="line"></span>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">INSS (%) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="inss" class="form-control" 
                               value="<?= e($config['inss']) ?>" min="0" max="100" required>
                        <div class="form-text"><i class="bi bi-info-circle"></i> Percentual de contribuição ao INSS</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IRRF (%) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="irrf" class="form-control" 
                               value="<?= e($config['irrf']) ?>" min="0" max="100" required>
                        <div class="form-text"><i class="bi bi-info-circle"></i> Percentual de Imposto de Renda Retido na Fonte</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">FGTS (%)</label>
                        <input type="number" step="0.01" name="fgts" class="form-control" 
                               value="<?= e($config['fgts']) ?>" min="0" max="100">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Percentual de depósito do FGTS</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">13º Salário (meses)</label>
                        <input type="number" name="decimo_terceiro" class="form-control" 
                               value="<?= e($config['decimo_terceiro']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Meses para cálculo do 13º salário (12 = proporcional)</div>
                    </div>
                </div>

                <!-- Tabelas de Impostos -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-table"></i> Tabelas de Impostos</span>
                    <span class="line"></span>
                </div>

                <div class="grid-3">
                    <div class="mb-3">
                        <label class="form-label">INSS Teto (MT)</label>
                        <input type="number" step="0.01" name="inss_teto" class="form-control" 
                               value="<?= e($config['inss_teto']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Teto máximo para cálculo do INSS</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IRRF Isenção (MT)</label>
                        <input type="number" step="0.01" name="irrf_isencao" class="form-control" 
                               value="<?= e($config['irrf_isencao']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Valor de isenção do IRRF</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IRRF Dependente (MT)</label>
                        <input type="number" step="0.01" name="irrf_dependente" class="form-control" 
                               value="<?= e($config['irrf_dependente']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Desconto por dependente no IRRF</div>
                    </div>
                </div>

                <!-- Benefícios -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-gift"></i> Benefícios</span>
                    <span class="line"></span>
                </div>

                <div class="grid-3">
                    <div class="mb-3">
                        <label class="form-label">Vale Transporte (%)</label>
                        <input type="number" step="0.01" name="vale_transporte" class="form-control" 
                               value="<?= e($config['vale_transporte']) ?>" min="0" max="100">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Percentual de desconto para vale transporte</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vale Refeição (MT)</label>
                        <input type="number" step="0.01" name="vale_refeicao" class="form-control" 
                               value="<?= e($config['vale_refeicao']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Valor diário do vale refeição</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plano de Saúde (MT)</label>
                        <input type="number" step="0.01" name="plano_saude" class="form-control" 
                               value="<?= e($config['plano_saude']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Valor mensal do plano de saúde</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Plano Odontológico (MT)</label>
                    <input type="number" step="0.01" name="plano_odontologico" class="form-control" 
                           value="<?= e($config['plano_odontologico']) ?>" min="0">
                    <div class="form-text"><i class="bi bi-info-circle"></i> Valor mensal do plano odontológico</div>
                </div>

                <!-- Ações -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar Configurações
                    </button>
                    <button type="reset" class="btn btn-reset">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo das configurações -->
    <div class="mt-4 p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--rh-border);">
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-clock me-1"></i> Horas/Mês
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#0891b2;"><?= e($config['horas_mes']) ?></div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-percent me-1"></i> INSS/IRRF
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#0891b2;"><?= e($config['inss']) ?>% / <?= e($config['irrf']) ?>%</div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-cash me-1"></i> Salário Mínimo
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#0891b2;">MT <?= number_format($config['salario_minimo'], 2, ',', '.') ?></div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-check-circle me-1"></i> Status
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:var(--rh-success);">✓ Configurado</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para campos monetários
    const moneyInputs = document.querySelectorAll('input[step="0.01"]');
    moneyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                let val = parseFloat(this.value);
                if (!isNaN(val)) {
                    this.value = val.toFixed(2);
                }
            }
        });
    });

    // Restaurar valores padrão
    document.querySelector('button[type="reset"]')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Deseja restaurar todos os valores para o padrão?')) {
            location.reload();
        }
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>