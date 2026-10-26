<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$pageTitle = 'Permissões e Acessos';

// ============================================================
// DEFINIÇÃO DAS PERMISSÕES POR PERFIL
// ============================================================
$permissoes = [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'descricao' => 'Acesso ao painel principal',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => true,
            'auditor' => true,
            'funcionario' => true,
        ]
    ],
    'funcionarios' => [
        'label' => 'Funcionários',
        'icon' => 'bi-people',
        'descricao' => 'Gerenciar funcionários',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => true,
            'auditor' => false,
            'funcionario' => false,
        ]
    ],
    'departamentos' => [
        'label' => 'Departamentos',
        'icon' => 'bi-diagram-3',
        'descricao' => 'Gerenciar departamentos',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => false,
            'auditor' => false,
            'funcionario' => false,
        ]
    ],
    'cargos' => [
        'label' => 'Cargos',
        'icon' => 'bi-briefcase',
        'descricao' => 'Gerenciar cargos',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => false,
            'auditor' => false,
            'funcionario' => false,
        ]
    ],
    'presenca' => [
        'label' => 'Presença/Ponto',
        'icon' => 'bi-clock-history',
        'descricao' => 'Registrar e visualizar presenças',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => true,
            'auditor' => true,
            'funcionario' => true,
        ]
    ],
    'ferias' => [
        'label' => 'Férias e Licenças',
        'icon' => 'bi-umbrella',
        'descricao' => 'Gerenciar férias e licenças',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => true,
            'auditor' => false,
            'funcionario' => true,
        ]
    ],
    'folha' => [
        'label' => 'Folha de Pagamento',
        'icon' => 'bi-cash-stack',
        'descricao' => 'Gerenciar folha de pagamento',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => false,
            'auditor' => true,
            'funcionario' => false,
        ]
    ],
    'recrutamento' => [
        'label' => 'Recrutamento',
        'icon' => 'bi-briefcase',
        'descricao' => 'Gerenciar vagas e candidatos',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => false,
            'auditor' => false,
            'funcionario' => false,
        ]
    ],
    'avaliacao' => [
        'label' => 'Avaliações',
        'icon' => 'bi-star',
        'descricao' => 'Gerenciar avaliações de desempenho',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => true,
            'auditor' => false,
            'funcionario' => false,
        ]
    ],
    'documentos' => [
        'label' => 'Documentos',
        'icon' => 'bi-file-earmark-text',
        'descricao' => 'Gerenciar documentos',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => true,
            'auditor' => true,
            'funcionario' => false,
        ]
    ],
    'relatorios' => [
        'label' => 'Relatórios',
        'icon' => 'bi-bar-chart',
        'descricao' => 'Visualizar relatórios',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => true,
            'auditor' => true,
            'funcionario' => false,
        ]
    ],
    'usuarios' => [
        'label' => 'Usuários e Acessos',
        'icon' => 'bi-shield-lock',
        'descricao' => 'Gerenciar usuários do sistema',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => false,
            'auditor' => false,
            'funcionario' => false,
        ]
    ],
    'auditoria' => [
        'label' => 'Auditoria',
        'icon' => 'bi-journal-text',
        'descricao' => 'Visualizar logs de auditoria',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => true,
            'gestor' => false,
            'auditor' => true,
            'funcionario' => false,
        ]
    ],
    'backup' => [
        'label' => 'Backup',
        'icon' => 'bi-cloud-arrow-down',
        'descricao' => 'Gerenciar backups do sistema',
        'permissoes' => [
            'super_admin' => true,
            'administrador_rh' => false,
            'gestor' => false,
            'auditor' => false,
            'funcionario' => false,
        ]
    ],
];

// ============================================================
// PERFIS DO SISTEMA
// ============================================================
$perfis = [
    'super_admin' => ['label' => 'Super Admin', 'color' => '#8b5cf6', 'icon' => 'bi-shield-check'],
    'administrador_rh' => ['label' => 'Administrador RH', 'color' => '#155e75', 'icon' => 'bi-person-gear'],
    'gestor' => ['label' => 'Gestor', 'color' => '#0e7490', 'icon' => 'bi-person-badge'],
    'auditor' => ['label' => 'Auditor', 'color' => '#f59e0b', 'icon' => 'bi-journal-text'],
    'funcionario' => ['label' => 'Funcionário', 'color' => '#14b8a6', 'icon' => 'bi-person'],
];

