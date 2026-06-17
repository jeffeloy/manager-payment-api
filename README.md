# Manager Payment API

Laravel 12 REST API for managing multi-currency payment requests. Users authenticated via Laravel Passport can submit payments in their local currency, fetch real-time exchange rates, and forward requests for approval by the finance team.

## Live demo

**Application:** [https://manager-payment-api.onrender.com](https://manager-payment-api.onrender.com)

| | URL |
|---|-----|
| **UI (login)** | [https://manager-payment-api.onrender.com/login](https://manager-payment-api.onrender.com/login) |
| **API base** | [https://manager-payment-api.onrender.com/api](https://manager-payment-api.onrender.com/api) |
| **Health** | [https://manager-payment-api.onrender.com/up](https://manager-payment-api.onrender.com/up) |

> **Render free tier:** after ~15 minutes of inactivity, the first visit may take ~1 minute (cold start).

| Role | Email | Password |
|------|-------|----------|
| Employee | `ana.silva@manager.test` | `password` |
| Finance | `finance.admin@manager.test` | `password` |

API documentation: see the [API Documentation](#api-documentation) section below.

## Requirements

- PHP 8.2+
- Composer 2.x
- SQLite (default) or MySQL
- PHP extensions: `bcmath`, `curl`, `dom`, `mbstring`, `sqlite3`, `xml`, `zip`

> To avoid local PHP dependencies, use Docker (see the Docker section below).

## Local setup

```bash
git clone <your-repository> manager-payment-api
cd manager-payment-api

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite

php artisan migrate --seed

php artisan serve
```

The API will be available at `http://localhost:8000/api`.

### MySQL (optional)

In `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manager_payment_api
DB_USERNAME=root
DB_PASSWORD=
```

### Scheduler (automatic expiration)

Requests pending for more than 48 hours are expired by the `payment-requests:expire` command, scheduled to run every hour.

Add to cron:

```bash
* * * * * cd /path/to/manager-payment-api && php artisan schedule:run >> /dev/null 2>&1
```

Manual execution:

```bash
php artisan payment-requests:expire
```

### Docker (alternative)

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan key:generate
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan migrate --seed
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan test
```

With Sail (`compose.yaml`), start with `docker compose up -d --build` and access:

- **API:** `http://localhost:8080/api`
- **Demo UI:** `http://localhost:8080/login`

On first startup, the container automatically builds frontend assets (`npm ci` + `npm run build`) if `public/build/manifest.json` does not exist yet. You do not need to enter the container to run `npm run dev`.

For development with hot reload (optional):

```bash
docker compose exec api npm run dev
```

The UI will use the Vite dev server at `http://localhost:5173`. To skip the automatic build on startup: `SKIP_FRONTEND_BUILD=1 docker compose up -d`.

Manual asset rebuild:

```bash
docker compose exec api npm run build
```

Passport keys (`storage/oauth-*.key`) require restricted permissions (`600` for private, `640` for public). The app fixes this automatically on boot; if it still fails in Insomnia, run inside the container:

```bash
docker compose exec api chmod 600 storage/oauth-private.key
docker compose exec api chmod 640 storage/oauth-public.key
```

### Production deploy (Oracle Cloud Always Free)

Full guide: [docs/deploy-oracle.md](docs/deploy-oracle.md)

Summary on the VM:

```bash
git clone git@github.com:YOUR_USER/manager-payment-api.git
cd manager-payment-api
cp .env.production.example .env
# Edit APP_URL=http://YOUR_IP:8080 and DB_PASSWORD
bash scripts/oracle-bootstrap.sh
bash scripts/oracle-deploy.sh
bash scripts/oracle-validate.sh
```

HTTPS (Phase 2): `sudo bash scripts/oracle-setup-nginx.sh demo.yourdomain.com`

### Production deploy (Render + Neon — recommended if Oracle has no capacity)

Full guide: [docs/deploy-render.md](docs/deploy-render.md)

Summary:

1. **Neon** — create a Postgres project and copy `DB_URL`
2. **Render** — New Blueprint → GitHub repo → [`render.yaml`](render.yaml)
3. Env vars: `APP_URL`, `DB_URL`, `APP_KEY` (see [`.env.render.example`](.env.render.example))
4. Automatic deploy → URL `https://manager-payment-api.onrender.com`

> **Database seeding on Render:** migrations run on every deploy; seeders run **only** when the database has no users (first deploy). To force a re-seed, set `RUN_DB_SEED=true` temporarily in Render env vars.

## Test users (seed)

Default password for all seeded users, including `finance`: `password`

| Name | Email | Role | Country | Currency |
|------|-------|------|---------|----------|
| Ana Silva | ana.silva@manager.test | employee | BR | BRL |
| John Smith | john.smith@manager.test | employee | US | USD |
| Emma Wilson | emma.wilson@manager.test | employee | GB | GBP |
| Yuki Tanaka | yuki.tanaka@manager.test | employee | JP | JPY |
| Hans Mueller | hans.mueller@manager.test | employee | DE | EUR |
| Sofia Rossi | sofia.rossi@manager.test | employee | IT | EUR |
| Finance Admin | finance.admin@manager.test | finance | PT | EUR |
| Finance Reviewer | finance.reviewer@manager.test | finance | PT | EUR |

## Technical decisions

- **Authentication:** Laravel Passport with Personal Access Tokens (`Bearer`).
- **Roles:** `employee` creates/views their own requests; `finance` views all and approves/rejects pending ones.
- **Exchange rate:** fetched on creation via [ExchangeRate-API](https://api.exchangerate-api.com), stored immutably (`exchange_rate`, `exchange_rate_source`, `exchange_rate_fetched_at`).
- **Conversion:** `amount_eur = amount / exchange_rate`, where the rate represents units of local currency per 1 EUR.
- **Expiration:** scheduled command marks `pending` requests older than 48 hours as `expired`.

## Architecture

The application exposes **two HTTP layers** that share the same domain logic (`PaymentRequestService`):

| Layer | Routes | Auth | Response | Purpose |
|-------|--------|------|----------|---------|
| **REST API** | `/api/*` | Passport (`Authorization: Bearer`) | JSON | Primary deliverable (Postman, integrations) |
| **Demo UI** | `/dashboard`, `/payment-requests/*` | Session (web login) | Inertia + redirects | Browser demonstration |

Controllers:

- `App\Http\Controllers\Api\PaymentRequestController` — JSON responses only
- `App\Http\Controllers\Web\PaymentRequestController` — `Inertia::render` and redirects only

Web authentication (`/login`, `/register`) remains in Breeze controllers under `App\Http\Controllers\Auth\*`. API authentication in `App\Http\Controllers\Api\AuthController`.

## API Documentation

Base URL: `/api`

### Authentication

Protected routes require the header:

```
Authorization: Bearer {access_token}
```

The token is returned by `POST /register` or `POST /login` in the `access_token` field. Public routes: `/register`, `/login`. All other routes listed below require authentication.

### Error format

| Status | When |
|--------|------|
| `401` | Missing, invalid, or expired token; invalid login credentials |
| `403` | Authenticated user lacks permission (e.g. employee trying to approve) |
| `404` | Resource not found |
| `409` | State conflict (e.g. approving a request that is not `pending`) |
| `422` | Validation failure |
| `503` | Exchange rate unavailable when creating a request |

**422 example (validation):**

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

**401 example (invalid login credentials):**

```json
{
  "message": "Invalid credentials."
}
```

---

### POST `/register`

Registers an employee (`employee`). The `currency` must match the country (`config/countries.php`).

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| `name` | body | string | yes | Full name (max 255) |
| `email` | body | string | yes | Unique email |
| `password` | body | string | yes | Minimum 8 characters |
| `password_confirmation` | body | string | yes | Must match `password` |
| `country` | body | string | yes | ISO 3166-1 alpha-2 (e.g. `BR`) |
| `currency` | body | string | yes | ISO 4217 (e.g. `BRL`); must match country |

**Supported countries (country → currency):** PT, ES, FR, DE, IE, IT, NL, BE → EUR; GB → GBP; US → USD; BR → BRL; JP → JPY.

**Auth:** no

**201 response:**

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

**Errors:** `422` (validation, including currency incompatible with country).

---

### POST `/login`

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| `email` | body | string | yes | Registered email |
| `password` | body | string | yes | User password |

**Auth:** no

**200 response:**

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

**Errors:** `401` invalid credentials; `422` validation.

---

### POST `/logout`

Revokes the current access token.

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| — | — | — | — | No body |

**Auth:** yes

**200 response:**

```json
{
  "message": "Logged out successfully."
}
```

**Errors:** `401` without a valid token.

---

### GET `/user`

Returns the authenticated user's profile.

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| — | — | — | — | No parameters |

**Auth:** yes

**200 response:**

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

**Errors:** `401` without a valid token.

---

### POST `/payment-requests`

Creates a payment request. `employee` only. The `currency` must match the authenticated user's local currency. The exchange rate is fetched on creation and stored immutably.

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| `title` | body | string | yes | Description (max 255) |
| `amount` | body | number | yes | Amount in local currency (> 0) |
| `currency` | body | string | yes | ISO 4217; must match the user's currency |

**Auth:** yes (role `employee`)

**201 response:**

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

**Errors:** `403` (finance cannot create), `422` (validation), `503` (exchange rate unavailable).

---

### GET `/payment-requests`

Lists payment requests. Employees see only their own; finance sees all.

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| `status` | query | string | no | Filter: `pending`, `approved`, `rejected`, `expired` |

**Auth:** yes

**Example:** `GET /api/payment-requests?status=pending`

**200 response:**

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

**Errors:** `401`, `422` (invalid status).

---

### GET `/payment-requests/{id}`

Payment request detail. Employees can access only their own; finance can access any.

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| `id` | path | integer | yes | Request ID |

**Auth:** yes

**200 response:** same format as a single item in `GET /payment-requests` (object in `data`).

**Errors:** `401`, `403` (employee trying to view another user's request), `404` (non-existent ID).

---

### PATCH `/payment-requests/{id}/approve`

Approves a `pending` request. `finance` only.

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| `id` | path | integer | yes | Request ID |

**Auth:** yes (role `finance`)

**200 response:**

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

**Errors:** `403` (employee, or request not `pending`), `404`.

---

### PATCH `/payment-requests/{id}/reject`

Rejects a `pending` request. `finance` only.

| Parameter | In | Type | Required | Description |
|-----------|-----|------|----------|-------------|
| `id` | path | integer | yes | Request ID |
| `rejection_reason` | body | string | yes | Rejection reason (max 1000) |

**Auth:** yes (role `finance`)

**200 response:**

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

**Errors:** `403`, `409`, `422` (missing `rejection_reason`), `404`.

---

## Tests

```bash
php artisan test
```

### Unit tests (critical functionality)

| Area | File | Coverage |
|------|------|----------|
| Payment request service | `tests/Unit/PaymentRequestServiceTest.php` | Create, approve, reject, expire, 409 conflict, employee/finance scope, stats |
| Exchange rate provider | `tests/Unit/ExchangerateApiProviderTest.php` | Fetch, unavailable provider, missing currency |

### API integration tests

| Endpoint | File | Scenarios |
|----------|------|-----------|
| `POST /register` | `tests/Feature/AuthTest.php` | Registration with token; invalid currency/country |
| `POST /login` | `tests/Feature/AuthTest.php` | Login; invalid credentials (401) |
| `POST /logout` | `tests/Feature/AuthTest.php` | Logout with token |
| `GET /user` | `tests/Feature/AuthTest.php` | Authenticated profile; 401 without token |
| `POST /payment-requests` | `tests/Feature/PaymentRequestTest.php` | Create with rate; invalid currency; 503; finance 403 |
| `GET /payment-requests` | `tests/Feature/PaymentRequestTest.php` | Employee scope; finance filter |
| `GET /payment-requests/{id}` | `tests/Feature/PaymentRequestTest.php` | Own show; 403 cross-user |
| `PATCH .../approve` | `tests/Feature/PaymentRequestTest.php` | Approve; employee 403; 403 non-pending |
| `PATCH .../reject` | `tests/Feature/PaymentRequestTest.php` | Reject; 422 without reason |

### Other

| Area | File |
|------|------|
| Automatic expiration (> 48h) | `tests/Feature/ExpirePendingPaymentRequestsTest.php` |
| Demo UI (Inertia) | `tests/Feature/Web/PaymentRequestWebTest.php` |

## Relevant structure

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
