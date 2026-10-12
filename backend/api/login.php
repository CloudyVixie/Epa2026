<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json; charset=utf-8');

function responderLogin(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function valorBooleanoBanco(mixed $valor): bool
{
    return in_array($valor, [true, 1, '1', 't', 'true', 'TRUE'], true);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        responderLogin(['erro' => 'Método não permitido.'], 405);
    }

    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados)) {
        responderLogin(['erro' => 'Corpo JSON inválido.'], 400);
    }

    $email = filter_var(trim((string) ($dados['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $senha = (string) ($dados['senha'] ?? '');

    if (!$email || $senha === '') {
        responderLogin(['erro' => 'Informe um e-mail e uma senha válidos.'], 400);
    }

    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT nome, email, senha, verificado
         FROM usuario
         WHERE email = ?
         LIMIT 1'
    );
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (
        !$usuario
        || !valorBooleanoBanco($usuario['verificado'])
    ) {
        responderLogin(['erro' => 'E-mail ou senha incorretos, ou conta ainda não verificada.'], 401);
    }

    $senhaValida = password_verify($senha, (string) $usuario['senha']);

    // Compatibilidade temporária com senhas SHA-256 gravadas pela versão anterior.
    if (!$senhaValida && hash_equals((string) $usuario['senha'], hash('sha256', $senha))) {
        $novaHash = password_hash($senha, PASSWORD_DEFAULT);
        $atualizar = $pdo->prepare('UPDATE usuario SET senha = ? WHERE email = ?');
        $atualizar->execute([$novaHash, $email]);
        $senhaValida = true;
    }

    if (!$senhaValida) {
        responderLogin(['erro' => 'E-mail ou senha incorretos, ou conta ainda não verificada.'], 401);
    }

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
    ];

    responderLogin([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso.',
        'usuario' => [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
        ],
    ]);
} catch (Throwable $erro) {
    error_log('Erro na API de login: ' . $erro->getMessage());
    responderLogin(['erro' => 'Erro interno. Confira os logs do servidor.'], 500);
}
