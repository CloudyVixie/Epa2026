<?php
    include_once '../config/conexao.php';
    class Blocker{
        private $pdo;
        public function __construct()
        {
            $this->pdo = getConexao();
        }

        public function bloquear($email){
            $sql = "insert into blocker (email, tempo_bloqueado)
            values (?, now())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
        }
        public function buscarPorEmail($email){
            $sql = "select * from blocker where email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch();
        }
        public function estaBloqueado($email){
            $sql = "select *, extract(epoch from (now() - tempo_bloqueio))::int as decorrido from blocker where email = ? and tempo_bloqueio > now() - interval '1 hour'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch();
        }
    }

?>