# manager Payment API

API REST em Laravel 12 para gerenciamento de solicitações de pagamento multimoeda. Usuários autenticados via Laravel Passport podem enviar pagamentos em moeda local, consultar taxas de câmbio em tempo real e encaminhar solicitações para aprovação pela equipe financeira.

## Requisitos

- PHP 8.2+
- Composer 2.x
- SQLite (padrão) ou MySQL
- Extensões PHP: `bcmath`, `curl`, `dom`, `mbstring`, `sqlite3`, `xml`, `zip`

> Se preferir evitar dependências locais de PHP, use Docker (veja seção Docker abaixo).

## Configuração local

```bash
git clone <seu-repositorio> manager-payment-api
cd manager-payment-api

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite

php artisan migrate --seed

php artisan serve
```

A API ficará disponível em `http://localhost:8000/api`.

### MySQL (opcional)

No `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manager_payment_api
DB_USERNAME=root
DB_PASSWORD=
```

### Scheduler (expiração automática)

Solicitações pendentes por mais de 48 horas são expiradas pelo comando `payment-requests:expire`, agendado de hora em hora.

Adicione ao cron:

```bash
* * * * * cd /caminho/para/manager-payment-api && php artisan schedule:run >> /dev/null 2>&1
```

Execução manual:

```bash
php artisan payment-requests:expire
```

### Docker (alternativa)

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan key:generate
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan migrate --seed
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan test
```

## Usuários de teste (seed)

Senha padrão para todos: `password`

| Nome | Email | Role | País | Moeda |
|------|-------|------|------|-------|
| Ana Silva | ana.silva@manager.test | employee | BR | BRL |
| John Smith | john.smith@manager.test | employee | US | USD |
| Emma Wilson | emma.wilson@manager.test | employee | GB | GBP |
| Yuki Tanaka | yuki.tanaka@manager.test | employee | JP | JPY |
| Hans Mueller | hans.mueller@manager.test | employee | DE | EUR |
| Sofia Rossi | sofia.rossi@manager.test | employee | IT | EUR |
| Finance Admin | finance.admin@manager.test | finance | PT | EUR |
| Finance Reviewer | finance.reviewer@manager.test | finance | PT | EUR |

## Decisões técnicas

- **Autenticação:** Laravel Passport com Personal Access Tokens (`Bearer`).
- **Papéis:** `employee` cria/consulta suas solicitações; `finance` visualiza todas e aprova/rejeita pendentes.
- **Taxa de câmbio:** buscada na criação via [ExchangeRate-API](https://api.exchangerate-api.com), armazenada de forma imutável (`exchange_rate`, `exchange_rate_source`, `exchange_rate_fetched_at`).
- **Conversão:** `amount_eur = amount / exchange_rate`, onde a taxa representa unidades de moeda local por 1 EUR.
- **Expiração:** comando agendado marca como `expired` solicitações `pending` com mais de 48 horas.

## Documentação da API

Base URL: `/api`

### POST `/register`

Registra um funcionário (`employee`).

**Body (JSON):**

```json
{
  "name": "Ana Silva",
  "email": "ana@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "country": "BR",
  "currency": "BRL"
}
```

**Resposta 201:**

```json
{
  "message": "User registered successfully.",
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Ana Silva",
    "email": "ana@example.com",
    "role": "employee",
    "country": "BR",
    "currency": "BRL"
  }
}
```

### POST `/login`

**Body (JSON):**

```json
{
  "email": "ana.silva@manager.test",
  "password": "password"
}
```

**Resposta 200:**

```json
{
  "message": "Login successful.",
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "user": { "...": "..." }
}
```

**Erro 401:** credenciais inválidas.

### POST `/logout`

Requer header `Authorization: Bearer {token}`.

**Resposta 200:**

```json
{
  "message": "Logged out successfully."
}
```

### GET `/user`

Retorna o perfil autenticado.

### POST `/payment-requests`

Requer autenticação. Apenas `employee`.

**Body (JSON):**

```json
{
  "title": "Office supplies reimbursement",
  "amount": 595,
  "currency": "BRL"
}
```

**Resposta 201:**

```json
{
  "message": "Payment request created successfully.",
  "data": {
    "id": 1,
    "title": "Office supplies reimbursement",
    "amount": 595,
    "currency": "BRL",
    "exchange_rate": 5.95,
    "exchange_rate_source": "https://api.exchangerate-api.com",
    "exchange_rate_fetched_at": "2026-06-12T10:00:00.000000Z",
    "amount_eur": 100,
    "status": "pending"
  }
}
```

**Erros:** `422` validação, `503` taxa indisponível.

### GET `/payment-requests`

Lista solicitações. Funcionários veem apenas as próprias; finance vê todas.

**Query params:** `status` (`pending`, `approved`, `rejected`, `expired`)

**Exemplo:** `/api/payment-requests?status=pending`

### GET `/payment-requests/{id}`

Detalhe de uma solicitação (própria ou qualquer, se finance).

### PATCH `/payment-requests/{id}/approve`

Requer role `finance`. Apenas solicitações `pending`.

**Resposta 200:** solicitação com `status: approved`.

**Erros:** `403` sem permissão, `409` status inválido.

### PATCH `/payment-requests/{id}/reject`

Requer role `finance`.

**Body (JSON):**

```json
{
  "rejection_reason": "Missing receipt attachment."
}
```

**Resposta 200:** solicitação com `status: rejected`.

## Testes

```bash
php artisan test
```

Cobertura principal:

- Integração com taxa de câmbio (mock HTTP)
- Serviço de solicitações (criar, aprovar, rejeitar, expirar)
- Autenticação (register, login, logout)
- Autorização employee vs finance
- Comando de expiração (> 48h)

## Estrutura relevante

```
app/
├── Console/Commands/ExpirePendingPaymentRequests.php
├── Contracts/ExchangeRateProviderInterface.php
├── Enums/
├── Http/Controllers/Api/
├── Models/
├── Policies/PaymentRequestPolicy.php
└── Services/
database/seeders/
tests/Unit/
tests/Feature/
```

## Git / publicação

```bash
git init
git add .
git commit -m "Initial implementation of manager payment API"
git remote add origin <url-do-repositorio>
git push -u origin main
```
