<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

// Verificar se o autoload existe
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];

$found = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $found = true;
        break;
    }
}

if (!$found) {
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Erro - Dompdf não encontrado</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { 
                    background: #f8f9fa; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    min-height: 100vh; 
                    font-family: "Segoe UI", sans-serif;
                }
                .error-box {
                    background: white;
                    padding: 40px;
                    border-radius: 16px;
                    box-shadow: 0 4px 24px rgba(0,0,0,0.1);
                    max-width: 600px;
                    text-align: center;
                }
                .error-box .icon {
                    font-size: 4rem;
                    color: #dc3545;
                    margin-bottom: 1rem;
                }
                .error-box h3 {
                    color: #1a1a2e;
                    margin-bottom: 0.5rem;
                }
                .error-box p {
                    color: #6c757d;
                    margin-bottom: 1.5rem;
                }
                .error-box code {
                    background: #f1f3f5;
                    padding: 10px 15px;
                    border-radius: 8px;
                    display: block;
                    font-size: 0.9rem;
                    margin: 1rem 0;
                    text-align: left;
                }
                .btn-primary {
                    background: linear-gradient(135deg, #1C6E8C, #2E9BAE);
                    border: none;
                    padding: 10px 30px;
                    border-radius: 10px;
                    font-weight: 500;
                }
                .btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 16px rgba(28,110,140,0.3);
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <div class="icon">📄</div>
                <h3>Erro: Biblioteca Dompdf não encontrada</h3>
                <p>Para gerar PDF, você precisa instalar o Dompdf via Composer.</p>
                <code>composer require dompdf/dompdf</code>
                <p style="font-size:0.8rem; margin-top:1rem;">
                    <strong>Ou baixe manualmente:</strong><br>
                    <a href="https://github.com/dompdf/dompdf/releases" target="_blank">https://github.com/dompdf/dompdf/releases</a>
                </p>
                <a href="index.php" class="btn btn-primary">Voltar</a>
            </div>
        </body>
        </html>
    ');
}

use Dompdf\Dompdf;
use Dompdf\Options;

$mesReferencia = $_GET['mes'] ?? date('Y-m');
$folhaModel = new FolhaPagamento();
$folhas = $folhaModel->listar($mesReferencia);

$totalMes = array_sum(array_column($folhas, 'salario_liquido'));

// Obter nome do mês
$meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
list($ano, $mes) = explode('-', $mesReferencia);
$nomeMes = $meses[$mes] . ' de ' . $ano;

// HTML do PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Folha de Pagamento - ' . $nomeMes . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1C6E8C;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #1C6E8C;
            margin: 0;
            font-size: 22px;
        }
        .header .subtitle {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .header .date {
            color: #999;
            font-size: 11px;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th {
            background: #1C6E8C;
            color: #fff;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        table tr:nth-child(even) {
            background: #f8f9fa;
        }
        table tr:hover {
            background: #e8f4f8;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .positive {
            color: #28a745;
        }
        .negative {
            color: #dc3545;
        }
        .total {
            font-weight: bold;
            border-top: 2px solid #1C6E8C;
            padding-top: 8px;
            margin-top: 5px;
        }
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #999;
            text-align: center;
        }
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        .status-pago {
            background: #d4edda;
            color: #155724;
        }
        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }
        .summary {
            margin-top: 15px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .summary .item {
            display: inline-block;
            margin-right: 30px;
        }
        .summary .item .label {
            font-size: 10px;
            color: #666;
        }
        .summary .item .value {
            font-size: 14px;
            font-weight: bold;
            color: #1C6E8C;
        }
        @page {
            margin: 20px;
        }
        @media print {
            body { margin: 0; padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>📄 Folha de Pagamento</h1>
        <div class="subtitle">Sistema de RH</div>
        <div class="date">Referência: ' . $nomeMes . ' | Gerado em: ' . date('d/m/Y H:i') . '</div>
    </div>

    <div class="summary">
        <span class="item">
            <span class="label">Total Funcionários</span><br>
            <span class="value">' . number_format(count($folhas)) . '</span>
        </span>
        <span class="item">
            <span class="label">Total Líquido</span><br>
            <span class="value">' . dashMoney($totalMes) . '</span>
        </span>
        <span class="item">
            <span class="label">Média Salarial</span><br>
            <span class="value">' . (count($folhas) > 0 ? dashMoney($totalMes / count($folhas)) : 'MT 0,00') . '</span>
        </span>
        <span class="item">
            <span class="label">Pagamentos</span><br>
            <span class="value">' . number_format(count(array_filter($folhas, function($f) { return $f['status'] === 'pago'; }))) . '/' . number_format(count($folhas)) . '</span>
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Funcionário</th>
                <th>Departamento</th>
                <th class="text-right">Salário Base</th>
                <th class="text-right">H. Extras</th>
                <th class="text-right">Bônus</th>
                <th class="text-right">Faltas</th>
                <th class="text-right">Descontos</th>
                <th class="text-right">Líquido</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>';

if (empty($folhas)) {
    $html .= '
            <tr>
                <td colspan="9" style="text-align:center; padding:30px; color:#999;">
                    Nenhuma folha gerada para este mês.
                </td>
            </tr>';
} else {
    foreach ($folhas as $f) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($f['funcionario_nome']) . '</td>
                <td>' . htmlspecialchars($f['departamento_nome']) . '</td>
                <td class="text-right">' . dashMoney($f['salario_base']) . '</td>
                <td class="text-right positive">+' . dashMoney($f['horas_extras_valor']) . '</td>
                <td class="text-right positive">+' . dashMoney($f['bonus']) . '</td>
                <td class="text-right negative">-' . dashMoney($f['faltas_desconto']) . '</td>
                <td class="text-right negative">-' . dashMoney($f['descontos']) . '</td>
                <td class="text-right" style="font-weight:bold;">' . dashMoney($f['salario_liquido']) . '</td>
                <td class="text-center">
                    <span class="status-badge status-' . $f['status'] . '">' . ucfirst($f['status']) . '</span>
                </td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        Este documento é gerado automaticamente pelo Sistema de RH.<br>
        Valores em Metical (MT) • ' . date('d/m/Y H:i:s') . '
    </div>

</body>
</html>';

// Configurar Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('chroot', __DIR__);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Enviar para download
$dompdf->stream('folha_pagamento_' . $mesReferencia . '.pdf', ['Attachment' => true]);
exit;