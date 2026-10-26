<?php
/** Vaga.php - Model para vagas de recrutamento. */

class Vaga
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(?string $status = null): array
    {
        $sql = 'SELECT v.*, d.nome AS departamento_nome,
                       (SELECT COUNT(*) FROM candidatos WHERE vaga_id = v.id) AS total_candidatos
                FROM vagas v JOIN departamentos d ON d.id = v.departamento_id WHERE 1=1';
        $params = [];
        if ($status) {
            $sql .= ' AND v.status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY v.data_publicacao DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT v.*, d.nome AS departamento_nome FROM vagas v
             JOIN departamentos d ON d.id = v.departamento_id WHERE v.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados, int $criadoPor): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vagas (titulo, departamento_id, descricao, requisitos, data_publicacao, criado_por)
             VALUES (:titulo, :dep, :descricao, :requisitos, CURDATE(), :criador)'
        );
        $stmt->execute([
            ':titulo'     => $dados['titulo'],
            ':dep'        => $dados['departamento_id'],
            ':descricao'  => $dados['descricao'] ?: null,
            ':requisitos' => $dados['requisitos'] ?: null,
            ':criador'    => $criadoPor,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vagas SET titulo = :titulo, departamento_id = :dep, descricao = :descricao,
             requisitos = :requisitos, status = :status WHERE id = :id'
        );
        return $stmt->execute([
            ':id'         => $id,
            ':titulo'     => $dados['titulo'],
            ':dep'        => $dados['departamento_id'],
            ':descricao'  => $dados['descricao'] ?: null,
            ':requisitos' => $dados['requisitos'] ?: null,
            ':status'     => $dados['status'],
        ]);
    }

    public function contarAbertas(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM vagas WHERE status = 'aberta'")->fetchColumn();
    }
}
