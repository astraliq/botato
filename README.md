# Botato

ИИ-ассистент для частных специалистов: лендинг, регистрация по email, личный кабинет.

## Стек

- PHP 8.3, Yii3 (yiisoft/app)
- PostgreSQL 16
- FrankenPHP + Caddy (Docker)
- Mailpit (локальная почта)
- Clean Architecture: Domain → Application → Infrastructure → Web

## Быстрый старт (WSL + Docker)

Требования: Docker Desktop с WSL2 backend, Docker Compose 2.24+

Перейдите в папку проекта в WSL (пример):

```bash
cd ~/botato
```

### Через Make (рекомендуется)

```bash
make composer update
make up
make yii migrate/up
```

### Через docker compose напрямую

Compose подхватывает `docker/.env` (там уже `UID=1000`, `GID=1000`). Если ваш WSL-пользователь другой — поправьте или экспортируйте:

```bash
export UID=$(id -u) GID=$(id -g)

docker compose -f docker/compose.yml -f docker/dev/compose.yml run --rm php composer update
docker compose -f docker/compose.yml -f docker/dev/compose.yml run --rm php php ./yii migrate/up
docker compose -f docker/compose.yml -f docker/dev/compose.yml up -d --build
```

Имя Docker-проекта: **botato** (контейнеры `botato-php-1`, `botato-postgres-1`, `botato-mailpit-1`).

### URL

| Сервис | URL |
|--------|-----|
| Приложение | http://botato.local:8081 или http://localhost:8081 |
| Mailpit (письма с кодами) | http://localhost:8025 |
| PostgreSQL | localhost:5432 (user: `app`, password: `secret`, db: `botato`) |

> Порт приложения задаётся в `docker/.env` (`DEV_PORT=8081`).

### Локальный домен `botato.local` (Windows)

1. В `C:\Windows\System32\drivers\etc\hosts` (от администратора):

```
127.0.0.1       botato.local
```

2. В `docker/dev/.env` для dev оставьте `SERVER_NAME=:80` — так Caddy слушает HTTP без редиректа на HTTPS (порт 443 в compose не проброшен).

3. Перезапустите контейнеры:

```bash
make down && make up
```

4. Откройте в браузере Windows: **http://botato.local:8081**

Чтобы открывать **без порта** (`http://botato.local`), в `docker/.env` поставьте `DEV_PORT=80` и перезапустите стек (порт 80 на Windows должен быть свободен).

## Полезные команды (WSL)

```bash
# Остановить
make down
# или
docker compose -f docker/compose.yml -f docker/dev/compose.yml down

# Пересобрать образ
make build

# Shell в контейнере
make shell

# Yii console
make yii migrate/up
make yii list
```

## Переменные окружения

Файл `docker/dev/.env`:

```
DB_DSN=pgsql:host=postgres;port=5432;dbname=botato
DB_USER=app
DB_PASSWORD=secret
MAILER_DSN=smtp://mailpit:1025
MAILER_FROM=noreply@botato.local
```

Docker project name в `docker/.env`:

```
STACK_NAME=botato
```

## Тест входа вручную

1. Откройте http://localhost:8081
2. Нажмите **Войти**, введите email
3. Откройте http://localhost:8025 — скопируйте 6-значный код из письма
4. Введите код в модалке → попадёте в `/cabinet`

## Если уже был запущен старый проект

Старые контейнеры `docker-*` или `botato-*` нужно остановить:

```bash
docker compose -p botato -f docker/compose.yml -f docker/dev/compose.yml down
docker compose -p docker -f docker/compose.yml -f docker/dev/compose.yml down
```

Затем поднимите заново с `STACK_NAME=botato`.
