<?php
/**
 * Departamento.php
 * Model para gestão de departamentos.
 */

class Departamento
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(bool $apenasAtivos = false): array
    {
        $sql = 'SELECT d.*, f.nome AS gestor_nome,
                       (SELECT COUNT(*) FROM funcionarios WHERE departamento_id = d.id AND status = "ativo") AS total_funcionarios
                FROM departamentos d
                LEFT JOIN funcionarios f ON f.id = d.gestor_id';
        if ($apenasAtivos) {
            $sql .= ' WHERE d.ativo = 1';
        }
        $sql .= ' ORDER BY d.nome ASC';

        return $this->db->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, f.nome AS gestor_nome FROM departamentos d
             LEFT JOIN funcionarios f ON f.id = d.gestor_id WHERE d.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO departamentos (nome, descricao, gestor_id) VALUES (:nome, :descricao, :gestor_id)'
        );
        $stmt->execute([
            ':nome'      => $dados['nome'],
            ':descricao' => $dados['descricao'] ?: null,
            ':gestor_id' => $dados['gestor_id'] ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE departamentos SET nome = :nome, descricao = :descricao, gestor_id = :gestor_id, ativo = :ativo WHERE id = :id'
        );
        return $stmt->execute([
            ':id'        => $id,
            ':nome'      => $dados['nome'],
            ':descricao' => $dados['descricao'] ?: null,
            ':gestor_id' => $dados['gestor_id'] ?: null,
            ':ativo'     => $dados['ativo'] ?? 1,
        ]);
    }

    /** Impede exclusão se houver funcionários ou cargos vinculados (integridade referencial). */
    public function podeExcluir(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM funcionarios WHERE departamento_id = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() === 0;
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM departamentos WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function contarTotal(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM departamentos WHERE ativo = 1')->fetchColumn();
    }
}
