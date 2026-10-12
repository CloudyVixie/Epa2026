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
        string $codigoHash
    ): void {
        $this->pdo->beginTransaction();

        try {
            $consulta = $this->pdo->prepare(
                'SELECT verificado FROM usuario WHERE email = ? FOR UPDATE'
            );
            $consulta->execute([$email]);
            $usuario = $consulta->fetch();

            if ($usuario && $usuario['verificado']) {
                throw new DomainException('Este e-mail já possui uma conta verificada.');
            }

            if ($usuario) {
                $atualizar = $this->pdo->prepare(
                    'UPDATE usuario SET nome = ?, senha = ? WHERE email = ?'
                );
                $atualizar->execute([$nome, $senhaHash, $email]);
                $this->apagarPorEmail($email);
            } else {
                $inserir = $this->pdo->prepare(
                    'INSERT INTO usuario (nome, senha, email, verificado)
                     VALUES (?, ?, ?, FALSE)'
                );
                $inserir->execute([$nome, $senhaHash, $email]);
            }

            $inserirCodigo = $this->pdo->prepare(
                'INSERT INTO verificacao
                    (email_usuario, codigo, tentativas, tempo_aquisicao)
                 VALUES (?, ?, 0, NOW())'
            );
            $inserirCodigo->execute([$email, $codigoHash]);

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
            'SELECT * FROM usuario WHERE email = ? AND verificado = FALSE'
        );
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function autenticarUsuario(string $email): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE usuario
                 SET verificado = TRUE
                 WHERE email = ? AND verificado = FALSE'
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

    public function apagarPorEmail(string $email): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM verificacao WHERE email_usuario = ?'
        );
        $stmt->execute([$email]);
    }

    public function incrementarTentativa(string $email): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE verificacao
             SET tentativas = tentativas + 1
             WHERE email_usuario = ?'
        );
        $stmt->execute([$email]);
    }

    public function verificarSituacao(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT *,
                    EXTRACT(EPOCH FROM (NOW() - tempo_aquisicao))::INTEGER AS decorrido
             FROM verificacao
             WHERE email_usuario = ?
             ORDER BY tempo_aquisicao DESC
             LIMIT 1'
        );
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function listarUsuarios(): array
    {
        return $this->pdo
            ->query('SELECT * FROM usuario WHERE verificado = FALSE')
            ->fetchAll();
    }
}
