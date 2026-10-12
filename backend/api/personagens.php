<?php
declare(strict_types=1);

require_once __DIR__ . '/../dao/personagensDAO.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $personagens = new Personagem();
    if (isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultado = $personagens->buscarPersonagem((int) $id);
        if (!$resultado) {
            http_response_code(404);
            echo json_encode(['erro' => 'Personagem não encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        $resultado = $personagens->buscarPersonagens();
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $erro) {
    error_log('[Epa2026 personagens] ' . $erro->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível carregar os personagens.'], JSON_UNESCAPED_UNICODE);
}
