<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_SUPER_ADMIN]);

$id = (int) ($_GET['id'] ?? 0);
$presencaModel = new Presenca();
$presenca = $presencaModel->buscarPorId($id);

if (!$presenca) {
    definirMensagem('danger', 'Registro não encontrado.');
    redirecionar('/modules/presenca/index.php');
}

$pageTitle = 'Editar Presença';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'hora_entrada' => $_POST['hora_entrada'] ?? '',
        'hora_saida_almoco' => $_POST['hora_saida_almoco'] ?? '',
        'hora_retorno' => $_POST['hora_retorno'] ?? '',
        'hora_saida' => $_POST['hora_saida'] ?? '',
        'horas_extras' => $_POST['horas_extras'] ?? 0,
        'status' => $_POST['status'] ?? 'presente',
        'observacao' => trim($_POST['observacao'] ?? ''),
    ];

    $presencaModel->atualizar($id, $dados);
    registrarAuditoria('presenca_atualizada', "ID $id");
    definirMensagem('success', 'Registro atualizado com sucesso.');
    redirecionar('/modules/presenca/index.php');
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width:600px;margin:0 auto;">
    <h5 class="mb-4"><i class="bi bi-pencil-square me-2"></i> Editar Presença</h5>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Entrada</label>
                <input type="time" name="hora_entrada" class="form-control" value="<?= !empty($presenca['hora_entrada']) ? date('H:i', strtotime($presenca['hora_entrada'])) : '' ?>" step="60">
            </div>
            <div class="col-md-6">
                <label class="form-label">Saída Almoço</label>
                <input type="time" name="hora_saida_almoco" class="form-control" value="<?= !empty($presenca['hora_saida_almoco']) ? date('H:i', strtotime($presenca['hora_saida_almoco'])) : '' ?>" step="60">
            </div>
            <div class="col-md-6">
                <label class="form-label">Retorno</label>
                <input type="time" name="hora_retorno" class="form-control" value="<?= !empty($presenca['hora_retorno']) ? date('H:i', strtotime($presenca['hora_retorno'])) : '' ?>" step="60">
            </div>
            <div class="col-md-6">
                <label class="form-label">Saída</label>
                <input type="time" name="hora_saida" class="form-control" value="<?= !empty($presenca['hora_saida']) ? date('H:i', strtotime($presenca['hora_saida'])) : '' ?>" step="60">
            </div>
            <div class="col-md-4">
                <label class="form-label">Horas Extras</label>
                <input type="number" step="0.5" min="0" name="horas_extras" class="form-control" value="<?= $presenca['horas_extras'] ?? 0 ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="presente" <?= ($presenca['status'] ?? '') === 'presente' ? 'selected' : '' ?>>✅ Presente</option>
                    <option value="falta" <?= ($presenca['status'] ?? '') === 'falta' ? 'selected' : '' ?>>❌ Falta</option>
                    <option value="atraso" <?= ($presenca['status'] ?? '') === 'atraso' ? 'selected' : '' ?>>⏰ Atraso</option>
                    <option value="justificado" <?= ($presenca['status'] ?? '') === 'justificado' ? 'selected' : '' ?>>📋 Justificado</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Observação</label>
                <textarea name="observacao" class="form-control" rows="2"><?= e($presenca['observacao'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy me-1"></i> Salvar</button>
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>