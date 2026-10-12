-- Execute uma vez no banco PostgreSQL do projeto.
-- PASSWORD_DEFAULT gera hashes maiores que o SHA-256 usado pela versão anterior.
ALTER TABLE usuario ALTER COLUMN senha TYPE VARCHAR(255);
