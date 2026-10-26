<?php
/**
 * Notificacao.php - Model para notificações do sistema
 * Gerencia férias, aniversários, contratos, alertas e eventos diversos.
 */

class Notificacao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ============================================================
    // MÉTODOS DE LISTAGEM
    // ============================================================

    public function listarPorUsuario(int $usuarioId, int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notificacoes WHERE usuario_id = :uid ORDER BY criado_em DESC LIMIT :limite'
        );
        $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarPaginado(int $usuarioId, int $porPagina, int $pagina): array
    {
        $stmtTotal = $this->db->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid');
        $stmtTotal->execute([':uid' => $usuarioId]);
        $total = (int) $stmtTotal->fetchColumn();

        $offset = max(0, ($pagina - 1) * $porPagina);
        $stmt = $this->db->prepare(
            'SELECT * FROM notificacoes WHERE usuario_id = :uid ORDER BY criado_em DESC LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['registros' => $stmt->fetchAll(), 'total' => $total];
    }

    public function listarNaoLidas(int $usuarioId, int $limite = 10, int $pagina = 1): array
    {
        $offset = ($pagina - 1) * $limite;
        $stmt = $this->db->prepare(
            'SELECT * FROM notificacoes 
             WHERE usuario_id = :uid AND lida = 0 
             ORDER BY criado_em DESC 
             LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ============================================================
    // MÉTODOS DE CONTAGEM
    // ============================================================

    public function contarNaoLidas(int $usuarioId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid AND lida = 0');
        $stmt->execute([':uid' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function contarPorUsuario(int $usuarioId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid');
        $stmt->execute([':uid' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function jaGerouHoje(): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notificacoes WHERE tipo IN ('aniversario', 'ferias') AND DATE(criado_em) = CURDATE()"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    // ============================================================
    // MÉTODOS DE MARCAÇÃO
    // ============================================================

    public function marcarComoLida(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare('UPDATE notificacoes SET lida = 1 WHERE id = :id AND usuario_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
    }

    public function marcarTodasComoLidas(int $usuarioId): bool
    {
        $stmt = $this->db->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = :uid');
        return $stmt->execute([':uid' => $usuarioId]);
    }

    // ============================================================
    // MÉTODOS DE CRIAÇÃO
    // ============================================================

    public function criar(int $usuarioId, string $tipo, string $titulo, string $mensagem): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem) VALUES (:uid, :tipo, :titulo, :msg)'
        );
        $stmt->execute([
            ':uid'   => $usuarioId,
            ':tipo'  => $tipo,
            ':titulo' => $titulo,
            ':msg'   => $mensagem
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function criarEmMassa(array $usuariosIds, string $tipo, string $titulo, string $mensagem): int
    {
        $count = 0;
        foreach ($usuariosIds as $usuarioId) {
            $this->criar((int) $usuarioId, $tipo, $titulo, $mensagem);
            $count++;
        }
        return $count;
    }

    // ============================================================
    // MÉTODOS DE EXCLUSÃO
    // ============================================================

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM notificacoes WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function excluirTodas(int $usuarioId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM notificacoes WHERE usuario_id = :uid');
        return $stmt->execute([':uid' => $usuarioId]);
    }

    public function excluirAntigas(int $dias = 90): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notificacoes WHERE criado_em < DATE_SUB(NOW(), INTERVAL :dias DAY)'
        );
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ============================================================
    // MÉTODOS DE BUSCA
    // ============================================================

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM notificacoes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function buscarPorTipo(int $usuarioId, string $tipo, int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notificacoes WHERE usuario_id = :uid AND tipo = :tipo ORDER BY criado_em DESC LIMIT :limite'
        );
        $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ============================================================
    // MÉTODOS DE ESTATÍSTICAS
    // ============================================================

    public function getEstatisticas(int $usuarioId): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN lida = 0 THEN 1 ELSE 0 END) as nao_lidas,
                COUNT(DISTINCT tipo) as tipos,
                MAX(criado_em) as ultima
             FROM notificacoes WHERE usuario_id = :uid"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetch();
    }

    public function contarPorTipo(int $usuarioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT tipo, COUNT(*) as total FROM notificacoes WHERE usuario_id = :uid GROUP BY tipo'
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    // ============================================================
    // GERAÇÃO AUTOMÁTICA DE NOTIFICAÇÕES
    // ============================================================

    /**
     * Gera notificações automáticas (aniversários, férias próximas, etc.)
     * @return array Estatísticas das notificações geradas
     */
    public function gerarNotificacoesAutomaticas(): array
    {
        $funcionarioModel = new Funcionario();
        $feriasModel = new Ferias();

        $resultado = [
            'geradas' => 0,
            'alertas' => 0,
            'detalhes' => []
        ];

        // Evita duplicar notificações no mesmo dia
        if ($this->jaGerouHoje()) {
            $resultado['detalhes'][] = 'Já gerou notificações hoje.';
            return $resultado;
        }

        // Destinatários: Super Admin e Admin RH
        $stmtRH = $this->db->query(
            "SELECT id FROM usuarios WHERE perfil IN ('super_admin', 'administrador_rh') AND ativo = 1"
        );
        $destinatarios = $stmtRH->fetchAll(PDO::FETCH_COLUMN);

        if (empty($destinatarios)) {
            $resultado['detalhes'][] = 'Nenhum destinatário encontrado.';
            return $resultado;
        }

        // 1. Aniversariantes próximos (7 dias)
        try {
            $aniversariantes = $funcionarioModel->aniversariantesProximos(7);
            foreach ($aniversariantes as $aniversariante) {
                $dias = $aniversariante['dias_para'] ?? 0;
                $msg = $aniversariante['nome'] . ' fará aniversário em ' . $dias . ' dia(s).';
                foreach ($destinatarios as $usuarioId) {
                    $this->criar(
                        $usuarioId,
                        'aniversario',
                        '🎂 Aniversário próximo',
                        $msg
                    );
                    $resultado['geradas']++;
                }
                $resultado['detalhes'][] = "Aniversário: {$aniversariante['nome']}";
            }
        } catch (Exception $e) {
            $resultado['detalhes'][] = 'Erro ao buscar aniversariantes: ' . $e->getMessage();
        }

        // 2. Férias próximas (7 dias)
        try {
            $feriasProximas = $feriasModel->proximasNoPeriodo(7);
            foreach ($feriasProximas as $ferias) {
                $inicio = date('d/m/Y', strtotime($ferias['data_inicio']));
                $msg = $ferias['funcionario_nome'] . ' inicia ' . $ferias['tipo'] . ' em ' . $inicio . '.';
                foreach ($destinatarios as $usuarioId) {
                    $this->criar(
                        $usuarioId,
                        'ferias',
                        '🏖️ ' . ucfirst(str_replace('_', ' ', $ferias['tipo'])) . ' se aproxima',
                        $msg
                    );
                    $resultado['geradas']++;
                }
                $resultado['detalhes'][] = "Férias: {$ferias['funcionario_nome']}";
            }
        } catch (Exception $e) {
            $resultado['detalhes'][] = 'Erro ao buscar férias: ' . $e->getMessage();
        }

        // 3. Documentos pendentes (USANDO CONSULTA DIRETA)
        try {
            $stmtDocs = $this->db->query(
                "SELECT COUNT(*) FROM funcionarios f 
                 WHERE f.status = 'ativo' 
                 AND NOT EXISTS (
                     SELECT 1 FROM documentos d 
                     WHERE d.funcionario_id = f.id
                 )"
            );
            $docsPendentes = (int) $stmtDocs->fetchColumn();
            
            if ($docsPendentes > 0) {
                foreach ($destinatarios as $usuarioId) {
                    $this->criar(
                        $usuarioId,
                        'documento',
                        '📄 Documentos pendentes',
                        $docsPendentes . ' funcionário(s) com documentos em falta.'
                    );
                    $resultado['geradas']++;
                    $resultado['alertas']++;
                }
                $resultado['detalhes'][] = "Documentos pendentes: $docsPendentes";
            }
        } catch (Exception $e) {
            $resultado['detalhes'][] = 'Erro ao verificar documentos: ' . $e->getMessage();
        }

        // 4. Avaliações pendentes (USANDO CONSULTA DIRETA)
        try {
            $stmtAval = $this->db->query(
                "SELECT COUNT(*) FROM funcionarios f 
                 WHERE f.status = 'ativo' 
                 AND NOT EXISTS (
                     SELECT 1 FROM avaliacoes_desempenho a 
                     WHERE a.funcionario_id = f.id 
                     AND YEAR(a.data_avaliacao) = YEAR(CURDATE())
                 )"
            );
            $avaliacoesPendentes = (int) $stmtAval->fetchColumn();
            
            if ($avaliacoesPendentes > 0) {
                foreach ($destinatarios as $usuarioId) {
                    $this->criar(
                        $usuarioId,
                        'avaliacao',
                        '⭐ Avaliações pendentes',
                        $avaliacoesPendentes . ' funcionário(s) sem avaliação anual.'
                    );
                    $resultado['geradas']++;
                    $resultado['alertas']++;
                }
                $resultado['detalhes'][] = "Avaliações pendentes: $avaliacoesPendentes";
            }
        } catch (Exception $e) {
            $resultado['detalhes'][] = 'Erro ao verificar avaliações: ' . $e->getMessage();
        }

        // 5. Contas bloqueadas
        try {
            $stmtBloqueados = $this->db->query(
                "SELECT COUNT(*) FROM usuarios WHERE bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()"
            );
            $contasBloqueadas = (int) $stmtBloqueados->fetchColumn();
            
            if ($contasBloqueadas > 0) {
                foreach ($destinatarios as $usuarioId) {
                    $this->criar(
                        $usuarioId,
                        'seguranca',
                        '🔒 Contas bloqueadas',
                        $contasBloqueadas . ' conta(s) bloqueadas no sistema.'
                    );
                    $resultado['geradas']++;
                    $resultado['alertas']++;
                }
                $resultado['detalhes'][] = "Contas bloqueadas: $contasBloqueadas";
            }
        } catch (Exception $e) {
            $resultado['detalhes'][] = 'Erro ao verificar contas bloqueadas: ' . $e->getMessage();
        }

        // 6. Vagas abertas há muito tempo
        try {
            $stmtVagasAntigas = $this->db->query(
                "SELECT COUNT(*) FROM vagas WHERE status = 'aberta' AND data_publicacao < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $vagasAntigas = (int) $stmtVagasAntigas->fetchColumn();
            
            if ($vagasAntigas > 0) {
                foreach ($destinatarios as $usuarioId) {
                    $this->criar(
                        $usuarioId,
                        'recrutamento',
                        '📋 Vagas antigas',
                        $vagasAntigas . ' vaga(s) abertas há mais de 30 dias.'
                    );
                    $resultado['geradas']++;
                    $resultado['alertas']++;
                }
                $resultado['detalhes'][] = "Vagas antigas: $vagasAntigas";
            }
        } catch (Exception $e) {
            $resultado['detalhes'][] = 'Erro ao verificar vagas: ' . $e->getMessage();
        }

        return $resultado;
    }

    // ============================================================
    // MÉTODOS DE UTILITÁRIOS
    // ============================================================

    public static function getIcon(string $tipo): string
    {
        $icons = [
            'aniversario' => 'bi-gift',
            'ferias' => 'bi-umbrella',
            'documento' => 'bi-file-earmark-text',
            'avaliacao' => 'bi-star',
            'seguranca' => 'bi-shield-check',
            'recrutamento' => 'bi-briefcase',
            'sistema' => 'bi-gear',
            'rh' => 'bi-people',
            'folha' => 'bi-cash-stack',
            'presenca' => 'bi-clock',
            'usuario' => 'bi-person',
        ];
        return $icons[$tipo] ?? 'bi-bell';
    }

    public static function getColor(string $tipo): string
    {
        $colors = [
            'aniversario' => 'text-pink',
            'ferias' => 'text-warning',
            'documento' => 'text-info',
            'avaliacao' => 'text-purple',
            'seguranca' => 'text-danger',
            'recrutamento' => 'text-primary',
            'sistema' => 'text-secondary',
            'rh' => 'text-primary',
            'folha' => 'text-success',
            'presenca' => 'text-info',
            'usuario' => 'text-secondary',
        ];
        return $colors[$tipo] ?? 'text-secondary';
    }

    public static function getBadge(string $tipo): string
    {
        $badges = [
            'aniversario' => 'bg-pink',
            'ferias' => 'bg-warning',
            'documento' => 'bg-info',
            'avaliacao' => 'bg-purple',
            'seguranca' => 'bg-danger',
            'recrutamento' => 'bg-primary',
            'sistema' => 'bg-secondary',
            'rh' => 'bg-primary',
            'folha' => 'bg-success',
            'presenca' => 'bg-info',
            'usuario' => 'bg-secondary',
        ];
        return $badges[$tipo] ?? 'bg-secondary';
    }
}