<?php
/**
 * Documento.php - Model para gerenciamento de documentos dos funcionários
 */

class Documento
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Lista documentos de um funcionário
     */
    public function listarPorFuncionario(int $funcionarioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM documentos WHERE funcionario_id = :fid ORDER BY criado_em DESC'
        );
        $stmt->execute([':fid' => $funcionarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca um documento por ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM documentos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Cria um novo documento
     */
    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO documentos (funcionario_id, tipo, nome_original, caminho_arquivo, tamanho_bytes, enviado_por) 
             VALUES (:fid, :tipo, :nome, :caminho, :tamanho, :enviado_por)'
        );
        $stmt->execute([
            ':fid'         => $dados['funcionario_id'],
            ':tipo'        => $dados['tipo'],
            ':nome'        => $dados['nome_original'],
            ':caminho'     => $dados['caminho_arquivo'],
            ':tamanho'     => $dados['tamanho_bytes'],
            ':enviado_por' => $dados['enviado_por']
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Exclui um documento
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM documentos WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Conta documentos por funcionário
     */
    public function contarPorFuncionario(int $funcionarioId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM documentos WHERE funcionario_id = :fid');
        $stmt->execute([':fid' => $funcionarioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta quantos funcionários ativos não têm documentos registrados
     * (USADO PELAS NOTIFICAÇÕES)
     */
    public function contarPendentes(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM funcionarios f 
             WHERE f.status = 'ativo' 
             AND NOT EXISTS (
                 SELECT 1 FROM documentos d 
                 WHERE d.funcionario_id = f.id
             )"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica se um funcionário tem documentos pendentes
     */
    public function temDocumentosPendentes(int $funcionarioId): bool
    {
        return $this->contarPorFuncionario($funcionarioId) === 0;
    }

    /**
     * Lista documentos por tipo
     */
    public function listarPorTipo(int $funcionarioId, string $tipo): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM documentos WHERE funcionario_id = :fid AND tipo = :tipo ORDER BY criado_em DESC'
        );
        $stmt->execute([':fid' => $funcionarioId, ':tipo' => $tipo]);
        return $stmt->fetchAll();
    }

    /**
     * Obtém estatísticas de documentos
     */
    public function getEstatisticas(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT funcionario_id) as funcionarios_com_docs,
                SUM(tamanho_bytes) as tamanho_total
             FROM documentos"
        );
        return $stmt->fetch();
    }
}