<?php
    include __DIR__ . '/../dao/personagensDAO.php';

    header('Content-Type: application/json');

    $personagens = new Personagem();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if($method == 'GET'){
        if(isset($_GET['id'])){
            $resultado = $personagens->buscarPersonagem($_GET['id']);
        }
        else{
            $resultado = $personagens->buscarPersonagens();
        }
        echo json_encode($resultado);
    }
?>