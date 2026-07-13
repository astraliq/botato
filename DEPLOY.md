# Деплой на VPS через GitHub Actions

Инструкция по настройке CI/CD для проекта **Botato**: автоматический деплой на удалённый VPS после успешных тестов в ветке `main`.

## Архитектура

```
Internet
   │
   ▼
Caddy (HTTPS, Let's Encrypt)
   │
   ▼
FrankenPHP (2 реплики, rolling update)
   │
   ▼
PostgreSQL 16
```

| Компонент | Назначение |
|-----------|------------|
| GitHub Actions `build.yml` | CI: тесты и деплой на push в `main` |
| Docker Swarm | Оркестрация на сервере |
| Caddy Docker Proxy | Автоматический HTTPS и маршрутизация |
| GHCR | Хранение Docker-образов (`ghcr.io/astraliq/botato`) |

### Схема CI/CD

```
Push в main
      │
      ▼
build.yml
      ├─ codeception (тесты)
      └─ deploy (VPS, после успешных тестов)
```

## Требования

- VPS с Ubuntu 22.04+ (или другой Linux)
- SSH-доступ к серверу (root или отдельный пользователь)
- Домен с A-записью на IP сервера
- Репозиторий на GitHub: [astraliq/botato](https://github.com/astraliq/botato)

## Файлы деплоя в репозитории

| Файл | Описание |
|------|----------|
| `.github/workflows/build.yml` | CI: тесты и деплой на VPS |
| `docker/prod/compose.yml` | Prod-стек: PHP + PostgreSQL |
| `docker/prod/.env.example` | Шаблон переменных приложения |
| `docker/prod/override.env.example` | Шаблон секретов (пароль SMTP и т.п.) |
| `docker/infra/caddy-stack.yml` | Одноразовый стек Caddy на VPS |
| `Makefile` | Команды `prod-build`, `prod-push`, `prod-deploy` для ручного деплоя |

---

## Часть 1. Подготовка VPS

Подключитесь к серверу через PuTTY (или SSH) и выполните команды ниже.

### 1.1. Установка Docker

```bash
curl -fsSL https://get.docker.com | sh
systemctl enable --now docker
```

### 1.2. Инициализация Docker Swarm

```bash
# Замените на публичный IP сервера
docker swarm init --advertise-addr ВАШ_IP
```

### 1.3. Сеть для reverse proxy

```bash
docker network create --driver=overlay caddy_public
```

### 1.4. Запуск Caddy

Скопируйте файл `docker/infra/caddy-stack.yml` на сервер (WinSCP, `scp` и т.п.), затем:

```bash
docker stack deploy -c caddy-stack.yml caddy
```

Caddy автоматически подхватывает сервисы с labels `caddy` и `caddy.reverse_proxy` и выпускает SSL-сертификаты через Let's Encrypt.

### 1.5. Firewall

```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### 1.6. DNS

Создайте A-запись домена (например, `botato.example.com`) → IP VPS.

> Let's Encrypt не выдаст сертификат, пока DNS не указывает на сервер и порты 80/443 доступны из интернета.

---

## Часть 2. SSH-ключ для GitHub Actions

### 2.1. Генерация ключа (Windows, PowerShell)

Ключ для CI **без пароля** (`-N ""`):

```powershell
ssh-keygen -t ed25519 -C "github-actions-deploy" -f $env:USERPROFILE\.ssh\botato_deploy -N ""
```

Если ключ уже создан с паролем — снимите его (введите старый пароль, новый оставьте пустым):

```powershell
ssh-keygen -p -f $env:USERPROFILE\.ssh\botato_deploy
```

Проверка: команда ниже **не должна** спрашивать passphrase:

```powershell
ssh-keygen -y -f $env:USERPROFILE\.ssh\botato_deploy
```

### 2.2. Добавление публичного ключа на VPS

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo "содержимое botato_deploy.pub" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

Приватный ключ понадобится для GitHub Secret (см. ниже).

### 2.3. Секрет для GitHub (рекомендуется base64)

При копировании многострочного ключа в GitHub Secrets с Windows он часто **ломается**. Надёжный способ — **base64**:

PowerShell:

```powershell
[Convert]::ToBase64String([IO.File]::ReadAllBytes("$env:USERPROFILE\.ssh\botato_deploy"))
```

Скопируйте **одну строку** вывода в секрет **`VPS_SSH_PRIVATE_KEY_B64`**.

Проверка локально (должна вывести публичный ключ):

```powershell
ssh-keygen -y -f $env:USERPROFILE\.ssh\botato_deploy
```

Проверка round-trip base64 (должна вывести тот же публичный ключ):

```powershell
$path = "$env:USERPROFILE\.ssh\botato_deploy"
$testPath = "$env:TEMP\botato_test"
$b64 = [Convert]::ToBase64String([IO.File]::ReadAllBytes($path))
[IO.File]::WriteAllBytes($testPath, [Convert]::FromBase64String($b64))
ssh-keygen -y -f $testPath
Remove-Item $testPath
```

Ключ **без пароля** (`-N ""` при генерации). С паролем CI не сможет его использовать.

> Рекомендуется отдельный пользователь `deploy` в группе `docker` вместо `root`. Для `root` шаги те же.

---

## Часть 3. Секреты в GitHub

Откройте **Settings → Secrets and variables → Actions → New repository secret** и добавьте:

| Secret | Обязательный | Описание |
|--------|:------------:|----------|
| `VPS_HOST` | да | IP или домен VPS |
| `VPS_USER` | да | `root` или `deploy` |
| `VPS_SSH_PRIVATE_KEY_B64` | да* | Base64 приватного ключа (рекомендуется, см. выше) |
| `VPS_SSH_PRIVATE_KEY` | да* | Многострочный приватный ключ (альтернатива base64) |
| `PROD_HOST` | да | Домен приложения, напр. `botato.example.com` |
| `DB_PASSWORD` | да | Пароль PostgreSQL |
| `PROD_ENV_FILE` | да | Полное содержимое `docker/prod/.env` |
| `PROD_OVERRIDE_ENV_FILE` | нет | Содержимое `docker/prod/override.env` (SMTP-пароль и др.) |
| `GHCR_PULL_TOKEN` | нет | PAT с `read:packages` для приватного репозитория |

\* Для SSH нужен **один** из секретов: `VPS_SSH_PRIVATE_KEY_B64` (предпочтительно) или `VPS_SSH_PRIVATE_KEY`.

### Пример `PROD_ENV_FILE`

Скопируйте и адаптируйте из `docker/prod/.env.example`:

```env
APP_ENV=prod
APP_DEBUG=false
SERVER_NAME=:80

DB_DSN=pgsql:host=postgres;port=5432;dbname=botato
DB_USER=app
DB_PASSWORD=ваш_сложный_пароль

MAILER_FROM=botato@example.com
MAILER_SMTP_HOST=smtp.example.com
MAILER_SMTP_PORT=465
```

### Пример `PROD_OVERRIDE_ENV_FILE`

Секреты, которые не должны попадать в основной env-файл:

```env
MAILER_PASSWORD=пароль_приложения_smtp
```

> `DB_PASSWORD` в `PROD_ENV_FILE` и секрет `DB_PASSWORD` должны совпадать: пароль нужен и приложению, и контейнеру PostgreSQL.

### Настройка почты

Приложение поддерживает два варианта:

1. **Отдельные переменные** (рекомендуется): `MAILER_FROM`, `MAILER_SMTP_HOST`, `MAILER_SMTP_PORT` в `PROD_ENV_FILE`, пароль — в `PROD_OVERRIDE_ENV_FILE`.
2. **Готовый DSN**: переменная `MAILER_DSN=smtps://user:pass@host:465` в `PROD_ENV_FILE`.

---

## PostgreSQL на сервере

Отдельно ставить PostgreSQL через `apt` **не нужно** — база поднимается в Docker вместе с приложением.

| Режим | Как запускается | Хост для приложения |
|-------|-----------------|---------------------|
| Dev (`make up`) | контейнер `botato-postgres-1` | `postgres` |
| Production (Swarm) | сервис `botato_postgres` | `postgres` |

Данные хранятся в Docker volume `postgres_data` и не теряются при перезапуске контейнеров.

### Dev: PostgreSQL через `make up`

На сервере в каталоге проекта:

```bash
cd /var/www/botato

# docker/dev/.env — если ещё не создан (см. README или пример ниже)
make up
make yii migrate/up
```

Проверка:

```bash
docker compose -f docker/compose.yml -f docker/dev/compose.yml ps
docker compose -f docker/compose.yml -f docker/dev/compose.yml exec postgres psql -U app -d botato -c '\dt'
```

Подключение снаружи (DBeaver, pgAdmin):

```
Host: IP_сервера
Port: 5432
Database: botato
User: app
Password: secret   # из docker/dev/.env → DB_PASSWORD
```

> В dev пароль по умолчанию `secret`. Для production используйте сложный пароль.

### Production: PostgreSQL через Docker Swarm

PostgreSQL деплоится вместе с приложением (`docker/prod/compose.yml`). Пароль задаётся:

- секрет GitHub `DB_PASSWORD` — для контейнера PostgreSQL;
- переменная `DB_PASSWORD` в `PROD_ENV_FILE` — для PHP-приложения.

Оба значения **должны совпадать**.

Первый ручной деплoy на VPS (если CI ещё не настроен):

```bash
cd /var/www/botato

cat > docker/.env << 'EOF'
STACK_NAME=botato
PROD_HOST=botato.ru
PROD_SSH="ssh://localhost"
IMAGE=ghcr.io/astraliq/business-assistant
IMAGE_TAG=latest
EOF

cp docker/prod/.env.example docker/prod/.env
# задайте DB_PASSWORD в docker/prod/.env

export POSTGRES_PASSWORD='ваш_пароль'
export IMAGE=ghcr.io/astraliq/business-assistant
export IMAGE_TAG=latest
export PROD_HOST=botato.ru
export STACK_NAME=botato

docker stack deploy \
  --with-registry-auth \
  -c docker/compose.yml \
  -c docker/prod/compose.yml \
  botato
```

Проверка:

```bash
docker service ls | grep botato
docker service logs botato_postgres --tail 30
docker service ps botato_postgres
```

Миграции после деплоя:

```bash
docker run --rm \
  --network botato_internal \
  --env-file /opt/botato/docker/prod/.env \
  ghcr.io/astraliq/business-assistant:latest \
  php ./yii migrate/up --interactive=0
```

### Бэкап PostgreSQL (production)

```bash
# создать дамп
docker exec $(docker ps -q -f name=botato_postgres) \
  pg_dump -U app botato > botato_$(date +%F).sql

# восстановить
cat botato_2026-07-12.sql | docker exec -i $(docker ps -q -f name=botato_postgres) \
  psql -U app -d botato
```

---

## Часть 4. Первый деплой

1. Закоммитьте и запушьте изменения в ветку `main`.
2. Workflow **build** запустит тесты, затем job **Deploy to VPS**.

Ручной деплой: **Actions → build → Run workflow** (только push/main или workflow_dispatch запускает deploy).

### Проверка на VPS

```bash
docker service ls
docker service logs botato_php --tail 50
docker service logs botato_postgres --tail 50
docker service logs caddy_caddy --tail 50
```

Приложение должно открываться по `https://ваш-домен`.

---

## Часть 5. Ручной деплой с локальной машины

Альтернатива GitHub Actions — команды из `Makefile`.

### 5.1. Настройка `docker/.env`

```env
STACK_NAME=botato
PROD_HOST=botato.example.com
PROD_SSH="ssh://root@ВАШ_IP"
IMAGE=ghcr.io/astraliq/botato
IMAGE_TAG=latest
```

### 5.2. SSH-конфиг (опционально)

В `~/.ssh/config`:

```
Host docker-web
    HostName ВАШ_IP
    User root
    IdentityFile ~/.ssh/botato_deploy
```

### 5.3. Создание `docker/prod/.env`

```bash
cp docker/prod/.env.example docker/prod/.env
# Отредактируйте значения
```

При необходимости создайте `docker/prod/override.env` по образцу `docker/prod/override.env.example`.

### 5.4. Деплой

```bash
make prod-build
make prod-push
make prod-deploy
```

---

## Устранение неполадок

| Проблема | Решение |
|----------|---------|
| Нет SSL-сертификата | Проверьте DNS (A-запись), порты 80/443, логи `caddy_caddy` |
| `network caddy_public not found` | `docker network create --driver=overlay caddy_public` |
| Образ не скачивается с GHCR | Добавьте `GHCR_PULL_TOKEN` или сделайте пакет public |
| Rollback при деплое | `docker service ps botato_php --no-trunc` |
| Ошибка миграций | Проверьте `DB_DSN`, логи `botato_postgres`, доступность сети `botato_internal` |
| Письма не отправляются | Проверьте `MAILER_*` / `MAILER_DSN` и `MAILER_PASSWORD` в override |

### Полезные команды

```bash
# Логи приложения в реальном времени
docker service logs -f botato_php

# Статус задач сервиса
docker service ps botato_php --no-trunc

# Масштабирование
docker service scale botato_php=3
```

---

## Безопасность

- Не коммитьте `docker/prod/.env` и `docker/prod/override.env` — они в `.gitignore`.
- Используйте GitHub Secrets для prod-переменных в CI/CD.
- Для приватного репозитория настройте `GHCR_PULL_TOKEN` с минимальными правами (`read:packages`).
- Регулярно обновляйте систему: `apt update && apt upgrade -y`.

---

## Ссылки

- [Документация Yii3: Docker](https://yiisoft.github.io/docs/guide/tutorial/docker.html)
- [Документация Yii3: Docker Swarm + Caddy](https://yiisoft.github.io/docs/ru/cookbook/deployment/docker-swarm-caddy.html)
- [Caddy Docker Proxy](https://github.com/lucaslorentz/caddy-docker-proxy)
