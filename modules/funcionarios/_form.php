<?php
/**
 * _form.php - Formulário de funcionário (criar/editar)
 * Este arquivo é incluído por criar.php e editar.php
 */

$isEdit = isset($id) && $id > 0;
$action = $isEdit ? "editar.php?id={$id}" : "criar.php";
?>

<style>
.form-section-title {
    font-weight: 700;
    font-size: 0.8rem;
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--rh-border);
}

.form-section-title i {
    color: var(--rh-primary);
    font-size: 0.9rem;
}

.required-field::after {
    content: '*';
    color: #ef4444;
    margin-left: 4px;
    font-weight: 700;
}

.status-options {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.status-options .status-option {
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    border: 2px solid var(--rh-border);
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.75rem;
    font-weight: 500;
    background: transparent;
    color: var(--bs-secondary-color);
}

.status-options .status-option:hover {
    border-color: var(--rh-primary);
}

.status-options .status-option.active {
    border-color: var(--rh-primary);
    background: var(--rh-primary);
    color: #fff;
}

.status-options .status-option.ativo { border-color: #10b981; color: #10b981; }
.status-options .status-option.ativo.active { background: #10b981; color: #fff; }
.status-options .status-option.ferias { border-color: #f59e0b; color: #f59e0b; }
.status-options .status-option.ferias.active { background: #f59e0b; color: #fff; }
.status-options .status-option.inativo { border-color: #ef4444; color: #ef4444; }
.status-options .status-option.inativo.active { background: #ef4444; color: #fff; }
.status-options .status-option.licenca { border-color: #0ea5e9; color: #0ea5e9; }
.status-options .status-option.licenca.active { background: #0ea5e9; color: #fff; }

.form-floating-custom {
    margin-bottom: 1rem;
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
    margin-right: 4px;
    font-size: 0.7rem;
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

/* ----- PHOTO UPLOAD ----- */
.photo-upload {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem;
    border-radius: 12px;
    background: var(--bs-tertiary-bg);
    border: 2px dashed var(--rh-border);
    transition: all 0.3s ease;
}

.photo-upload:hover {
    border-color: var(--rh-primary);
    background: var(--bs-secondary-bg);
}

.photo-upload .photo-preview {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 3px solid var(--rh-border);
    background: var(--rh-card-bg);
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-upload .photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-upload .photo-preview .no-photo {
    font-size: 2rem;
    font-weight: 700;
    color: var(--rh-text-muted);
    background: var(--bs-tertiary-bg);
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-upload .photo-actions .btn {
    border-radius: 8px;
    padding: 0.3rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.photo-upload .photo-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.photo-upload .photo-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(21, 94, 117, 0.3);
}

.photo-upload .photo-actions .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    margin-top: 4px;
}

/* ----- FORM DIVIDER ----- */
.form-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.5rem 0;
}

.form-divider::before,
.form-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--rh-border);
}

.form-divider span {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--rh-text-muted);
    font-weight: 600;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-divider span i {
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

.form-actions .btn-danger {
    border: none;
    background: #ef4444;
    color: #fff;
}

.form-actions .btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .photo-upload {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .photo-upload .photo-preview {
        width: 100px;
        height: 100px;
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
    .photo-upload .photo-preview {
        width: 80px;
        height: 80px;
    }
    .status-options .status-option {
        font-size: 0.65rem;
        padding: 0.2rem 0.6rem;
    }
}
</style>

<!-- ============================================================
     ERROS
     ============================================================ -->
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

<!-- ============================================================
     FORM
     ============================================================ -->
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

    <!-- DADOS PESSOAIS -->
    <div class="form-section-title">
        <i class="bi bi-person"></i> Dados Pessoais
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-person"></i> Nome completo</label>
                <input type="text" name="nome" class="form-control <?= in_array('Informe o nome.', $erros) ? 'is-invalid' : '' ?>" 
                       value="<?= e($funcionario['nome'] ?? '') ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-id-card"></i> CPF/BI</label>
                <input type="text" name="cpf" class="form-control <?= in_array('Informe o CPF/BI.', $erros) ? 'is-invalid' : '' ?>" 
                       value="<?= e($funcionario['cpf'] ?? '') ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label"><i class="bi bi-card-text"></i> RG</label>
                <input type="text" name="rg" class="form-control" value="<?= e($funcionario['rg'] ?? '') ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label"><i class="bi bi-calendar3"></i> Data de nascimento</label>
                <input type="date" name="data_nascimento" class="form-control" 
                       value="<?= e($funcionario['data_nascimento'] ?? '') ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label"><i class="bi bi-gender-ambiguous"></i> Género</label>
                <select name="genero" class="form-select">
                    <option value="">Selecione...</option>
                    <option value="Masculino" <?= ($funcionario['genero'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                    <option value="Feminino" <?= ($funcionario['genero'] ?? '') === 'Feminino' ? 'selected' : '' ?>>Feminino</option>
                    <option value="Outro" <?= ($funcionario['genero'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-envelope"></i> E-mail</label>
                <input type="email" name="email" class="form-control <?= in_array('E-mail inválido.', $erros) ? 'is-invalid' : '' ?>" 
                       value="<?= e($funcionario['email'] ?? '') ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label"><i class="bi bi-telephone"></i> Telefone</label>
                <input type="text" name="telefone" class="form-control" value="<?= e($funcionario['telefone'] ?? '') ?>">
                <div class="form-text"><i class="bi bi-info-circle"></i> Ex: (84) 98765-4321</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label"><i class="bi bi-geo-alt"></i> Endereço</label>
                <input type="text" name="endereco" class="form-control" value="<?= e($funcionario['endereco'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- DADOS PROFISSIONAIS -->
    <div class="form-divider">
        <span><i class="bi bi-briefcase"></i> Dados Profissionais</span>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-briefcase"></i> Cargo</label>
                <select name="cargo_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($cargos as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($funcionario['cargo_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-diagram-3"></i> Departamento</label>
                <select name="departamento_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($funcionario['departamento_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                            <?= e($d['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-calendar-check"></i> Data de admissão</label>
                <input type="date" name="data_admissao" class="form-control <?= in_array('Informe a data de admissão.', $erros) ? 'is-invalid' : '' ?>" 
                       value="<?= e($funcionario['data_admissao'] ?? '') ?>" required>
            </div>
        </div>
        <?php if (usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH])): ?>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-cash-coin"></i> Salário (MT)</label>
                <input type="number" step="0.01" name="salario" class="form-control <?= in_array('Salário inválido.', $erros) ? 'is-invalid' : '' ?>" 
                       value="<?= e($funcionario['salario'] ?? '') ?>" required>
                <div class="form-text"><i class="bi bi-info-circle"></i> Valor mensal em Meticais (MT)</div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-md-6">
            <div class="form-floating-custom">
                <label class="form-label required-field"><i class="bi bi-circle"></i> Status</label>
                <div class="status-options" role="group">
                    <label class="status-option ativo <?= ($funcionario['status'] ?? 'ativo') === 'ativo' ? 'active' : '' ?>">
                        <input type="radio" name="status" value="ativo" <?= ($funcionario['status'] ?? 'ativo') === 'ativo' ? 'checked' : '' ?> hidden>
                        <i class="bi bi-check-circle"></i> Ativo
                    </label>
                    <label class="status-option ferias <?= ($funcionario['status'] ?? '') === 'ferias' ? 'active' : '' ?>">
                        <input type="radio" name="status" value="ferias" <?= ($funcionario['status'] ?? '') === 'ferias' ? 'checked' : '' ?> hidden>
                        <i class="bi bi-sun"></i> Férias
                    </label>
                    <label class="status-option licenca <?= ($funcionario['status'] ?? '') === 'licenca' ? 'active' : '' ?>">
                        <input type="radio" name="status" value="licenca" <?= ($funcionario['status'] ?? '') === 'licenca' ? 'checked' : '' ?> hidden>
                        <i class="bi bi-heart-pulse"></i> Licença
                    </label>
                    <label class="status-option inativo <?= ($funcionario['status'] ?? '') === 'inativo' ? 'active' : '' ?>">
                        <input type="radio" name="status" value="inativo" <?= ($funcionario['status'] ?? '') === 'inativo' ? 'checked' : '' ?> hidden>
                        <i class="bi bi-person-x"></i> Inativo
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- FOTO -->
    <div class="form-divider">
        <span><i class="bi bi-image"></i> Foto</span>
    </div>

    <div class="photo-upload">
        <div class="photo-preview">
            <?php if (!empty($funcionario['foto'] ?? '')): ?>
                <img src="foto.php?id=<?= $id ?? 0 ?>" alt="Foto">
            <?php else: ?>
                <div class="no-photo">
                    <i class="bi bi-person"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="photo-actions">
            <label class="btn btn-primary btn-sm">
                <i class="bi bi-upload"></i> Escolher foto
                <input type="file" name="foto" accept="image/*" hidden>
            </label>
            <div class="form-text">
                <i class="bi bi-info-circle"></i> Formatos: JPG, PNG, GIF. Máx: 2MB
            </div>
            <?php if (!empty($funcionario['foto'] ?? '')): ?>
                <div class="form-text text-warning">
                    <i class="bi bi-exclamation-triangle"></i> Deixe em branco para manter a foto atual
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- BOTÕES -->
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy"></i> Salvar alterações
        </button>
        <a href="visualizar.php?id=<?= $id ?? 0 ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Cancelar
        </a>
        <?php if ($isEdit && usuarioTemPerfil([PERFIL_ADMIN, PERFIL_RH]) && ($funcionario['status'] ?? '') !== 'inativo' && ($funcionario['status'] ?? '') !== 'desligado'): ?>
            <a href="desligar.php?id=<?= $id ?>" class="btn btn-danger ms-auto js-confirm" 
               data-confirm-msg="Confirma o desligamento deste funcionário?">
                <i class="bi bi-person-x"></i> Desligar
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- ============================================================
     SCRIPT PARA STATUS
     ============================================================ -->
<script>
document.querySelectorAll('.status-option').forEach(label => {
    label.addEventListener('click', function() {
        document.querySelectorAll('.status-option').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        const radio = this.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    });
});
</script>