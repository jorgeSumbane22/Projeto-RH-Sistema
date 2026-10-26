<?php
/**
 * Funcionario.php
 * Model responsável por toda a persistência relacionada a funcionários.
 * Cada método monta e executa uma query preparada, nunca concatenando
 * entrada do usuário diretamente no SQL (proteção contra SQL Injection).
 */

class Funcionario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Lista funcionários com filtros opcionais (nome, departamento, status). */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT f.*, c.titulo AS cargo_titulo, d.nome AS departamento_nome
                FROM funcionarios f
                JOIN cargos c ON c.id = f.cargo_id
                JOIN departamentos d ON d.id = f.departamento_id
                WHERE 1=1';
        $params = [];

        if (!empty($filtros['nome'])) {
            $sql .= ' AND f.nome LIKE :nome';
            $params[':nome'] = '%' . $filtros['nome'] . '%';
        }
        if (!empty($filtros['departamento_id'])) {
            $sql .= ' AND f.departamento_id = :departamento_id';
            $params[':departamento_id'] = $filtros['departamento_id'];
        }
        if (!empty($filtros['status'])) {
            $sql .= ' AND f.status = :status';
            $params[':status'] = $filtros['status'];
        }

        $sql .= ' ORDER BY f.nome ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Lista apenas funcionários ativos (método de conveniência) */
    public function listarAtivos(): array
    {
        return $this->listar(['status' => 'ativo']);
    }

    /** Versão paginada de listar(), usada na tela de listagem principal. */
    public function listarPaginado(array $filtros, int $porPagina, int $pagina): array
    {
        $sqlBase = 'FROM funcionarios f
                JOIN cargos c ON c.id = f.cargo_id
                JOIN departamentos d ON d.id = f.departamento_id
                WHERE 1=1';
        $params = [];

        if (!empty($filtros['nome'])) {
            $sqlBase .= ' AND f.nome LIKE :nome';
            $params[':nome'] = '%' . $filtros['nome'] . '%';
        }
        if (!empty($filtros['departamento_id'])) {
            $sqlBase .= ' AND f.departamento_id = :departamento_id';
            $params[':departamento_id'] = $filtros['departamento_id'];
        }
        if (!empty($filtros['status'])) {
            $sqlBase .= ' AND f.status = :status';
            $params[':status'] = $filtros['status'];
        }

        $stmtTotal = $this->db->prepare('SELECT COUNT(*) ' . $sqlBase);
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $offset = max(0, ($pagina - 1) * $porPagina);
        $stmt = $this->db->prepare('SELECT f.*, c.titulo AS cargo_titulo, d.nome AS departamento_nome ' . $sqlBase . ' ORDER BY f.nome ASC LIMIT :limite OFFSET :offset');
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['registros' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, c.titulo AS cargo_titulo, d.nome AS departamento_nome
             FROM funcionarios f
             JOIN cargos c ON c.id = f.cargo_id
             JOIN departamentos d ON d.id = f.departamento_id
             WHERE f.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO funcionarios
                (nome, cpf, rg, data_nascimento, genero, email, telefone, endereco,
                 data_admissao, cargo_id, departamento_id, salario, status, foto)
             VALUES
                (:nome, :cpf, :rg, :data_nascimento, :genero, :email, :telefone, :endereco,
                 :data_admissao, :cargo_id, :departamento_id, :salario, :status, :foto)'
        );
        $stmt->execute([
            ':nome'            => $dados['nome'],
            ':cpf'             => $dados['cpf'],
            ':rg'              => $dados['rg'] ?: null,
            ':data_nascimento' => $dados['data_nascimento'] ?: null,
            ':genero'          => $dados['genero'] ?: null,
            ':email'           => $dados['email'],
            ':telefone'        => $dados['telefone'] ?: null,
            ':endereco'        => $dados['endereco'] ?: null,
            ':data_admissao'   => $dados['data_admissao'],
            ':cargo_id'        => $dados['cargo_id'],
            ':departamento_id' => $dados['departamento_id'],
            ':salario'         => $dados['salario'],
            ':status'          => $dados['status'] ?? 'ativo',
            ':foto'            => $dados['foto'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = 'UPDATE funcionarios SET
                    nome = :nome, cpf = :cpf, rg = :rg, data_nascimento = :data_nascimento,
                    genero = :genero, email = :email, telefone = :telefone, endereco = :endereco,
                    data_admissao = :data_admissao, cargo_id = :cargo_id,
                    departamento_id = :departamento_id, salario = :salario, status = :status';
        $params = [
            ':id'              => $id,
            ':nome'            => $dados['nome'],
            ':cpf'             => $dados['cpf'],
            ':rg'              => $dados['rg'] ?: null,
            ':data_nascimento' => $dados['data_nascimento'] ?: null,
            ':genero'          => $dados['genero'] ?: null,
            ':email'           => $dados['email'],
            ':telefone'        => $dados['telefone'] ?: null,
            ':endereco'        => $dados['endereco'] ?: null,
            ':data_admissao'   => $dados['data_admissao'],
            ':cargo_id'        => $dados['cargo_id'],
            ':departamento_id' => $dados['departamento_id'],
            ':salario'         => $dados['salario'],
            ':status'          => $dados['status'],
        ];

        if (!empty($dados['foto'])) {
            $sql .= ', foto = :foto';
            $params[':foto'] = $dados['foto'];
        }

        $sql .= ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Desligamento: marca como inativo e grava a data de demissão (soft delete lógico). */
    public function desligar(int $id, string $dataDemissao): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE funcionarios SET status = 'inativo', data_demissao = :data WHERE id = :id"
        );
        return $stmt->execute([':id' => $id, ':data' => $dataDemissao]);
    }

    public function contarPorStatus(string $status = 'ativo'): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM funcionarios WHERE status = :status');
        $stmt->execute([':status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    /** Funcionários cujo aniversário cai nos próximos $dias dias (para notificações). */
    public function aniversariantesProximos(int $dias = 7): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nome, email, data_nascimento FROM funcionarios
             WHERE status = 'ativo' AND DATE_FORMAT(data_nascimento, '%m-%d')
                   BETWEEN DATE_FORMAT(CURDATE(), '%m-%d')
                   AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL :dias DAY), '%m-%d')"
        );
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll();
    }

    public function existeCpfOuEmail(string $cpf, string $email, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM funcionarios WHERE (cpf = :cpf OR email = :email)';
        $params = [':cpf' => $cpf, ':email' => $email];
        if ($ignorarId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignorarId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ============================================================
    // MÉTODOS PARA DASHBOARD
    // ============================================================

    /**
     * Conta o total de funcionários (todos os status)
     */
    public function contarTotal(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM funcionarios");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta funcionários por status específico (com alias para compatibilidade)
     */
    public function contarPorStatusTodos(): array
    {
        $stmt = $this->db->query("SELECT status, COUNT(*) as total FROM funcionarios GROUP BY status");
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $status = [];
        foreach ($resultados as $row) {
            $status[$row['status']] = (int) $row['total'];
        }
        return $status;
    }

    /**
     * Conta contratações de um mês específico
     */
    public function contarContratacoesMes(string $mes): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM funcionarios 
            WHERE DATE_FORMAT(data_admissao, '%Y-%m') = :mes 
            AND status = 'ativo'
        ");
        $stmt->execute([':mes' => $mes]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Conta demissões de um mês específico
     */
    public function contarDemissoesMes(string $mes): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM funcionarios 
            WHERE DATE_FORMAT(data_demissao, '%Y-%m') = :mes 
            AND status = 'inativo'
        ");
        $stmt->execute([':mes' => $mes]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtém evolução mensal de funcionários
     */
    public function obterEvolucaoMensal(int $meses = 12): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(data_admissao, '%Y-%m') as mes,
                COUNT(*) as total,
                (SELECT COUNT(*) FROM funcionarios WHERE data_admissao <= CONCAT(DATE_FORMAT(data_admissao, '%Y-%m-01'), ' 23:59:59')) as acumulado
            FROM funcionarios
            WHERE data_admissao >= DATE_SUB(CURDATE(), INTERVAL :meses MONTH)
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->execute([':meses' => $meses]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'meses' => array_column($resultados, 'mes'),
            'totais' => array_column($resultados, 'acumulado')
        ];
    }

    /**
     * Obtém contratações por mês
     */
    public function obterContratacoesPorMes(int $meses = 12): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(data_admissao, '%Y-%m') as mes,
                COUNT(*) as total
            FROM funcionarios
            WHERE data_admissao >= DATE_SUB(CURDATE(), INTERVAL :meses MONTH)
            AND status = 'ativo'
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->execute([':meses' => $meses]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'meses' => array_column($resultados, 'mes'),
            'totais' => array_column($resultados, 'total')
        ];
    }

    /**
     * Obtém desligamentos por mês
     */
    public function obterDesligamentosPorMes(int $meses = 12): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(data_demissao, '%Y-%m') as mes,
                COUNT(*) as total
            FROM funcionarios
            WHERE data_demissao IS NOT NULL 
            AND data_demissao >= DATE_SUB(CURDATE(), INTERVAL :meses MONTH)
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->execute([':meses' => $meses]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'meses' => array_column($resultados, 'mes'),
            'totais' => array_column($resultados, 'total')
        ];
    }

    /**
     * Obtém atividades recentes (da tabela log_auditoria)
     */
    public function obterAtividadesRecentes(int $limite = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    l.acao,
                    l.detalhes,
                    l.criado_em,
                    u.nome as usuario
                FROM log_auditoria l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                ORDER BY l.criado_em DESC
                LIMIT :limite
            ");
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Obtém aniversariantes do mês
     */
    public function obterAniversariantesMes(string $mes): array
    {
        $stmt = $this->db->prepare("
            SELECT nome, data_nascimento 
            FROM funcionarios
            WHERE status = 'ativo' 
            AND data_nascimento IS NOT NULL
            AND MONTH(data_nascimento) = MONTH(:mes)
            ORDER BY DAY(data_nascimento)
        ");
        $stmt->execute([':mes' => $mes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém funcionários recém contratados
     */
    public function obterRecentes(int $dias = 30, int $limite = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT nome, data_admissao 
            FROM funcionarios
            WHERE data_admissao >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
            AND status = 'ativo'
            ORDER BY data_admissao DESC 
            LIMIT :limite
        ");
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém funcionário por ID (alias para buscarPorId)
     */
    public function obterPorId(int $id): ?array
    {
        return $this->buscarPorId($id);
    }

    /**
     * Conta total de usuários (para dashboard)
     */
    public function contarTotalUsuarios(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta usuários ativos (para dashboard)
     */
    public function contarUsuariosAtivos(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta total de candidatos (para dashboard)
     */
    public function contarTotalCandidatos(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM candidatos");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta candidatos em processo (para dashboard)
     */
    public function contarCandidatosProcesso(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM candidatos WHERE status = 'em_processo'");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Lista departamentos com total de funcionários
     */
    public function getDepartamentosComTotal(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT d.nome, COUNT(f.id) as total 
                FROM departamentos d 
                LEFT JOIN funcionarios f ON f.departamento_id = d.id AND f.status = 'ativo' 
                WHERE d.ativo = 1 
                GROUP BY d.id, d.nome 
                ORDER BY total DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Lista cargos com total de funcionários
     */
    public function getCargosComTotal(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT c.titulo as nome, COUNT(f.id) as total 
                FROM cargos c 
                LEFT JOIN funcionarios f ON f.cargo_id = c.id AND f.status = 'ativo' 
                WHERE c.ativo = 1 
                GROUP BY c.id, c.titulo 
                ORDER BY total DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Lista funcionários por departamento
     */
    public function listarPorDepartamento(int $departamentoId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    f.*,
                    c.titulo as cargo
                FROM funcionarios f
                LEFT JOIN cargos c ON f.cargo_id = c.id
                WHERE f.departamento_id = :departamento_id 
                AND f.status IN ('ativo', 'ferias')
                ORDER BY f.nome
            ");
            $stmt->execute([':departamento_id' => $departamentoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Conta ativos por departamento
     */
    public function contarAtivosPorDepartamento(int $departamentoId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM funcionarios 
                WHERE departamento_id = :departamento_id AND status = 'ativo'
            ");
            $stmt->execute([':departamento_id' => $departamentoId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta funcionários em férias por departamento
     */
    public function contarFeriasPorDepartamento(int $departamentoId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM funcionarios 
                WHERE departamento_id = :departamento_id AND status = 'ferias'
            ");
            $stmt->execute([':departamento_id' => $departamentoId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Obtém aniversariantes por departamento
     */
    public function obterAniversariantesPorDepartamento(int $departamentoId, string $mes): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT nome, data_nascimento 
                FROM funcionarios
                WHERE departamento_id = :departamento_id 
                AND status = 'ativo' 
                AND data_nascimento IS NOT NULL
                AND MONTH(data_nascimento) = MONTH(:mes)
                ORDER BY DAY(data_nascimento)
            ");
            $stmt->execute([
                ':departamento_id' => $departamentoId,
                ':mes' => $mes
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Obtém distribuição de gênero
     */
    public function distribuicaoGenero(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    CASE 
                        WHEN genero = 'M' OR genero = 'Masculino' THEN 'Masculino'
                        WHEN genero = 'F' OR genero = 'Feminino' THEN 'Feminino'
                        ELSE 'Não informado'
                    END as genero,
                    COUNT(*) as total
                FROM funcionarios
                WHERE status = 'ativo'
                GROUP BY genero
            ");
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $distribuicao = [];
            foreach ($resultados as $row) {
                $distribuicao[$row['genero']] = (int) $row['total'];
            }
            return $distribuicao;
        } catch (PDOException $e) {
            return ['Masculino' => 0, 'Feminino' => 0, 'Não informado' => 0];
        }
    }

    /**
     * Obtém distribuição etária
     */
    public function distribuicaoEtaria(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 25 THEN '18-25'
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 25 AND 35 THEN '26-35'
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 46 AND 55 THEN '46-55'
                        ELSE '56+'
                    END as faixa,
                    COUNT(*) as total
                FROM funcionarios
                WHERE status = 'ativo' AND data_nascimento IS NOT NULL
                GROUP BY faixa
                ORDER BY 
                    CASE 
                        WHEN faixa = '18-25' THEN 1
                        WHEN faixa = '26-35' THEN 2
                        WHEN faixa = '36-45' THEN 3
                        WHEN faixa = '46-55' THEN 4
                        ELSE 5
                    END
            ");
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $distribuicao = [];
            foreach ($resultados as $row) {
                $distribuicao[$row['faixa']] = (int) $row['total'];
            }
            return $distribuicao;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Conta contratos próximos do vencimento
     */
    public function contratosVencendo(int $dias = 30): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM funcionarios 
                WHERE status = 'ativo' 
                AND data_fim_contrato IS NOT NULL
                AND data_fim_contrato BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
            ");
            $stmt->execute([':dias' => $dias]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta funcionários com muitas faltas
     */
    public function funcionariosComMuitasFaltas(int $limite = 5): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT f.id) 
                FROM funcionarios f
                JOIN presencas p ON p.funcionario_id = f.id
                WHERE f.status = 'ativo' 
                AND MONTH(p.data) = MONTH(CURDATE())
                AND YEAR(p.data) = YEAR(CURDATE())
                AND p.status = 'falta'
                GROUP BY f.id
                HAVING COUNT(*) >= :limite
            ");
            $stmt->execute([':limite' => $limite]);
            return (int) $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta funcionários sem avaliação no último ano
     */
    public function contarSemAvaliacao(): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM funcionarios f 
                WHERE f.status = 'ativo' 
                AND NOT EXISTS (
                    SELECT 1 FROM avaliacoes_desempenho a 
                    WHERE a.funcionario_id = f.id 
                    AND YEAR(a.data_avaliacao) = YEAR(CURDATE())
                )
            ");
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Conta documentos pendentes
     */
    public function contarDocumentosPendentes(): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM funcionarios f 
                WHERE f.status = 'ativo' 
                AND NOT EXISTS (
                    SELECT 1 FROM documentos d 
                    WHERE d.funcionario_id = f.id AND d.tipo = 'contrato'
                )
            ");
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Obtém notificações de um funcionário
     */
    public function obterNotificacoes(int $funcionarioId, int $limite = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id FROM usuarios u 
                WHERE u.funcionario_id = :funcionario_id
            ");
            $stmt->execute([':funcionario_id' => $funcionarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT * FROM notificacoes 
                WHERE usuario_id = :usuario_id 
                ORDER BY criado_em DESC 
                LIMIT :limite
            ");
            $stmt->bindValue(':usuario_id', $usuario['id'], PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}