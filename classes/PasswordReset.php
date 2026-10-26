<?php
/**
 * PasswordReset.php
 * Model responsável pelo fluxo de "esqueci minha senha": geração, validação
 * e consumo de tokens de redefinição.
 *
 * IMPORTANTE (ver README): este projeto não tem servidor de e-mail configurado
 * (é comum não haver SMTP disponível em XAMPP local). Por isso, em ambiente de
 * desenvolvimento (APP_ENV = 'development'), o link de redefinição é exibido
 * diretamente na tela em vez de enviado por e-mail. Em produção, troque isso
 * por um envio real via PHPMailer/SMTP.
 */

class PasswordReset
{
    private PDO $db;
    private const VALIDADE_MINUTOS = 60;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Gera um novo token de redefinição para o usuário e retorna o token em texto puro. */
    public function gerarToken(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', strtotime('+' . self::VALIDADE_MINUTOS . ' minutes'));

        $stmt = $this->db->prepare(
            'INSERT INTO senha_reset_tokens (usuario_id, token, expira_em) VALUES (:uid, :token, :expira)'
        );
        $stmt->execute([':uid' => $usuarioId, ':token' => $token, ':expira' => $expiraEm]);

        return $token;
    }

    /** Retorna os dados do token se ele existir, não tiver sido usado e ainda estiver válido. */
    public function validarToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT rt.*, u.email, u.nome FROM senha_reset_tokens rt
             JOIN usuarios u ON u.id = rt.usuario_id
             WHERE rt.token = :token AND rt.usado = 0 AND rt.expira_em > NOW()'
        );
        $stmt->execute([':token' => $token]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function marcarComoUsado(int $tokenId): void
    {
        $stmt = $this->db->prepare('UPDATE senha_reset_tokens SET usado = 1 WHERE id = :id');
        $stmt->execute([':id' => $tokenId]);
    }

    /** Invalida tokens antigos ainda ativos do usuário (evita múltiplos links válidos simultâneos). */
    public function invalidarTokensAnteriores(int $usuarioId): void
    {
        $stmt = $this->db->prepare('UPDATE senha_reset_tokens SET usado = 1 WHERE usuario_id = :uid AND usado = 0');
        $stmt->execute([':uid' => $usuarioId]);
    }
}
