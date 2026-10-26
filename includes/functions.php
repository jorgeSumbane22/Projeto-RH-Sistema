<?php
/**
 * functions.php
 * Funções utilitárias reaproveitadas em várias páginas/módulos.
 */

/** Escapa texto para saída segura em HTML (evita XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formata um valor numérico como moeda brasileira (R$). */
function formatarMoeda(?float $valor): string
{
    return 'R$ ' . number_format($valor ?? 0, 2, ',', '.');
}

/**
 * Formata um valor numérico como moeda Metical (MT)
 * Usado para a folha de pagamento em Moçambique
 */
function formatarMoedaMT(?float $valor): string
{
    return 'MT ' . number_format($valor ?? 0, 2, ',', '.');
}

/**
 * Formata um valor numérico como moeda genérica
 * @param float|null $valor O valor a ser formatado
 * @param string $simbolo O símbolo da moeda (padrão: MT)
 */
function formatarMoedaPersonalizada(?float $valor, string $simbolo = 'MT'): string
{
    return $simbolo . ' ' . number_format($valor ?? 0, 2, ',', '.');
}

/** Converte data do formato Y-m-d (banco) para d/m/Y (exibição). */
function formatarData(?string $data): string
{
    if (empty($data)) {
        return '-';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : '-';
}

/** Converte data e hora do formato do banco para exibição d/m/Y H:i. */
function formatarDataHora(?string $dataHora): string
{
    if (empty($dataHora)) {
        return '-';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dataHora);
    return $dt ? $dt->format('d/m/Y H:i') : '-';
}

/** Calcula a idade a partir de uma data de nascimento. */
function calcularIdade(?string $dataNascimento): ?int
{
    if (empty($dataNascimento)) {
        return null;
    }
    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    return $hoje->diff($nascimento)->y;
}

/**
 * Grava uma mensagem "flash" (exibida uma única vez após redirecionamento).
 * Tipo pode ser: success | danger | warning | info (classes do Bootstrap).
 */
function definirMensagem(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Exibe (e remove) a mensagem flash armazenada na sessão, se houver. */
function exibirMensagem(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert alert-' . e($msg['tipo']) . ' alert-dismissible fade show" role="alert">'
        . e($msg['texto'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>'
        . '</div>';
}

/**
 * Faz o upload seguro de um arquivo para o diretório informado.
 * Retorna o novo nome do arquivo em caso de sucesso, ou null em caso de erro
 * (o motivo do erro é colocado em $erro por referência).
 */
function fazerUploadArquivo(array $arquivo, string $diretorioDestino, ?string &$erro = null): ?string
{
    $erro = null;

    if (!isset($arquivo['error'], $arquivo['tmp_name'], $arquivo['name'], $arquivo['size'])
        || $arquivo['error'] !== UPLOAD_ERR_OK
        || !is_uploaded_file($arquivo['tmp_name'])) {
        $erro = 'Falha no envio do arquivo.';
        return null;
    }

    if ($arquivo['size'] <= 0 || $arquivo['size'] > LIMITE_UPLOAD_MB * 1024 * 1024) {
        $erro = 'Arquivo vazio ou superior ao limite de ' . LIMITE_UPLOAD_MB . 'MB.';
        return null;
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, EXTENSOES_PERMITIDAS, true)) {
        $erro = 'Extensão de arquivo não permitida.';
        return null;
    }

    $mimePermitidos = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($arquivo['tmp_name']);
    if (!in_array($mime, $mimePermitidos[$extensao] ?? [], true)) {
        $erro = 'O conteúdo do arquivo não corresponde ao tipo informado.';
        return null;
    }

    $diretorioDestino = rtrim($diretorioDestino, DIRECTORY_SEPARATOR);
    if (!is_dir($diretorioDestino) && !mkdir($diretorioDestino, 0750, true)) {
        $erro = 'Não foi possível preparar o diretório de armazenamento.';
        return null;
    }
    if (!is_writable($diretorioDestino)) {
        $erro = 'O diretório de armazenamento não permite gravação.';
        return null;
    }

    $novoNome = bin2hex(random_bytes(16)) . '.' . $extensao;
    $destino = $diretorioDestino . DIRECTORY_SEPARATOR . $novoNome;
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        $erro = 'Não foi possível salvar o arquivo no servidor.';
        return null;
    }
    @chmod($destino, 0640);

    return $novoNome;
}

/** Cria uma notificação para um usuário específico. */
function criarNotificacao(int $usuarioId, string $tipo, string $titulo, string $mensagem): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare(
        'INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem) VALUES (:uid, :tipo, :titulo, :msg)'
    );
    $stmt->execute([
        ':uid'    => $usuarioId,
        ':tipo'   => $tipo,
        ':titulo' => $titulo,
        ':msg'    => $mensagem,
    ]);
}

/** Retorna a badge (rótulo colorido Bootstrap) correspondente a um status. */
function badgeStatus(string $status): string
{
    $mapa = [
        'ativo'      => 'success',
        'inativo'    => 'secondary',
        'pendente'   => 'warning text-dark',
        'aprovado'   => 'success',
        'rejeitado'  => 'danger',
        'presente'   => 'success',
        'falta'      => 'danger',
        'atraso'     => 'warning text-dark',
        'justificado'=> 'info text-dark',
        'ferias'     => 'info text-dark',
        'licenca'    => 'info text-dark',
        'pago'       => 'success',
        'aberta'     => 'success',
        'fechada'    => 'secondary',
        'pausada'    => 'warning text-dark',
        'reprovado'  => 'danger',
        'inscrito'   => 'secondary',
        'triagem'    => 'info text-dark',
        'entrevista' => 'primary',
    ];
    $cor = $mapa[$status] ?? 'secondary';
    return '<span class="badge bg-' . $cor . '">' . e(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

/**
 * Monta os dados de paginação a partir do total de registros.
 * Uso: $p = paginar($total, $paginaAtual, 20); depois inclua includes/paginacao.php
 */
function paginar(int $total, int $paginaAtual, int $porPagina): array
{
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $paginaAtual = max(1, min($paginaAtual, $totalPaginas));
    return [
        'pagina_atual'  => $paginaAtual,
        'total_paginas' => $totalPaginas,
        'total'         => $total,
        'por_pagina'    => $porPagina,
    ];
}

/**
 * Trunca um texto de forma segura para exibição (ex.: prévia de descrição de vaga).
 * Usa mb_strimwidth quando a extensão mbstring está disponível (padrão no XAMPP),
 * com fallback para substr() para não quebrar em ambientes onde ela não esteja habilitada.
 */
function truncarTexto(?string $texto, int $tamanho, string $sufixo = '...'): string
{
    $texto = $texto ?? '';
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($texto, 0, $tamanho, $sufixo);
    }
    return strlen($texto) > $tamanho ? substr($texto, 0, $tamanho) . $sufixo : $texto;
}

/** Redireciona para uma URL relativa à raiz do sistema e encerra o script. */
function redirecionar(string $caminho): void
{
    header('Location: ' . BASE_URL . $caminho);
    exit;
}

// ============================================================
// FUNÇÕES PARA O DASHBOARD (NOVAS - NÃO ESTÃO NO auth.php)
// ============================================================

/**
 * Calcula o tempo decorrido desde uma data
 */
function timeAgo(?string $data): string
{
    if (empty($data)) return '';
    
    try {
        $diff = time() - strtotime($data);
        if ($diff < 60) return 'agora';
        if ($diff < 3600) return floor($diff / 60) . 'm atrás';
        if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
        if ($diff < 2592000) return floor($diff / 86400) . 'd atrás';
        if ($diff < 31536000) return floor($diff / 2592000) . ' meses atrás';
        return floor($diff / 31536000) . ' anos atrás';
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Função para executar query e retornar um único valor
 */
function dashScalar(string $sql, array $params = [], $default = 0)
{
    try {
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false || $value === null ? $default : $value;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Função para executar query e retornar múltiplas linhas
 */
function dashRows(string $sql, array $params = []): array
{
    try {
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Formata valor para moeda (alias para formatarMoedaMT)
 */
function dashMoney($value): string
{
    return formatarMoedaMT((float) $value);
}

/**
 * Mensagem de vazio
 */
function dashEmpty(string $message): void
{
    echo '<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>' . e($message) . '</div>';
}

/**
 * Card KPI
 */
function dashKpi(string $label, $value, string $icon, string $tone = 'primary', string $sub = ''): void
{
    ?>
    <div class="col-6 col-xl-3">
        <div class="card kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="text-muted small text-uppercase fw-semibold"><?= e($label) ?></div>
                    <div class="kpi-value text-truncate"><?= e((string) $value) ?></div>
                    <?php if ($sub !== ''): ?><div class="small text-muted"><?= e($sub) ?></div><?php endif; ?>
                </div>
                <div class="kpi-icon bg-<?= e($tone) ?> bg-opacity-10 text-<?= e($tone) ?>">
                    <i class="bi <?= e($icon) ?>"></i>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Cabeçalho da dashboard
 */
function dashHeader(string $title, string $subtitle): void
{
    global $pageTitle;
    $pageTitle = $title;
    ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1"><?= e(usuarioAtualNome()) ?></h4>
            <p class="text-muted mb-0"><?= e($subtitle) ?></p>
        </div>
        <span class="badge rounded-pill text-bg-primary px-3 py-2">
            <?= e(nomePerfil(usuarioAtualPerfil())) ?>
        </span>
    </div>
    <?php
}

/**
 * Rodapé da dashboard
 */
function dashFooter(): void
{
    // O footer já é incluído pelo header.php
}