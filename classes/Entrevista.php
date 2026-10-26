<?php
/** Entrevista.php - Model para agendamento de entrevistas. */

class Entrevista
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarPorCandidato(int $candidatoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.nome AS entrevistador_nome FROM entrevistas e
             LEFT JOIN usuarios u ON u.id = e.entrevistador_id
             WHERE e.candidato_id = :cid ORDER BY e.data_hora DESC'
        );
        $stmt->execute([':cid' => $candidatoId]);
        return $stmt->fetchAll();
    }

    public function listarProximas(): array
    {
        $stmt = $this->db->query(
            "SELECT e.*, c.nome AS candidato_nome, v.titulo AS vaga_titulo
             FROM entrevistas e
             JOIN candidatos c ON c.id = e.candidato_id
             JOIN vagas v ON v.id = c.vaga_id
             WHERE e.data_hora >= NOW() AND e.resultado = 'pendente'
             ORDER BY e.data_hora ASC LIMIT 10"
        );
        return $stmt->fetchAll();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO entrevistas (candidato_id, entrevistador_id, data_hora, local_ou_link, observacoes)
             VALUES (:cid, :entrevistador, :data, :local, :obs)'
        );
        $stmt->execute([
            ':cid'          => $dados['candidato_id'],
            ':entrevistador'=> $dados['entrevistador_id'] ?: null,
            ':data'         => $dados['data_hora'],
            ':local'        => $dados['local_ou_link'] ?: null,
            ':obs'          => $dados['observacoes'] ?: null,
        ]);

        // Atualiza o status do candidato para "entrevista"
        $stmtCand = $this->db->prepare("UPDATE candidatos SET status = 'entrevista' WHERE id = :cid");
        $stmtCand->execute([':cid' => $dados['candidato_id']]);

        return (int) $this->db->lastInsertId();
    }

    public function registrarResultado(int $id, string $resultado, ?string $observacoes): bool
    {
        $stmt = $this->db->prepare('UPDATE entrevistas SET resultado = :resultado, observacoes = :obs WHERE id = :id');
        return $stmt->execute([':resultado' => $resultado, ':obs' => $observacoes, ':id' => $id]);
    }
}
