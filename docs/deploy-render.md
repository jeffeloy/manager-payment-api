# Deploy no Render + Neon (grátis)

Alternativa à Oracle VM quando não há capacidade ARM. **Render** hospeda a app; **Neon** hospeda PostgreSQL.

**Trade-off:** plano free do Render **dorme** após ~15 min sem acesso; a 1ª requisição pode levar ~1 min.

---

## Parte 1 — Neon (PostgreSQL)

1. Crie conta em [neon.tech](https://neon.tech)
2. **New project** → região próxima (ex.: AWS São Paulo se disponível)
3. Copie a **Connection string** (formato `postgresql://user:pass@host/db?sslmode=require`)
4. Guarde — será a variável **`DB_URL`** no Render

> Laravel usa `DB_URL` com `DB_CONNECTION=pgsql`.

---

## Parte 2 — Render (Web Service)

### 2.1 Conectar repositório

1. [dashboard.render.com](https://dashboard.render.com) → **New +** → **Blueprint**
2. Conecte GitHub → repositório `manager-payment-api`
3. Render detecta [`render.yaml`](render.yaml) → **Apply**

**Ou manualmente:** **New Web Service** → repo → **Runtime: Docker** → Dockerfile `./Dockerfile`

### 2.2 Variáveis de ambiente

Em **Environment** do serviço:

| Variável | Valor |
|----------|--------|
| `APP_KEY` | Generate (Render) ou `php artisan key:generate --show` local |
| `APP_URL` | `https://SEU-SERVICO.onrender.com` (URL do Render após criar) |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Connection string do Neon (com `?sslmode=require`) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

Template completo: [`.env.render.example`](../.env.render.example)

### 2.3 Deploy

O Render builda o [`Dockerfile`](../Dockerfile):

1. `npm run build` (frontend)
2. `composer install --no-dev`
3. Start: [`scripts/render-start.sh`](../scripts/render-start.sh) → migrate, seed, `artisan serve`

**Health check:** `/up`

Primeiro deploy: **10–20 min**.

---

## Parte 3 — Validar

Substitua pela URL do Render:

| Teste | URL |
|-------|-----|
| Health | `https://SEU-SERVICO.onrender.com/up` |
| UI | `https://SEU-SERVICO.onrender.com/login` |
| API | `https://SEU-SERVICO.onrender.com/api` |

**Logins seed:**

| Perfil | Email | Senha |
|--------|-------|-------|
| Employee | `ana.silva@manager.test` | `password` |
| Finance | `finance.admin@manager.test` | `password` |

---

## Parte 4 — README para o avaliador

```markdown
## Demo ao vivo

- UI: https://SEU-SERVICO.onrender.com/login
- API: https://SEU-SERVICO.onrender.com/api

Nota: após ~15 min sem uso, a 1ª visita pode levar ~1 min (cold start).

| Perfil | Email | Senha |
|--------|-------|-------|
| Employee | ana.silva@manager.test | password |
| Finance | finance.admin@manager.test | password |
```

---

## Passport (opcional)

Chaves OAuth ficam no disco efêmero do Render. O start script gera na 1ª subida. Para persistir entre redeploys, copie para env:

```bash
# Local
php artisan passport:keys --force
```

Cole em Render → `PASSPORT_PRIVATE_KEY` e `PASSPORT_PUBLIC_KEY` (quebras de linha como `\n`).

---

## Troubleshooting

| Problema | Solução |
|----------|---------|
| Build falha no `npm run build` | Ver logs Render; testar `npm run build` local |
| DB connection error | `DB_URL` do Neon com `sslmode=require`; `DB_CONNECTION=pgsql` |
| 502 / timeout no cold start | Normal no free; aguarde ~1 min e recarregue |
| API 401 após redeploy | Tokens antigos invalidados; faça login de novo |
| APP_URL errado | Deve ser URL HTTPS exata do Render; `php artisan config:cache` no redeploy |

---

## Custo

| Serviço | Plano |
|---------|--------|
| Render Web Service | Free |
| Neon Postgres | Free tier |

Sem cartão obrigatório no Neon; Render pode pedir cartão mas free tier não cobra uso normal da demo.
