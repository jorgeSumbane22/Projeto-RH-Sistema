<?php
/**
 * Usuario.php
 * Model responsável pela autenticação e gestão de contas de acesso.
 */

class Usuario
{
    private PDO $db;
    private const MAX_TENTATIVAS = 5;
    private const BLOQUEIO_MINUTOS = 15;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Retorna true se a conta estiver temporariamente bloqueada por excesso de
     * tentativas de login incorretas. Usado ANTES de checar a senha.
     */
    public function estaBloqueado(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT bloqueado_ate FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $bloqueadoAte = $stmt->fetchColumn();
        return $bloqueadoAte && strtotime($bloqueadoAte) > time();
    }

    /** Minutos restantes de bloqueio (para exibir na mensagem de erro). */
    public function minutosRestantesBloqueio(string $email): int
    {
        $stmt = $this->db->prepare('SELECT bloqueado_ate FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $bloqueadoAte = $stmt->fetchColumn();
        if (!$bloqueadoAte) {
            return 0;
        }
        return max(0, (int) ceil((strtotime($bloqueadoAte) - time()) / 60));
    }

    /** Registra uma tentativa de login incorreta; bloqueia a conta ao atingir o limite. */
    public function registrarTentativaFalha(string $email): void
    {
        $stmt = $this->db->prepare('SELECT id, tentativas_login FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();
        if (!$usuario) {
            return; // não revela se o e-mail existe ou não
        }

        $tentativas = (int) $usuario['tentativas_login'] + 1;
        $bloqueadoAte = null;
        if ($tentativas >= self::MAX_TENTATIVAS) {
            $bloqueadoAte = date('Y-m-d H:i:s', strtotime('+' . self::BLOQUEIO_MINUTOS . ' minutes'));
            $tentativas = 0; // zera o contador ao aplicar o bloqueio
        }

        $upd = $this->db->prepare('UPDATE usuarios SET tentativas_login = :tent, bloqueado_ate = :bloq WHERE id = :id');
        $upd->execute([':tent' => $tentativas, ':bloq' => $bloqueadoAte, ':id' => $usuario['id']]);
    }

    /** Zera o contador de tentativas após um login bem-sucedido. */
    public function resetarTentativas(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /** Retorna os dados do usuário se e-mail/senha forem válidos e a conta estiver ativa. */
    public function autenticar(string $email, string $senha): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email AND ativo = 1');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            return $usuario;
        }
        return null;
    }

    public function atualizarUltimoLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function listar(): array
    {
        $sql = 'SELECT u.*, f.nome AS funcionario_nome FROM usuarios u
                LEFT JOIN funcionarios f ON f.id = u.funcionario_id ORDER BY u.nome ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, perfil, funcionario_id, ativo)
             VALUES (:nome, :email, :senha, :perfil, :funcionario_id, :ativo)'
        );
        $stmt->execute([
            ':nome'           => $dados['nome'],
            ':email'          => $dados['email'],
            ':senha'          => password_hash($dados['senha'], PASSWORD_BCRYPT),
            ':perfil'         => $dados['perfil'],
            ':funcionario_id' => $dados['funcionario_id'] ?: null,
            ':ativo'          => $dados['ativo'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = 'UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil,
                funcionario_id = :funcionario_id, ativo = :ativo';
        $params = [
            ':id'             => $id,
            ':nome'           => $dados['nome'],
            ':email'          => $dados['email'],
            ':perfil'         => $dados['perfil'],
            ':funcionario_id' => $dados['funcionario_id'] ?: null,
            ':ativo'          => $dados['ativo'] ?? 1,
        ];

        if (!empty($dados['senha'])) {
            $sql .= ', senha_hash = :senha';
            $params[':senha'] = password_hash($dados['senha'], PASSWORD_BCRYPT);
        }

        $sql .= ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /** Usado pela tela "Meu Perfil": qualquer usuário pode trocar o próprio nome/senha. */
    public function atualizarProprioPerfil(int $id, string $nome, ?string $novaSenha): bool
    {
        $sql = 'UPDATE usuarios SET nome = :nome';
        $params = [':id' => $id, ':nome' => $nome];

        if (!empty($novaSenha)) {
            $sql .= ', senha_hash = :senha';
            $params[':senha'] = password_hash($novaSenha, PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Redefine a senha diretamente (usado no fluxo de "esqueci minha senha", após validar o token). */
    public function redefinirSenha(int $id, string $novaSenha): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET senha_hash = :senha, tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id');
        return $stmt->execute([':senha' => password_hash($novaSenha, PASSWORD_BCRYPT), ':id' => $id]);
    }

    public function atualizarTema(int $id, string $tema): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET tema_preferido = :tema WHERE id = :id');
        return $stmt->execute([':tema' => $tema, ':id' => $id]);
    }

    public function existeEmail(string $email, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $params = [':email' => $email];
        if ($ignorarId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignorarId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
