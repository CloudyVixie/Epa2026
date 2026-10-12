<?php
    include_once '../config/conexao.php';
    class Verificacao{
        private $pdo;
        public function __construct()
        {
            $this->pdo = getConexao();
        }

        public function salvarUsuarioNaoVerificado($nome, $senha, $email, $codigo){
            $sql = "insert into usuario (nome, senha, email)
            values (?, ?, ?);";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nome, $senha, $email]);

            $sql2 = "insert into verificacao (email_usuario, codigo)
            values (?, ?)";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([$email, $codigo]);
        }
        public function verificarUsuarioNaoSalvo($email){
            $sql = "select * from usuario where email = ? and verificado = false";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch();
        }
        public function autenticarUsuario($email){
            $sql = "update from usuario
            set verificado = true
            where email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);

            $sql2 = "delete from verificacao where email = ?";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([$email]);
        }
        public function apagarPorEmail($email){
            $sql = "delete from verificacao where email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
        }
        public function incrementarTentativa($email){
            $sql = "update verificacao set tentativas = tentativas + 1 where email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
        }
        public function verificarSituacao($email){
            $sql = "select * from personagem where email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch();
        }
        public function listarUsuarios(){
            $sql = "select * from usuario where verificado = false";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([]);
            return $stmt->fetchAll();
        }

    }

?>