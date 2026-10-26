<?php
/**
 * Ferias.php - Model para férias e licenças
 */

class Ferias
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ============================================================
    // MÉTODOS DE LISTAGEM
    // ============================================================

    /**
     * Lista férias/licenças com filtros
     */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT fl.*, f.nome as funcionario_nome, f.departamento_id 
                FROM ferias_licencas fl
                JOIN funcionarios f ON f.id = fl.funcionario_id';
        
        $where = [];
        $params = [];

        if (!empty($filtros['funcionario_id'])) {
            $where[] = 'fl.funcionario_id = :fid';
            $params[':fid'] = $filtros['funcionario_id'];
        }

        if (!empty($filtros['departamento_id'])) {
            $where[] = 'f.departamento_id = :dept';
            $params[':dept'] = $filtros['departamento_id'];
        }

        if (!empty($filtros['status'])) {
            $where[] = 'fl.status = :status';
            $params[':status'] = $filtros['status'];
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 'fl.tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY fl.data_inicio DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lista férias por período (para calendário)
     */
    public function listarPorPeriodo(string $dataInicio, string $dataFim, ?string $status = null): array
    {
        $sql = 'SELECT fl.*, f.nome as funcionario_nome 
                FROM ferias_licencas fl
                JOIN funcionarios f ON f.id = fl.funcionario_id
                WHERE fl.data_inicio <= :fim AND fl.data_fim >= :inicio';
        $params = [':inicio' => $dataInicio, ':fim' => $dataFim];
        
        if ($status) {
            $sql .= ' AND fl.status = :status';
            $params[':status'] = $status;
        }
        
        $sql .= ' ORDER BY fl.data_inicio ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Busca uma férias/licença por ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT fl.*, f.nome as funcionario_nome 
             FROM ferias_licencas fl
             JOIN funcionarios f ON f.id = fl.funcionario_id
             WHERE fl.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Lista férias próximas em um período
     */
    public function proximasNoPeriodo(int $dias = 7): array
    {
        $stmt = $this->db->prepare(
            "SELECT fl.*, f.nome as funcionario_nome 
             FROM ferias_licencas fl
             JOIN funcionarios f ON f.id = fl.funcionario_id
             WHERE fl.status = 'aprovado' 
             AND fl.data_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
             ORDER BY fl.data_inicio ASC"
        );
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll();
    }

    /**
     * Lista férias por funcionário
     */
    public function listarPorFuncionario(int $funcionarioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ferias_licencas 
             WHERE funcionario_id = :fid 
             ORDER BY data_inicio DESC'
        );
        $stmt->execute([':fid' => $funcionarioId]);
        return $stmt->fetchAll();
    }

    // ============================================================
    // MÉTODOS DE CRIAÇÃO E ATUALIZAÇÃO
    // ============================================================

    /**
     * Cria uma solicitação de férias/licença
     */
    public function solicitar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ferias_licencas (funcionario_id, tipo, data_inicio, data_fim, dias, motivo, status) 
             VALUES (:fid, :tipo, :inicio, :fim, :dias, :motivo, "pendente")'
        );
        $stmt->execute([
            ':fid'    => $dados['funcionario_id'],
            ':tipo'   => $dados['tipo'],
            ':inicio' => $dados['data_inicio'],
            ':fim'    => $dados['data_fim'],
            ':dias'   => $dados['dias'],
            ':motivo' => $dados['motivo'] ?? ''
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza o status de uma solicitação
     */
    public function atualizarStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE ferias_licencas SET status = :status WHERE id = :id');
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Atualiza uma solicitação completa
     */
    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ferias_licencas SET 
                tipo = :tipo,
                data_inicio = :inicio,
                data_fim = :fim,
                dias = :dias,
                motivo = :motivo,
                status = :status
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id'      => $id,
            ':tipo'    => $dados['tipo'],
            ':inicio'  => $dados['data_inicio'],
            ':fim'     => $dados['data_fim'],
            ':dias'    => $dados['dias'],
            ':motivo'  => $dados['motivo'] ?? '',
            ':status'  => $dados['status'] ?? 'pendente'
        ]);
    }

    /**
     * Exclui uma solicitação
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ferias_licencas WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ============================================================
    // MÉTODOS DE CONTAGEM
    // ============================================================

    /**
     * Conta férias pendentes
     */
    public function contarPendentes(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM ferias_licencas WHERE status = 'pendente'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta férias aprovadas
     */
    public function contarAprovadas(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM ferias_licencas WHERE status = 'aprovado'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta férias rejeitadas
     */
    public function contarRejeitadas(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM ferias_licencas WHERE status = 'rejeitado'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta férias canceladas
     */
    public function contarCanceladas(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM ferias_licencas WHERE status = 'cancelado'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta dias de férias usados por um funcionário
     */
    public function contarDiasUsados(int $funcionarioId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(dias), 0) FROM ferias_licencas 
             WHERE funcionario_id = :fid AND status = 'aprovado'"
        );
        $stmt->execute([':fid' => $funcionarioId]);
        return (int) $stmt->fetchColumn();
    }

    // ============================================================
    // MÉTODOS DE SALDO E VALIDAÇÃO
    // ============================================================

    /**
     * Calcula o saldo de férias disponível para um funcionário
     */
    public function saldoDisponivel(int $funcionarioId): int
    {
        $usados = $this->contarDiasUsados($funcionarioId);
        return max(0, 30 - $usados);
    }

    /**
     * Verifica se um funcionário já tem solicitação pendente para um período
     */
    public function temSolicitacaoPendente(int $funcionarioId, string $dataInicio, string $dataFim): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ferias_licencas 
             WHERE funcionario_id = :fid 
             AND status = 'pendente'
             AND ((data_inicio <= :inicio AND data_fim >= :inicio)
             OR (data_inicio <= :fim AND data_fim >= :fim)
             OR (data_inicio >= :inicio AND data_fim <= :fim))"
        );
        $stmt->execute([
            ':fid' => $funcionarioId,
            ':inicio' => $dataInicio,
            ':fim' => $dataFim
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ============================================================
    // MÉTODOS DE ESTATÍSTICAS
    // ============================================================

    /**
     * Obtém estatísticas gerais de férias
     */
    public function getEstatisticas(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
                SUM(CASE WHEN status = 'rejeitado' THEN 1 ELSE 0 END) as rejeitados,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                COALESCE(SUM(dias), 0) as total_dias,
                COALESCE(SUM(CASE WHEN status = 'aprovado' THEN dias ELSE 0 END), 0) as dias_aprovados
             FROM ferias_licencas"
        );
        return $stmt->fetch();
    }

    /**
     * Obtém estatísticas por departamento
     */
    public function getEstatisticasPorDepartamento(): array
    {
        $stmt = $this->db->query(
            "SELECT d.nome as departamento,
                    COUNT(fl.id) as total,
                    SUM(CASE WHEN fl.status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN fl.status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
                    COALESCE(SUM(CASE WHEN fl.status = 'aprovado' THEN fl.dias ELSE 0 END), 0) as dias_aprovados
             FROM ferias_licencas fl
             JOIN funcionarios f ON f.id = fl.funcionario_id
             JOIN departamentos d ON d.id = f.departamento_id
             WHERE d.ativo = 1
             GROUP BY d.id, d.nome
             ORDER BY total DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Obtém férias por mês (para gráficos)
     */
    public function getFeriasPorMes(int $ano): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                MONTH(data_inicio) as mes,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                COALESCE(SUM(CASE WHEN status = 'aprovado' THEN dias ELSE 0 END), 0) as dias
             FROM ferias_licencas
             WHERE YEAR(data_inicio) = :ano
             GROUP BY MONTH(data_inicio)
             ORDER BY mes ASC"
        );
        $stmt->execute([':ano' => $ano]);
        
        // Preencher meses vazios
        $resultado = array_fill(1, 12, ['total' => 0, 'aprovados' => 0, 'pendentes' => 0, 'dias' => 0]);
        foreach ($stmt->fetchAll() as $row) {
            $resultado[(int) $row['mes']] = $row;
        }
        return $resultado;
    }

    // ============================================================
    // MÉTODOS DE UTILITÁRIOS
    // ============================================================

    /**
     * Calcula o número de dias entre duas datas
     */
    public function calcularDias(string $dataInicio, string $dataFim): int
    {
        $inicio = new DateTime($dataInicio);
        $fim = new DateTime($dataFim);
        return $inicio->diff($fim)->days + 1;
    }

    /**
     * Verifica se um período é válido
     */
    public function validarPeriodo(string $dataInicio, string $dataFim): bool
    {
        $inicio = new DateTime($dataInicio);
        $fim = new DateTime($dataFim);
        return $fim >= $inicio;
    }

    /**
     * Obtém o status em formato de badge
     */
    public static function getStatusBadge(string $status): string
    {
        $badges = [
            'pendente' => 'warning',
            'aprovado' => 'success',
            'rejeitado' => 'danger',
            'cancelado' => 'secondary'
        ];
        return $badges[$status] ?? 'secondary';
    }

    /**
     * Obtém o ícone para o status
     */
    public static function getStatusIcon(string $status): string
    {
        $icons = [
            'pendente' => 'bi-clock',
            'aprovado' => 'bi-check-circle',
            'rejeitado' => 'bi-x-circle',
            'cancelado' => 'bi-x-circle'
        ];
        return $icons[$status] ?? 'bi-circle';
    }

    /**
     * Obtém a cor para o tipo
     */
    public static function getTipoCor(string $tipo): string
    {
        $cores = [
            'ferias' => 'primary',
            'licenca_medica' => 'danger',
            'licenca_maternidade' => 'pink',
            'licenca_paternidade' => 'info',
            'outro' => 'secondary'
        ];
        return $cores[$tipo] ?? 'secondary';
    }

    /**
     * Obtém o ícone para o tipo
     */
    public static function getTipoIcon(string $tipo): string
    {
        $icons = [
            'ferias' => 'bi-umbrella',
            'licenca_medica' => 'bi-heart-pulse',
            'licenca_maternidade' => 'bi-person-heart',
            'licenca_paternidade' => 'bi-person-heart',
            'outro' => 'bi-file-text'
        ];
        return $icons[$tipo] ?? 'bi-file-text';
    }
}