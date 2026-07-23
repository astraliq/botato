# Telegram: исходящий прокси и входящий webhook-relay

При сервере приложения в РФ часто нужны **два** обхода блокировок:

1. **Исходящие** (PHP → `api.telegram.org`) — SOCKS5 на VPS (`TELEGRAM_PROXY`).
2. **Входящие** (Telegram → ваш webhook) — приём на VPS и проксирование на app (`TELEGRAM_WEBHOOK_BASE_URL`).

```
Telegram ──POST──► VPS (HTTPS, вне РФ) ──► app-сервер в РФ (лучше через WireGuard)
                      ▲
PHP в РФ ──SOCKS5─────┘ ──► api.telegram.org  (getMe / setWebhook / sendMessage)
```

---

## Часть A. SOCKS5 на VPS (исходящие)

### 1. Установка microsocks

```bash
# Debian/Ubuntu
sudo apt update
sudo apt install -y git build-essential
git clone https://github.com/rofl0r/microsocks.git
cd microsocks && make
sudo cp microsocks /usr/local/bin/
```

### 2. Systemd-сервис с логином и паролем

```bash
sudo useradd -r -s /usr/sbin/nologin microsocks || true

sudo tee /etc/systemd/system/microsocks.service >/dev/null <<'EOF'
[Unit]
Description=microsocks SOCKS5 proxy
After=network.target

[Service]
User=microsocks
ExecStart=/usr/local/bin/microsocks -i 0.0.0.0 -p 1080 -u tgproxy -P CHANGE_ME_PASSWORD
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable --now microsocks
sudo systemctl status microsocks
```

Замените `CHANGE_ME_PASSWORD` на свой пароль.

### 3. Firewall: порт 1080 только с IP сервера приложения

```bash
sudo ufw allow from APP_SERVER_PUBLIC_IP to any port 1080 proto tcp
```

Не открывайте `1080/tcp` всему интернету без ограничения по IP.

### 4. Проверка с сервера приложения / из PHP-контейнера

```bash
curl -sS --max-time 20 -x socks5h://tgproxy:CHANGE_ME_PASSWORD@VPS_PUBLIC_IP:1080 \
  -I https://api.telegram.org
```

Ожидается HTTP-ответ (например 302), не timeout.

`socks5h://` предпочтительнее `socks5://`: DNS резолвится на стороне прокси.

---

## Часть B. Webhook-relay на VPS (входящие)

Симптом в `getWebhookInfo`: `last_error_message: Connection timed out`,  
`url` указывает на `https://botato.ru/...` — Telegram не может достучаться до хоста в РФ.

### Схема

1. DNS: `tg-hook.ваш-домен` → A-запись на IP VPS вне РФ.
2. На VPS: Caddy/Nginx принимает HTTPS и проксирует `/webhooks/telegram/*` на app-сервер.
3. В botato: `TELEGRAM_WEBHOOK_BASE_URL=https://tg-hook.ваш-домен`.
4. При подключении бота `setWebhook` ставит URL вида  
   `https://tg-hook.ваш-домен/webhooks/telegram/{channelId}`.
5. Путь и обработчик на app **не меняются** — тот же `/webhooks/telegram/{channelId}`.

### Почему лучше WireGuard, а не `https://botato.ru`

Если VPS сделает `reverse_proxy` на публичный `botato.ru`, запрос Telegram→VPS успешен,  
но **VPS→РФ** тоже может таймаутиться. Надёжнее проксировать на адрес app в сети WireGuard  
(например `10.8.0.10:80`).

Пошаговая инструкция: [`docker/infra/TELEGRAM_WEBHOOK_RELAY.md`](docker/infra/TELEGRAM_WEBHOOK_RELAY.md).  
Пример Caddyfile: [`docker/infra/telegram-webhook-relay.Caddyfile`](docker/infra/telegram-webhook-relay.Caddyfile).

Кратко на VPS:

