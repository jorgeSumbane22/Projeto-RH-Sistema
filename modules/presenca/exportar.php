<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN, PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR, PERFIL_AUDITOR]);
require_once __DIR__ . '/../dashboard/_helpers.php';
$rows=dashRows('SELECT f.nome,p.data,p.hora_entrada,p.hora_saida,p.horas_extras,p.status FROM presencas p JOIN funcionarios f ON f.id=p.funcionario_id ORDER BY p.data DESC LIMIT 1000');
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=presencas.csv'); $out=fopen('php://output','w'); fputcsv($out,['Funcionário','Data','Entrada','Saída','Horas extras','Estado'],';'); foreach($rows as $r) fputcsv($out,$r,';'); fclose($out);
