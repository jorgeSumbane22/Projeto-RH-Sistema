<?php
/**
 * Avaliacao.php - Model para avaliações de desempenho
 */

class Avaliacao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Lista avaliações com filtros
     */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT a.*, f.nome as funcionario_nome, u.nome as avaliador_nome 
                FROM avaliacoes_desempenho a
                JOIN funcionarios f ON f.id = a.funcionario_id
                LEFT JOIN usuarios u ON u.id = a.avaliador_id';
        
        $where = [];
        $params = [];

        if (!empty($filtros['funcionario_id'])) {
            $where[] = 'a.funcionario_id = :fid';
            $params[':fid'] = $filtros['funcionario_id'];
        }

        if (!empty($filtros['departamento_id'])) {
            $where[] = 'f.departamento_id = :dept';
            $params[':dept'] = $filtros['departamento_id'];
        }

        if (!empty($filtros['periodo'])) {
            $where[] = 'a.periodo = :periodo';
            $params[':periodo'] = $filtros['periodo'];
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.data_avaliacao DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Busca uma avaliação por ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, f.nome as funcionario_nome, u.nome as avaliador_nome 
             FROM avaliacoes_desempenho a
             JOIN funcionarios f ON f.id = a.funcionario_id
             LEFT JOIN usuarios u ON u.id = a.avaliador_id
             WHERE a.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Cria uma nova avaliação
     */
    public function criar(array $dados, int $avaliadorId): int
    {
        $notaFinal = ($dados['pontualidade'] + $dados['qualidade_trabalho'] + 
                      $dados['trabalho_equipe'] + $dados['proatividade']) / 4;

        $stmt = $this->db->prepare(
            'INSERT INTO avaliacoes_desempenho 
             (funcionario_id, avaliador_id, periodo, pontualidade, qualidade_trabalho, 
              trabalho_equipe, proatividade, nota_final, comentarios) 
             VALUES (:fid, :aid, :periodo, :pontualidade, :qualidade, :equipe, :proatividade, :nota, :comentarios)'
        );
        $stmt->execute([
            ':fid'          => $dados['funcionario_id'],
            ':aid'          => $avaliadorId,
            ':periodo'      => $dados['periodo'],
            ':pontualidade' => $dados['pontualidade'],
            ':qualidade'    => $dados['qualidade_trabalho'],
            ':equipe'       => $dados['trabalho_equipe'],
            ':proatividade' => $dados['proatividade'],
            ':nota'         => $notaFinal,
            ':comentarios'  => $dados['comentarios'] ?? ''
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza uma avaliação
     */
    public function atualizar(int $id, array $dados): bool
    {
        $notaFinal = ($dados['pontualidade'] + $dados['qualidade_trabalho'] + 
                      $dados['trabalho_equipe'] + $dados['proatividade']) / 4;

        $stmt = $this->db->prepare(
            'UPDATE avaliacoes_desempenho SET 
                periodo = :periodo,
                pontualidade = :pontualidade,
                qualidade_trabalho = :qualidade,
                trabalho_equipe = :equipe,
                proatividade = :proatividade,
                nota_final = :nota,
                comentarios = :comentarios
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id'           => $id,
            ':periodo'      => $dados['periodo'],
            ':pontualidade' => $dados['pontualidade'],
            ':qualidade'    => $dados['qualidade_trabalho'],
            ':equipe'       => $dados['trabalho_equipe'],
            ':proatividade' => $dados['proatividade'],
            ':nota'         => $notaFinal,
            ':comentarios'  => $dados['comentarios'] ?? ''
        ]);
    }

    /**
     * Conta avaliações por funcionário
     */
    public function contarPorFuncionario(int $funcionarioId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM avaliacoes_desempenho WHERE funcionario_id = :fid');
        $stmt->execute([':fid' => $funcionarioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta quantos funcionários ativos não têm avaliação no ano atual
     * (USADO PELAS NOTIFICAÇÕES)
     */
    public function contarPendentes(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM funcionarios f 
             WHERE f.status = 'ativo' 
             AND NOT EXISTS (
                 SELECT 1 FROM avaliacoes_desempenho a 
                 WHERE a.funcionario_id = f.id 
                 AND YEAR(a.data_avaliacao) = YEAR(CURDATE())
             )"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtém a última avaliação de um funcionário
     */
    public function ultimaPorFuncionario(int $funcionarioId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM avaliacoes_desempenho 
             WHERE funcionario_id = :fid 
             ORDER BY data_avaliacao DESC 
             LIMIT 1'
        );
        $stmt->execute([':fid' => $funcionarioId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obtém a média de avaliações por departamento
     */
    public function mediaPorDepartamento(): array
    {
        $stmt = $this->db->query(
            "SELECT d.nome, AVG(a.nota_final) as media, COUNT(a.id) as total
             FROM avaliacoes_desempenho a
             JOIN funcionarios f ON f.id = a.funcionario_id
             JOIN departamentos d ON d.id = f.departamento_id
             WHERE a.nota_final IS NOT NULL
             GROUP BY d.id, d.nome
             ORDER BY media DESC"
        );
        return $stmt->fetchAll();
    }
}