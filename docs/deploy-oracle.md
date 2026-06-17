# Deploy na Oracle Cloud (Always Free)

Guia para publicar a aplicação em uma VM **Ampere A1 Always Free** com Docker Compose.

**Fase 1:** `http://IP:8080` (HTTP)  
**Fase 2:** `https://seu-dominio.com` (Nginx + Certbot)

---

## Parte 1 — Conta e VM Oracle

### 1.1 Criar conta

1. [oracle.com/cloud/free](https://www.oracle.com/cloud/free/)
2. Escolha **Home Region** (ex.: `sa-saopaulo-1`) — não pode mudar depois
3. Use apenas recursos **Always Free eligible**

### 1.2 Criar VM

**Compute → Instances → Create instance**

| Campo | Valor |
|-------|-------|
| Name | `manager-payment-api` |
| Image | Ubuntu 22.04 ou 24.04 |
| Shape | **Ampere** → `VM.Standard.A1.Flex` (Always Free) |
| OCPUs / RAM | 2 OCPUs + 12 GB |
| Boot volume | 50 GB |
| SSH key | Upload da sua chave pública |
| Public IP | Assign a public IPv4 address |

Se falhar por **capacidade ARM**, tente outra Availability Domain ou horário diferente.

### 1.3 Encontrar o IP público e acessar a VM (SSH)

#### Onde ver o IP

1. Console Oracle → menu ☰ → **Compute** → **Instances**
2. Clique na instância `manager-payment-api`
3. Em **Instance access** / **Primary VNIC**, copie o **Public IP address**  
   Exemplo: `123.45.67.89`

Se **Public IP** estiver vazio com a VM **Running**, anexe um IP manualmente (passo a passo abaixo).

#### Anexar Public IPv4 em uma VM já criada (sem IP)

1. **Compute** → **Instances** → clique na sua instância
2. Aba **Attached VNICs** → clique na **Subnet** (link azul)
3. Verifique se a subnet permite IP público:
   - Aba **Security** ou **Subnet information**
   - **Prohibit public IP on VNIC** deve estar **desmarcado** (No / false)
   - Se estiver prohibido: **Edit subnet** → desmarque **Prohibit public IP on VNIC** → Save
4. Volte à instância → **Attached VNICs** → nos três pontinhos **⋮** do VNIC → **Edit VNIC**
   - Ou clique no **Private IP** → página **IPv4 addresses**
5. Em **Public IP type** / **Public IP address**, escolha:
   - **Ephemeral public IP** (grátis, muda se parar/reiniciar a VM — ok para demo)
   - ou **Reserved public IP** (grátis no Always Free, IP fixo)
6. **Update** / **Assign**

**Caminho alternativo (Reserved Public IP):**

1. Menu ☰ → **Networking** → **IP management** → **Reserved public IPs**
2. **Reserve public IP address** → Reserve
3. Volte à instância → VNIC → **IPv4 addresses** → **Assign public IP** → selecione o IP reservado

#### Conferir Internet Gateway (rede precisa sair para internet)

1. **Networking** → **Virtual cloud networks** → sua VCN
2. **Internet Gateways** — deve existir um com status **Available**
3. Se não existir: **Create Internet Gateway** → Attach to VCN
4. **Route Tables** → **Default Route Table for vcn-...**
5. Deve haver regra: **Destination** `0.0.0.0/0` → **Target** Internet Gateway

Sem Internet Gateway + rota `0.0.0.0/0`, mesmo com IP público a VM não responde de fora.

Depois de anexar, atualize a página da instância — o **Public IP** deve aparecer em 1–2 minutos.

#### Chave SSH — você precisa de um par de chaves

**No seu Linux (sua máquina local):**

```bash
# Se ainda não tiver chave
ssh-keygen -t ed25519 -C "oracle-vm" -f ~/.ssh/oracle_manager -N ""

# Mostrar a chave PÚBLICA (foi essa que deveria ter sido colada na Oracle ao criar a VM)
cat ~/.ssh/oracle_manager.pub
```

Na criação da VM, em **Add SSH keys**, você colou o conteúdo de `oracle_manager.pub`.

**Conectar:**

```bash
ssh -i ~/.ssh/oracle_manager ubuntu@SEU_IP_PUBLICO
```

- Usuário padrão Ubuntu na Oracle: **`ubuntu`**
- Se usar a chave default: `ssh ubuntu@SEU_IP_PUBLICO`

**Erros comuns:**

| Erro | Solução |
|------|---------|
| `Permission denied (publickey)` | Chave errada ou não foi cadastrada na VM; use `-i` apontando para a chave correta |
| `Connection timed out` | Security List sem porta 22 **ou** IP errado **ou** VM parada |
| `WARNING: UNPROTECTED PRIVATE KEY` | `chmod 600 ~/.ssh/oracle_manager` |

#### Alternativa sem SSH local: Cloud Shell + Instance Console

Se SSH local não funcionar:

1. Na página da instância → botão **Console connection** (serial console no browser)
2. Ou menu ☰ → **Developer services** → **Cloud Shell** (terminal no browser da Oracle)

A serial console pede login — para Ubuntu cloud images às vezes é usuário `ubuntu` (sem senha se só SSH key). O caminho mais confiável continua sendo SSH com a chave certa.

---

### 1.4 Firewall (Security List) — passo a passo

A Oracle tem **dois** firewalls: **Security List** (rede) e **iptables/ufw** (dentro da VM). Você precisa liberar a porta **22** na Security List para conseguir SSH.

#### Caminho mais fácil (a partir da instância)

1. **Compute** → **Instances** → clique na sua instância
2. Aba **Attached VNICs** (ou seção **Primary VNIC**)
3. Clique no **nome da Subnet** (link azul, ex.: `subnet-20260317-...`)
4. Na página da Subnet, clique na **Security List** (ex.: `Default Security List for vcn-...`)
5. Aba **Security rules** → **Add ingress rules**

Repita **Add ingress rules** para cada linha (ou uma regra com port range se preferir):

| Campo | Valor (porta 22) | Valor (porta 8080) |
|-------|------------------|---------------------|
| **Stateless** | Desmarcado (No) | Desmarcado (No) |
| **Source Type** | CIDR | CIDR |
| **Source CIDR** | `0.0.0.0/0` | `0.0.0.0/0` |
| **IP Protocol** | TCP | TCP |
| **Source port range** | (vazio) | (vazio) |
| **Destination port range** | `22` | `8080` |
| **Description** | SSH | App HTTP |

Para Fase 2 (HTTPS), adicione também portas **80** e **443**.

Clique **Add ingress rules** → **Save**.

#### Caminho pelo menu Networking

1. Menu ☰ → **Networking** → **Virtual cloud networks**
2. Clique na VCN da sua instância (geralmente `vcn-...`)
3. **Security Lists** (menu esquerdo) → **Default Security List**
4. **Add ingress rules** (mesma tabela acima)

#### Conferir se a regra existe

Na Security List, aba **Security rules**, deve aparecer algo como:

```
0.0.0.0/0  TCP  22   (SSH)
0.0.0.0/0  TCP  8080 (App)
```

#### Depois do SSH: firewall dentro da VM (opcional)

Quando já estiver logado:

```bash
sudo ufw allow 22/tcp
sudo ufw allow 8080/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
sudo ufw status
```

> **Nota:** Se não conseguir SSH, o problema quase sempre é Security List **sem porta 22**, não o ufw (ufw só importa depois que você já entrou).

#### Testar conectividade

Na **sua máquina local**:

```bash
# Porta SSH aberta?
nc -zv SEU_IP_PUBLICO 22

# Depois do deploy — porta da app
nc -zv SEU_IP_PUBLICO 8080
```

Se `22` der **timeout**, volte à Security List. Se der **succeeded**, tente `ssh ubuntu@SEU_IP_PUBLICO`.

---

### 1.5 Custo zero

- Shape **somente** `VM.Standard.A1.Flex` Always Free
- Não criar Load Balancer, NAT Gateway, MySQL HeatWave ou VMs x86 pagas
- Billing → Cost Analysis deve mostrar **$0**

---

## Parte 2 — Bootstrap na VM

Conecte via SSH (veja [1.3](#13-encontrar-o-ip-público-e-acessar-a-vm-ssh)):

```bash
ssh -i ~/.ssh/oracle_manager ubuntu@SEU_IP_PUBLICO
```

### Opção A — Script automático

```bash
REPO_URL=git@github.com:SEU_USUARIO/manager-payment-api.git \
  bash -c "$(curl -fsSL https://raw.githubusercontent.com/SEU_USUARIO/manager-payment-api/main/scripts/oracle-bootstrap.sh)"
```

### Opção B — Manual

```bash
git clone git@github.com:SEU_USUARIO/manager-payment-api.git
cd manager-payment-api
cp .env.production.example .env
nano .env   # APP_URL=http://SEU_IP:8080, DB_PASSWORD forte
bash scripts/oracle-bootstrap.sh   # instala Docker + UFW se necessário
```

---

## Parte 3 — Deploy (Fase 1)

```bash
cd ~/manager-payment-api
bash scripts/oracle-deploy.sh
```

O script executa:

- `docker compose -f compose.yaml -f compose.prod.yaml up -d --build`
- `php artisan key:generate`, `migrate --seed`, cache
- Permissões Passport

Validação:

```bash
bash scripts/oracle-validate.sh
```

Ou manualmente:

| Teste | URL |
|-------|-----|
| Health | `http://SEU_IP:8080/up` |
| UI | `http://SEU_IP:8080/login` |
| API | `http://SEU_IP:8080/api` |

**Credenciais seed:** `ana.silva@manager.test` / `password` (employee), `finance.admin@manager.test` / `password` (finance)

---

## Parte 4 — Fase 2 (HTTPS)

1. Aponte um **A record** do domínio para o IP da VM
2. Na VM:

```bash
sudo bash scripts/oracle-setup-nginx.sh demo.seudominio.com
```

3. Atualize o README com a URL HTTPS para o avaliador

---

## Parte 5 — Scheduler (opcional)

```bash
crontab -e
```

```cron
* * * * * cd ~/manager-payment-api && docker compose -f compose.yaml -f compose.prod.yaml exec -T api php artisan schedule:run >> /dev/null 2>&1
```

---

## Troubleshooting

| Problema | Solução |
|----------|---------|
| `groupadd: invalid group ID 'sail'` | `WWWGROUP=1000` no `.env` |
| UI 500 / Vite manifest | `docker compose exec api npm run build && docker compose restart api` |
| DB connection refused | `DB_HOST=mysql`, aguardar MySQL healthy |
| API 401 com token | `chmod 600/640` nas chaves Passport |
| Porta 8080 inacessível | Security List Oracle + `sudo ufw allow 8080` |
| SSH timeout | Security List: ingress TCP 22; IP público anexado; Internet Gateway + rota 0.0.0.0/0 |
| Sem Public IP (Running) | VNIC → Edit → Ephemeral/Reserved public IP; subnet sem "Prohibit public IP" |
| Permission denied (publickey) | `ssh -i ~/.ssh/SUA_CHAVE ubuntu@IP`; chave deve ser a mesma cadastrada na VM |

---

## Arquivos relacionados

| Arquivo | Descrição |
|---------|-----------|
| [`.env.production.example`](../.env.production.example) | Template `.env` produção |
| [`compose.prod.yaml`](../compose.prod.yaml) | Override Docker (sem Xdebug, MySQL/Redis só interno) |
| [`scripts/oracle-bootstrap.sh`](../scripts/oracle-bootstrap.sh) | Docker + clone + `.env` |
| [`scripts/oracle-deploy.sh`](../scripts/oracle-deploy.sh) | Build + migrate + cache |
| [`scripts/oracle-setup-nginx.sh`](../scripts/oracle-setup-nginx.sh) | Nginx + Certbot |
| [`scripts/oracle-validate.sh`](../scripts/oracle-validate.sh) | Smoke tests HTTP |
