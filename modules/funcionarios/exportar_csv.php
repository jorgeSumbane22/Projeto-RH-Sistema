<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN, PERFIL_ADMIN_RH, PERFIL_RH, PERFIL_GESTOR]);
require_once __DIR__ . '/../dashboard/_helpers.php';
$rows = dashRows('SELECT f.nome,f.email,f.telefone,f.data_admissao,f.status,d.nome departamento,c.titulo cargo FROM funcionarios f LEFT JOIN departamentos d ON d.id=f.departamento_id LEFT JOIN cargos c ON c.id=f.cargo_id ORDER BY f.nome');
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=funcionarios.csv');
$out=fopen('php://output','w'); fputcsv($out,['Nome','Email','Telefone','Admissão','Estado','Departamento','Cargo'],';'); foreach($rows as $r) fputcsv($out,$r,';'); fclose($out);
