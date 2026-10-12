<?php

declare(strict_types=1);

require_once __DIR__ . '/../dao/verificacaoDAO.php';
require_once __DIR__ . '/../dao/blockerDAO.php';
require_once __DIR__ . '/../config/email.php';

header('Content-Type: application/json; charset=utf-8');

function responder(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

try {
    $verificacao = new Verificacao();
    $blocker = new Blocker();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            responder(['erro' => 'Informe um e-mail válido.'], 400);
        }

        if (isset($_GET['bloqueado'])) {
            $registro = $blocker->estaBloqueado($email);

            responder($registro
                ? [
                    'bloqueado' => true,
                    'segundos_restantes' => max(
                        0,
                        ($registro['permanente'] ? PHP_INT_MAX : 3600) - (int) $registro['decorrido']
                    ),
                    'permanente' => (bool) $registro['permanente'],
                ]
                : ['bloqueado' => false]);
        }

        if (isset($_GET['status'])) {
            $registro = $verificacao->verificarSituacao($email);

            responder($registro
                ? [
                    'existe' => true,
                    'segundos_restantes' => max(0, 900 - (int) $registro['decorrido']),
                ]
                : ['existe' => false]);
        }

        responder(['erro' => 'Parâmetro de consulta inválido.'], 400);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        responder(['erro' => 'Método não permitido.'], 405);
    }

    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados) || !isset($dados['acao'])) {
        responder(['erro' => 'Corpo JSON inválido ou ação ausente.'], 400);
    }

    $email = filter_var(trim((string) ($dados['email'] ?? '')), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        responder(['erro' => 'Informe um e-mail válido.'], 400);
    }

    if ($dados['acao'] === 'solicitar') {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');

        if ($nome === '' || mb_strlen($nome) > 100) {
            responder(['erro' => 'Informe um nome com até 100 caracteres.'], 400);
        }

        if (strlen($senha) < 9 || strlen($senha) > 72) {
            responder(['erro' => 'A senha deve ter entre 9 e 72 bytes.'], 400);
        }

        if ($blocker->estaBloqueado($email)) {
            responder(['erro' => 'Este e-mail está bloqueado temporariamente.'], 429);
        }

        $codigo = (string) random_int(100000, 999999);
        $verificacao->salvarUsuarioNaoVerificado(
            $nome,
            password_hash($senha, PASSWORD_DEFAULT),
            $email,
            hash('sha256', $codigo)
        );

        if (!enviarEmailCodigo($email, $codigo)) {
            responder(['erro' => 'Não foi possível enviar o e-mail. Confira a configuração SMTP e tente novamente.'], 502);
        }

        responder(['sucesso' => true, 'mensagem' => 'Código enviado para seu e-mail.']);
    }

    if ($dados['acao'] === 'confirmar') {
        $codigo = preg_replace('/\D/', '', (string) ($dados['codigo'] ?? ''));

        if (strlen($codigo) !== 6) {
            responder(['erro' => 'Informe os seis dígitos do código.'], 400);
        }

        $registro = $verificacao->verificarSituacao($email);

        if (!$registro) {
            responder(['erro' => 'Código expirado ou não solicitado.'], 400);
        }

        if ((int) $registro['decorrido'] > 900) {
            $blocker->bloquear($email);
            $verificacao->apagarPorEmail($email);
            responder(['erro' => 'expirado'], 410);
        }

        if (hash_equals((string) $registro['codigo'], hash('sha256', $codigo))) {
            $verificacao->autenticarUsuario($email);
            responder(['valido' => true, 'mensagem' => 'E-mail verificado. Você já pode entrar.']);
        }

        $verificacao->incrementarTentativa($email);

        if (((int) $registro['tentativas'] + 1) >= 5) {
            $blocker->bloquear($email);
            $verificacao->apagarPorEmail($email);
            responder(['erro' => 'bloqueado'], 429);
        }

        responder(['erro' => 'Código incorreto.'], 400);
    }

    responder(['erro' => 'Ação desconhecida.'], 400);
} catch (DomainException $erro) {
    responder(['erro' => $erro->getMessage()], 409);
} catch (Throwable $erro) {
    error_log('Erro na API de verificação: ' . $erro->getMessage());
    responder(['erro' => 'Erro interno. Confira os logs do servidor.'], 500);
}
