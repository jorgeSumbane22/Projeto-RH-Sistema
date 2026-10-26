<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$feriasModel = new Ferias();
$ferias = $feriasModel->buscarPorId($id);

if (!$ferias) {
    definirMensagem('danger', 'Solicitação não encontrada.');
    redirecionar('/modules/ferias/index.php');
}

// Verificar permissão
if (usuarioAtualPerfil() === PERFIL_FUNCIONARIO && $ferias['funcionario_id'] != usuarioAtualFuncionarioId()) {
    http_response_code(403);
    require __DIR__ . '/../auth/acesso_negado.php';
    exit;
}

$pageTitle = 'Visualizar Solicitação - ' . $ferias['funcionario_nome'];
require __DIR__ . '/../../includes/header.php';
?>

<style>
.visualizar-container {
    max-width: 600px;
    margin: 0 auto;
}

.visualizar-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1.5rem;
    box-shadow: var(--rh-shadow);
    transition: all 0.3s ease;
}

.visualizar-card:hover {
    box-shadow: var(--rh-shadow-hover);
}

.visualizar-card .card-header {
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--rh-border);
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.visualizar-card .card-header h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin: 0;
}

.visualizar-card .card-header h5 i {
    color: var(--rh-accent);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem 1.5rem;
}

.info-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--rh-border);
}

.info-item.full {
    grid-column: 1 / -1;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item .info-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    display: block;
}

.info-item .info-value {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--rh-text-primary);
}

.btn-group-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--rh-border);
}

.btn-group-actions .btn {
    border-radius: 10px;
    padding: 0.4rem 1.2rem;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.btn-group-actions .btn-primary {
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    border: none;
    color: #fff;
}

.btn-group-actions .btn-secondary {
    border: 1.5px solid var(--rh-border);
    background: transparent;
    color: var(--rh-text-muted);
}

@media (max-width: 575.98px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    .visualizar-card {
        padding: 1rem;
    }
    .btn-group-actions .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="visualizar-container">
    <div class="visualizar-card">
        <div class="card-header">
            <h5><i class="bi bi-umbrella me-2"></i> Detalhes da Solicitação</h5>
            <span class="badge-status <?= $ferias['status'] ?>">
                <i class="bi <?= $ferias['status'] === 'aprovado' ? 'bi-check-circle' : ($ferias['status'] === 'pendente' ? 'bi-clock' : 'bi-x-circle') ?>"></i>
                <?= ucfirst($ferias['status']) ?>
            </span>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Funcionário</span>
                <span class="info-value"><?= e($ferias['funcionario_nome']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Tipo</span>
                <span class="info-value"><?= e(ucfirst(str_replace('_', ' ', $ferias['tipo']))) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Data Início</span>
                <span class="info-value"><?= formatarData($ferias['data_inicio']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Data Fim</span>
                <span class="info-value"><?= formatarData($ferias['data_fim']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Dias</span>
                <span class="info-value"><?= $ferias['dias'] ?> dias</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value"><?= ucfirst($ferias['status']) ?></span>
            </div>
            <?php if (!empty($ferias['motivo'])): ?>
                <div class="info-item full">
                    <span class="info-label">Motivo</span>
                    <span class="info-value"><?= e($ferias['motivo']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="btn-group-actions">
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
            <?php if ($ferias['status'] === 'pendente' && usuarioTemPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_SUPER_ADMIN])): ?>
                <form method="POST" action="aprovar.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= $ferias['id'] ?>">
                    <input type="hidden" name="status" value="aprovado">
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Aprovar</button>
                </form>
                <form method="POST" action="rejeitar.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= $ferias['id'] ?>">
                    <input type="hidden" name="status" value="rejeitado">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i> Rejeitar</button>
                </form>
            <?php endif; ?>
            <?php if ($ferias['status'] === 'pendente' && usuarioAtualFuncionarioId() == $ferias['funcionario_id']): ?>
                <a href="cancelar.php?id=<?= $ferias['id'] ?>" class="btn btn-warning js-confirm" data-confirm-msg="Cancelar esta solicitação?"><i class="bi bi-x-lg me-1"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>