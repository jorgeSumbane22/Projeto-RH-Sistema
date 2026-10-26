<?php
// classes/FolhaPagamento.php

class FolhaPagamento
{
    private PDO $db;
    private const VALOR_HORA_EXTRA_MULTIPLICADOR = 1.5;
    private const HORAS_MES = 220;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ============================================================
    // MÉTODOS EXISTENTES
    // ============================================================

    public function listar(string $mesReferencia): array
    {
        $stmt = $this->db->prepare(
            'SELECT fp.*, f.nome AS funcionario_nome, d.nome AS departamento_nome
             FROM folha_pagamento fp
             JOIN funcionarios f ON f.id = fp.funcionario_id
             JOIN departamentos d ON d.id = f.departamento_id
             WHERE fp.mes_referencia = :mes ORDER BY f.nome ASC'
        );
        $stmt->execute([':mes' => $mesReferencia]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT fp.*, f.nome AS funcionario_nome, f.cargo_id, c.titulo AS cargo_titulo
             FROM folha_pagamento fp
             JOIN funcionarios f ON f.id = fp.funcionario_id
             JOIN cargos c ON c.id = f.cargo_id
             WHERE fp.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }
    
    /**
     * Buscar folha com todos os dados relacionados (departamento, cargo)
     */
    public function buscarPorIdCompleto(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT fp.*, 
                    f.nome AS funcionario_nome,
                    f.salario AS salario_funcionario,
                    d.nome AS departamento_nome,
                    c.titulo AS cargo_titulo
             FROM folha_pagamento fp
             JOIN funcionarios f ON f.id = fp.funcionario_id
             LEFT JOIN departamentos d ON d.id = f.departamento_id
             LEFT JOIN cargos c ON c.id = f.cargo_id
             WHERE fp.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    private function buscarPorFuncionarioEMes(int $funcionarioId, string $mesReferencia): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM folha_pagamento WHERE funcionario_id = :fid AND mes_referencia = :mes');
        $stmt->execute([':fid' => $funcionarioId, ':mes' => $mesReferencia]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function gerarFolhaMensal(string $mesReferencia): array
    {
        $funcionarioModel = new Funcionario();
        $presencaModel = new Presenca();
        $ativos = $funcionarioModel->listarAtivos();

        $gerados = 0;
        foreach ($ativos as $func) {
            $salarioBase = (float) $func['salario'];
            $valorHora = $salarioBase / self::HORAS_MES;

            $horasExtras = $presencaModel->somarHorasExtrasMes((int) $func['id'], $mesReferencia);
            $valorHorasExtras = round($horasExtras * $valorHora * self::VALOR_HORA_EXTRA_MULTIPLICADOR, 2);

            $faltas = $presencaModel->contarFaltasMes((int) $func['id'], $mesReferencia);
            $valorFaltas = round($faltas * ($valorHora * 8), 2);

            $existente = $this->buscarPorFuncionarioEMes((int) $func['id'], $mesReferencia);
            $bonus = $existente ? (float) $existente['bonus'] : 0.00;
            $descontos = $existente ? (float) $existente['descontos'] : 0.00;

            $liquido = round($salarioBase + $bonus + $valorHorasExtras - $descontos - $valorFaltas, 2);

            $stmt = $this->db->prepare(
                'INSERT INTO folha_pagamento
                    (funcionario_id, mes_referencia, salario_base, bonus, horas_extras_valor, descontos, faltas_desconto, salario_liquido)
                 VALUES (:fid, :mes, :base, :bonus, :extras, :descontos, :faltas, :liquido)
                 ON DUPLICATE KEY UPDATE
                    salario_base = :base_upd, horas_extras_valor = :extras_upd,
                    faltas_desconto = :faltas_upd, salario_liquido = :liquido_upd'
            );
            $stmt->execute([
                ':fid'        => $func['id'],
                ':mes'        => $mesReferencia,
                ':base'       => $salarioBase,
                ':bonus'      => $bonus,
                ':extras'     => $valorHorasExtras,
                ':descontos'  => $descontos,
                ':faltas'     => $valorFaltas,
                ':liquido'    => $liquido,
                ':base_upd'   => $salarioBase,
                ':extras_upd' => $valorHorasExtras,
                ':faltas_upd' => $valorFaltas,
                ':liquido_upd'=> $liquido,
            ]);
            $gerados++;
        }

        return ['gerados' => $gerados];
    }

    public function atualizarBonusDesconto(int $id, float $bonus, float $descontos): bool
    {
        $folha = $this->buscarPorId($id);
        if (!$folha) {
            return false;
        }
        $liquido = round(
            (float) $folha['salario_base'] + $bonus + (float) $folha['horas_extras_valor']
            - $descontos - (float) $folha['faltas_desconto'],
            2
        );

        $stmt = $this->db->prepare(
            'UPDATE folha_pagamento SET bonus = :bonus, descontos = :descontos, salario_liquido = :liquido WHERE id = :id'
        );
        return $stmt->execute([':bonus' => $bonus, ':descontos' => $descontos, ':liquido' => $liquido, ':id' => $id]);
    }

    public function marcarComoPago(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE folha_pagamento SET status = 'pago', data_pagamento = CURDATE() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function totalGastoMes(string $mesReferencia): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(salario_liquido),0) FROM folha_pagamento WHERE mes_referencia = :mes'
        );
        $stmt->execute([':mes' => $mesReferencia]);
        return (float) $stmt->fetchColumn();
    }

    public function historicoPorFuncionario(int $funcionarioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM folha_pagamento WHERE funcionario_id = :fid ORDER BY mes_referencia DESC'
        );
        $stmt->execute([':fid' => $funcionarioId]);
        return $stmt->fetchAll();
    }

