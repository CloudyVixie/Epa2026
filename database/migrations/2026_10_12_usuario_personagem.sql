-- Execute uma vez no banco PostgreSQL do Epa2026 antes de testar o cadastro.
-- Faça um backup do banco antes de aplicar esta alteração.

ALTER TABLE usuario
    ADD COLUMN IF NOT EXISTS personagem_id INTEGER;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_usuario_personagem'
    ) THEN
        ALTER TABLE usuario
            ADD CONSTRAINT fk_usuario_personagem
            FOREIGN KEY (personagem_id)
            REFERENCES personagem(id)
            ON DELETE SET NULL;
    END IF;
END $$;

-- Se este índice falhar, existem e-mails duplicados ignorando maiúsculas/minúsculas.
-- Resolva as duplicatas antes de executar novamente.
CREATE UNIQUE INDEX IF NOT EXISTS ux_usuario_email_lower
    ON usuario (LOWER(email));

CREATE INDEX IF NOT EXISTS ix_verificacao_email_usuario
    ON verificacao (LOWER(email_usuario));

CREATE INDEX IF NOT EXISTS ix_blocker_email_usuario
    ON blocker (LOWER(email_usuario));
