<?php
declare(strict_types=1);

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
        string $senhaHash,
        string $email,
        string $codigoHash,
        int $personagemId
    ): void {
        $this->pdo->beginTransaction();

        try {
            $consulta = $this->pdo->prepare(
                'SELECT id, verificado FROM usuario WHERE LOWER(email) = LOWER(?) FOR UPDATE'
            );
            $consulta->execute([$email]);
            $existente = $consulta->fetch();

            if ($existente && in_array($existente['verificado'], [true, 1, '1', 't', 'true'], true)) {
                throw new DomainException('Este e-mail já possui uma conta.');
            }

            if ($existente) {
                $stmt = $this->pdo->prepare(
                    'UPDATE usuario SET nome = ?, senha = ?, personagem_id = ? WHERE id = ?'
                );
                $stmt->execute([$nome, $senhaHash, $personagemId, $existente['id']]);

                $stmt = $this->pdo->prepare(
                    'DELETE FROM verificacao WHERE email_usuario = ?'
                );
                $stmt->execute([$email]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO usuario (nome, senha, email, verificado, personagem_id)
                     VALUES (?, ?, ?, FALSE, ?)'
                );
                $stmt->execute([$nome, $senhaHash, $email, $personagemId]);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO verificacao (email_usuario, codigo, tentativas, tempo_aquisicao)
                 VALUES (?, ?, 0, NOW())'
            );
            $stmt->execute([$email, $codigoHash]);

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
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email FROM usuario WHERE LOWER(email) = LOWER(?) AND verificado = FALSE'
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function autenticarUsuario(string $email): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE usuario SET verificado = TRUE
                 WHERE LOWER(email) = LOWER(?) AND verificado = FALSE'
            );
            $stmt->execute([$email]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível confirmar o usuário.');
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

    public function apagarCadastroPendente(string $email): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('DELETE FROM verificacao WHERE email_usuario = ?');
            $stmt->execute([$email]);

            $stmt = $this->pdo->prepare(
                'DELETE FROM usuario WHERE LOWER(email) = LOWER(?) AND verificado = FALSE'
            );
            $stmt->execute([$email]);

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
        $stmt = $this->pdo->prepare('DELETE FROM verificacao WHERE LOWER(email_usuario) = LOWER(?)');
        $stmt->execute([$email]);
    }

    public function incrementarTentativa(string $email): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE verificacao SET tentativas = tentativas + 1 WHERE LOWER(email_usuario) = LOWER(?)'
        );
        $stmt->execute([$email]);
    }

    public function verificarSituacao(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT email_usuario, codigo, tentativas,
                    EXTRACT(EPOCH FROM (NOW() - tempo_aquisicao))::INTEGER AS decorrido
             FROM verificacao
             WHERE LOWER(email_usuario) = LOWER(?)
             ORDER BY tempo_aquisicao DESC
             LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
