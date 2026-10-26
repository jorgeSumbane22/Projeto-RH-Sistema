<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

if (!usuarioAtualFuncionarioId()) {
    definirMensagem('danger', 'Sua conta não está vinculada a um funcionário.');
    redirecionar('/modules/ferias/index.php');
}

$pageTitle = 'Solicitar Férias/Licença';
$feriasModel = new Ferias();
$erros = [];

// Buscar saldo de férias
$saldoFerias = $feriasModel->saldoDisponivel(usuarioAtualFuncionarioId());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'funcionario_id' => usuarioAtualFuncionarioId(),
        'tipo'           => $_POST['tipo'] ?? '',
        'data_inicio'    => $_POST['data_inicio'] ?? '',
        'data_fim'       => $_POST['data_fim'] ?? '',
        'motivo'         => trim($_POST['motivo'] ?? ''),
    ];

    if (empty($dados['tipo'])) $erros[] = 'Selecione o tipo de solicitação.';
    if (empty($dados['data_inicio']) || empty($dados['data_fim'])) $erros[] = 'Informe o período.';
    if (!empty($dados['data_inicio']) && !empty($dados['data_fim']) && $dados['data_fim'] < $dados['data_inicio']) {
        $erros[] = 'A data final não pode ser anterior à data inicial.';
    }

    if (empty($erros)) {
        $inicio = new DateTime($dados['data_inicio']);
        $fim = new DateTime($dados['data_fim']);
        $dias = $inicio->diff($fim)->days + 1;
        $dados['dias'] = $dias;

        $feriasModel->solicitar($dados);
        registrarAuditoria('ferias_solicitada', "Funcionário {$dados['funcionario_id']} - {$dados['tipo']}");
        definirMensagem('success', 'Solicitação enviada com sucesso e aguarda aprovação.');
        redirecionar('/modules/ferias/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   SOLICITAR FÉRIAS - DESIGN PREMIUM
   ============================================================ */

.form-container {
    max-width: 680px;
    margin: 0 auto;
}

.form-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
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
    flex-wrap: wrap;
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

.form-card .form-card-header .saldo-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.8rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 500;
    margin-left: auto;
}

.form-card .form-card-header .saldo-badge strong {
    color: var(--rh-primary);
}

.form-card .form-card-body {
    padding: 1.5rem;
}

/* ----- FORM FLUTUANTE ----- */
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

.form-floating-custom .form-label .required {
    color: #ef4444;
    margin-left: 4px;
}

.form-floating-custom .form-control,
.form-floating-custom .form-select {
    border-radius: 10px;
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.3s ease;
    color: var(--bs-body-color);
}

.form-floating-custom .form-control:focus,
.form-floating-custom .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
    outline: none;
}

.form-floating-custom .form-control.is-invalid,
.form-floating-custom .form-select.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.form-floating-custom .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    margin-top: 4px;
}

.form-floating-custom .form-text i {
    margin-right: 4px;
}

/* ----- ALERT INFO ----- */
.alert-info-custom {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    background: var(--bs-tertiary-bg);
    border-left: 4px solid var(--rh-primary);
    margin-bottom: 1.25rem;
}

.alert-info-custom i {
    color: var(--rh-primary);
    font-size: 1.2rem;
}

.alert-info-custom span {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

/* ----- DIAS PREVIEW ----- */
.dias-preview {
    font-weight: 700;
    color: var(--rh-primary);
    font-size: 1.2rem;
    padding: 0.25rem 0;
}

/* ----- FORM ACTIONS ----- */
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

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
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
    .form-card .form-card-header .saldo-badge {
        margin-left: 0;
        width: 100%;
        text-align: center;
    }
}
</style>

<!-- ============================================================
     FORMULÁRIO
     ============================================================ -->
<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-pencil-square" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Solicitar Férias/Licença</h5>
            <span class="saldo-badge">
                <i class="bi bi-umbrella me-1"></i> Saldo: <strong><?= max(0, $saldoFerias ?? 0) ?></strong> dias
            </span>
        </div>
        <div class="form-card-body">
            <div class="alert-info-custom">
                <i class="bi bi-info-circle"></i>
                <span>Preencha os dados abaixo para solicitar férias ou licença. A solicitação será enviada para aprovação.</span>
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
                    <label class="form-label">
                        <i class="bi bi-tag"></i> Tipo de solicitação
                        <span class="required">*</span>
                    </label>
                    <select name="tipo" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="ferias">🏖️ Férias</option>
                        <option value="licenca_medica">🏥 Licença médica</option>
                        <option value="licenca_maternidade">👶 Licença maternidade</option>
                        <option value="licenca_paternidade">👶 Licença paternidade</option>
                        <option value="outro">📋 Outro</option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Selecione o tipo de solicitação
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <label class="form-label">
                                <i class="bi bi-calendar-plus"></i> Data início
                                <span class="required">*</span>
                            </label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <label class="form-label">
                                <i class="bi bi-calendar-x"></i> Data fim
                                <span class="required">*</span>
                            </label>
                            <input type="date" id="data_fim" name="data_fim" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-clock"></i> Total estimado
                    </label>
                    <div class="dias-preview" id="total_dias_preview">—</div>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> O total de dias será calculado automaticamente
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-chat"></i> Motivo / observações
                    </label>
                    <textarea name="motivo" class="form-control" rows="3" placeholder="Descreva o motivo da solicitação (se necessário)..."></textarea>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Informações adicionais sobre a solicitação (opcional)
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Enviar solicitação
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');
    const preview = document.getElementById('total_dias_preview');

    function calcularDias() {
        if (dataInicio.value && dataFim.value) {
            const inicio = new Date(dataInicio.value);
            const fim = new Date(dataFim.value);
            if (fim >= inicio) {
                const diffTime = Math.abs(fim - inicio);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                preview.textContent = diffDays + ' dia(s)';
                preview.style.color = 'var(--rh-primary)';
            } else {
                preview.textContent = '⚠️ Data final inválida';
                preview.style.color = '#ef4444';
            }
        } else {
            preview.textContent = '—';
            preview.style.color = '';
        }
    }

    dataInicio.addEventListener('change', calcularDias);
    dataFim.addEventListener('change', calcularDias);
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>