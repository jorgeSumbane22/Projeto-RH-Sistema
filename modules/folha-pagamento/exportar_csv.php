<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);

$mesReferencia = $_GET['mes'] ?? date('Y-m');
$folhaModel = new FolhaPagamento();
$folhas = $folhaModel->listar($mesReferencia);

registrarAuditoria('folha_exportada', "Mês $mesReferencia");

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="folha_' . $mesReferencia . '.csv"');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF");

fputcsv($saida, ['Funcionário', 'Departamento', 'Salário Base (MT)', 'Horas Extras (MT)', 'Bônus (MT)', 'Faltas (MT)', 'Descontos (MT)', 'Salário Líquido (MT)', 'Status'], ';');
foreach ($folhas as $f) {
    fputcsv($saida, [
        $f['funcionario_nome'],
        $f['departamento_nome'],
        number_format((float) $f['salario_base'], 2, ',', '.'),
        number_format((float) $f['horas_extras_valor'], 2, ',', '.'),
        number_format((float) $f['bonus'], 2, ',', '.'),
        number_format((float) $f['faltas_desconto'], 2, ',', '.'),
        number_format((float) $f['descontos'], 2, ',', '.'),
        number_format((float) $f['salario_liquido'], 2, ',', '.'),
        ucfirst($f['status']),
    ], ';');
}
fclose($saida);
exit;