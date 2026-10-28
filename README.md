# Epa2026

Aplicação web desenvolvida como projeto de estudo, com cadastro de usuários, verificação de e-mail e gerenciamento de fila.

## Tecnologias

- PHP 8.1 ou superior
- PostgreSQL
- PDO para acesso ao banco de dados
- Composer
- PHPMailer para envio de e-mails
- PHP dotenv para carregar configurações locais

## Estrutura do projeto

- `backend/config/`: conexão com o banco e configuração de e-mail.
- `backend/dao/`: acesso e operações sobre os dados.
- `backend/migrations/`: alterações na estrutura do banco.
- `backend/scripts/`: scripts auxiliares.
- `frontend/`: arquivos da interface.
- `cadastrar.html`, `login.html` e `telaverificacao.html`: páginas do fluxo de conta.
- `.env.example`: modelo das variáveis de ambiente.
- `composer.json`: dependências PHP.

## Como configurar

### 1. Requisitos

Instale PHP 8.1 ou superior com a extensão PDO PostgreSQL habilitada, Composer e PostgreSQL.

### 2. Instalar as dependências

Na raiz do repositório, execute:

```bash
composer install
```

### 3. Configurar as variáveis de ambiente

Copie `.env.example` para um arquivo chamado `.env` e preencha os dados do seu ambiente:

```env
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=nome_do_banco
DB_USER=usuario_do_banco
DB_PASS=

GMAIL_USER=
GMAIL_APP_PASSWORD=
```

Para enviar os códigos de verificação, use uma senha de app do Gmail, não a senha normal da conta. Não envie o arquivo `.env` para o GitHub.

### 4. Preparar o banco

Crie o banco PostgreSQL e configure as tabelas necessárias antes de executar a aplicação. O repositório não inclui uma migração inicial completa do banco. O arquivo `backend/migrations/001_senha_varchar_255.sql` altera a coluna `senha` da tabela `usuario` para aceitar hashes maiores; ele deve ser executado uma única vez em um banco que já possua essa tabela.

### 5. Iniciar o servidor local

Na raiz do projeto, execute:

```bash
php -S 127.0.0.1:8000 -t .
```

Depois, acesse [http://127.0.0.1:8000/cadastrar.html](http://127.0.0.1:8000/cadastrar.html) ou [http://127.0.0.1:8000/login.html](http://127.0.0.1:8000/login.html).

Mantenha o PostgreSQL ativo e confira as configurações do `.env` caso a conexão com o banco ou o envio de e-mails não funcione.

## Observações

Este projeto está em desenvolvimento. A configuração local pode exigir ajustes conforme o ambiente e a estrutura do banco utilizada.
