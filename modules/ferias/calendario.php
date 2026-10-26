<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_SUPER_ADMIN]);

$pageTitle = 'Calendário de Férias';
$feriasModel = new Ferias();
$ano = $_GET['ano'] ?? date('Y');
$mes = $_GET['mes'] ?? date('m');

$feriasAprovadas = $feriasModel->listarPorPeriodo($ano . '-01-01', $ano . '-12-31', 'aprovado');

// Cores para cada funcionário (paleta)
$cores = [
    '#4F46E5', '#0EA5E9', '#10B981', '#F59E0B', '#EF4444',
    '#8B5CF6', '#EC4899', '#14B8A6', '#F97316', '#6366F1',
    '#06B6D4', '#84CC16', '#E11D48', '#7C3AED', '#0D9488'
];

$funcionarioCores = [];
$corIndex = 0;

require __DIR__ . '/../../includes/header.php';
?>

<style>
.calendario-container {
    max-width: 1200px;
    margin: 0 auto;
}

.calendario-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.calendario-header h5 {
    font-weight: 700;
    color: var(--rh-text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.calendario-header h5 i {
    color: var(--rh-accent);
}

.calendario-nav {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.calendario-nav .btn {
    border-radius: 10px;
    padding: 0.3rem 0.8rem;
    font-size: 0.8rem;
}

.calendario-nav .mes-ano {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--rh-text-primary);
    min-width: 140px;
    text-align: center;
}

/* ----- GRID DO CALENDÁRIO ----- */
.calendario-grid {
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
    overflow: hidden;
    box-shadow: var(--rh-shadow);
}

.calendario-grid .grid-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: var(--bs-tertiary-bg);
    border-bottom: 2px solid var(--rh-border);
}

.calendario-grid .grid-header .day-name {
    padding: 0.6rem 0.5rem;
    text-align: center;
    font-weight: 700;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rh-text-muted);
}

.calendario-grid .grid-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendario-grid .day-cell {
    min-height: 80px;
    border-right: 1px solid var(--rh-border);
    border-bottom: 1px solid var(--rh-border);
    padding: 0.25rem;
    transition: all 0.2s ease;
    position: relative;
}

.calendario-grid .day-cell:nth-child(7n) {
    border-right: none;
}

.calendario-grid .day-cell:hover {
    background: var(--bs-tertiary-bg);
}

.calendario-grid .day-cell .day-number {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--rh-text-muted);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    display: inline-block;
}

.calendario-grid .day-cell .day-number.today {
    background: var(--rh-primary);
    color: #fff;
}

.calendario-grid .day-cell .day-number.other-month {
    opacity: 0.3;
}

.calendario-grid .day-cell .ferias-bar {
    font-size: 0.55rem;
    padding: 0.1rem 0.25rem;
    border-radius: 3px;
    margin-top: 0.1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.calendario-grid .day-cell .ferias-bar:hover {
    transform: scale(1.02);
    opacity: 0.9;
}

.calendario-grid .day-cell .ferias-bar .bar-dias {
    font-size: 0.5rem;
    opacity: 0.7;
    margin-left: 2px;
}

/* ----- LEGENDA ----- */
.legenda-container {
    margin-top: 1.5rem;
    padding: 1rem 1.25rem;
    background: var(--rh-card-bg);
    border: 1px solid var(--rh-border);
    border-radius: var(--rh-radius);
}

.legenda-container .legenda-title {
    font-weight: 600;
    font-size: 0.75rem;
    color: var(--rh-text-muted);
    margin-bottom: 0.5rem;
}

.legenda-container .legenda-items {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.5rem;
}

.legenda-container .legenda-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    color: var(--rh-text-primary);
}

.legenda-container .legenda-item .color-box {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    flex-shrink: 0;
}

/* ----- RESPONSIVE ----- */
@media (max-width: 767.98px) {
    .calendario-grid .day-cell {
        min-height: 60px;
        padding: 0.15rem;
    }
    .calendario-grid .day-cell .ferias-bar {
        font-size: 0.45rem;
        padding: 0.05rem 0.15rem;
    }
    .calendario-grid .day-cell .day-number {
        font-size: 0.6rem;
    }
    .calendario-nav .mes-ano {
        font-size: 0.9rem;
        min-width: 100px;
    }
}

@media (max-width: 575.98px) {
    .calendario-grid .day-cell {
        min-height: 50px;
    }
    .calendario-grid .day-cell .ferias-bar {
        font-size: 0.4rem;
        padding: 0.05rem 0.1rem;
        margin-top: 0.05rem;
    }
    .calendario-grid .day-cell .day-number {
        font-size: 0.5rem;
    }
    .calendario-grid .grid-header .day-name {
        font-size: 0.5rem;
        padding: 0.3rem 0.2rem;
    }
}
</style>