require __DIR__ . '/../../includes/header.php';
?>

<style>
/* ============================================================
   PERMISSÕES - DESIGN PREMIUM
   ============================================================ */

.permissoes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.permissoes-header-left h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin-bottom: 0.15rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.permissoes-header-left h5 i {
    color: var(--rh-accent);
}

.permissoes-header-left p {
    color: var(--rh-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

/* ----- Matriz de Permissões ----- */
.permissoes-matrix {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    overflow: hidden;
    box-shadow: var(--rh-shadow);
}

.permissoes-matrix .table {
    margin-bottom: 0;
}

.permissoes-matrix .table thead th {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-weight: 700;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid var(--rh-border);
    text-align: center;
}

.permissoes-matrix .table thead th:first-child {
    text-align: left;
}

.permissoes-matrix .table thead th .perfil-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
    color: #fff;
}

.permissoes-matrix .table tbody td {
    padding: 0.6rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rh-border);
    text-align: center;
}

.permissoes-matrix .table tbody td:first-child {
    text-align: left;
}

.permissoes-matrix .table tbody tr {
    transition: all 0.2s ease;
}

.permissoes-matrix .table tbody tr:hover {
    background: rgba(21, 94, 117, 0.03);
}

.permissoes-matrix .table tbody tr:last-child td {
    border-bottom: none;
}

/* ----- Módulo Info ----- */
.module-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.module-info .module-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    background: var(--bs-tertiary-bg);
    color: var(--rh-primary);
}

.module-info .module-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--rh-text-primary);
}

.module-info .module-desc {
    font-size: 0.7rem;
    color: var(--rh-text-muted);
}

/* ----- Permissão Badge ----- */
.permissao-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 600;
}

.permissao-badge.permited {
    background: rgba(16, 185, 129, 0.15);
    color: var(--rh-accent);
}

.permissao-badge.denied {
    background: rgba(239, 68, 68, 0.10);
    color: #ef4444;
}

.permissao-badge i {
    font-size: 0.6rem;
}

/* ----- Status Badge ----- */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50px;
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.15);
    color: var(--rh-accent);
}

.status-badge.inactive {
    background: rgba(239, 68, 68, 0.10);
    color: #ef4444;
}

/* ----- Legend ----- */
.legend {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
    background: var(--bs-tertiary-bg);
    border-radius: 10px;
    margin-top: 1rem;
}

.legend .legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--rh-text-muted);
}

.legend .legend-item .dot {
    width: 12px;
    height: 12px;
    border-radius: 4px;
}

