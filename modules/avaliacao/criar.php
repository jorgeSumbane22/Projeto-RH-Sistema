<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR]);

$pageTitle = 'Nova Avaliação de Desempenho';
$funcionarioModel = new Funcionario();
$avaliacaoModel = new Avaliacao();

$filtroFunc = ['status' => 'ativo'];
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    $filtroFunc['departamento_id'] = $meuFuncionario['departamento_id'] ?? 0;
}
$funcionarios = $funcionarioModel->listar($filtroFunc);
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'funcionario_id'     => $_POST['funcionario_id'] ?? '',
        'periodo'            => trim($_POST['periodo'] ?? ''),
        'pontualidade'       => (int) ($_POST['pontualidade'] ?? 0),
        'qualidade_trabalho' => (int) ($_POST['qualidade_trabalho'] ?? 0),
        'trabalho_equipe'    => (int) ($_POST['trabalho_equipe'] ?? 0),
        'proatividade'       => (int) ($_POST['proatividade'] ?? 0),
        'comentarios'        => trim($_POST['comentarios'] ?? ''),
    ];

    if ($dados['funcionario_id'] === '') $erros[] = 'Selecione o funcionário.';
    if ($dados['periodo'] === '') $erros[] = 'Informe o período avaliado (ex: 2026-T1).';
    foreach (['pontualidade','qualidade_trabalho','trabalho_equipe','proatividade'] as $campo) {
        if ($dados[$campo] < 1 || $dados[$campo] > 5) $erros[] = 'As notas devem estar entre 1 e 5.';
    }

    if (empty($erros)) {
        $avaliacaoModel->criar($dados, usuarioAtualId());
        registrarAuditoria('avaliacao_criada', "Funcionário {$dados['funcionario_id']} - {$dados['periodo']}");
        definirMensagem('success', 'Avaliação registrada com sucesso.');
        redirecionar('/modules/avaliacao/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   CRIAR AVALIAÇÃO - DESIGN PREMIUM
   ============================================================ */

.form-container {
    max-width: 680px;
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

.page-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.page-header-custom h5 {
    font-weight: 700;
    color: var(--rh-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-header-custom h5 i {
    color: var(--rh-accent);
}

.page-header-custom .btn-back {
    border-radius: 10px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--bs-secondary-color);
    transition: all 0.3s ease;
    text-decoration: none;
}

.page-header-custom .btn-back:hover {
    background: var(--bs-tertiary-bg);
    border-color: var(--rh-primary);
    color: var(--rh-primary);
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

/* ----- CRITÉRIOS ----- */
.criterio-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0.75rem;
    border-radius: 10px;
    background: var(--bs-tertiary-bg);
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
}

.criterio-row:hover {
    background: var(--bs-secondary-bg);
}

.criterio-row .criterio-label {
    flex: 1;
    font-weight: 500;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.criterio-row .criterio-label i {
    margin-right: 6px;
    color: var(--rh-primary);
}

.criterio-row .criterio-input {
    width: 80px;
}

.criterio-row .criterio-input input {
    text-align: center;
    border-radius: 8px;
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    background: var(--rh-card-bg);
    border: 1.5px solid var(--rh-border);
    color: var(--bs-body-color);
    transition: all 0.3s ease;
    width: 100%;
}

.criterio-row .criterio-input input:focus {
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
    outline: none;
}

.criterio-row .criterio-input input::-webkit-inner-spin-button {
    opacity: 1;
}

/* ----- NOTA PREVIEW ----- */
.nota-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    background: var(--bs-tertiary-bg);
    border-left: 4px solid var(--rh-primary);
    margin-bottom: 1.25rem;
}

.nota-preview .label {
    font-size: 0.75rem;
    color: var(--rh-text-muted);
    font-weight: 500;
}

.nota-preview .label i {
    margin-right: 4px;
}

.nota-preview .valor {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--rh-primary);
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
    .page-header-custom {
        flex-direction: column;
        align-items: stretch;
    }
    .page-header-custom .btn-back {
        text-align: center;
    }
    .form-card .form-card-body {
        padding: 1rem;
    }
    .criterio-row {
        flex-wrap: wrap;
    }
    .criterio-row .criterio-input {
        width: 100%;
    }
    .nota-preview {
        flex-direction: column;
        text-align: center;
    }
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="page-header-custom">
    <h5>
        <i class="bi bi-star-plus"></i> Nova Avaliação de Desempenho
    </h5>
    <a href="index.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Voltar à lista
    </a>
</div>

<!-- ============================================================
     FORMULÁRIO
     ============================================================ -->
<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-star" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Avaliar desempenho do colaborador</h5>
        </div>
        <div class="form-card-body">
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
                        <i class="bi bi-person"></i> Funcionário
                        <span class="required">*</span>
                    </label>
                    <select name="funcionario_id" class="form-select" required>
                        <option value="">Selecione um funcionário...</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= e($f['nome']) ?> - <?= e($f['cargo_titulo'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Selecione o funcionário a ser avaliado
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-calendar3"></i> Período
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="periodo" class="form-control" placeholder="Ex: 2026-T1 (Trimestre 1)" required>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Ex: 2026-T1, 2026-S1, 2026-Anual
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-sliders2"></i> Critérios de avaliação
                        <span class="required">*</span>
                    </label>

                    <div class="criterio-row">
                        <span class="criterio-label"><i class="bi bi-clock"></i> Pontualidade</span>
                        <span class="criterio-input">
                            <input type="number" min="1" max="5" name="pontualidade" id="pontualidade" required value="3">
                        </span>
                    </div>

                    <div class="criterio-row">
                        <span class="criterio-label"><i class="bi bi-check-circle"></i> Qualidade do trabalho</span>
                        <span class="criterio-input">
                            <input type="number" min="1" max="5" name="qualidade_trabalho" id="qualidade_trabalho" required value="3">
                        </span>
                    </div>

                    <div class="criterio-row">
                        <span class="criterio-label"><i class="bi bi-people"></i> Trabalho em equipe</span>
                        <span class="criterio-input">
                            <input type="number" min="1" max="5" name="trabalho_equipe" id="trabalho_equipe" required value="3">
                        </span>
                    </div>

                    <div class="criterio-row">
                        <span class="criterio-label"><i class="bi bi-rocket"></i> Proatividade</span>
                        <span class="criterio-input">
                            <input type="number" min="1" max="5" name="proatividade" id="proatividade" required value="3">
                        </span>
                    </div>

                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Notas de 1 a 5 (1 = Insuficiente, 5 = Excelente)
                    </div>
                </div>

                <!-- Nota Preview -->
                <div class="nota-preview">
                    <span class="label"><i class="bi bi-calculator"></i> Nota final estimada</span>
                    <span class="valor" id="nota_final_preview">-</span>
                    <span style="font-size:0.85rem;color:var(--rh-text-muted);">/ 5.00</span>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-chat"></i> Comentários
                    </label>
                    <textarea name="comentarios" class="form-control" rows="3" placeholder="Observações sobre a avaliação..."></textarea>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Feedback adicional sobre o desempenho (opcional)
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar Avaliação
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
    const inputs = ['pontualidade', 'qualidade_trabalho', 'trabalho_equipe', 'proatividade'];
    const preview = document.getElementById('nota_final_preview');

    function calcularNota() {
        let soma = 0;
        let valido = true;
        inputs.forEach(id => {
            const input = document.getElementById(id);
            const valor = parseFloat(input.value);
            if (isNaN(valor) || valor < 1 || valor > 5) {
                valido = false;
            } else {
                soma += valor;
            }
        });
        if (valido) {
            const media = soma / inputs.length;
            preview.textContent = media.toFixed(2);
            preview.style.color = media >= 4 ? 'var(--rh-accent)' : media >= 3 ? '#f59e0b' : '#ef4444';
        } else {
            preview.textContent = '-';
            preview.style.color = '';
        }
    }

    inputs.forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener('input', calcularNota);
        input.addEventListener('change', function() {
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) this.value = 1;
            if (val > 5) this.value = 5;
            calcularNota();
        });
    });

    calcularNota();
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>