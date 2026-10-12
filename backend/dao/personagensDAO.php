<?php
    include_once __DIR__ . '/../config/conexao.php';
    class Personagem{
        private $pdo;
        public function __construct(){
            $this->pdo = getConexao();
        }

        public function buscarPersonagens(){
            $sql = "select * from personagem";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([]);
            return $stmt->fetchAll();
        }
        
        public function buscarPersonagem($id){
            $sql = "select * from personagem where id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        }
    }
?>