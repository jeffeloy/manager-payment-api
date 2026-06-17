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

Com Sail (`compose.yaml`), suba com `docker compose up -d --build` e acesse:

- **API:** `http://localhost:8080/api`
- **UI demo:** `http://localhost:8080/login`

Na primeira subida, o container compila automaticamente os assets do frontend (`npm ci` + `npm run build`) se `public/build/manifest.json` ainda não existir. Não é necessário entrar no container para rodar `npm run dev`.

Para desenvolvimento com hot reload (opcional):

```bash
docker compose exec api npm run dev
```

A UI passa a usar o Vite dev server em `http://localhost:5173`. Para pular o build automático na subida: `SKIP_FRONTEND_BUILD=1 docker compose up -d`.

Rebuild manual dos assets:

```bash
docker compose exec api npm run build
```

As chaves Passport (`storage/oauth-*.key`) exigem permissões restritas (`600` na private, `640` na public). O app corrige isso automaticamente no boot; se ainda falhar no Insomnia, rode dentro do container:

```bash
docker compose exec api chmod 600 storage/oauth-private.key
docker compose exec api chmod 640 storage/oauth-public.key
```

### Deploy em produção (Oracle Cloud Always Free)

Guia completo: [docs/deploy-oracle.md](docs/deploy-oracle.md)

Resumo na VM:

```bash
git clone git@github.com:SEU_USUARIO/manager-payment-api.git
cd manager-payment-api
cp .env.production.example .env
# Edite APP_URL=http://SEU_IP:8080 e DB_PASSWORD
bash scripts/oracle-bootstrap.sh
bash scripts/oracle-deploy.sh
bash scripts/oracle-validate.sh
```

HTTPS (Fase 2): `sudo bash scripts/oracle-setup-nginx.sh demo.seudominio.com`

### Deploy em produção (Render + Neon — recomendado se Oracle sem capacidade)

Guia completo: [docs/deploy-render.md](docs/deploy-render.md)

Resumo:

1. **Neon** — crie projeto Postgres e copie `DB_URL`
2. **Render** — New Blueprint → repo GitHub → [`render.yaml`](render.yaml)
3. Env vars: `APP_URL`, `DB_URL`, `APP_KEY` (ver [`.env.render.example`](.env.render.example))
4. Deploy automático → URL `https://seu-servico.onrender.com`

## Demo ao vivo

> Substitua pela URL após deploy (**Render** ou **Oracle**).

| | URL |
|---|-----|
| **UI (login)** | `https://SEU-SERVICO.onrender.com/login` |
| **API base** | `https://SEU-SERVICO.onrender.com/api` |
| **Health** | `https://SEU-SERVICO.onrender.com/up` |

Render free: após ~15 min sem uso, 1ª visita pode levar ~1 min (cold start).

| Perfil | Email | Senha |
|--------|-------|-------|
| Employee | `ana.silva@manager.test` | `password` |
| Finance | `finance.admin@manager.test` | `password` |

