<?php
/**
 * Cargo.php - Model para gestão de cargos
 */

class Cargo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        $stmt = $this->db->query(
            'SELECT c.*, d.nome AS departamento_nome 
             FROM cargos c
             LEFT JOIN departamentos d ON d.id = c.departamento_id
             ORDER BY c.titulo ASC'
        );
        return $stmt->fetchAll();
    }

    public function listarAtivos(): array
    {
        $stmt = $this->db->query(
            'SELECT c.*, d.nome AS departamento_nome 
             FROM cargos c
             LEFT JOIN departamentos d ON d.id = c.departamento_id
             WHERE c.ativo = 1
             ORDER BY c.titulo ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * LISTA CARGOS POR DEPARTAMENTO - MÉTODO CORRIGIDO
     */
    public function listarPorDepartamento(int $departamentoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, 
                    (SELECT COUNT(*) FROM funcionarios f WHERE f.cargo_id = c.id AND f.status = "ativo") AS total_funcionarios
             FROM cargos c
             WHERE c.departamento_id = :dept
             ORDER BY c.titulo ASC'
        );
        $stmt->execute([':dept' => $departamentoId]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, d.nome AS departamento_nome 
             FROM cargos c
             LEFT JOIN departamentos d ON d.id = c.departamento_id
             WHERE c.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cargos (titulo, descricao, departamento_id, salario_base, ativo) 
             VALUES (:titulo, :descricao, :dept, :salario, :ativo)'
        );
        $stmt->execute([
            ':titulo'    => $dados['titulo'],
            ':descricao' => $dados['descricao'] ?? '',
            ':dept'      => $dados['departamento_id'],
            ':salario'   => $dados['salario_base'],
            ':ativo'     => $dados['ativo'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = 'UPDATE cargos SET 
                    titulo = :titulo, 
                    descricao = :descricao, 
                    departamento_id = :dept, 
                    salario_base = :salario, 
                    ativo = :ativo 
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'        => $id,
            ':titulo'    => $dados['titulo'],
            ':descricao' => $dados['descricao'] ?? '',
            ':dept'      => $dados['departamento_id'],
            ':salario'   => $dados['salario_base'],
            ':ativo'     => $dados['ativo'] ?? 1,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cargos WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function podeExcluir(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM funcionarios WHERE cargo_id = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() === 0;
    }
}