<?php
/**
 * _form.php - Partial compartilhado entre criar.php e editar.php
 */
$c = $cargo ?? [];
$isEdit = isset($c['id']) && $c['id'] > 0;
?>

<style>
.form-section-title {
    font-weight: 700;
    font-size: 0.75rem;
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--rh-border);
}

.form-section-title i {
    color: var(--rh-primary);
    font-size: 0.85rem;
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

/* Switch */
.form-switch-custom .form-check-input {
    width: 2.5rem;
    height: 1.4rem;
    cursor: pointer;
    border-radius: 50px;
    border: 1.5px solid var(--rh-border);
    background-color: var(--bs-tertiary-bg);
    transition: all 0.3s ease;
}

.form-switch-custom .form-check-input:checked {
    background-color: var(--rh-primary);
    border-color: var(--rh-primary);
}

.form-switch-custom .form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.15);
}

.form-switch-custom .form-check-label {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--bs-body-color);
    cursor: pointer;
}

.form-switch-custom .form-check-label i {
    margin-right: 6px;
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

@media (max-width: 575.98px) {
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

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

    <!-- DADOS PRINCIPAIS -->
    <div class="form-section-title">
        <i class="bi bi-briefcase"></i> Informações do Cargo
    </div>

    <div class="form-floating-custom">
        <label class="form-label">
            <i class="bi bi-tag"></i> Título do cargo
            <span class="required">*</span>
        </label>
        <input type="text" name="titulo" class="form-control <?= in_array('Informe o título do cargo.', $erros) ? 'is-invalid' : '' ?>" 
               value="<?= e($c['titulo'] ?? '') ?>" placeholder="Ex: Analista de Sistemas" required>
        <div class="form-text">
            <i class="bi bi-info-circle"></i> Nome único e descritivo para o cargo
        </div>
    </div>

    <div class="form-floating-custom">
        <label class="form-label">
            <i class="bi bi-building"></i> Departamento
            <span class="required">*</span>
        </label>
        <select name="departamento_id" class="form-select <?= in_array('Selecione o departamento.', $erros) ? 'is-invalid' : '' ?>" required>
            <option value="">Selecione um departamento...</option>
            <?php foreach ($departamentos as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($c['departamento_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">
            <i class="bi bi-info-circle"></i> Departamento ao qual o cargo pertence
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label">
                    <i class="bi bi-cash-coin"></i> Salário base (MT)
                    <span class="required">*</span>
                </label>
                <input type="number" step="0.01" min="0" name="salario_base" class="form-control <?= in_array('Salário base inválido.', $erros) ? 'is-invalid' : '' ?>" 
                       value="<?= e((string)($c['salario_base'] ?? '')) ?>" placeholder="0.00" required>
                <div class="form-text">
                    <i class="bi bi-info-circle"></i> Valor mensal em Meticais (MT)
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label">
                    <i class="bi bi-level-up"></i> Nível
                </label>
                <select name="nivel" class="form-select">
                    <option value="">Selecione...</option>
                    <option value="junior" <?= ($c['nivel'] ?? '') === 'junior' ? 'selected' : '' ?>>Júnior</option>
                    <option value="pleno" <?= ($c['nivel'] ?? '') === 'pleno' ? 'selected' : '' ?>>Pleno</option>
                    <option value="senior" <?= ($c['nivel'] ?? '') === 'senior' ? 'selected' : '' ?>>Sênior</option>
                    <option value="especialista" <?= ($c['nivel'] ?? '') === 'especialista' ? 'selected' : '' ?>>Especialista</option>
                    <option value="gestor" <?= ($c['nivel'] ?? '') === 'gestor' ? 'selected' : '' ?>>Gestor</option>
                </select>
                <div class="form-text">
                    <i class="bi bi-info-circle"></i> Nível de senioridade do cargo
                </div>
            </div>
        </div>
    </div>

    <div class="form-floating-custom">
        <label class="form-label">
            <i class="bi bi-align-left"></i> Descrição
        </label>
        <textarea name="descricao" class="form-control" rows="4" placeholder="Descreva as responsabilidades e requisitos do cargo..."><?= e($c['descricao'] ?? '') ?></textarea>
        <div class="form-text">
            <i class="bi bi-info-circle"></i> Descrição opcional das atividades e requisitos
        </div>
    </div>

    <!-- STATUS -->
    <?php if ($isEdit): ?>
        <div class="form-section-title">
            <i class="bi bi-circle"></i> Status
        </div>

        <div class="form-floating-custom">
            <div class="form-switch-custom d-flex align-items-center gap-3">
                <input type="checkbox" name="ativo" id="ativo" class="form-check-input" value="1" <?= ($c['ativo'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="ativo">
                    <i class="bi <?= ($c['ativo'] ?? 1) ? 'bi-check-circle text-success' : 'bi-x-circle text-danger' ?>"></i>
                    Cargo ativo
                </label>
            </div>
            <div class="form-text" style="margin-top:0.5rem;">
                <i class="bi bi-info-circle"></i> Cargos inativos não aparecem em listas principais
            </div>
        </div>
    <?php endif; ?>

    <!-- BOTÕES -->
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="bi <?= $isEdit ? 'bi-floppy' : 'bi-plus-lg' ?>"></i>
            <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Cancelar
        </a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const switchInput = document.getElementById('ativo');
    if (switchInput) {
        switchInput.addEventListener('change', function() {
            const label = document.querySelector('label[for="ativo"] i');
            if (this.checked) {
                label.className = 'bi bi-check-circle text-success';
            } else {
                label.className = 'bi bi-x-circle text-danger';
            }
        });
    }
});
</script>