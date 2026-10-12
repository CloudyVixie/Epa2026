<?php
declare(strict_types=1);

require_once __DIR__ . '/../dao/verificacaoDAO.php';
require_once __DIR__ . '/../dao/blockerDAO.php';
require_once __DIR__ . '/../config/email.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $verificacao = new Verificacao();
    $blocker = new Blocker();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $email = filter_var(trim((string) ($_GET['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            responder(['erro' => 'Informe um e-mail válido.'], 400);
        }

        if (isset($_GET['bloqueado'])) {
            $registro = $blocker->estaBloqueado($email);
            if (!$registro) {
                responder(['bloqueado' => false]);
            }

            $permanente = (bool) $registro['permanente'];
            responder([
                'bloqueado' => true,
                'permanente' => $permanente,
                'segundos_restantes' => $permanente ? null : max(0, 3600 - (int) $registro['decorrido']),
            ]);
        }

        if (isset($_GET['status'])) {
            $registro = $verificacao->verificarSituacao($email);
            responder($registro
                ? ['existe' => true, 'segundos_restantes' => max(0, 900 - (int) $registro['decorrido'])]
                : ['existe' => false]);
        }

        responder(['erro' => 'Ação GET inválida.'], 400);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        responder(['erro' => 'Método não permitido.'], 405);
    }

    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados)) {
        responder(['erro' => 'Corpo JSON inválido.'], 400);
    }

    $acao = $dados['acao'] ?? '';
    $email = filter_var(trim((string) ($dados['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        responder(['erro' => 'Informe um e-mail válido.'], 400);
    }

    if ($acao === 'solicitar') {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');
        $personagemId = filter_var($dados['personagem_id'] ?? null, FILTER_VALIDATE_INT);

        if ($nome === '' || strlen($nome) > 300) {
            responder(['erro' => 'Informe um nome com até 100 caracteres.'], 400);
        }
        if (strlen($senha) < 9 || strlen($senha) > 72) {
            responder(['erro' => 'A senha deve ter entre 9 e 72 bytes.'], 400);
        }
        if (!$personagemId || $personagemId < 1) {
            responder(['erro' => 'Selecione um personagem.'], 400);
        }

        if ($blocker->estaBloqueado($email)) {
            responder(['erro' => 'Este e-mail está temporariamente bloqueado.'], 429);
        }

        $pdo = getConexao();
        $stmt = $pdo->prepare('SELECT id FROM personagem WHERE id = ?');
        $stmt->execute([$personagemId]);
        if (!$stmt->fetch()) {
            responder(['erro' => 'O personagem selecionado não existe.'], 400);
        }

        $codigo = (string) random_int(100000, 999999);
        try {
            $verificacao->salvarUsuarioNaoVerificado(
                $nome,
                password_hash($senha, PASSWORD_DEFAULT),
                $email,
                password_hash($codigo, PASSWORD_DEFAULT),
                (int) $personagemId
            );
        } catch (DomainException $erro) {
            responder(['erro' => $erro->getMessage()], 409);
        } catch (PDOException $erro) {
            if ($erro->getCode() === '23505') {
                responder(['erro' => 'Este e-mail já possui uma conta.'], 409);
            }
            throw $erro;
        }

        if (!enviarEmailCodigo($email, $codigo)) {
            $verificacao->apagarCadastroPendente($email);
            responder(['erro' => 'Não foi possível enviar o e-mail. Confira a configuração do Gmail e tente novamente.'], 502);
        }

        responder(['sucesso' => true, 'mensagem' => 'Código enviado. Confira sua caixa de entrada e o spam.']);
    }

    if ($acao === 'confirmar') {
        $codigo = trim((string) ($dados['codigo'] ?? ''));
        if (!preg_match('/^\d{6}$/', $codigo)) {
            responder(['erro' => 'O código deve conter exatamente 6 números.'], 400);
        }

        $registro = $verificacao->verificarSituacao($email);
        if (!$registro) {
            responder(['erro' => 'Não há código ativo para este e-mail. Solicite um novo cadastro.'], 404);
        }

        if ((int) $registro['decorrido'] > 900) {
            $verificacao->apagarPorEmail($email);
            $blocker->bloquear($email);
            responder(['erro' => 'O código expirou. O e-mail ficará bloqueado por uma hora.'], 410);
        }

        if ((int) $registro['tentativas'] >= 5) {
            $blocker->bloquear($email);
            $verificacao->apagarPorEmail($email);
            responder(['erro' => 'Limite de tentativas atingido. Aguarde uma hora.'], 429);
        }

        if (!password_verify($codigo, (string) $registro['codigo'])) {
            $verificacao->incrementarTentativa($email);
            $tentativas = (int) $registro['tentativas'] + 1;
            if ($tentativas >= 5) {
                $blocker->bloquear($email);
                $verificacao->apagarPorEmail($email);
                responder(['erro' => 'Limite de tentativas atingido. Aguarde uma hora.'], 429);
            }
            responder(['erro' => 'Código incorreto.', 'tentativas_restantes' => 5 - $tentativas], 400);
        }

        $verificacao->autenticarUsuario($email);
        responder(['valido' => true, 'mensagem' => 'E-mail confirmado. Sua conta está ativa.']);
    }

    responder(['erro' => 'Ação inválida.'], 400);
} catch (Throwable $erro) {
    error_log('[Epa2026 verificacao] ' . $erro->getMessage());
    responder(['erro' => 'Erro interno. Confira a configuração do servidor e os logs do PHP.'], 500);
}
