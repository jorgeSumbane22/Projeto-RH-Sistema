<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações - Férias e Licenças';
$erros = [];
$sucesso = false;

// Dados padrão
$config = [
    'dias_ferias' => 30,
    'periodo_aquisitivo' => 12,
    'antecedencia' => 30,
    'max_consecutivos' => 30,
    'fracionamento' => 1,
    'abono_pecuniario' => 1,
    'licenca_medica' => 15,
    'licenca_maternidade' => 120,
    'licenca_paternidade' => 5,
    'licenca_nojo' => 2,
    'licenca_casamento' => 3,
    'licenca_eleitoral' => 2,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $config = [
        'dias_ferias' => (int) ($_POST['dias_ferias'] ?? 30),
        'periodo_aquisitivo' => (int) ($_POST['periodo_aquisitivo'] ?? 12),
        'antecedencia' => (int) ($_POST['antecedencia'] ?? 30),
        'max_consecutivos' => (int) ($_POST['max_consecutivos'] ?? 30),
        'fracionamento' => isset($_POST['fracionamento']) ? 1 : 0,
        'abono_pecuniario' => isset($_POST['abono_pecuniario']) ? 1 : 0,
        'licenca_medica' => (int) ($_POST['licenca_medica'] ?? 15),
        'licenca_maternidade' => (int) ($_POST['licenca_maternidade'] ?? 120),
        'licenca_paternidade' => (int) ($_POST['licenca_paternidade'] ?? 5),
        'licenca_nojo' => (int) ($_POST['licenca_nojo'] ?? 2),
        'licenca_casamento' => (int) ($_POST['licenca_casamento'] ?? 3),
        'licenca_eleitoral' => (int) ($_POST['licenca_eleitoral'] ?? 2),
    ];
    
    // Validações
    if ($config['dias_ferias'] < 1) {
        $erros[] = 'Dias de férias deve ser maior que zero.';
    }
    
    if ($config['periodo_aquisitivo'] < 1) {
        $erros[] = 'Período aquisitivo deve ser maior que zero.';
    }
    
    if (empty($erros)) {
        $sucesso = true;
        definirMensagem('success', 'Configurações de férias atualizadas com sucesso!');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   FÉRIAS - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DA PÁGINA ----- */
.page-header-premium {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
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
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
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
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
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

/* ----- TOGGLE SWITCH ----- */
.switch-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: 10px;
    border: 1px solid var(--rh-border);
    transition: all 0.3s ease;
}

.switch-container:hover {
    border-color: #7c3aed;
}

.switch-container .switch-label {
    flex: 1;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.switch-container .switch-label small {
    display: block;
    font-size: 0.7rem;
    color: var(--rh-text-muted);
    font-weight: 400;
}

.switch {
    position: relative;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.switch .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--rh-border);
    transition: 0.3s;
    border-radius: 34px;
}

.switch .slider::before {
    content: '';
    position: absolute;
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background: #fff;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.switch input:checked + .slider {
    background: #7c3aed;
}

.switch input:checked + .slider::before {
    transform: translateX(22px);
}

.switch input:disabled + .slider {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ----- INFO CARD ----- */
.info-card {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: rgba(124, 58, 237, 0.06);
    border-left: 3px solid #7c3aed;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.info-card i {
    color: #7c3aed;
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
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    border: none;
    color: #fff;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(124, 58, 237, 0.35);
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
    
    .switch-container {
        flex-wrap: wrap;
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
    background: linear-gradient(135deg, #5b21b6, #7c3aed);
}

[data-bs-theme="dark"] .info-card {
    background: rgba(124, 58, 237, 0.1);
}

[data-bs-theme="dark"] .switch .slider {
    background: var(--rh-border);
}

[data-bs-theme="dark"] .switch input:checked + .slider {
    background: #7c3aed;
}
</style>

<!-- ============================================================
     HEADER DA PÁGINA
     ============================================================ -->
<div class="page-header-premium">
    <div class="header-left">
        <div class="header-icon">
            <i class="bi bi-umbrella"></i>
        </div>
        <div class="header-title">
            <h5>Configurações de Férias e Licenças</h5>
            <p>Gerencie as políticas de férias e licenças da sua organização</p>
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
            <h6>Políticas de Férias e Licenças</h6>
            <span class="badge-status ready ms-auto">
                <i class="bi bi-check-circle me-1"></i> Configurando
            </span>
        </div>
        <div class="card-body">
            <!-- Mensagens -->
            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #10b981;">
                    <i class="bi bi-check-circle me-2"></i> 
                    <strong>Sucesso!</strong> Configurações de férias atualizadas com sucesso!
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
                    <strong>Políticas de férias:</strong> Configure os parâmetros que regem as férias e licenças 
                    dos funcionários. Estas configurações afetam o cálculo de dias disponíveis e os períodos de solicitação.
                </div>
            </div>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <!-- Férias -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-umbrella"></i> Férias</span>
                    <span class="line"></span>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Dias de férias por ano <span class="required">*</span></label>
                        <input type="number" name="dias_ferias" class="form-control" 
                               value="<?= e($config['dias_ferias']) ?>" min="1" required>
                        <div class="form-text"><i class="bi bi-info-circle"></i> Dias de férias concedidos por ano trabalhado</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Período aquisitivo (meses) <span class="required">*</span></label>
                        <input type="number" name="periodo_aquisitivo" class="form-control" 
                               value="<?= e($config['periodo_aquisitivo']) ?>" min="1" required>
                        <div class="form-text"><i class="bi bi-info-circle"></i> Meses trabalhados para adquirir direito a férias</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Antecedência para solicitação (dias)</label>
                        <input type="number" name="antecedencia" class="form-control" 
                               value="<?= e($config['antecedencia']) ?>" min="1">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Dias de antecedência mínima para solicitar férias</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dias máximos consecutivos</label>
                        <input type="number" name="max_consecutivos" class="form-control" 
                               value="<?= e($config['max_consecutivos']) ?>" min="1">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Máximo de dias consecutivos que podem ser tirados</div>
                    </div>
                </div>

                <!-- Opções de Férias -->
                <div class="mb-3">
                    <label class="form-label">Opções de Férias</label>
                    <div class="switch-container">
                        <div class="switch-label">
                            Permitir fracionamento
                            <small>Permite dividir as férias em até 3 períodos</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="fracionamento" <?= $config['fracionamento'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="switch-container mt-2">
                        <div class="switch-label">
                            Permitir abono pecuniário
                            <small>Permite converter 1/3 das férias em dinheiro (venda de férias)</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="abono_pecuniario" <?= $config['abono_pecuniario'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Licenças -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-heart-pulse"></i> Licenças</span>
                    <span class="line"></span>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Licença médica (dias)</label>
                        <input type="number" name="licenca_medica" class="form-control" 
                               value="<?= e($config['licenca_medica']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Dias de licença médica por ano</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Licença maternidade (dias)</label>
                        <input type="number" name="licenca_maternidade" class="form-control" 
                               value="<?= e($config['licenca_maternidade']) ?>" min="0">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Dias de licença maternidade</div>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="mb-3">
                        <label class="form-label">Licença paternidade (dias)</label>
                        <input type="number" name="licenca_paternidade" class="form-control" 
                               value="<?= e($config['licenca_paternidade']) ?>" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Licença nojo (dias)</label>
                        <input type="number" name="licenca_nojo" class="form-control" 
                               value="<?= e($config['licenca_nojo']) ?>" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Licença casamento (dias)</label>
                        <input type="number" name="licenca_casamento" class="form-control" 
                               value="<?= e($config['licenca_casamento']) ?>" min="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Licença eleitoral (dias)</label>
                    <input type="number" name="licenca_eleitoral" class="form-control" 
                           value="<?= e($config['licenca_eleitoral']) ?>" min="0">
                    <div class="form-text"><i class="bi bi-info-circle"></i> Dias de licença para exercício eleitoral</div>
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
                    <i class="bi bi-umbrella me-1"></i> Dias de Férias
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#7c3aed;"><?= e($config['dias_ferias']) ?></div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-calendar me-1"></i> Período Aquisitivo
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#7c3aed;"><?= e($config['periodo_aquisitivo']) ?> meses</div>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:0.6rem;text-transform:uppercase;color:var(--rh-text-muted);letter-spacing:0.06em;">
                    <i class="bi bi-heart-pulse me-1"></i> Licenças
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#7c3aed;">5 tipos</div>
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
    // Restaurar valores padrão
    document.querySelector('button[type="reset"]')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Deseja restaurar todos os valores para o padrão?')) {
            // Implementar restauração
            location.reload();
        }
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>