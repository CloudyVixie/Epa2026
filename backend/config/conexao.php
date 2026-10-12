<?php
function getConexao() {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=bucetinha_preta", "postgres", "bucetinha");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}
?>