    // ============================================================
    // NOVOS MÉTODOS PARA COMPATIBILIDADE
    // ============================================================

    /**
     * Listar com filtros (status e busca)
     */
    public function listarComFiltros(string $mes, string $status = 'todos', string $busca = ''): array
    {
        $sql = 'SELECT fp.*, 
                       f.nome AS funcionario_nome, 
                       d.nome AS departamento_nome
                FROM folha_pagamento fp
                JOIN funcionarios f ON f.id = fp.funcionario_id
                JOIN departamentos d ON d.id = f.departamento_id
                WHERE fp.mes_referencia = :mes';
        
        $params = [':mes' => $mes];
        
        if ($status !== 'todos') {
            $sql .= ' AND fp.status = :status';
            $params[':status'] = $status;
        }
        
        if (!empty($busca)) {
            $sql .= ' AND f.nome LIKE :busca';
            $params[':busca'] = "%$busca%";
        }
        
        $sql .= ' ORDER BY fp.status DESC, f.nome ASC';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Verificar se existe folha para um funcionário em um determinado mês
     */
    public function existeFolha(int $funcionarioId, string $mesReferencia): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM folha_pagamento WHERE funcionario_id = :fid AND mes_referencia = :mes'
        );
        $stmt->execute([':fid' => $funcionarioId, ':mes' => $mesReferencia]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Criar nova folha manualmente
     */
    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO folha_pagamento 
                (funcionario_id, mes_referencia, salario_base, horas_extras_valor, 
                 bonus, faltas_desconto, descontos, salario_liquido, status) 
             VALUES 
                (:funcionario_id, :mes_referencia, :salario_base, :horas_extras_valor, 
                 :bonus, :faltas_desconto, :descontos, :salario_liquido, :status)'
        );
        $stmt->execute([
            ':funcionario_id' => $dados['funcionario_id'],
            ':mes_referencia' => $dados['mes_referencia'],
            ':salario_base' => $dados['salario_base'],
            ':horas_extras_valor' => $dados['horas_extras_valor'] ?? 0,
            ':bonus' => $dados['bonus'] ?? 0,
            ':faltas_desconto' => $dados['faltas_desconto'] ?? 0,
            ':descontos' => $dados['descontos'] ?? 0,
            ':salario_liquido' => $dados['salario_liquido'],
            ':status' => $dados['status'] ?? 'pendente'
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualizar folha (completa)
     */
    public function atualizarFolha(int $id, array $dados): bool
    {
        $salarioLiquido = $dados['salario_base'] + ($dados['horas_extras_valor'] ?? 0) 
                         + ($dados['bonus'] ?? 0) - ($dados['faltas_desconto'] ?? 0) 
                         - ($dados['descontos'] ?? 0);
        
        $stmt = $this->db->prepare(
            'UPDATE folha_pagamento 
             SET salario_base = :salario_base,
                 horas_extras_valor = :horas_extras_valor,
                 bonus = :bonus,
                 faltas_desconto = :faltas_desconto,
                 descontos = :descontos,
                 salario_liquido = :salario_liquido
             WHERE id = :id'
        );
        return $stmt->execute([
            ':salario_base' => $dados['salario_base'],
            ':horas_extras_valor' => $dados['horas_extras_valor'] ?? 0,
            ':bonus' => $dados['bonus'] ?? 0,
            ':faltas_desconto' => $dados['faltas_desconto'] ?? 0,
            ':descontos' => $dados['descontos'] ?? 0,
            ':salario_liquido' => $salarioLiquido,
            ':id' => $id
        ]);
    }

    /**
     * Marcar como pago com dados adicionais
     */
    public function marcarComoPagoCompleto(int $id, string $dataPagamento, string $metodo, string $comprovante = ''): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE folha_pagamento 
             SET status = "pago", 
                 data_pagamento = :data_pagamento,
                 metodo_pagamento = :metodo,
                 comprovante = :comprovante
             WHERE id = :id'
        );
        return $stmt->execute([
            ':data_pagamento' => $dataPagamento,
            ':metodo' => $metodo,
            ':comprovante' => $comprovante,
            ':id' => $id
        ]);
    }

    /**
     * Obter estatísticas do mês
     */
    public function getStats(string $mesReferencia): array
    {
        $stmt = $this->db->prepare(
            'SELECT 
                COUNT(*) as total_funcionarios,
                COALESCE(SUM(salario_liquido), 0) as total_liquido,
                COALESCE(SUM(CASE WHEN status = "pago" THEN 1 ELSE 0 END), 0) as total_pagos,
                COALESCE(SUM(CASE WHEN status = "pendente" THEN 1 ELSE 0 END), 0) as total_pendentes
             FROM folha_pagamento 
             WHERE mes_referencia = :mes'
        );
        $stmt->execute([':mes' => $mesReferencia]);
        return $stmt->fetch();
    }
}