<?php
include '../dao/verificacaoDAO.php';
include '../dao/blockerDAO.php';
include '../config/email.php';

header('Content-Type: application/json');

$verificacao = new Verificacao;
$blocker = new Blocker;
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    if (isset($_GET['bloqueado'])) {
        $registro = $blocker->estaBloqueado($_GET['email']);
        if ($registro) {
            $restante = 3600 - $registro['decorrido'];
            echo json_encode($restante > 0
                ? ['bloqueado' => true, 'segundos_restantes' => $restante]
                : ['bloqueado' => false]);
        } else {
            echo json_encode(['bloqueado' => false]);
        }
    } else if (isset($_GET['status'])) {
        $registro = $verificacao->verificarSituacao($_GET['email']);
        if ($registro) {
            echo json_encode(['existe' => true, 'segundos_restantes' => max(0, 900 - $registro['decorrido'])]);
        } else {
            echo json_encode(['existe' => false]);
        }
    }
} else if ($method == 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if ($dados['acao'] == 'solicitar') {
        if ($blocker->estaBloqueado($dados['email'])) {
            echo json_encode(['erro' => 'Email bloqueado temporariamente.']);
        } else {
            $codigo = strval(random_int(100000, 999999));
            $verificacao->salvarUsuarioNaoVerificado($dados['nome'], hash('sha256', $dados['senha']), $dados['email'], hash('sha256', $codigo));
            echo json_encode(['sucesso' => enviarEmailCodigo($dados['email'], $codigo)]);
        }
    } else if ($dados['acao'] == 'confirmar') {
        $registro = $verificacao->verificarSituacao($dados['email']);

        if (!$registro) {
            echo json_encode(['erro' => 'Código expirado ou não solicitado.']);
        } else if ($registro['decorrido'] > 900) {
            $blocker->bloquear($dados['email']);
            $verificacao->apagarPorEmail($dados['email']);
            echo json_encode(['erro' => 'expirado']);
        } else if (hash('sha256', $dados['codigo']) == $registro['codigo']) {
            $verificacao->apagarPorEmail($dados['email']);
            echo json_encode(['valido' => true]);
        } else {
            $verificacao->incrementarTentativa($dados['email']);
            if (($registro['tentativas'] + 1) >= 5) {
                $blocker->bloquear($dados['email']);
                $verificacao->apagarPorEmail($dados['email']);
                echo json_encode(['erro' => 'bloqueado']);
            } else {
                echo json_encode(['erro' => 'Código incorreto.']);
            }
        }
    }
}

?>