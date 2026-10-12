<?php

require_once __DIR__ . '/../config/conexao.php';

class Verificacao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConexao();
    }

    public function salvarUsuarioNaoVerificado(
        string $nome,
        string $senha,
        string $email,
        string $codigo
    ): void {
        $this->pdo->beginTransaction();

        try {
            $sql = "INSERT INTO usuario (nome, senha, email, verificado)
                    VALUES (?, ?, ?, FALSE)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nome, $senha, $email]);

            $sql = "INSERT INTO verificacao
                    (email_usuario, codigo, tentativas, tempo_aquisicao)
                    VALUES (?, ?, 0, NOW())";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $codigo]);

            $this->pdo->commit();
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $erro;
        }
    }

    public function verificarUsuarioNaoSalvo(string $email): array|false
    {
        $sql = "SELECT *
                FROM usuario
                WHERE email = ? AND verificado = FALSE";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function autenticarUsuario(string $email): void
    {
        $this->pdo->beginTransaction();

        try {
            $sql = "UPDATE usuario
                    SET verificado = TRUE
                    WHERE email = ? AND verificado = FALSE";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível confirmar o usuário.'
                );
            }

            $this->apagarPorEmail($email);
            $this->pdo->commit();
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $erro;
        }
    }

    public function apagarPorEmail(string $email): void
    {
        $sql = "DELETE FROM verificacao WHERE email_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
    }

    public function incrementarTentativa(string $email): void
    {
        $sql = "UPDATE verificacao
                SET tentativas = tentativas + 1
                WHERE email_usuario = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
    }

    public function verificarSituacao(string $email): array|false
    {
        $sql = "SELECT *,
                       EXTRACT(
                           EPOCH FROM (NOW() - tempo_aquisicao)
                       )::INTEGER AS decorrido
                FROM verificacao
                WHERE email_usuario = ?
                ORDER BY tempo_aquisicao DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function listarUsuarios(): array
    {
        $sql = "SELECT *
                FROM usuario
                WHERE verificado = FALSE";

        return $this->pdo->query($sql)->fetchAll();
    }
}