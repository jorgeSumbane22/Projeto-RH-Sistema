<?php
/** Candidato.php - Model para candidatos inscritos em vagas. */

class Candidato
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarPorVaga(int $vagaId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM candidatos WHERE vaga_id = :vid ORDER BY data_inscricao DESC');
        $stmt->execute([':vid' => $vagaId]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, v.titulo AS vaga_titulo FROM candidatos c JOIN vagas v ON v.id = c.vaga_id WHERE c.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO candidatos (vaga_id, nome, email, telefone, curriculo_arquivo)
             VALUES (:vaga, :nome, :email, :telefone, :curriculo)'
        );
        $stmt->execute([
            ':vaga'      => $dados['vaga_id'],
            ':nome'      => $dados['nome'],
            ':email'     => $dados['email'],
            ':telefone'  => $dados['telefone'] ?: null,
            ':curriculo' => $dados['curriculo_arquivo'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE candidatos SET status = :status WHERE id = :id');
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