```bash
# DNS уже указывает tg-hook.botato.ru на этот VPS
# Установка Caddy из официального apt-репозитория — см. TELEGRAM_WEBHOOK_RELAY.md §4
# затем /etc/caddy/Caddyfile и: sudo systemctl reload caddy
```

На app-сервере в WG-клиенте **не** используйте `AllowedIPs = 0.0.0.0/0` — только сеть VPN, например `10.8.0.0/24`.

Проверка с VPS до app по туннелю:

```bash
curl -sS --max-time 10 -X POST "http://10.8.0.10/webhooks/telegram/test" \
  -H "Content-Type: application/json" -d '{}'
```

Ожидается быстрый ответ приложения (часто `403` без secret — маршрут жив).

Проверка «как Telegram» с любой машины:

```bash
curl -sS --max-time 15 -X POST "https://tg-hook.ваш-домен/webhooks/telegram/{channelId}" \
  -H "Content-Type: application/json" -d '{}'
```

---

## Часть C. Переменные botato

В `docker/prod/.env` или `override.env`:

```env
APP_PUBLIC_URL=https://botato.ru

# Исходящие API-вызовы
TELEGRAM_PROXY=socks5h://tgproxy:CHANGE_ME_PASSWORD@VPS_PUBLIC_IP:1080

# Куда Telegram шлёт webhook (домен на VPS). Если пусто — берётся APP_PUBLIC_URL.
TELEGRAM_WEBHOOK_BASE_URL=https://tg-hook.ваш-домен
```

После смены env — **stack deploy** / пересоздание PHP-сервиса (правка файла на диске сама по себе Swarm не обновляет).

Уже подключённых ботов нужно **переподключить** в кабинете (или заново вызвать `setWebhook`),  
чтобы URL сменился с `botato.ru` на `tg-hook...`.

Проверка:

```bash
curl -sS --max-time 20 -x "$TELEGRAM_PROXY" \
  "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

Ожидание: `url` начинается с `TELEGRAM_WEBHOOK_BASE_URL`, `last_error_message` пустой/устаревший,  
после тестового сообщения боту нет свежего `Connection timed out`.

---

## Опционально: SOCKS только внутри WireGuard

1. На VPS SOCKS слушает только `10.8.0.1:1080`.
2. На хосте приложения — WG-клиент с `AllowedIPs = 10.8.0.0/24`.
3. `TELEGRAM_PROXY=socks5h://tgproxy:PASS@10.8.0.1:1080`.

Контейнеры часто не видят WG-интерфейс хоста — тогда оставляйте публичный SOCKS + firewall по IP (часть A).

---

## Что куда идёт

| Трафик | Маршрут |
|--------|---------|
| `getMe` / `setWebhook` / `sendMessage` | PHP → `TELEGRAM_PROXY` → VPS → api.telegram.org |
| Входящий webhook | Telegram → VPS (`TELEGRAM_WEBHOOK_BASE_URL`) → app (лучше WG) |
| Сайт, БД, Redis, почта | напрямую с сервера приложения |

---

## Чеклист

1. VPS: microsocks + firewall на 1080.
2. Из PHP-контейнера: `curl -x "$TELEGRAM_PROXY" -I https://api.telegram.org` — успех.
3. VPS: Caddy relay + DNS `tg-hook` → VPS.
4. VPS → app по WG: POST на `/webhooks/telegram/...` отвечает.
5. В env: `TELEGRAM_PROXY` + `TELEGRAM_WEBHOOK_BASE_URL`.
6. Redeploy PHP, переподключить бота в кабинете.
7. `getWebhookInfo`: URL на `tg-hook`, без свежего timeout; бот отвечает заглушкой.

---

## Безопасность

- Не коммитьте `override.env` с паролями.
- Не открывайте SOCKS без пароля и без ограничения по IP.
- На relay наружу открыты только 80/443 (Let's Encrypt + webhook path).
- Ключи WireGuard и пароли не публикуйте в чатах и репозитории.
