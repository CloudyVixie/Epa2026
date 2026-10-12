# Configuração local do Epa2026

## Requisitos

- PHP 8.1 ou superior.
- Composer.
- PostgreSQL e o banco de dados do Epa2026.
- Apache do XAMPP (ou outro servidor PHP).
- Uma conta Google com verificação em duas etapas para gerar uma senha de app.

Abra o projeto pelo servidor, por exemplo: `http://localhost/Epa2026/cadastrar.html`. Não abra os arquivos com `file://`.

## 1. Instalar as dependências

No terminal, entre na pasta raiz do projeto (a mesma que contém `composer.json`) e execute:

```bash
composer install
```

Isso instala o PHPMailer e o Dotenv. A pasta `vendor/` não deve ser enviada ao Git.

## 2. Configurar o ambiente

Copie `.env.example` para um arquivo chamado `.env`, na raiz do projeto. No Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Edite o `.env` e preencha `EPA_DB_NAME`, `EPA_DB_USER` e `EPA_DB_PASS` com os dados reais do PostgreSQL. Configure também `GMAIL_USER` e `GMAIL_APP_PASSWORD`.

Para o Gmail, use uma senha de app criada na página oficial https://myaccount.google.com/apppasswords, após ativar a verificação em duas etapas. Não use a senha normal da conta Google. Nunca publique o arquivo `.env`.

## 3. Ativar o driver PostgreSQL do PHP

No PHP que o Apache do XAMPP usa, confirme que a extensão `pdo_pgsql` está habilitada no `php.ini`. Depois de alterar o arquivo, reinicie o Apache.

Confira pelo navegador criando temporariamente um arquivo de diagnóstico local com `<?php echo extension_loaded('pdo_pgsql') ? 'OK' : 'FALTA pdo_pgsql';` e remova-o depois do teste. O terminal e o Apache podem usar arquivos `php.ini` diferentes.

## 4. Atualizar o banco de dados

Faça backup do banco antes de executar alterações. Abra o banco correto no pgAdmin e execute o arquivo:

`database/migrations/2026_10_12_usuario_personagem.sql`

A migração adiciona a coluna `personagem_id` à tabela `usuario`, cria a chave estrangeira para `personagem` e índices usados nas consultas. Se a criação do índice de e-mail falhar por duplicatas, resolva os e-mails duplicados e execute a migração novamente.

As tabelas `usuario`, `personagem`, `verificacao` e `blocker` devem existir com as colunas documentadas no projeto.

## 5. Testar o fluxo

1. Inicie PostgreSQL e Apache.
2. Abra `http://localhost/Epa2026/cadastrar.html`.
3. Confirme que a lista de personagens carrega.
4. Cadastre uma conta com um e-mail que você consegue acessar.
5. Confira a caixa de entrada e a pasta de spam; o código expira em 15 minutos.
6. Digite o código de seis dígitos.
7. Entre em `http://localhost/Epa2026/login.html` com a mesma conta.

Se ocorrer erro 500, consulte o log de erros do PHP/Apache e o log de erro do PostgreSQL. A API retorna uma mensagem genérica ao navegador para não expor detalhes internos.

## Segurança importante

As credenciais antigas que estavam escritas diretamente em `backend/config/conexao.php` foram removidas do arquivo atual, mas continuam visíveis em commits antigos do Git. Se eram credenciais reais, troque a senha do usuário PostgreSQL e verifique o histórico do repositório. Colocar o segredo no `.env` não o remove do histórico.
