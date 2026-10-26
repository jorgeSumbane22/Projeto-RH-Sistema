<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
exigirPerfil([PERFIL_SUPER_ADMIN]);

$permitidas = [
    'departamentos',
    'cargos',
    'funcionarios',
    'usuarios',
    'documentos',
    'presencas',
    'ferias_licencas',
    'folha_pagamento',
    'vagas',
    'candidatos',
    'entrevistas',
    'avaliacoes_desempenho',
    'notificacoes',
    'log_auditoria'
];

$db = Database::getConnection();
$backup = [
    'gerado_em' => date(DATE_ATOM),
    'sistema' => 'RH-System',
    'versao' => '2.0.1',
    'tabelas' => []
];

foreach ($permitidas as $tabela) {
    $stmt = $db->query('SELECT * FROM `' . $tabela . '`');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Remove dados sensíveis dos usuários
    if ($tabela === 'usuarios') {
        foreach ($rows as &$row) {
            unset($row['senha_hash'], $row['tentativas_login'], $row['bloqueado_ate']);
        }
        unset($row);
    }
    
    // Remove dados sensíveis dos documentos (caminhos completos)
    if ($tabela === 'documentos') {
        foreach ($rows as &$row) {
            if (isset($row['caminho_arquivo'])) {
                $row['caminho_arquivo'] = basename($row['caminho_arquivo']);
            }
        }
        unset($row);
    }
    
    $backup['tabelas'][$tabela] = $rows;
}

// Registra a ação de auditoria
registrarAuditoria('backup_exportado', 'Exportação JSON das tabelas principais');

// Define o nome do arquivo
$nome = 'rh-system-backup-' . date('Y-m-d-His') . '.json';

// Headers para download
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Estatísticas para o cabeçalho do arquivo
$totalTabelas = count($permitidas);
$totalRegistros = 0;
foreach ($backup['tabelas'] as $rows) {
    $totalRegistros += count($rows);
}

// Adiciona metadados ao backup
$backup['metadata'] = [
    'total_tabelas' => $totalTabelas,
    'total_registros' => $totalRegistros,
    'tamanho_estimado' => round(strlen(json_encode($backup)) / 1024, 2) . ' KB',
    'usuario' => usuarioAtualNome(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconhecido'
];

// Saída do JSON
echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;