<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// ============================================================
// PERMISSÕES CORRETAS
// ============================================================
// O módulo de documentos deve ser acessível por:
// - PERFIL_ADMIN_RH (Administrador RH)
// - PERFIL_ADMIN (Administrador)
// - PERFIL_RH (Recursos Humanos)
// - PERFIL_GESTOR (Gestor - apenas para sua equipe)
// ============================================================
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR]);

$funcionarioId = (int) ($_GET['funcionario_id'] ?? $_POST['funcionario_id'] ?? 0);
$funcionarioModel = new Funcionario();
$documentoModel = new Documento();

$funcionario = $funcionarioModel->buscarPorId($funcionarioId);
if (!$funcionario) {
    definirMensagem('danger', 'Funcionário não encontrado.');
    redirecionar('/modules/funcionarios/index.php');
}

// ============================================================
// VERIFICAÇÃO DE PERMISSÃO PARA GESTOR
// ============================================================
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    if (!$meuFuncionario || $funcionario['departamento_id'] != $meuFuncionario['departamento_id']) {
        http_response_code(403);
        die('
            <!DOCTYPE html>
            <html>
            <head><meta charset="UTF-8"><title>Acesso negado</title></head>
            <body style="font-family:sans-serif;text-align:center;padding:50px;background:var(--rh-bg);">
                <div style="max-width:400px;margin:auto;background:var(--rh-card-bg);padding:40px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.1);">
                    <i class="bi bi-shield-lock" style="font-size:3rem;color:#ef4444;"></i>
                    <h3 style="margin:1rem 0 0.5rem;color:var(--rh-text-primary);">Acesso negado</h3>
                    <p style="color:var(--rh-text-muted);">Você não tem permissão para enviar documentos para este funcionário.</p>
                    <a href="'.BASE_URL.'/modules/funcionarios/visualizar.php?id='.$funcionarioId.'" style="display:inline-block;margin-top:1rem;padding:0.5rem 2rem;background:var(--rh-primary);color:white;text-decoration:none;border-radius:10px;">Voltar</a>
                </div>
            </body>
            </html>
        ');
    }
}

$pageTitle = 'Enviar Documento — ' . $funcionario['nome'];
$erros = [];

