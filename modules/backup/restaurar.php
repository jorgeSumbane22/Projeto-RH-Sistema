<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Restaurar Backup';
require __DIR__ . '/../../includes/header.php';

$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    validarCsrf();
    
    $arquivo = $_FILES['backup_file'];
    
    // Verifica se o arquivo foi enviado corretamente
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Erro no upload do arquivo.';
    } elseif ($arquivo['type'] !== 'application/json' && pathinfo($arquivo['name'], PATHINFO_EXTENSION) !== 'json') {
        $erros[] = 'O arquivo deve ser um JSON válido.';
    } elseif ($arquivo['size'] > 10 * 1024 * 1024) {
        $erros[] = 'O arquivo excede o limite de 10MB.';
    } else {
        $conteudo = file_get_contents($arquivo['tmp_name']);
        $dados = json_decode($conteudo, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $erros[] = 'Arquivo JSON inválido: ' . json_last_error_msg();
        } elseif (!isset($dados['tabelas']) || !is_array($dados['tabelas'])) {
            $erros[] = 'Estrutura de backup inválida.';
        } else {
            // Aqui você implementaria a restauração
            // Por segurança, este é um processo complexo que deve ser feito com cuidado
            $erros[] = 'Função de restauração em desenvolvimento. Use o arquivo JSON para restauração manual.';
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   RESTAURAR BACKUP - DESIGN PREMIUM
   ============================================================ */

.restaurar-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 2rem;
    box-shadow: var(--rh-shadow);
    transition: all 0.3s ease;
    max-width: 600px;
    margin: 0 auto;
}

.restaurar-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.restaurar-card .restaurar-icon {
    font-size: 4rem;
    color: #f59e0b;
    display: block;
    margin-bottom: 1rem;
}

.restaurar-card h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.5rem;
}

.restaurar-card .restaurar-description {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.restaurar-card .form-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

.restaurar-card .form-control {
    border-radius: 10px;
    padding: 0.5rem 0.875rem;
    font-size: 0.9rem;
    border: 1.5px solid var(--rh-border);
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-primary);
}

.restaurar-card .form-control:focus {
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
    background: var(--rh-card-bg);
}

.restaurar-card .btn-restaurar {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border: none;
    border-radius: 10px;
    padding: 0.6rem 2rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #fff;
    transition: all 0.3s ease;
    width: 100%;
}

.restaurar-card .btn-restaurar:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(245,158,11,0.35);
}

.restaurar-card .btn-voltar {
    border: 1px solid var(--rh-border);
    border-radius: 10px;
    padding: 0.6rem 2rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rh-text-muted);
    background: transparent;
    transition: all 0.3s ease;
    width: 100%;
    text-align: center;
    text-decoration: none;
}

.restaurar-card .btn-voltar:hover {
    background: var(--bs-tertiary-bg);
}

.backup-alert-danger {
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    color: var(--rh-text-primary);
}

.backup-alert-danger i {
    color: #ef4444;
    font-size: 1.2rem;
    margin-top: 2px;
}

.backup-alert-danger .alert-text {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.backup-alert-danger .alert-text strong {
    color: var(--rh-text-primary);
}

@media (max-width: 575.98px) {
    .restaurar-card {
        padding: 1.25rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="backup-header">
    <div class="backup-header-left">
        <h5>
            <i class="bi bi-cloud-upload"></i> Restaurar Backup
        </h5>
        <p>Restauração de dados a partir de um arquivo JSON</p>
    </div>
</div>

<!-- ============================================================
     RESTAURAR CARD
     ============================================================ -->
<div class="restaurar-card">
    <span class="restaurar-icon"><i class="bi bi-cloud-upload"></i></span>
    <h5>Restaurar Dados</h5>
    <p class="restaurar-description">
        Selecione um arquivo de backup JSON para restaurar os dados do sistema.
    </p>

    <!-- Alert -->
    <div class="backup-alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <div class="alert-text">
            <strong>Atenção:</strong> A restauração substituirá os dados atuais. 
            Esta ação não pode ser desfeita. Certifique-se de ter um backup atual.
        </div>
    </div>

    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
            <i class="bi bi-x-circle me-2"></i>
            <strong>Erro ao restaurar:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-file-earmark-json"></i> Arquivo de backup</label>
            <input type="file" name="backup_file" class="form-control" accept=".json" required>
            <div class="form-text" style="font-size:0.65rem;color:var(--rh-text-muted);">
                <i class="bi bi-info-circle"></i> Apenas arquivos JSON até 10MB
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn-restaurar" id="btnRestaurar">
                <i class="bi bi-cloud-upload me-1"></i> Restaurar
            </button>
            <a href="index.php" class="btn-voltar">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnRestaurar = document.getElementById('btnRestaurar');
    
    btnRestaurar.addEventListener('click', function(e) {
        const confirmMsg = 'ATENÇÃO: Esta ação substituirá todos os dados atuais. Continuar?';
        if (!confirm(confirmMsg)) {
            e.preventDefault();
            return false;
        }
        
        // Adiciona estado de carregamento
        const originalText = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> A restaurar...';
        this.disabled = true;
        
        // Restaura após envio
        setTimeout(() => {
            this.innerHTML = originalText;
            this.disabled = false;
        }, 5000);
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>