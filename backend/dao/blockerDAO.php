<?php

require_once __DIR__ . '/../config/conexao.php';

class Blocker
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getConexao();
    }

    public function bloquear(string $email, bool $permanente = false): void
    {
        $this->pdo->beginTransaction();

        try {
            // Remove registros anteriores para evitar bloqueios duplicados.
            $stmt = $this->pdo->prepare(
                "DELETE FROM blocker WHERE email_usuario = ?"
            );
            $stmt->execute([$email]);

            $sql = "INSERT INTO blocker
                    (email_usuario, tempo_bloqueado, permanente)
                    VALUES (?, NOW(), ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $permanente]);

            $this->pdo->commit();
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $erro;
        }
    }

    public function buscarPorEmail(string $email): array|false
    {
        $sql = "SELECT *
                FROM blocker
                WHERE email_usuario = ?
                ORDER BY tempo_bloqueado DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function estaBloqueado(string $email): array|false
    {
        $sql = "SELECT *,
                       EXTRACT(
                           EPOCH FROM (NOW() - tempo_bloqueado)
                       )::INTEGER AS decorrido
                FROM blocker
                WHERE email_usuario = ?
                  AND (
                      permanente = TRUE
                      OR tempo_bloqueado > NOW() - INTERVAL '1 hour'
                  )
                ORDER BY tempo_bloqueado DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetch();
    }
}