Documentação da API: ver seção [Documentação da API](#documentação-da-api) abaixo.

## Usuários de teste (seed)

Senha padrão para todos os usuários seedados, incluindo `finance`: `password`

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

## Arquitetura

A aplicação expõe **duas camadas HTTP** que compartilham a mesma lógica de domínio (`PaymentRequestService`):

| Camada | Rotas | Auth | Resposta | Uso |
|--------|-------|------|----------|-----|
| **API REST** | `/api/*` | Passport (`Authorization: Bearer`) | JSON | Entrega principal do teste (Postman, integrações) |
| **UI demo** | `/dashboard`, `/payment-requests/*` | Session (login web) | Inertia + redirects | Demonstração em browser |

Controllers:

- `App\Http\Controllers\Api\PaymentRequestController` — somente `JsonResponse`
- `App\Http\Controllers\Web\PaymentRequestController` — somente `Inertia::render` e redirects

Autenticação web (`/login`, `/register`) permanece nos controllers Breeze em `App\Http\Controllers\Auth\*`. Autenticação API em `App\Http\Controllers\Api\AuthController`.

## Documentação da API

Base URL: `/api`

### Autenticação

Rotas protegidas exigem o header:

```
Authorization: Bearer {access_token}
```

O token é retornado em `POST /register` ou `POST /login` no campo `access_token`. Rotas públicas: `/register`, `/login`. Demais rotas listadas abaixo requerem autenticação.

### Formato de erros

| Status | Quando |
|--------|--------|
| `401` | Token ausente, inválido ou expirado; credenciais de login inválidas |
| `403` | Usuário autenticado sem permissão (ex.: employee tentando aprovar) |
| `404` | Recurso não encontrado |
| `409` | Conflito de estado (ex.: aprovar solicitação que não está `pending`) |
| `422` | Falha de validação |
| `503` | Taxa de câmbio indisponível na criação de solicitação |

**Exemplo 422 (validação):**

```json
{
  "message": "The currency field must match your local currency [BRL].",
  "errors": {
    "currency": [
      "The currency must match your local currency [BRL]."
    ]
  }
}
```

**Exemplo 401 (credenciais inválidas no login):**

```json
{
  "message": "Invalid credentials."
}
```

---

### POST `/register`

Registra um funcionário (`employee`). A `currency` deve corresponder ao país (`config/countries.php`).

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| `name` | body | string | sim | Nome completo (max 255) |
| `email` | body | string | sim | E-mail único |
| `password` | body | string | sim | Mínimo 8 caracteres |
| `password_confirmation` | body | string | sim | Deve coincidir com `password` |
| `country` | body | string | sim | ISO 3166-1 alpha-2 (ex.: `BR`) |
| `currency` | body | string | sim | ISO 4217 (ex.: `BRL`); deve corresponder ao país |

**Países suportados (country → currency):** PT, ES, FR, DE, IE, IT, NL, BE → EUR; GB → GBP; US → USD; BR → BRL; JP → JPY.

**Auth:** não

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
    "email_verified_at": null,
    "role": "employee",
    "country": "BR",
    "currency": "BRL",
    "created_at": "2026-06-12T10:00:00.000000Z"
  }
}
```

**Erros:** `422` (validação, incluindo currency incompatível com country).

---

### POST `/login`

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| `email` | body | string | sim | E-mail cadastrado |
| `password` | body | string | sim | Senha do usuário |

**Auth:** não

**Resposta 200:**

```json
{
  "message": "Login successful.",
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Ana Silva",
    "email": "ana.silva@manager.test",
    "email_verified_at": null,
    "role": "employee",
    "country": "BR",
    "currency": "BRL",
    "created_at": "2026-06-12T10:00:00.000000Z"
  }
}
```

**Erros:** `401` credenciais inválidas; `422` validação.

---

### POST `/logout`

Revoga o token de acesso atual.

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| — | — | — | — | Sem body |

**Auth:** sim

**Resposta 200:**

```json
{
  "message": "Logged out successfully."
}
```

**Erros:** `401` sem token válido.

---

### GET `/user`

Retorna o perfil do usuário autenticado.

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| — | — | — | — | Sem parâmetros |

**Auth:** sim

**Resposta 200:**

```json
{
  "data": {
    "id": 1,
    "name": "Ana Silva",
    "email": "ana.silva@manager.test",
    "email_verified_at": null,
    "role": "employee",
    "country": "BR",
    "currency": "BRL",
    "created_at": "2026-06-12T10:00:00.000000Z"
  }
}
```

**Erros:** `401` sem token válido.

---

### POST `/payment-requests`

Cria uma solicitação de pagamento. Apenas `employee`. A `currency` deve ser igual à moeda local do usuário autenticado. A taxa de câmbio é obtida na criação e armazenada de forma imutável.

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| `title` | body | string | sim | Descrição (max 255) |
| `amount` | body | number | sim | Valor na moeda local (> 0) |
| `currency` | body | string | sim | ISO 4217; deve coincidir com a moeda do usuário |

**Auth:** sim (role `employee`)

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
    "status": "pending",
    "rejection_reason": null,
    "reviewed_at": null,
    "created_at": "2026-06-12T10:00:00.000000Z",
    "updated_at": "2026-06-12T10:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "Ana Silva",
      "email": "ana.silva@manager.test",
      "email_verified_at": null,
      "role": "employee",
      "country": "BR",
      "currency": "BRL",
      "created_at": "2026-06-12T10:00:00.000000Z"
    },
    "reviewed_by": null
  }
}
```

**Erros:** `403` (finance não pode criar), `422` (validação), `503` (taxa de câmbio indisponível).

---

### GET `/payment-requests`

Lista solicitações. Employees veem apenas as próprias; finance vê todas.

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| `status` | query | string | não | Filtro: `pending`, `approved`, `rejected`, `expired` |

**Auth:** sim

**Exemplo:** `GET /api/payment-requests?status=pending`

