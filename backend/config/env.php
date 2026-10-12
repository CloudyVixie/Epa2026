<?php
declare(strict_types=1);

$raizProjeto = dirname(__DIR__, 2);
$autoload = $raizProjeto . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;

    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable($raizProjeto)->safeLoad();
    }
}

function envValor(string $chave, ?string $padrao = null): ?string
{
    $valor = $_ENV[$chave] ?? $_SERVER[$chave] ?? getenv($chave);

    if ($valor === false || $valor === null || $valor === '') {
        return $padrao;
    }

    return (string) $valor;
}
