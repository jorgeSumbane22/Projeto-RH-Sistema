<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirLogin();
requireModuleAccess('funcionarios');
$pageTitle = 'Histórico do funcionário';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-card p-4">
  <div class="d-flex align-items-center gap-3 mb-3"><div class="metric-icon" style="--metric-color:#155e75"><i class="bi bi-clock-history"></i></div><div><h4 class="mb-1">Histórico do funcionário</h4><p class="text-muted mb-0">Linha do tempo profissional e alterações registadas.</p></div></div>
  <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Esta área está disponível na estrutura premium e pronta para integração com os dados do sistema.</div>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/modules/funcionarios/index.php"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
