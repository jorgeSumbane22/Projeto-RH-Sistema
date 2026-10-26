<?php
/**
 * Presenca.php - Model para gestão de presenças e ponto
 */

class Presenca
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Lista presenças com filtros
     */
    public function listarComFiltros(string $dataInicio, string $dataFim, $funcionarioId = null): array
    {
        $sql = 'SELECT p.*, f.nome as funcionario_nome, f.departamento_id 
                FROM presencas p
                JOIN funcionarios f ON f.id = p.funcionario_id
                WHERE p.data BETWEEN :inicio AND :fim';
        $params = [':inicio' => $dataInicio, ':fim' => $dataFim];
        
        if ($funcionarioId) {
            $sql .= ' AND p.funcionario_id = :fid';
            $params[':fid'] = $funcionarioId;
        }
        
        $sql .= ' ORDER BY p.data DESC, p.funcionario_id ASC';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lista presenças por data
     */
    public function listarPorData(string $data): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, f.nome as funcionario_nome 
             FROM presencas p
             JOIN funcionarios f ON f.id = p.funcionario_id
             WHERE p.data = :data'
        );
        $stmt->execute([':data' => $data]);
        return $stmt->fetchAll();
    }

    /**
     * Busca presença por ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, f.nome as funcionario_nome 
             FROM presencas p
             JOIN funcionarios f ON f.id = p.funcionario_id
             WHERE p.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca presença do dia para um funcionário
     */
    public function buscarDoDia(int $funcionarioId, string $data): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM presencas WHERE funcionario_id = :fid AND data = :data'
        );
        $stmt->execute([':fid' => $funcionarioId, ':data' => $data]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Lista presenças de um funcionário em um período
     */
    public function listarPorFuncionario(int $funcionarioId, string $dataInicio, string $dataFim): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM presencas 
             WHERE funcionario_id = :fid 
             AND data BETWEEN :inicio AND :fim 
             ORDER BY data ASC'
        );
        $stmt->execute([
            ':fid' => $funcionarioId,
            ':inicio' => $dataInicio,
            ':fim' => $dataFim
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Registra entrada
     */
    public function registrarEntrada(int $funcionarioId): array
    {
        $hoje = date('Y-m-d');
        $hora = date('H:i:s');
        
        $registro = $this->buscarDoDia($funcionarioId, $hoje);
        if ($registro) {
            return ['sucesso' => false, 'mensagem' => 'Entrada já registrada hoje.'];
        }
        
        $stmt = $this->db->prepare(
            'INSERT INTO presencas (funcionario_id, data, hora_entrada, status) 
             VALUES (:fid, :data, :hora, "presente")'
        );
        $stmt->execute([
            ':fid' => $funcionarioId,
            ':data' => $hoje,
            ':hora' => $hora
        ]);
        
        return ['sucesso' => true, 'mensagem' => 'Entrada registrada com sucesso.'];
    }

    /**
     * Registra saída
     */
    public function registrarSaida(int $funcionarioId): array
    {
        $hoje = date('Y-m-d');
        $hora = date('H:i:s');
        
        $registro = $this->buscarDoDia($funcionarioId, $hoje);
        if (!$registro) {
            return ['sucesso' => false, 'mensagem' => 'Nenhuma entrada registrada hoje.'];
        }
        if ($registro['hora_saida']) {
            return ['sucesso' => false, 'mensagem' => 'Saída já registrada hoje.'];
        }
        
        $stmt = $this->db->prepare(
            'UPDATE presencas SET hora_saida = :hora WHERE id = :id'
        );
        $stmt->execute([':hora' => $hora, ':id' => $registro['id']]);
        
        return ['sucesso' => true, 'mensagem' => 'Saída registrada com sucesso.'];
    }

    /**
     * Lança presença manualmente
     */
    public function lancarManual(array $dados): int
    {
        $existente = $this->buscarDoDia($dados['funcionario_id'], $dados['data']);
        if ($existente) {
            $this->atualizar($existente['id'], $dados);
            return $existente['id'];
        }
        
        $stmt = $this->db->prepare(
            'INSERT INTO presencas 
             (funcionario_id, data, hora_entrada, hora_saida_almoco, hora_retorno, hora_saida, horas_extras, status, observacao) 
             VALUES (:fid, :data, :entrada, :saida_almoco, :retorno, :saida, :extras, :status, :obs)'
        );
        $stmt->execute([
            ':fid' => $dados['funcionario_id'],
            ':data' => $dados['data'],
            ':entrada' => $dados['hora_entrada'] ?: null,
            ':saida_almoco' => $dados['hora_saida_almoco'] ?? null,
            ':retorno' => $dados['hora_retorno'] ?? null,
            ':saida' => $dados['hora_saida'] ?: null,
            ':extras' => $dados['horas_extras'] ?? 0,
            ':status' => $dados['status'],
            ':obs' => $dados['observacao'] ?? '',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza uma presença
     */
    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE presencas SET 
                hora_entrada = :entrada,
                hora_saida_almoco = :saida_almoco,
                hora_retorno = :retorno,
                hora_saida = :saida,
                horas_extras = :extras,
                status = :status,
                observacao = :obs
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':entrada' => $dados['hora_entrada'] ?: null,
            ':saida_almoco' => $dados['hora_saida_almoco'] ?? null,
            ':retorno' => $dados['hora_retorno'] ?? null,
            ':saida' => $dados['hora_saida'] ?: null,
            ':extras' => $dados['horas_extras'] ?? 0,
            ':status' => $dados['status'],
            ':obs' => $dados['observacao'] ?? '',
        ]);
    }

    /**
     * Exclui uma presença
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM presencas WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Calcula horas trabalhadas
     */
    public function calcularHorasTrabalhadas(array $presenca): float
    {
        $total = 0;
        if (!empty($presenca['hora_entrada']) && !empty($presenca['hora_saida'])) {
            $entrada = strtotime($presenca['hora_entrada']);
            $saida = strtotime($presenca['hora_saida']);
            $total = ($saida - $entrada) / 3600;
            
            if (!empty($presenca['hora_saida_almoco']) && !empty($presenca['hora_retorno'])) {
                $almoco = strtotime($presenca['hora_retorno']) - strtotime($presenca['hora_saida_almoco']);
                $total -= $almoco / 3600;
            }
        }
        return max(0, $total);
    }

    /**
     * Obtém estatísticas de presença
     */
    public function getEstatisticas(string $dataInicio, string $dataFim, int $departamentoId = null): array
    {
        $sql = 'SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "presente" THEN 1 ELSE 0 END) as presentes,
                    SUM(CASE WHEN status = "falta" THEN 1 ELSE 0 END) as faltas,
                    SUM(CASE WHEN status = "atraso" THEN 1 ELSE 0 END) as atrasos,
                    SUM(CASE WHEN status = "justificado" THEN 1 ELSE 0 END) as justificados,
                    COALESCE(SUM(horas_extras), 0) as horas_extras
                FROM presencas p
                JOIN funcionarios f ON f.id = p.funcionario_id
                WHERE p.data BETWEEN :inicio AND :fim';
        $params = [':inicio' => $dataInicio, ':fim' => $dataFim];
        
        if ($departamentoId) {
            $sql .= ' AND f.departamento_id = :dept';
            $params[':dept'] = $departamentoId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Obtém faltas excessivas
     */
    public function faltasExcessivas(string $dataInicio, string $dataFim, int $limite = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.nome, f.id, COUNT(p.id) as faltas
             FROM presencas p
             JOIN funcionarios f ON f.id = p.funcionario_id
             WHERE p.status = "falta" 
             AND p.data BETWEEN :inicio AND :fim
             GROUP BY f.id, f.nome
             HAVING faltas > :limite
             ORDER BY faltas DESC'
        );
        $stmt->execute([
            ':inicio' => $dataInicio,
            ':fim' => $dataFim,
            ':limite' => $limite
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Soma as horas extras de um funcionário em um determinado mês
     *
     * @param int    $funcionarioId ID do funcionário
     * @param string $mes           Mês no formato 'YYYY-MM' (ex: '2026-10')
     * @return float                Total de horas extras no mês
     */
    public function somarHorasExtrasMes(int $funcionarioId, string $mes): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(horas_extras), 0) AS total
             FROM presencas
             WHERE funcionario_id = :fid
               AND DATE_FORMAT(data, "%Y-%m") = :mes'
        );
        $stmt->execute([
            ':fid' => $funcionarioId,
            ':mes' => $mes
        ]);

        $row = $stmt->fetch();
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Conta faltas de um funcionário em um mês
     *
     * @param int    $funcionarioId ID do funcionário
     * @param string $mes           Mês no formato 'YYYY-MM' (ex: '2026-10')
     * @return int                  Total de faltas no mês
     */
    public function contarFaltasMes(int $funcionarioId, string $mes): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total
             FROM presencas
             WHERE funcionario_id = :fid
               AND status = "falta"
               AND DATE_FORMAT(data, "%Y-%m") = :mes'
        );
        $stmt->execute([
            ':fid' => $funcionarioId,
            ':mes' => $mes
        ]);

        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Conta atrasos de um funcionário em um mês
     *
     * @param int    $funcionarioId ID do funcionário
     * @param string $mes           Mês no formato 'YYYY-MM' (ex: '2026-10')
     * @return int                  Total de atrasos no mês
     */
    public function contarAtrasosMes(int $funcionarioId, string $mes): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total
             FROM presencas
             WHERE funcionario_id = :fid
               AND status = "atraso"
               AND DATE_FORMAT(data, "%Y-%m") = :mes'
        );
        $stmt->execute([
            ':fid' => $funcionarioId,
            ':mes' => $mes
        ]);

        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }
}