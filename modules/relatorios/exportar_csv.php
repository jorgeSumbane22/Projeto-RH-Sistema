<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_ADMIN, PERFIL_RH, PERFIL_GESTOR]);

$departamentoModel = new Departamento();
$departamentos = $departamentoModel->listar(true);

registrarAuditoria('relatorio_exportado', 'Funcionários por departamento');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="relatorio_departamentos_' . date('Y-m-d') . '.csv"');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF");
fputcsv($saida, ['Departamento', 'Gestor', 'Funcionários Ativos'], ';');
foreach ($departamentos as $d) {
    fputcsv($saida, [$d['nome'], $d['gestor_nome'] ?? '-', $d['total_funcionarios']], ';');
}
fclose($saida);
exit;