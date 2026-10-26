<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$vagaId = (int) ($_GET['vaga_id'] ?? $_POST['vaga_id'] ?? 0);
$vagaModel = new Vaga();
$candidatoModel = new Candidato();

$vaga = $vagaModel->buscarPorId($vagaId);
if (!$vaga || $vaga['status'] !== 'aberta') {
    http_response_code(404);
    die('
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><title>Vaga indisponível</title></head>
        <body style="font-family:sans-serif;text-align:center;padding:50px;background:var(--rh-bg);">
            <div style="max-width:400px;margin:auto;background:var(--rh-card-bg);padding:40px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.1);">
                <i class="bi bi-x-circle" style="font-size:3rem;color:#ef4444;"></i>
                <h3 style="margin:1rem 0 0.5rem;color:var(--rh-text-primary);">Vaga não encontrada</h3>
                <p style="color:var(--rh-text-muted);">A vaga não está mais disponível ou foi encerrada.</p>
                <a href="vagas.php" style="display:inline-block;margin-top:1rem;padding:0.5rem 2rem;background:var(--rh-primary);color:white;text-decoration:none;border-radius:10px;">Ver vagas</a>
            </div>
        </body>
        </html>
    ');
}

$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'vaga_id'  => $vagaId,
        'nome'     => trim($_POST['nome'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
    ];
    if ($dados['nome'] === '') $erros[] = 'Informe seu nome.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (empty($_FILES['curriculo']['name'])) $erros[] = 'Anexe seu currículo (PDF ou Word).';

    $curriculoNome = null;
    if (empty($erros)) {
        $erroUpload = null;
        $curriculoNome = fazerUploadArquivo($_FILES['curriculo'], UPLOADS_CURRICULOS, $erroUpload);
        if ($erroUpload) $erros[] = $erroUpload;
    }

    if (empty($erros)) {
        $dados['curriculo_arquivo'] = $curriculoNome;
        $candidatoModel->criar($dados);
        $sucesso = true;
    }
}

$pageTitle = 'Candidatar-se — ' . $vaga['titulo'];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatar-se — <?= e($vaga['titulo']) ?></title>
    <script>
        (function() {
            var tema = localStorage.getItem('rh_tema') || 'light';
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        /* ============================================================
           CANDIDATURA - DESIGN PREMIUM
           ============================================================ */
        
        body {
            background: linear-gradient(135deg, var(--rh-bg) 0%, var(--bs-tertiary-bg) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 20px;
        }
        
        .card-candidatar {
            background: var(--rh-card-bg);
            border: 1px solid var(--rh-border);
            border-radius: 20px;
            box-shadow: var(--rh-shadow-hover);
            width: 100%;
            max-width: 520px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card-candidatar:hover {
            box-shadow: 0 24px 80px rgba(0,0,0,0.15);
        }
        
        .card-candidatar .card-header {
            background: linear-gradient(135deg, var(--rh-primary) 0%, #0B3242 100%);
            color: #fff;
            padding: 1.5rem 2rem;
            border: none;
        }
        
        .card-candidatar .card-header h5 {
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-candidatar .card-header h5 i {
            font-size: 1.3rem;
        }
        
        .card-candidatar .card-header .vaga-info {
            font-size: 0.8rem;
            opacity: 0.85;
            margin-top: 4px;
        }
        
        .card-candidatar .card-header .vaga-info i {
            margin-right: 4px;
        }
        
        .card-candidatar .card-header .btn-back {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .card-candidatar .card-header .btn-back:hover {
            color: #fff;
        }
        
        .card-candidatar .card-body {
            padding: 2rem;
        }
        
        .card-candidatar .form-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--rh-text-muted);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .card-candidatar .form-label i {
            margin-right: 4px;
        }
        
        .card-candidatar .form-label .required {
            color: #ef4444;
            margin-left: 4px;
        }
        
        .card-candidatar .form-control {
            border-radius: 10px;
            padding: 0.5rem 0.875rem;
            font-size: 0.9rem;
            border: 1.5px solid var(--rh-border);
            background: var(--bs-tertiary-bg);
            transition: all 0.3s ease;
            color: var(--rh-text-primary);
        }
        
        .card-candidatar .form-control:focus {
            border-color: var(--rh-primary);
            box-shadow: 0 0 0 4px rgba(21,94,117,0.1);
            background: var(--rh-card-bg);
        }
        
        .card-candidatar .form-control.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239,68,68,0.1);
        }
        
        .card-candidatar .form-control.is-valid {
            border-color: var(--rh-accent);
            box-shadow: 0 0 0 4px rgba(20,184,166,0.1);
        }
        
        .card-candidatar .btn-enviar {
            background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
            border: none;
            border-radius: 10px;
            padding: 0.65rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: #fff;
            width: 100%;
        }
        
        .card-candidatar .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(21,94,117,0.35);
        }
        
        .card-candidatar .btn-enviar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .card-candidatar .btn-enviar i {
            margin-right: 6px;
        }
        
        .card-candidatar .btn-voltar {
            border: none;
            background: transparent;
            color: var(--rh-text-muted);
            font-size: 0.85rem;
            width: 100%;
            padding: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .card-candidatar .btn-voltar:hover {
            color: var(--rh-primary);
        }
        
        /* ----- SUCCESS ----- */
        .success-icon {
            font-size: 4rem;
            color: var(--rh-accent);
            display: block;
            margin-bottom: 1rem;
        }
        
        .success-title {
            font-weight: 700;
            color: var(--rh-text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .success-text {
            color: var(--rh-text-muted);
            font-size: 0.9rem;
        }
        
        .success-text strong {
            color: var(--rh-text-primary);
        }
        
        /* ----- FILE UPLOAD ----- */
        .file-upload-wrapper {
            position: relative;
        }
        
        .file-upload-wrapper .file-name {
            font-size: 0.7rem;
            color: var(--rh-text-muted);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .file-upload-wrapper .file-name i {
            font-size: 0.7rem;
        }
        
        .file-upload-area {
            border: 2px dashed var(--rh-border);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: var(--bs-tertiary-bg);
        }
        
        .file-upload-area:hover {
            border-color: var(--rh-primary);
            background: var(--bs-secondary-bg);
        }
        
        .file-upload-area.has-file {
            border-color: var(--rh-accent);
            background: rgba(20,184,166,0.05);
        }
        
        .file-upload-area .upload-icon {
            font-size: 2rem;
            color: var(--rh-text-muted);
            display: block;
            margin-bottom: 0.3rem;
        }
        
        .file-upload-area .upload-text {
            font-size: 0.85rem;
            color: var(--rh-text-muted);
        }
        
        .file-upload-area .upload-hint {
            font-size: 0.65rem;
            color: var(--rh-text-muted);
        }
        
        /* ----- RESPONSIVE ----- */
        @media (max-width: 575.98px) {
            .card-candidatar .card-body {
                padding: 1.25rem;
            }
            .card-candidatar .card-header {
                padding: 1.25rem;
            }
            .card-candidatar .card-header h5 {
                font-size: 1rem;
            }
            .file-upload-area {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="card-candidatar">
    <?php if ($sucesso): ?>
        <!-- ===== SUCESSO ===== -->
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle-fill success-icon"></i>
            <h5 class="success-title">Candidatura enviada! 🎉</h5>
            <p class="success-text">
                Obrigado pelo interesse na vaga de <strong><?= e($vaga['titulo']) ?></strong>.<br>
                Nossa equipe de RH entrará em contato em breve.
            </p>
            <div class="d-flex flex-column gap-2 mt-4">
                <a href="vagas.php" class="btn btn-primary" style="border-radius:10px;padding:0.5rem 2rem;">
                    <i class="bi bi-arrow-left me-1"></i> Ver outras vagas
                </a>
                <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn btn-outline-secondary" style="border-radius:10px;padding:0.5rem 2rem;">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Área do colaborador
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- ===== FORMULÁRIO ===== -->
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5>
                        <i class="bi bi-send"></i> Candidatar-se
                    </h5>
                    <div class="vaga-info">
                        <i class="bi bi-briefcase"></i> <?= e($vaga['titulo']) ?>
                        <span class="mx-2">·</span>
                        <i class="bi bi-building"></i> <?= e($vaga['departamento_nome']) ?>
                    </div>
                </div>
                <a href="vagas.php" class="btn-back">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" novalidate id="candidaturaForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="vaga_id" value="<?= $vagaId ?>">

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

                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-person"></i> Nome completo
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="nome" class="form-control <?= in_array('Informe seu nome.', $erros) ? 'is-invalid' : '' ?>" 
                           value="<?= e($_POST['nome'] ?? '') ?>" placeholder="Seu nome completo" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-envelope"></i> E-mail
                        <span class="required">*</span>
                    </label>
                    <input type="email" name="email" class="form-control <?= in_array('E-mail inválido.', $erros) ? 'is-invalid' : '' ?>" 
                           value="<?= e($_POST['email'] ?? '') ?>" placeholder="seu@email.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-telephone"></i> Telefone
                    </label>
                    <input type="text" name="telefone" class="form-control" 
                           value="<?= e($_POST['telefone'] ?? '') ?>" placeholder="(84) 91234-5678">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-file-earmark"></i> Currículo (PDF/DOC)
                        <span class="required">*</span>
                    </label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="bi bi-cloud-arrow-up upload-icon"></i>
                        <div class="upload-text">Clique para selecionar seu currículo</div>
                        <div class="upload-hint">PDF, DOC, DOCX — máximo 5MB</div>
                        <input type="file" name="curriculo" id="fileInput" class="d-none" 
                               accept=".pdf,.doc,.docx" required>
                    </div>
                    <div id="fileInfo" style="display:none; margin-top:0.5rem;">
                        <div class="d-flex align-items-center gap-2 text-success">
                            <i class="bi bi-check-circle-fill"></i>
                            <span id="fileName" style="font-size:0.85rem;"></span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-enviar" id="submitBtn">
                    <i class="bi bi-send"></i> Enviar candidatura
                </button>

                <a href="vagas.php" class="btn-voltar">
                    <i class="bi bi-arrow-left"></i> Voltar para vagas
                </a>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const uploadArea = document.getElementById('fileUploadArea');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const submitBtn = document.getElementById('submitBtn');

    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });

    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--rh-primary)';
        this.style.background = 'rgba(21,94,117,0.05)';
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '';
        this.style.background = '';
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '';
        this.style.background = '';
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFileInfo();
        }
    });

    fileInput.addEventListener('change', updateFileInfo);

    function updateFileInfo() {
        if (fileInput.files.length) {
            const file = fileInput.files[0];
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            fileName.textContent = file.name + ' (' + sizeMB + ' MB)';
            fileInfo.style.display = 'block';
            uploadArea.classList.add('has-file');
            uploadArea.querySelector('.upload-text').textContent = 'Currículo selecionado';
            uploadArea.querySelector('.upload-hint').textContent = 'Clique para trocar o arquivo';
            uploadArea.querySelector('.upload-icon').className = 'bi bi-file-earmark-check upload-icon';
            uploadArea.style.borderColor = 'var(--rh-accent)';
        } else {
            fileInfo.style.display = 'none';
            uploadArea.classList.remove('has-file');
            uploadArea.querySelector('.upload-text').textContent = 'Clique para selecionar seu currículo';
            uploadArea.querySelector('.upload-hint').textContent = 'PDF, DOC, DOCX — máximo 5MB';
            uploadArea.querySelector('.upload-icon').className = 'bi bi-cloud-arrow-up upload-icon';
            uploadArea.style.borderColor = '';
        }
    }

    // Validação do formulário
    document.getElementById('candidaturaForm').addEventListener('submit', function(e) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Enviando...';
    });
});
</script>

</body>
</html>