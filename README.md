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
- `backend/migrations/`: arquivos de migração do banco.
- `backend/scripts/`: scripts auxiliares do sistema.
- `frontend/`: arquivos da interface.
- `.env.example`: modelo das variáveis de ambiente.
- `composer.json`: dependências PHP.

## Como configurar

### 1. Requisitos

Instale PHP 8.1 ou superior com a extensão PDO PostgreSQL habilitada, Composer e PostgreSQL.

### 2. Dependências

Na raiz do repositório, execute:

```bash
composer install
```

### 3. Variáveis de ambiente

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

Use uma senha de app do Gmail para o envio de e-mails, não a senha normal da conta. Não envie o arquivo `.env` para o GitHub.

### 4. Banco de dados

Crie o banco no PostgreSQL e aplique os arquivos de migração disponíveis em `backend/migrations/`, seguindo a ordem indicada pelos nomes dos arquivos. Confira também os scripts em `backend/scripts/` antes de executar operações de cadastro ou manutenção.

### 5. Executar

O projeto não define um comando único de inicialização no `composer.json`. Para testar localmente, configure o servidor PHP para servir a pasta pública correta do projeto e mantenha a conexão com o PostgreSQL disponível. Consulte a estrutura de pastas e os pontos de entrada existentes antes de configurar o servidor.

## Observações

Este repositório está em desenvolvimento. Os passos de execução podem variar conforme a configuração local do servidor e do banco de dados.
