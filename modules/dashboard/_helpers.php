<?php
/**
 * _helpers.php - Funções auxiliares EXCLUSIVAS do dashboard
 * 
 * ATENÇÃO: Não duplicar funções que já existem em includes/functions.php
 */

// ============================================================
// FUNÇÕES ESPECÍFICAS DO DASHBOARD
// ============================================================

if (!function_exists('dashGreeting')) {
    function dashGreeting(): string
    {
        $hora = (int) date('H');
        return $hora < 12 ? '🌅 Bom dia' : ($hora < 18 ? '☀️ Boa tarde' : '🌙 Boa noite');
    }
}

if (!function_exists('saJson')) {
    function saJson($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '[]';
    }
}

if (!function_exists('dashGetMonthRange')) {
    function dashGetMonthRange(string $inicioMes, int $quantidade = 6): array
    {
        $meses = [];
        for ($i = $quantidade - 1; $i >= 0; $i--) {
            $meses[] = date('Y-m', strtotime('-' . $i . ' months', strtotime($inicioMes)));
        }
        return $meses;
    }
}

if (!function_exists('dashPalette')) {
    function dashPalette(): array
    {
        return ['#155e75', '#14b8a6', '#0e7490', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
    }
}

// ============================================================
// DASHCHART - Integrado com seu CSS
// ============================================================

if (!function_exists('dashChart')) {
    function dashChart(string $id, string $type, array $labels, array $values, array $colors = [], array $options = []): void
    {
        $defaultColors = ['#155e75', '#14b8a6', '#0e7490', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        $chartColors = !empty($colors) ? $colors : array_slice($defaultColors, 0, count($values));
        
        $numericValues = array_map('floatval', $values);
        $labelsJson = json_encode(array_values($labels), JSON_UNESCAPED_UNICODE);
        $valuesJson = json_encode($numericValues);
        $colorsJson = json_encode($chartColors);
        
        $typeConfigs = [
            'line' => [
                'fill' => true,
                'tension' => 0.4,
                'pointRadius' => 4,
                'pointHoverRadius' => 7,
                'borderWidth' => 3
            ],
            'bar' => [
                'borderRadius' => 8,
                'borderSkipped' => false,
                'barPercentage' => 0.7
            ],
            'doughnut' => [
                'cutout' => '70%',
                'borderWidth' => 3
            ],
            'pie' => [
                'borderWidth' => 3
            ]
        ];
        
        $typeConfig = $typeConfigs[$type] ?? $typeConfigs['line'];
        $typeOptions = '';
        foreach ($typeConfig as $key => $value) {
            if (is_bool($value)) {
                $typeOptions .= "$key: " . ($value ? 'true' : 'false') . ",";
            } else {
                $typeOptions .= "$key: $value,";
            }
        }
        
        $datasetConfig = "
            label: 'Registos',
            data: $valuesJson,
            backgroundColor: $colorsJson,
            borderColor: $colorsJson,
            $typeOptions
        ";
        
        if ($type === 'doughnut' || $type === 'pie') {
            $datasetConfig = "
                data: $valuesJson,
                backgroundColor: $colorsJson,
                borderColor: '#fff',
                borderWidth: 3,
                hoverOffset: 10
            ";
        }
        
        echo <<<HTML
        <div class="chart-container" style="position:relative;height:260px;width:100%;">
            <canvas id="{$id}"></canvas>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('{$id}');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const textColor = dark ? '#e6edef' : '#183044';
            const gridColor = dark ? 'rgba(255,255,255,.08)' : 'rgba(24,48,68,.08)';
            
            let options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { 
                            color: textColor,
                            usePointStyle: true,
                            padding: 16,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: dark ? '#14232d' : '#fff',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: true,
                        boxPadding: 4
                    }
                }
            };
            
            if (!['doughnut', 'pie'].includes('{$type}')) {
                options.scales = {
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#8a9aa0', font: { size: 10 } }
                    },
                    y: { 
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: { color: '#8a9aa0', font: { size: 10 } }
                    }
                };
            } else {
                options.cutout = '70%';
                options.plugins.legend.position = 'bottom';
                options.plugins.legend.labels.padding = 12;
            }
            
            new Chart(canvas, {
                type: '{$type}',
                data: {
                    labels: {$labelsJson},
                    datasets: [{
                        {$datasetConfig}
                    }]
                },
                options: options
            });
        });
        </script>
HTML;
    }
}

// ============================================================
// DASHKPI - Integrado com seu CSS
// ============================================================

if (!function_exists('dashKpi')) {
    function dashKpi(string $label, $value, string $icon, string $tone = 'primary', string $sub = ''): void
    {
        $toneColors = [
            'primary' => ['bg' => '#155e75', 'light' => 'rgba(21,94,117,0.12)'],
            'success' => ['bg' => '#14b8a6', 'light' => 'rgba(20,184,166,0.12)'],
            'warning' => ['bg' => '#f59e0b', 'light' => 'rgba(245,158,11,0.12)'],
            'danger' => ['bg' => '#ef4444', 'light' => 'rgba(239,68,68,0.12)'],
            'info' => ['bg' => '#0e7490', 'light' => 'rgba(14,116,144,0.12)'],
            'purple' => ['bg' => '#8b5cf6', 'light' => 'rgba(139,92,246,0.12)'],
            'pink' => ['bg' => '#ec4899', 'light' => 'rgba(236,72,153,0.12)'],
            'secondary' => ['bg' => '#6b7c88', 'light' => 'rgba(107,124,136,0.12)'],
        ];
        
        $color = $toneColors[$tone] ?? $toneColors['primary'];
        
        ?>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;letter-spacing:0.06em;">
                            <?= e($label) ?>
                        </div>
                        <div class="kpi-value text-truncate"><?= e((string) $value) ?></div>
                        <?php if ($sub !== ''): ?>
                            <div class="small text-muted" style="font-size:0.7rem;"><?= e($sub) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="kpi-icon" style="background:<?= $color['light'] ?>;color:<?= $color['bg'] ?>">
                        <i class="bi <?= e($icon) ?>"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}