<?php
/** LogAuditoria.php - Model para consulta do histórico de ações do sistema. */

class LogAuditoria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(array $filtros = [], int $porPagina = 20, int $pagina = 1): array
    {
        $sql = 'SELECT l.*, u.nome AS usuario_nome, u.email AS usuario_email
                FROM log_auditoria l
                LEFT JOIN usuarios u ON u.id = l.usuario_id
                WHERE 1=1';
        $params = [];

        if (!empty($filtros['acao'])) {
            $sql .= ' AND l.acao LIKE :acao';
            $params[':acao'] = '%' . $filtros['acao'] . '%';
        }
        if (!empty($filtros['usuario_id'])) {
            $sql .= ' AND l.usuario_id = :uid';
            $params[':uid'] = $filtros['usuario_id'];
        }
        if (!empty($filtros['data_inicio'])) {
            $sql .= ' AND DATE(l.criado_em) >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $sql .= ' AND DATE(l.criado_em) <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'];
        }

        $sqlTotal = str_replace('SELECT l.*, u.nome AS usuario_nome, u.email AS usuario_email', 'SELECT COUNT(*)', $sql);
        $stmtTotal = $this->db->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $offset = max(0, ($pagina - 1) * $porPagina);
        $sql .= ' ORDER BY l.criado_em DESC LIMIT :limite OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['registros' => $stmt->fetchAll(), 'total' => $total];
    }
}
