<?php
declare(strict_types=1);

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
        $stmt = $this->pdo->prepare('DELETE FROM blocker WHERE LOWER(email_usuario) = LOWER(?)');
        $stmt->execute([$email]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO blocker (email_usuario, tempo_bloqueado, permanente)
             VALUES (?, NOW(), ?)'
        );
        $stmt->execute([$email, $permanente]);
    }

    public function buscarPorEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM blocker WHERE LOWER(email_usuario) = LOWER(?)
             ORDER BY tempo_bloqueado DESC LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function estaBloqueado(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT *, EXTRACT(EPOCH FROM (NOW() - tempo_bloqueado))::INTEGER AS decorrido
             FROM blocker
             WHERE LOWER(email_usuario) = LOWER(?)
               AND (permanente = TRUE OR tempo_bloqueado > NOW() - INTERVAL \'1 hour\')
             ORDER BY tempo_bloqueado DESC LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
