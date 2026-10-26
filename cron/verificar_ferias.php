<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$rows=dashRows('SELECT fl.id,f.nome,fl.data_inicio FROM ferias_licencas fl JOIN funcionarios f ON f.id=fl.funcionario_id WHERE fl.status="aprovado" AND fl.data_inicio=DATE_ADD(CURDATE(),INTERVAL 7 DAY)'); echo count($rows) . " férias começam em 7 dias.
";