.legend .legend-item .dot.green { background: var(--rh-accent); }
.legend .legend-item .dot.red { background: #ef4444; }

/* ----- Responsive ----- */
@media (max-width: 991.98px) {
    .permissoes-matrix .table thead th,
    .permissoes-matrix .table tbody td {
        padding: 0.4rem 0.5rem;
        font-size: 0.7rem;
    }
    .module-info .module-desc {
        display: none;
    }
    .module-info .module-icon {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
}

@media (max-width: 767.98px) {
    .permissoes-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .permissoes-matrix .table thead th .perfil-badge span {
        display: none;
    }
    .permissoes-matrix .table thead th {
        font-size: 0.55rem;
        padding: 0.3rem 0.3rem;
    }
    .permissoes-matrix .table tbody td {
        padding: 0.3rem 0.3rem;
        font-size: 0.65rem;
    }
    .permissao-badge {
        font-size: 0.55rem;
        padding: 0.1rem 0.4rem;
    }
    .module-info .module-name {
        font-size: 0.7rem;
    }
}

@media (max-width: 575.98px) {
    .permissoes-matrix .table thead th .perfil-badge i {
        font-size: 0.7rem;
    }
    .permissoes-matrix .table thead th .perfil-badge span {
        display: none;
    }
    .legend {
        flex-direction: column;
        gap: 0.3rem;
    }
}
</style>

<!-- ============================================================
     HEADER
     ============================================================ -->
<div class="permissoes-header">
    <div class="permissoes-header-left">
        <h5>
            <i class="bi bi-shield-check"></i> Permissões e Acessos
            <span class="badge bg-primary rounded-pill ms-2" style="font-size:0.65rem;font-weight:500;">
                <?= count($permissoes) ?> módulos
            </span>
        </h5>
        <p>Matriz de permissões por perfil de acesso</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;padding:0.4rem 1rem;">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<!-- ============================================================
     MATRIZ DE PERMISSÕES
     ============================================================ -->
<div class="permissoes-matrix">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width:200px;">Módulo</th>
                    <?php foreach ($perfis as $key => $p): ?>
                        <th style="min-width:100px;">
                            <span class="perfil-badge" style="background:<?= $p['color'] ?>">
                                <i class="bi <?= $p['icon'] ?>"></i>
                                <span><?= $p['label'] ?></span>
                            </span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissoes as $modulo => $data): ?>
                    <tr>
                        <td>
                            <div class="module-info">
                                <div class="module-icon">
                                    <i class="bi <?= $data['icon'] ?>"></i>
                                </div>
                                <div>
                                    <div class="module-name"><?= e($data['label']) ?></div>
                                    <div class="module-desc"><?= e($data['descricao']) ?></div>
                                </div>
                            </div>
                        </td>
                        <?php foreach ($perfis as $perfilKey => $p): ?>
                            <td>
                                <?php 
                                $temPermissao = $data['permissoes'][$perfilKey] ?? false;
                                ?>
                                <span class="permissao-badge <?= $temPermissao ? 'permited' : 'denied' ?>">
                                    <i class="bi <?= $temPermissao ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i>
                                    <?= $temPermissao ? 'Sim' : 'Não' ?>
                                </span>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     LEGENDA
     ============================================================ -->
<div class="legend">
    <span class="legend-item">
        <span class="dot green"></span> Permitido
    </span>
    <span class="legend-item">
        <span class="dot red"></span> Negado
    </span>
    <span class="legend-item">
        <i class="bi bi-shield-check text-success"></i> Acesso total
    </span>
    <span class="legend-item">
        <i class="bi bi-eye text-info"></i> Visualização apenas
    </span>
    <span class="legend-item">
        <i class="bi bi-pencil text-warning"></i> Edição permitida
    </span>
</div>

<!-- ============================================================
     RESUMO POR PERFIL
     ============================================================ -->
<div class="row g-3 mt-3">
    <?php foreach ($perfis as $perfilKey => $p): 
        $total = 0;
        $permitidos = 0;
        foreach ($permissoes as $modulo => $data) {
            $total++;
            if ($data['permissoes'][$perfilKey] ?? false) {
                $permitidos++;
            }
        }
        $percentual = $total > 0 ? round(($permitidos / $total) * 100) : 0;
    ?>
        <div class="col-md-2 col-6">
            <div class="p-3 rounded" style="background:<?= $p['color'] ?>15;border:1px solid <?= $p['color'] ?>30;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi <?= $p['icon'] ?>" style="color:<?= $p['color'] ?>;font-size:1.2rem;"></i>
                    <div>
                        <div class="small fw-bold" style="color:<?= $p['color'] ?>;"><?= $p['label'] ?></div>
                        <div class="small" style="color:var(--rh-text-muted);">
                            <?= $permitidos ?>/<?= $total ?> módulos (<?= $percentual ?>%)
                        </div>
                    </div>
                </div>
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar" role="progressbar" style="width:<?= $percentual ?>%;background:<?= $p['color'] ?>;" aria-valuenow="<?= $percentual ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============================================================
     BOTÃO DE AJUDA / INFORMAÇÃO
     ============================================================ -->
<div class="mt-4 p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--rh-border);">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <i class="bi bi-info-circle" style="color:var(--rh-primary);font-size:1.5rem;"></i>
        <div>
            <div class="small fw-bold" style="color:var(--rh-text-primary);">Sobre as permissões</div>
            <div class="small" style="color:var(--rh-text-muted);">
                As permissões são definidas pelo <strong>Super Admin</strong> e determinam o que cada perfil pode acessar no sistema.
                Para alterar uma permissão, entre em contato com o administrador do sistema.
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>