// ============================================================
// VERIFICAÇÃO DO DIRETÓRIO DE UPLOAD
// ============================================================
$uploadDir = UPLOADS_DOCUMENTOS;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $tipo = $_POST['tipo'] ?? 'outro';

    // Verifica se o arquivo foi enviado
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
        $erros[] = 'Selecione um arquivo.';
    } elseif ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Erro no upload do arquivo. Código: ' . $_FILES['arquivo']['error'];
    } else {
        // Verifica o tamanho do arquivo
        $maxSize = LIMITE_UPLOAD_MB * 1024 * 1024;
        if ($_FILES['arquivo']['size'] > $maxSize) {
            $erros[] = 'O arquivo excede o limite de ' . LIMITE_UPLOAD_MB . 'MB.';
        }
        
        // Verifica a extensão
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (!in_array($extensao, $extensoesPermitidas)) {
            $erros[] = 'Extensão de arquivo não permitida. Use: PDF, JPG, PNG, DOC, DOCX.';
        }
        
        // Verifica o MIME type (se disponível)
        if (empty($erros) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['arquivo']['tmp_name']);
            finfo_close($finfo);
            
            $mimesPermitidos = [
                'pdf' => ['application/pdf'],
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'doc' => ['application/msword'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip']
            ];
            if (!in_array($mime, $mimesPermitidos[$extensao] ?? [], true)) {
                $erros[] = 'O conteúdo do arquivo não corresponde ao tipo informado.';
            }
        }
        
        // Faz o upload se não houver erros
        if (empty($erros)) {
            $nomeOriginal = basename($_FILES['arquivo']['name']);
            $novoNome = bin2hex(random_bytes(16)) . '.' . $extensao;
            $destino = $uploadDir . DIRECTORY_SEPARATOR . $novoNome;
            
            if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
                $erros[] = 'Não foi possível salvar o arquivo no servidor.';
            } else {
                @chmod($destino, 0640);
                
                // Salva no banco de dados
                $documentoModel->criar([
                    'funcionario_id'  => $funcionarioId,
                    'tipo'            => $tipo,
                    'nome_original'   => $nomeOriginal,
                    'caminho_arquivo' => $novoNome,
                    'tamanho_bytes'   => $_FILES['arquivo']['size'],
                    'enviado_por'     => usuarioAtualId(),
                ]);
                registrarAuditoria('documento_enviado', "Funcionário $funcionarioId - $tipo");
                definirMensagem('success', 'Documento enviado com sucesso.');
                redirecionar('/modules/funcionarios/visualizar.php?id=' . $funcionarioId);
            }
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<!-- ============================================================
     CSS DA PÁGINA
     ============================================================ -->
<style>
.form-container {
    max-width: 600px;
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

.form-card .form-card-header .funcionario-info {
    margin-left: auto;
    font-size: 0.8rem;
    color: var(--rh-text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-card .form-card-body {
    padding: 1.5rem;
}

.avatar-sm {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    color: #fff;
    font-weight: 700;
    font-size: 0.7rem;
    flex-shrink: 0;
    text-transform: uppercase;
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

/* ----- FILE UPLOAD AREA ----- */
.file-upload-area {
    border: 2px dashed var(--rh-border);
    border-radius: 12px;
    padding: 2rem 1rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: var(--bs-tertiary-bg);
}

.file-upload-area:hover {
    border-color: var(--rh-primary);
    background: var(--bs-secondary-bg);
}

.file-upload-area i {
    font-size: 2.5rem;
    color: var(--rh-text-muted);
    display: block;
    margin-bottom: 0.5rem;
}

.file-upload-area .upload-text {
    font-size: 0.9rem;
    color: var(--rh-text-muted);
}

.file-upload-area .upload-hint {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
    margin-top: 0.25rem;
}

.file-upload-area.has-file {
    border-color: var(--rh-accent);
    background: rgba(20, 184, 166, 0.08);
}

.file-upload-area.has-file i {
    color: var(--rh-accent);
}

.file-upload-area.has-file .upload-text {
    color: var(--rh-accent);
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
    .form-card .form-card-header {
        flex-direction: column;
        align-items: stretch;
    }
    .form-card .form-card-header .funcionario-info {
        margin-left: 0;
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
    .file-upload-area {
        padding: 1.5rem 1rem;
    }
    .file-upload-area i {
        font-size: 2rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="page-header-custom">
    <h5>
        <i class="bi bi-file-earmark-arrow-up"></i> Enviar Documento
    </h5>
    <a href="<?= BASE_URL ?>/modules/funcionarios/visualizar.php?id=<?= $funcionarioId ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i> Voltar ao perfil
    </a>
</div>

<!-- ============================================================
     FORMULÁRIO
     ============================================================ -->
<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-file-earmark-arrow-up" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Enviar Documento</h5>
            <span class="funcionario-info">
                <span class="avatar-sm"><?= e(strtoupper(substr($funcionario['nome'], 0, 1))) ?></span>
                <?= e($funcionario['nome']) ?>
            </span>
        </div>
        <div class="form-card-body">
            <form method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="funcionario_id" value="<?= $funcionarioId ?>">

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
                        <i class="bi bi-tag"></i> Tipo de documento
                        <span class="required">*</span>
                    </label>
                    <select name="tipo" class="form-select" required>
                        <option value="contrato">📄 Contrato</option>
                        <option value="bi">🪪 BI / Documento de identidade</option>
                        <option value="certificado">📜 Certificado</option>
                        <option value="outro">📎 Outro</option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Selecione a categoria do documento
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label">
                        <i class="bi bi-file-earmark"></i> Arquivo
                        <span class="required">*</span>
                    </label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <div class="upload-text">Clique para selecionar um arquivo</div>
                        <div class="upload-hint">PDF, JPG, PNG, DOC — máx. <?= LIMITE_UPLOAD_MB ?>MB</div>
                        <input type="file" name="arquivo" id="fileInput" class="d-none" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div class="form-text" id="fileInfo" style="display:none;">
                        <i class="bi bi-check-circle text-success"></i> Arquivo selecionado: <span id="fileName"></span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-upload me-1"></i> Enviar
                    </button>
                    <a href="<?= BASE_URL ?>/modules/funcionarios/visualizar.php?id=<?= $funcionarioId ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
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
            uploadArea.querySelector('.upload-text').textContent = 'Arquivo selecionado';
            uploadArea.querySelector('.upload-hint').textContent = 'Clique para trocar o arquivo';
            uploadArea.querySelector('i').className = 'bi bi-file-earmark-check';
        } else {
            fileInfo.style.display = 'none';
            uploadArea.classList.remove('has-file');
            uploadArea.querySelector('.upload-text').textContent = 'Clique para selecionar um arquivo';
            uploadArea.querySelector('.upload-hint').textContent = 'PDF, JPG, PNG, DOC — máx. <?= LIMITE_UPLOAD_MB ?>MB';
            uploadArea.querySelector('i').className = 'bi bi-cloud-arrow-up';
        }
    }

    // Feedback de envio
    document.querySelector('form').addEventListener('submit', function(e) {
        if (fileInput.files.length === 0) {
            e.preventDefault();
            alert('Por favor, selecione um arquivo.');
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Enviando...';
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>