<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();
requireModuleAccess('backup');
$pageTitle = 'Backup';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-card p-4">
  <div class="d-flex align-items-center gap-3 mb-3"><div class="metric-icon" style="--metric-color:#155e75"><i class="bi bi-cloud-arrow-down"></i></div><div><div class="text-uppercase small fw-bold text-muted">Backup</div><h4 class="mb-1">Historico</h4><p class="text-muted mb-0">Página preparada para a operação do módulo backup.</p></div></div>
  <div class="alert alert-info"><i class="bi bi-check-circle me-2"></i>Estrutura disponível, acesso protegido por perfil e interface premium ativa.</div>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/modules/backup/index.php"><i class="bi bi-arrow-left me-1"></i>Voltar ao módulo</a>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
