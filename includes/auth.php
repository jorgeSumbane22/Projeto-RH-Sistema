<?php
/**
 * auth.php
 * Funções de autenticação e autorização (RBAC) usadas em todas as páginas
 * protegidas do sistema. Depende de config/config.php já ter sido incluído
 * (para a sessão estar iniciada).
 */

/**
 * Retorna true se houver um usuário autenticado na sessão atual.
 */
function estaLogado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Bloqueia o acesso a quem não estiver logado, redirecionando para o login.
 */
function exigirLogin(): void
{
    if (!estaLogado()) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
}

/**
 * Bloqueia o acesso caso o perfil do usuário logado não esteja na lista
 * de perfis permitidos. Deve ser chamada DEPOIS de exigirLogin().
 *
 * Exemplo: exigirPerfil([PERFIL_ADMIN, PERFIL_RH]);
 */
function exigirPerfil(array $perfisPermitidos): void
{
    exigirLogin();

    $perfilAtual = usuarioAtualPerfil();
    $autorizado = $perfilAtual === PERFIL_SUPER_ADMIN
        || in_array($perfilAtual, $perfisPermitidos, true);

    if (!$autorizado) {
        http_response_code(403);
        require __DIR__ . '/../modules/auth/acesso_negado.php';
        exit;
    }
}

/**
 * Atalho para checar perfil sem interromper a execução (útil para
 * exibir/ocultar elementos na interface, ex.: itens de menu).
 */
function usuarioTemPerfil(array $perfis): bool
{
    if (!estaLogado()) {
        return false;
    }
    return usuarioAtualPerfil() === PERFIL_SUPER_ADMIN
        || in_array(usuarioAtualPerfil(), $perfis, true);
}

function usuarioPodeGerirUtilizadores(): bool
{
    return usuarioTemPerfil([PERFIL_ADMIN]);
}

function nomePerfil(string $perfil): string
{
    return NOMES_PERFIS[$perfil] ?? ucfirst(str_replace('_', ' ', $perfil));
}

function usuarioAtualId(): ?int
{
    return $_SESSION['usuario_id'] ?? null;
}

function usuarioAtualNome(): string
{
    return $_SESSION['usuario_nome'] ?? '';
}

function usuarioAtualPerfil(): string
{
    return $_SESSION['usuario_perfil'] ?? '';
}

function usuarioAtualFuncionarioId(): ?int
{
    return $_SESSION['usuario_funcionario_id'] ?? null;
}

/**
 * Gera (ou reaproveita) um token CSRF armazenado na sessão e o retorna.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado em um formulário POST. Encerra a execução
 * com erro 400 se o token for inválido ou ausente.
 */
function validarCsrf(): void
{
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tokenEnviado)) {
        http_response_code(400);
        die('Token de segurança inválido. Recarregue a página e tente novamente.');
    }
}

/**
 * Registra uma ação no log de auditoria (tabela log_auditoria).
 */
function registrarAuditoria(string $acao, ?string $detalhes = null): void
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO log_auditoria (usuario_id, acao, detalhes, ip) VALUES (:uid, :acao, :detalhes, :ip)'
        );
        $stmt->execute([
            ':uid'      => usuarioAtualId(),
            ':acao'     => $acao,
            ':detalhes' => $detalhes,
            ':ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('Falha ao registrar auditoria: ' . $e->getMessage());
    }
}
