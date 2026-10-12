<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

function getConexao(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = envValor('EPA_DB_HOST', '127.0.0.1');
    $porta = envValor('EPA_DB_PORT', '5432');
    $banco = envValor('EPA_DB_NAME');
    $usuario = envValor('EPA_DB_USER');
    $senha = envValor('EPA_DB_PASS', '');

    if (!$banco || !$usuario) {
        throw new RuntimeException(
            'Configure EPA_DB_NAME e EPA_DB_USER no arquivo .env.'
        );
    }

    $pdo = new PDO(
        "pgsql:host={$host};port={$porta};dbname={$banco}",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}