**Resposta 200:**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Office supplies reimbursement",
      "amount": 595,
      "currency": "BRL",
      "exchange_rate": 5.95,
      "exchange_rate_source": "https://api.exchangerate-api.com",
      "exchange_rate_fetched_at": "2026-06-12T10:00:00.000000Z",
      "amount_eur": 100,
      "status": "pending",
      "rejection_reason": null,
      "reviewed_at": null,
      "created_at": "2026-06-12T10:00:00.000000Z",
      "updated_at": "2026-06-12T10:00:00.000000Z",
      "user": {
        "id": 1,
        "name": "Ana Silva",
        "email": "ana.silva@manager.test",
        "email_verified_at": null,
        "role": "employee",
        "country": "BR",
        "currency": "BRL",
        "created_at": "2026-06-12T10:00:00.000000Z"
      },
      "reviewed_by": null
    }
  ]
}
```

**Erros:** `401`, `422` (status inválido).

---

### GET `/payment-requests/{id}`

Detalhe de uma solicitação. Employee acessa apenas as próprias; finance acessa qualquer uma.

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| `id` | path | integer | sim | ID da solicitação |

**Auth:** sim

**Resposta 200:** mesmo formato de um item em `GET /payment-requests` (objeto em `data`).

**Erros:** `401`, `403` (employee tentando ver solicitação de outro), `404` (ID inexistente).

---

### PATCH `/payment-requests/{id}/approve`

Aprova uma solicitação `pending`. Apenas `finance`.

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| `id` | path | integer | sim | ID da solicitação |

**Auth:** sim (role `finance`)

**Resposta 200:**

```json
{
  "message": "Payment request approved successfully.",
  "data": {
    "id": 1,
    "title": "Office supplies reimbursement",
    "amount": 595,
    "currency": "BRL",
    "exchange_rate": 5.95,
    "exchange_rate_source": "https://api.exchangerate-api.com",
    "exchange_rate_fetched_at": "2026-06-12T10:00:00.000000Z",
    "amount_eur": 100,
    "status": "approved",
    "rejection_reason": null,
    "reviewed_at": "2026-06-12T11:00:00.000000Z",
    "created_at": "2026-06-12T10:00:00.000000Z",
    "updated_at": "2026-06-12T11:00:00.000000Z"
  }
}
```

**Erros:** `403` (employee, ou solicitação que não está `pending`), `404`.

---

### PATCH `/payment-requests/{id}/reject`

Rejeita uma solicitação `pending`. Apenas `finance`.

| Parâmetro | In | Tipo | Obrigatório | Descrição |
|-----------|-----|------|-------------|-----------|
| `id` | path | integer | sim | ID da solicitação |
| `rejection_reason` | body | string | sim | Motivo da rejeição (max 1000) |

**Auth:** sim (role `finance`)

**Resposta 200:**

```json
{
  "message": "Payment request rejected successfully.",
  "data": {
    "id": 1,
    "title": "Office supplies reimbursement",
    "amount": 595,
    "currency": "BRL",
    "exchange_rate": 5.95,
    "exchange_rate_source": "https://api.exchangerate-api.com",
    "exchange_rate_fetched_at": "2026-06-12T10:00:00.000000Z",
    "amount_eur": 100,
    "status": "rejected",
    "rejection_reason": "Missing receipt attachment.",
    "reviewed_at": "2026-06-12T11:00:00.000000Z",
    "created_at": "2026-06-12T10:00:00.000000Z",
    "updated_at": "2026-06-12T11:00:00.000000Z"
  }
}
```

**Erros:** `403`, `409`, `422` (`rejection_reason` ausente), `404`.

---

## Testes

```bash
php artisan test
```

### Testes unitários (funcionalidades críticas)

| Área | Arquivo | Cobertura |
|------|---------|-----------|
| Serviço de solicitações | `tests/Unit/PaymentRequestServiceTest.php` | Criar, aprovar, rejeitar, expirar, conflito 409, escopo employee/finance, stats |
| Provedor de câmbio | `tests/Unit/ExchangerateApiProviderTest.php` | Fetch, provider indisponível, moeda ausente |

### Testes de integração da API

| Endpoint | Arquivo | Cenários |
|----------|---------|----------|
| `POST /register` | `tests/Feature/AuthTest.php` | Registro com token; currency/country inválidos |
| `POST /login` | `tests/Feature/AuthTest.php` | Login; credenciais inválidas (401) |
| `POST /logout` | `tests/Feature/AuthTest.php` | Logout com token |
| `GET /user` | `tests/Feature/AuthTest.php` | Perfil autenticado; 401 sem token |
| `POST /payment-requests` | `tests/Feature/PaymentRequestTest.php` | Criar com taxa; currency inválida; 503; finance 403 |
| `GET /payment-requests` | `tests/Feature/PaymentRequestTest.php` | Escopo employee; filtro finance |
| `GET /payment-requests/{id}` | `tests/Feature/PaymentRequestTest.php` | Show próprio; 403 cross-user |
| `PATCH .../approve` | `tests/Feature/PaymentRequestTest.php` | Aprovar; employee 403; 403 non-pending |
| `PATCH .../reject` | `tests/Feature/PaymentRequestTest.php` | Rejeitar; 422 sem motivo |

### Outros

| Área | Arquivo |
|------|---------|
| Expiração automática (> 48h) | `tests/Feature/ExpirePendingPaymentRequestsTest.php` |
| UI demo (Inertia) | `tests/Feature/Web/PaymentRequestWebTest.php` |

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