<div class="calendario-container">
    <!-- Header -->
    <div class="calendario-header">
        <h5><i class="bi bi-calendar3"></i> Calendário de Férias</h5>
        <div class="calendario-nav">
            <a href="?ano=<?= date('Y', strtotime("$ano-$mes-01 -1 month")) ?>&mes=<?= date('m', strtotime("$ano-$mes-01 -1 month")) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="mes-ano"><?= ucfirst(date('F', strtotime("$ano-$mes-01"))) . ' ' . $ano ?></span>
            <a href="?ano=<?= date('Y', strtotime("$ano-$mes-01 +1 month")) ?>&mes=<?= date('m', strtotime("$ano-$mes-01 +1 month")) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-right"></i>
            </a>
            <a href="?ano=<?= date('Y') ?>&mes=<?= date('m') ?>" class="btn btn-primary btn-sm">Hoje</a>
        </div>
    </div>

    <!-- Calendário -->
    <?php
    $primeiroDia = strtotime("$ano-$mes-01");
    $ultimoDia = strtotime("$ano-$mes-" . date('t', $primeiroDia));
    $diasNoMes = date('t', $primeiroDia);
    $primeiroDiaSemana = date('N', $primeiroDia); // 1 = Segunda, 7 = Domingo

    $diasSemana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
    $hoje = date('Y-m-d');
    ?>

    <div class="calendario-grid">
        <div class="grid-header">
            <?php foreach ($diasSemana as $dia): ?>
                <div class="day-name"><?= $dia ?></div>
            <?php endforeach; ?>
        </div>

        <div class="grid-body">
            <?php
            // Dias do mês anterior
            $diasMesAnterior = date('t', strtotime("$ano-$mes-01 -1 month"));
            $inicioOffset = $primeiroDiaSemana - 1;

            for ($i = $inicioOffset - 1; $i >= 0; $i--) {
                $dia = $diasMesAnterior - $i;
                echo '<div class="day-cell"><span class="day-number other-month">' . $dia . '</span></div>';
            }

            // Dias do mês atual
            for ($dia = 1; $dia <= $diasNoMes; $dia++) {
                $dataAtual = "$ano-$mes-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
                $isToday = $dataAtual === $hoje;
                $dataObj = new DateTime($dataAtual);

                echo '<div class="day-cell">';
                echo '<span class="day-number' . ($isToday ? ' today' : '') . '">' . $dia . '</span>';

                // Férias neste dia
                foreach ($feriasAprovadas as $f) {
                    $inicio = new DateTime($f['data_inicio']);
                    $fim = new DateTime($f['data_fim']);

                    if ($dataObj >= $inicio && $dataObj <= $fim) {
                        // Atribuir cor ao funcionário
                        if (!isset($funcionarioCores[$f['funcionario_nome']])) {
                            $funcionarioCores[$f['funcionario_nome']] = $cores[$corIndex % count($cores)];
                            $corIndex++;
                        }
                        $cor = $funcionarioCores[$f['funcionario_nome']];
                        $nome = $f['funcionario_nome'];
                        $dias = $f['dias'];

                        // Mostrar apenas no primeiro dia da férias ou se for o único dia
                        if ($dataAtual === $f['data_inicio'] || ($inicio == $fim)) {
                            echo '<div class="ferias-bar" style="background:' . $cor . ';color:#fff;" title="' . $nome . ' - ' . $dias . ' dias">';
                            echo $nome . ' <span class="bar-dias">(' . $dias . 'd)</span>';
                            echo '</div>';
                        }
                    }
                }

                echo '</div>';
            }

            // Dias do mês seguinte
            $diasRestantes = (7 - (($inicioOffset + $diasNoMes) % 7)) % 7;
            for ($i = 1; $i <= $diasRestantes; $i++) {
                echo '<div class="day-cell"><span class="day-number other-month">' . $i . '</span></div>';
            }
            ?>
        </div>
    </div>

    <!-- Legenda -->
    <div class="legenda-container">
        <div class="legenda-title"><i class="bi bi-palette me-1"></i> Legenda por Funcionário</div>
        <div class="legenda-items">
            <?php foreach ($funcionarioCores as $nome => $cor): ?>
                <div class="legenda-item">
                    <span class="color-box" style="background:<?= $cor ?>;"></span>
                    <?= e($nome) ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($funcionarioCores)): ?>
                <span class="text-muted">Nenhuma férias aprovada neste mês.</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>