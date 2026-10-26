<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../modules/dashboard/_helpers.php';
$dir=__DIR__ . '/../storage/backups'; if (!is_dir($dir)) mkdir($dir,0750,true);
$file=$dir . '/backup_' . date('Ymd_His') . '.json'; $data=[]; foreach(['usuarios','funcionarios','departamentos','folha_pagamento','presencas','ferias_licencas'] as $table) $data[$table]=dashRows('SELECT * FROM `' . $table . '`'); file_put_contents($file,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); echo "Backup criado: {$file}
";
