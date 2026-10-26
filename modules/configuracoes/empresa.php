<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Configurações - Empresa';
$erros = [];
$sucesso = false;

// Buscar configurações atuais (exemplo)
$empresa = [
    'nome' => 'RH System',
    'cnpj' => '00.000.000/0001-00',
    'inscricao_estadual' => '123.456.789',
    'telefone' => '(11) 9999-9999',
    'celular' => '(11) 98888-8888',
    'email' => 'contato@rhsystem.com',
    'site' => 'www.rhsystem.com',
    'endereco' => 'Av. Principal, 1000',
    'numero' => '1000',
    'complemento' => 'Sala 101',
    'bairro' => 'Centro',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'cep' => '00000-000',
    'logo' => null,
];

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    
    $empresa = [
        'nome' => $_POST['nome'] ?? '',
        'cnpj' => $_POST['cnpj'] ?? '',
        'inscricao_estadual' => $_POST['inscricao_estadual'] ?? '',
        'telefone' => $_POST['telefone'] ?? '',
        'celular' => $_POST['celular'] ?? '',
        'email' => $_POST['email'] ?? '',
        'site' => $_POST['site'] ?? '',
        'endereco' => $_POST['endereco'] ?? '',
        'numero' => $_POST['numero'] ?? '',
        'complemento' => $_POST['complemento'] ?? '',
        'bairro' => $_POST['bairro'] ?? '',
        'cidade' => $_POST['cidade'] ?? '',
        'estado' => $_POST['estado'] ?? '',
        'cep' => $_POST['cep'] ?? '',
    ];
    
    // Validações
    if (empty($empresa['nome'])) {
        $erros[] = 'O nome da empresa é obrigatório.';
    }
    
    if (empty($empresa['email'])) {
        $erros[] = 'O e-mail é obrigatório.';
    } elseif (!filter_var($empresa['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido.';
    }
    
    if (empty($erros)) {
        // Salvar configurações...
        $sucesso = true;
        definirMensagem('success', 'Configurações da empresa atualizadas com sucesso!');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   EMPRESA - DESIGN PREMIUM
   ============================================================ */

/* ----- HEADER DA PÁGINA ----- */
.page-header-premium {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
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
    background: var(--rh-primary);
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
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
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

/* ----- UPLOAD LOGO ----- */
.logo-upload {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: 12px;
    border: 2px dashed var(--rh-border);
    transition: all 0.3s ease;
}

.logo-upload:hover {
    border-color: var(--rh-primary);
    background: var(--rh-card-bg);
}

.logo-upload .logo-preview {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--rh-text-muted);
    flex-shrink: 0;
    overflow: hidden;
}

.logo-upload .logo-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.logo-upload .logo-actions {
    flex: 1;
}

.logo-upload .logo-actions .btn-upload {
    padding: 0.3rem 1.2rem;
    border-radius: 8px;
    font-size: 0.8rem;
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--bs-body-color);
    transition: all 0.3s ease;
}

.logo-upload .logo-actions .btn-upload:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
}

.logo-upload .logo-actions .btn-remove {
    padding: 0.3rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
    border: none;
    background: transparent;
    color: var(--rh-danger);
    transition: all 0.3s ease;
}

.logo-upload .logo-actions .btn-remove:hover {
    background: var(--rh-danger);
    color: #fff;
}

.logo-upload .logo-actions small {
    display: block;
    color: var(--rh-text-muted);
    font-size: 0.65rem;
    margin-top: 0.25rem;
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
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
    box-shadow: 0 4px 14px rgba(21, 94, 117, 0.25);
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(21, 94, 117, 0.35);
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
    
    .logo-upload {
        flex-direction: column;
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
[data-bs-theme="dark"] .logo-upload {
    border-color: var(--rh-border);
}

[data-bs-theme="dark"] .logo-upload:hover {
    border-color: var(--rh-primary-light);
}

[data-bs-theme="dark"] .page-header-premium {
    background: linear-gradient(135deg, #0a2a38, #0e7490);
}
</style>

<!-- ============================================================
     HEADER DA PÁGINA
     ============================================================ -->
<div class="page-header-premium">
    <div class="header-left">
        <div class="header-icon">
            <i class="bi bi-building"></i>
        </div>
        <div class="header-title">
            <h5>Configurações da Empresa</h5>
            <p>Gerencie os dados cadastrais da sua organização</p>
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
            <div class="header-icon-sm"><i class="bi bi-pencil"></i></div>
            <h6>Dados Cadastrais</h6>
            <span class="badge-status ready ms-auto">
                <i class="bi bi-check-circle me-1"></i> Configurando
            </span>
        </div>
        <div class="card-body">
            <!-- Mensagens -->
            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #10b981;">
                    <i class="bi bi-check-circle me-2"></i> 
                    <strong>Sucesso!</strong> Configurações da empresa atualizadas com sucesso!
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

            <form method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <!-- Logo -->
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-image me-1"></i> Logo da Empresa</label>
                    <div class="logo-upload">
                        <div class="logo-preview">
                            <?php if (!empty($empresa['logo'])): ?>
                                <img src="<?= e($empresa['logo']) ?>" alt="Logo">
                            <?php else: ?>
                                <i class="bi bi-building"></i>
                            <?php endif; ?>
                        </div>
                        <div class="logo-actions">
                            <div>
                                <button type="button" class="btn-upload" onclick="document.getElementById('logoInput').click();">
                                    <i class="bi bi-cloud-upload me-1"></i> Upload
                                </button>
                                <button type="button" class="btn-remove" onclick="removerLogo();">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                Formatos: JPG, PNG, SVG • Máx: 2MB • 200x200px
                            </small>
                            <input type="file" id="logoInput" accept="image/*" style="display:none;">
                        </div>
                    </div>
                </div>

                <!-- Informações Básicas -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-info-circle"></i> Informações Básicas</span>
                    <span class="line"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome da Empresa <span class="required">*</span></label>
                    <input type="text" name="nome" class="form-control <?= in_array('nome', $erros) ? 'is-invalid' : '' ?>" 
                           value="<?= e($empresa['nome']) ?>" placeholder="Digite o nome da empresa" required>
                    <div class="form-text"><i class="bi bi-info-circle"></i> Nome oficial da organização</div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" class="form-control" value="<?= e($empresa['cnpj']) ?>" 
                               placeholder="00.000.000/0001-00">
                        <div class="form-text"><i class="bi bi-info-circle"></i> Cadastro Nacional da Pessoa Jurídica</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Inscrição Estadual</label>
                        <input type="text" name="inscricao_estadual" class="form-control" 
                               value="<?= e($empresa['inscricao_estadual'] ?? '') ?>" placeholder="123.456.789">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">Telefone <span class="required">*</span></label>
                        <input type="text" name="telefone" class="form-control" value="<?= e($empresa['telefone']) ?>" 
                               placeholder="(11) 9999-9999" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Celular</label>
                        <input type="text" name="celular" class="form-control" value="<?= e($empresa['celular']) ?>" 
                               placeholder="(11) 98888-8888">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="mb-3">
                        <label class="form-label">E-mail <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control <?= in_array('email', $erros) ? 'is-invalid' : '' ?>" 
                               value="<?= e($empresa['email']) ?>" placeholder="contato@empresa.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site</label>
                        <input type="text" name="site" class="form-control" value="<?= e($empresa['site']) ?>" 
                               placeholder="www.empresa.com">
                    </div>
                </div>

                <!-- Endereço -->
                <div class="section-divider">
                    <span class="label"><i class="bi bi-geo-alt"></i> Endereço</span>
                    <span class="line"></span>
                </div>

                <div class="grid-3">
                    <div class="mb-3">
                        <label class="form-label">CEP</label>
                        <input type="text" name="cep" class="form-control" value="<?= e($empresa['cep']) ?>" 
                               placeholder="00000-000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Selecione</option>
                            <option value="AC" <?= $empresa['estado'] === 'AC' ? 'selected' : '' ?>>AC</option>
                            <option value="AL" <?= $empresa['estado'] === 'AL' ? 'selected' : '' ?>>AL</option>
                            <option value="AP" <?= $empresa['estado'] === 'AP' ? 'selected' : '' ?>>AP</option>
                            <option value="AM" <?= $empresa['estado'] === 'AM' ? 'selected' : '' ?>>AM</option>
                            <option value="BA" <?= $empresa['estado'] === 'BA' ? 'selected' : '' ?>>BA</option>
                            <option value="CE" <?= $empresa['estado'] === 'CE' ? 'selected' : '' ?>>CE</option>
                            <option value="DF" <?= $empresa['estado'] === 'DF' ? 'selected' : '' ?>>DF</option>
                            <option value="ES" <?= $empresa['estado'] === 'ES' ? 'selected' : '' ?>>ES</option>
                            <option value="GO" <?= $empresa['estado'] === 'GO' ? 'selected' : '' ?>>GO</option>
                            <option value="MA" <?= $empresa['estado'] === 'MA' ? 'selected' : '' ?>>MA</option>
                            <option value="MT" <?= $empresa['estado'] === 'MT' ? 'selected' : '' ?>>MT</option>
                            <option value="MS" <?= $empresa['estado'] === 'MS' ? 'selected' : '' ?>>MS</option>
                            <option value="MG" <?= $empresa['estado'] === 'MG' ? 'selected' : '' ?>>MG</option>
                            <option value="PA" <?= $empresa['estado'] === 'PA' ? 'selected' : '' ?>>PA</option>
                            <option value="PB" <?= $empresa['estado'] === 'PB' ? 'selected' : '' ?>>PB</option>
                            <option value="PR" <?= $empresa['estado'] === 'PR' ? 'selected' : '' ?>>PR</option>
                            <option value="PE" <?= $empresa['estado'] === 'PE' ? 'selected' : '' ?>>PE</option>
                            <option value="PI" <?= $empresa['estado'] === 'PI' ? 'selected' : '' ?>>PI</option>
                            <option value="RJ" <?= $empresa['estado'] === 'RJ' ? 'selected' : '' ?>>RJ</option>
                            <option value="RN" <?= $empresa['estado'] === 'RN' ? 'selected' : '' ?>>RN</option>
                            <option value="RS" <?= $empresa['estado'] === 'RS' ? 'selected' : '' ?>>RS</option>
                            <option value="RO" <?= $empresa['estado'] === 'RO' ? 'selected' : '' ?>>RO</option>
                            <option value="RR" <?= $empresa['estado'] === 'RR' ? 'selected' : '' ?>>RR</option>
                            <option value="SC" <?= $empresa['estado'] === 'SC' ? 'selected' : '' ?>>SC</option>
                            <option value="SP" <?= $empresa['estado'] === 'SP' ? 'selected' : '' ?>>SP</option>
                            <option value="SE" <?= $empresa['estado'] === 'SE' ? 'selected' : '' ?>>SE</option>
                            <option value="TO" <?= $empresa['estado'] === 'TO' ? 'selected' : '' ?>>TO</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" class="form-control" value="<?= e($empresa['cidade']) ?>" 
                               placeholder="São Paulo">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="mb-3">
                        <label class="form-label">Logradouro</label>
                        <input type="text" name="endereco" class="form-control" value="<?= e($empresa['endereco']) ?>" 
                               placeholder="Av. Principal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Número</label>
                        <input type="text" name="numero" class="form-control" value="<?= e($empresa['numero']) ?>" 
                               placeholder="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Complemento</label>
                        <input type="text" name="complemento" class="form-control" value="<?= e($empresa['complemento'] ?? '') ?>" 
                               placeholder="Sala 101">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bairro</label>
                    <input type="text" name="bairro" class="form-control" value="<?= e($empresa['bairro'] ?? '') ?>" 
                           placeholder="Centro">
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

    <!-- Info adicional -->
    <div class="mt-4 p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--rh-border);">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <i class="bi bi-info-circle" style="color:var(--rh-primary);font-size:1.1rem;"></i>
            <span style="font-size:0.8rem;color:var(--rh-text-muted);">
                <strong class="text-body">Dica:</strong> Os dados da empresa são utilizados em 
                relatórios, recibos, documentos oficiais e comunicações do sistema.
            </span>
            <span class="badge bg-light text-dark ms-auto" style="font-size:0.6rem;">
                <i class="bi bi-clock me-1"></i> Última atualização: hoje
            </span>
        </div>
    </div>
</div>

<script>
// Função para remover logo
function removerLogo() {
    if (confirm('Deseja remover a logo da empresa?')) {
        // Implementar remoção
        document.querySelector('.logo-preview').innerHTML = '<i class="bi bi-building"></i>';
    }
}

// Máscara para CNPJ
document.addEventListener('DOMContentLoaded', function() {
    const cnpjInput = document.querySelector('input[name="cnpj"]');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                this.value = value;
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>