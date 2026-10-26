<?php
/** _form.php - reaproveitado por criar.php e editar.php */
$u = $usuario ?? [];
$ehEdicao = isset($u['id']);
?>

<style>
.form-premium .form-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    margin-bottom: 0.3rem;
}

.form-premium .form-control,
.form-premium .form-select {
    border-radius: 10px;
    border-color: var(--rh-border);
    padding: 0.65rem 1rem;
    transition: all 0.2s ease;
    background: var(--bs-body-bg);
}

.form-premium .form-control:focus,
.form-premium .form-select:focus {
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
}

.form-premium .form-control.is-invalid,
.form-premium .form-select.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.form-premium .form-text {
    font-size: 0.75rem;
    color: var(--rh-text-muted);
    margin-top: 0.25rem;
}

.form-premium .form-check {
    padding-left: 2.2rem;
}

.form-premium .form-check-input {
    width: 1.1rem;
    height: 1.1rem;
    margin-left: -2.2rem;
    border-radius: 4px;
    border-color: var(--rh-border);
    cursor: pointer;
}

.form-premium .form-check-input:checked {
    background-color: var(--rh-primary);
    border-color: var(--rh-primary);
}

.form-premium .form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.15);
}

.form-premium .form-check-label {
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
}

.form-premium .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: 0;
    padding: 0.6rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.form-premium .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.25);
}

.form-premium .btn-outline-secondary {
    border-radius: 10px;
    padding: 0.6rem 2rem;
    font-weight: 600;
}

.form-premium .required-star {
    color: #ef4444;
    margin-left: 2px;
}

@media (max-width: 575.98px) {
    .form-premium .btn-primary,
    .form-premium .btn-outline-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<form method="POST" novalidate class="form-premium">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    
    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger border-0 rounded-3" style="background:rgba(239,68,68,0.08);border-left:4px solid #ef4444;">
            <ul class="mb-0 ps-3">
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row g-3">
        <div class="col-12">
            <div class="mb-3">
                <label class="form-label">
                    Nome completo <span class="required-star">*</span>
                </label>
                <input type="text" name="nome" class="form-control <?= isset($erros) && empty($u['nome']) ? 'is-invalid' : '' ?>" 
                       required value="<?= e($u['nome'] ?? '') ?>" placeholder="Ex: João Silva">
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label class="form-label">
                    E-mail <span class="required-star">*</span>
                </label>
                <input type="email" name="email" class="form-control <?= isset($erros) && empty($u['email']) ? 'is-invalid' : '' ?>" 
                       required value="<?= e($u['email'] ?? '') ?>" placeholder="exemplo@empresa.com">
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label class="form-label">
                    Senha <?= $ehEdicao ? '<span class="text-muted" style="font-weight:400;text-transform:none;font-size:0.7rem;">(deixe em branco para manter)</span>' : '<span class="required-star">*</span>' ?>
                </label>
                <input type="password" name="senha" class="form-control" 
                       <?= $ehEdicao ? '' : 'required' ?> minlength="6" 
                       placeholder="<?= $ehEdicao ? '••••••••' : 'Mínimo 6 caracteres' ?>">
                <div class="form-text">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= $ehEdicao ? 'Preencha apenas se desejar alterar a senha.' : 'A senha deve ter no mínimo 6 caracteres.' ?>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label class="form-label">
                    Perfil de acesso <span class="required-star">*</span>
                </label>
                <select name="perfil" class="form-select" required>
                    <?php if (usuarioAtualPerfil() === PERFIL_SUPER_ADMIN): ?>
                        <option value="super_admin" <?= ($u['perfil'] ?? '') === PERFIL_SUPER_ADMIN ? 'selected' : '' ?>>
                            🛡️ Super Admin
                        </option>
                    <?php endif; ?>
                    <option value="administrador_rh" <?= ($u['perfil'] ?? '') === PERFIL_ADMIN_RH ? 'selected' : '' ?>>
                        👔 Administrador RH
                    </option>
                    <option value="gestor" <?= ($u['perfil'] ?? '') === PERFIL_GESTOR ? 'selected' : '' ?>>
                        📋 Gestor
                    </option>
                    <option value="funcionario" <?= ($u['perfil'] ?? PERFIL_FUNCIONARIO) === PERFIL_FUNCIONARIO ? 'selected' : '' ?>>
                        👤 Funcionário
                    </option>
                    <option value="auditor" <?= ($u['perfil'] ?? '') === PERFIL_AUDITOR ? 'selected' : '' ?>>
                        📊 Auditor
                    </option>
                </select>
                <div class="form-text">
                    <i class="bi bi-shield-check me-1"></i>
                    <?php if (usuarioAtualPerfil() === PERFIL_SUPER_ADMIN): ?>
                        Super Admin: acesso total ao sistema.
                    <?php else: ?>
                        Administrador RH: gestão de pessoas e folha.<br>
                        Gestor: gestão de equipe.<br>
                        Funcionário: acesso pessoal.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label class="form-label">Funcionário vinculado</label>
                <select name="funcionario_id" class="form-select">
                    <option value="">Nenhum (conta apenas de sistema)</option>
                    <?php foreach ($funcionarios as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= ($u['funcionario_id'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                            <?= e($f['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    <i class="bi bi-person me-1"></i>
                    Necessário para login, ponto, férias e dados pessoais.
                    <?php if (usuarioAtualPerfil() === PERFIL_SUPER_ADMIN): ?>
                        O Super Admin pode não ter funcionário vinculado.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($ehEdicao): ?>
            <div class="col-12">
                <div class="mb-3 form-check">
                    <input type="checkbox" name="ativo" id="ativo" class="form-check-input" value="1" <?= ($u['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo">
                        <i class="bi bi-check-circle me-1"></i> Conta ativa
                    </label>
                    <div class="form-text" style="margin-left:0;">
                        Usuários inativos não podem acessar o sistema.
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="col-12">
            <hr style="border-color:var(--rh-border);margin:0.5rem 0 1.5rem 0;">
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> <?= $ehEdicao ? 'Atualizar' : 'Criar' ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancelar
                </a>
            </div>
        </div>
    </div>
</form>