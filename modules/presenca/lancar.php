<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Lançar Presença';
$funcionarioModel = new Funcionario();
$presencaModel = new Presenca();

$funcionarios = $funcionarioModel->listar(['status' => 'ativo']);
$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'funcionario_id' => $_POST['funcionario_id'] ?? '',
        'data' => $_POST['data'] ?? date('Y-m-d'),
        'hora_entrada' => $_POST['hora_entrada'] ?? '',
        'hora_saida_almoco' => $_POST['hora_saida_almoco'] ?? '',
        'hora_retorno' => $_POST['hora_retorno'] ?? '',
        'hora_saida' => $_POST['hora_saida'] ?? '',
        'horas_extras' => $_POST['horas_extras'] ?? 0,
        'status' => $_POST['status'] ?? 'presente',
        'observacao' => trim($_POST['observacao'] ?? ''),
    ];

    if (empty($dados['funcionario_id'])) $erros[] = 'Selecione o funcionário.';
    if (empty($dados['data'])) $erros[] = 'Informe a data.';

    if (empty($erros)) {
        $presencaModel->lancarManual($dados);
        registrarAuditoria('presenca_lancada', "Funcionário {$dados['funcionario_id']} em {$dados['data']}");
        definirMensagem('success', 'Presença lançada com sucesso.');
        redirecionar('/modules/presenca/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   LANÇAR PRESENÇA - DESIGN PREMIUM
   ============================================================ */

.form-container {
    max-width: 650px;
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
    color: var(--rh-text-primary);
}

.form-card .form-card-header h5 i {
    color: var(--rh-primary);
    margin-right: 6px;
}

.form-card .form-card-header .header-badge {
    margin-left: auto;
    font-size: 0.6rem;
    padding: 0.2rem 0.7rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
}

.form-card .form-card-body {
    padding: 1.5rem;
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
    color: var(--rh-text-primary);
}

.form-floating-custom .form-control:focus,
.form-floating-custom .form-select:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21,94,117,0.12);
}

.form-floating-custom .form-text {
    font-size: 0.65rem;
    color: var(--rh-text-muted);
    margin-top: 4px;
}

.form-floating-custom .form-text i {
    margin-right: 4px;
}

/* ----- STATUS BADGE PREVIEW ----- */
.status-preview {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-preview.presente { background: #d1fae5; color: #065f46; }
.status-preview.falta { background: #fee2e2; color: #991b1b; }
.status-preview.atraso { background: #fef3c7; color: #92400e; }
.status-preview.justificado { background: #dbeafe; color: #1e40af; }

[data-bs-theme="dark"] .status-preview.presente { background: #065f46; color: #d1fae5; }
[data-bs-theme="dark"] .status-preview.falta { background: #991b1b; color: #fee2e2; }
[data-bs-theme="dark"] .status-preview.atraso { background: #92400e; color: #fef3c7; }
[data-bs-theme="dark"] .status-preview.justificado { background: #1e40af; color: #dbeafe; }

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
    box-shadow: 0 6px 20px rgba(21,94,117,0.3);
}

.form-actions .btn-secondary {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
}

.form-actions .btn-secondary:hover {
    background: var(--bs-tertiary-bg);
}

@media (max-width: 767.98px) {
    .form-card .form-card-body {
        padding: 1rem;
    }
    .form-card .form-card-header .header-badge {
        margin-left: 0;
        width: 100%;
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

<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-clock" style="color:var(--rh-primary);font-size:1.2rem;"></i>
            <h5>Lançar Presença</h5>
            <span class="header-badge"><i class="bi bi-info-circle"></i> Lançamento manual</span>
        </div>
        <div class="form-card-body">
            <form method="POST" id="formLancarPresenca">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border-left:4px solid #ef4444;">
                        <i class="bi bi-x-circle me-2"></i>
                        <strong>Erros:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="form-floating-custom">
                    <label class="form-label"><i class="bi bi-person"></i> Funcionário <span class="required">*</span></label>
                    <select name="funcionario_id" class="form-select" required id="funcionarioSelect">
                        <option value="">Selecione...</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= e($f['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label"><i class="bi bi-calendar3"></i> Data <span class="required">*</span></label>
                    <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required id="dataInput">
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <label class="form-label"><i class="bi bi-arrow-right"></i> Entrada</label>
                            <input type="time" name="hora_entrada" class="form-control" step="60" id="horaEntrada">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <label class="form-label"><i class="bi bi-arrow-left"></i> Saída Almoço</label>
                            <input type="time" name="hora_saida_almoco" class="form-control" step="60" id="horaSaidaAlmoco">
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <label class="form-label"><i class="bi bi-arrow-right"></i> Retorno</label>
                            <input type="time" name="hora_retorno" class="form-control" step="60" id="horaRetorno">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <label class="form-label"><i class="bi bi-arrow-left"></i> Saída</label>
                            <input type="time" name="hora_saida" class="form-control" step="60" id="horaSaida">
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-floating-custom">
                            <label class="form-label"><i class="bi bi-clock"></i> Horas Extras</label>
                            <input type="number" step="0.5" min="0" name="horas_extras" class="form-control" value="0">
                            <div class="form-text"><i class="bi bi-info-circle"></i> Em horas (ex: 1.5)</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-floating-custom">
                            <label class="form-label"><i class="bi bi-circle"></i> Status <span class="required">*</span></label>
                            <select name="status" class="form-select" required id="statusSelect">
                                <option value="presente">✅ Presente</option>
                                <option value="falta">❌ Falta</option>
                                <option value="atraso">⏰ Atraso</option>
                                <option value="justificado">📋 Justificado</option>
                            </select>
                            <div class="form-text" id="statusPreview">
                                <i class="bi bi-info-circle"></i> Status selecionado: 
                                <span class="status-preview presente" id="statusPreviewBadge">✅ Presente</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-floating-custom">
                    <label class="form-label"><i class="bi bi-chat"></i> Observação</label>
                    <textarea name="observacao" class="form-control" rows="2" placeholder="Justificativa ou observação..."></textarea>
                    <div class="form-text"><i class="bi bi-info-circle"></i> Informe o motivo da correção (opcional)</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="btnSalvar">
                        <i class="bi bi-floppy me-1"></i> Salvar
                    </button>
                    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview do status selecionado
    const statusSelect = document.getElementById('statusSelect');
    const statusPreview = document.getElementById('statusPreviewBadge');
    
    const statusMap = {
        'presente': { label: '✅ Presente', class: 'presente' },
        'falta': { label: '❌ Falta', class: 'falta' },
        'atraso': { label: '⏰ Atraso', class: 'atraso' },
        'justificado': { label: '📋 Justificado', class: 'justificado' }
    };
    
    statusSelect.addEventListener('change', function() {
        const status = this.value;
        const info = statusMap[status] || statusMap['presente'];
        statusPreview.textContent = info.label;
        statusPreview.className = 'status-preview ' + info.class;
    });

    // Feedback do botão submit
    document.getElementById('formLancarPresenca').addEventListener('submit', function(e) {
        const btn = document.getElementById('btnSalvar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Salvando...';
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>