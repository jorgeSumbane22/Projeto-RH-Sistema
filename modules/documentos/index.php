<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// ============================================================
// PERMISSÕES CORRETAS
// ============================================================
// - Funcionário: só vê seus próprios documentos
// - Gestor: vê documentos do seu departamento
// - Admin RH / RH / Admin: vê todos
// ============================================================
exigirLogin();

$pageTitle = 'Documentos';
$funcionarioModel = new Funcionario();
$documentoModel = new Documento();

// ============================================================
// FUNCIONÁRIO COMUM - Redireciona para seus documentos
// ============================================================
if (usuarioAtualPerfil() === PERFIL_FUNCIONARIO) {
    if (!usuarioAtualFuncionarioId()) {
        definirMensagem('danger', 'Sua conta não está vinculada a um funcionário.');
        redirecionar('/modules/dashboard/index.php');
    }
    redirecionar('/modules/funcionarios/visualizar.php?id=' . usuarioAtualFuncionarioId());
}

// ============================================================
// GESTOR - Filtra por departamento
// ============================================================
$filtros = ['nome' => trim($_GET['nome'] ?? '')];
if (usuarioAtualPerfil() === PERFIL_GESTOR) {
    $meuFuncionario = $funcionarioModel->buscarPorId(usuarioAtualFuncionarioId());
    if ($meuFuncionario) {
        $filtros['departamento_id'] = $meuFuncionario['departamento_id'] ?? 0;
    }
}

// ============================================================
// BUSCAR FUNCIONÁRIOS
// ============================================================
$funcionarios = $funcionarioModel->listar(array_filter($filtros));

// ============================================================
// ESTATÍSTICAS
// ============================================================
$totalFuncionariosDocs = count($funcionarios);
$totalDocumentos = 0;
foreach ($funcionarios as $f) {
    $totalDocumentos += count($documentoModel->listarPorFuncionario($f['id']));
}

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   DOCUMENTOS - DESIGN PREMIUM
   ============================================================ */

/* ----- STATS CARDS ----- */
.documentos-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.documentos-stat-card {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.documentos-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--rh-shadow-hover);
}

.documentos-stat-card .stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.documentos-stat-card .stat-icon.primary {
    background: rgba(21, 94, 117, 0.12);
    color: var(--rh-primary);
}
.documentos-stat-card .stat-icon.info {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.documentos-stat-card .stat-info .stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rh-text-primary);
    line-height: 1.2;
}

.documentos-stat-card .stat-info .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
}

/* ----- HEADER ----- */
.documentos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.documentos-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.documentos-header-left h5 i {
    color: var(--rh-accent);
}

.documentos-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

/* ----- FILTER CARD ----- */
.filter-card-premium {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.filter-card-premium:hover {
    box-shadow: var(--rh-shadow-hover);
}

.filter-card-premium .form-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.filter-card-premium .form-control {
    border-radius: 8px;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
    background: var(--bs-tertiary-bg);
    border: 1.5px solid var(--rh-border);
    transition: all 0.2s ease;
    color: var(--rh-text-primary);
}

.filter-card-premium .form-control:focus {
    background: var(--rh-card-bg);
    border-color: var(--rh-primary);
    box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.12);
}

.filter-card-premium .btn-filter {
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 0.35rem 1.2rem;
    background: var(--rh-primary);
    border: none;
    color: #fff;
    transition: all 0.3s ease;
    width: 100%;
}

.filter-card-premium .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(21, 94, 117, 0.25);
}

/* ----- FUNCIONÁRIO CARD ----- */
.funcionario-card-doc {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    text-decoration: none;
    color: var(--rh-text-primary);
    display: block;
    height: 100%;
}

.funcionario-card-doc:hover {
    transform: translateY(-4px);
    box-shadow: var(--rh-shadow-hover);
    text-decoration: none;
    border-color: var(--rh-primary);
}

.funcionario-card-doc .funcionario-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.2rem;
    flex-shrink: 0;
    text-transform: uppercase;
}

