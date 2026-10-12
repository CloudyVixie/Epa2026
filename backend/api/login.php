<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderLogin(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
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
        responderLogin(['erro' => 'Informe e-mail e senha válidos.'], 400);
    }

    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT id, nome, email, senha, verificado, personagem_id
         FROM usuario WHERE LOWER(email) = LOWER(?) LIMIT 1'
    );
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, (string) $usuario['senha'])) {
        responderLogin(['erro' => 'E-mail ou senha incorretos.'], 401);
    }

    if (!in_array($usuario['verificado'], [true, 1, '1', 't', 'true'], true)) {
        responderLogin(['erro' => 'Confirme seu e-mail antes de entrar.'], 403);
    }

    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = (string) $usuario['nome'];
    $_SESSION['usuario_email'] = (string) $usuario['email'];

    responderLogin([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso.',
        'usuario' => [
            'id' => (int) $usuario['id'],
            'nome' => (string) $usuario['nome'],
            'email' => (string) $usuario['email'],
            'personagem_id' => $usuario['personagem_id'] === null ? null : (int) $usuario['personagem_id'],
        ],
    ]);
} catch (Throwable $erro) {
    error_log('[Epa2026 login] ' . $erro->getMessage());
    responderLogin(['erro' => 'Erro interno. Confira a configuração do servidor e os logs do PHP.'], 500);
}