.funcionario-card-doc .funcionario-nome {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rh-text-primary);
}

.funcionario-card-doc .funcionario-meta {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

.funcionario-card-doc .funcionario-docs {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--rh-text-muted);
}

.funcionario-card-doc .funcionario-docs i {
    font-size: 0.55rem;
    color: var(--rh-primary);
}

/* ----- EMPTY STATE ----- */
.empty-state-premium {
    text-align: center;
    padding: 3rem 1rem;
    grid-column: 1 / -1;
}

.empty-state-premium .empty-icon {
    font-size: 3.5rem;
    color: var(--rh-border);
    display: block;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.empty-state-premium h6 {
    font-weight: 600;
    color: var(--rh-text-primary);
    margin-bottom: 0.25rem;
}

.empty-state-premium p {
    color: var(--rh-text-muted);
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 991.98px) {
    .documentos-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    .documentos-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .documentos-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .documentos-stat-card {
        padding: 0.75rem 1rem;
    }
    .documentos-stat-card .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }
    .documentos-stat-card .stat-info .stat-value {
        font-size: 1.1rem;
    }
    .funcionario-card-doc .funcionario-avatar {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
}

@media (max-width: 575.98px) {
    .documentos-stats {
        grid-template-columns: 1fr;
    }
    .documentos-header-left h5 {
        font-size: 1.1rem;
    }
}
</style>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="documentos-stats">
    <div class="documentos-stat-card">
        <div class="stat-icon primary"><i class="bi bi-people"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalFuncionariosDocs) ?></div>
            <div class="stat-label">Funcionários</div>
        </div>
    </div>
    <div class="documentos-stat-card">
        <div class="stat-icon info"><i class="bi bi-file-earmark"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalDocumentos) ?></div>
            <div class="stat-label">Total Documentos</div>
        </div>
    </div>
</div>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="documentos-header">
    <div class="documentos-header-left">
        <h5>
            <i class="bi bi-folder-open"></i> Documentos por Funcionário
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= number_format($totalFuncionariosDocs) ?> registos
            </span>
        </h5>
        <p>Gerencie documentos dos colaboradores</p>
    </div>
</div>

<!-- ============================================================
     FILTER CARD
     ============================================================ -->
<div class="filter-card-premium">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label"><i class="bi bi-search me-1"></i> Buscar</label>
            <input type="text" name="nome" class="form-control" placeholder="Nome do funcionário..." value="<?= e($filtros['nome']) ?>">
        </div>
        <div class="col-md-2">
            <button class="btn-filter w-100">
                <i class="bi bi-funnel me-1"></i> Buscar
            </button>
        </div>
    </form>
</div>

<!-- ============================================================
     FUNCIONÁRIOS CARDS
     ============================================================ -->
<div class="row g-3">
    <?php if (empty($funcionarios)): ?>
        <div class="col-12">
            <div class="empty-state-premium">
                <span class="empty-icon"><i class="bi bi-people"></i></span>
                <h6>Nenhum funcionário encontrado</h6>
                <p>Tente ajustar os filtros de busca.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($funcionarios as $f): ?>
        <?php $totalDocs = count($documentoModel->listarPorFuncionario($f['id'])); ?>
        <div class="col-md-4 col-lg-3">
            <a href="<?= BASE_URL ?>/modules/funcionarios/visualizar.php?id=<?= $f['id'] ?>#tab-documentos" class="funcionario-card-doc">
                <div class="d-flex align-items-center gap-3">
                    <span class="funcionario-avatar"><?= e(strtoupper(substr($f['nome'], 0, 1))) ?></span>
                    <div>
                        <div class="funcionario-nome"><?= e($f['nome']) ?></div>
                        <div class="funcionario-meta"><?= e($f['cargo_titulo'] ?? '') ?></div>
                        <div class="mt-1">
                            <span class="funcionario-docs">
                                <i class="bi bi-file-earmark"></i> <?= $totalDocs ?> documento(s)
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>