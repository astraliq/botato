# Caddy relay: Telegram webhook на VPS (`tg-hook.botato.ru`)

Приём входящих webhook от Telegram на VPS **вне РФ** и проксирование на app-сервер.  
Нужно, когда `getWebhookInfo` показывает `last_error_message: Connection timed out` для URL на `botato.ru`.

В botato:

```env
TELEGRAM_WEBHOOK_BASE_URL=https://tg-hook.botato.ru
# если 443 на VPS занят (Amnezia Xray и т.п.) — см. вариант C:
# TELEGRAM_WEBHOOK_BASE_URL=https://tg-hook.botato.ru:8443
```

Пример Caddyfile: [`telegram-webhook-relay.Caddyfile`](telegram-webhook-relay.Caddyfile).  
Общая схема (SOCKS + relay): [`../../TELEGRAM_PROXY.md`](../../TELEGRAM_PROXY.md).

```
Telegram ──POST──► https://tg-hook.botato.ru/webhooks/telegram/{channelId}
                         │
                         ▼
                    Caddy на VPS (147.45.230.203)
                         │
         ┌───────────────┴───────────────┐
         ▼                               ▼
  WireGuard → app:80              https://botato.ru
  (предпочтительно)               (если с VPS стабильно открывается)
```

Путь `/webhooks/telegram/...` на app **не меняется**. Меняется только публичный хост, который видит Telegram.

---

## 1. DNS

В зоне `botato.ru`:

| Тип | Имя | Значение |
|-----|-----|----------|
| A | `tg-hook` | публичный IP VPS (например `147.45.230.203`) |

Проверка:

```bash
dig +short tg-hook.botato.ru
```

Должен вернуться IP VPS.

---

## 2. WireGuard (для варианта A)

На **хосте приложения** (не full tunnel):

```ini
[Interface]
PrivateKey = <client_private_key>
Address = 10.8.0.10/24

[Peer]
PublicKey = <server_public_key>
PresharedKey = <preshared_key>
AllowedIPs = 10.8.0.0/24
Endpoint = 147.45.230.203:51820
PersistentKeepalive = 25
```

На VPS у peer app-сервера: `AllowedIPs = 10.8.0.10/32`.

**Не используйте** `AllowedIPs = 0.0.0.0/0` на app-сервере — уйдёт весь трафик хоста в VPN.

Проверка с VPS:

```bash
ping -c 3 10.8.0.10
```

На app по адресу WG должен отвечать HTTP (часто порт 80 на хосте / edge). Если Swarm слушает только overlay и с VPS `10.8.0.10:80` недоступен — используйте **вариант B**.

---

## 3. Выбор бэкенда: проверка с VPS

```bash
# Вариант A — WireGuard
curl -sS --max-time 10 -o /dev/null -w "%{http_code}\n" \
  -X POST "http://10.8.0.10/webhooks/telegram/test" \
  -H "Content-Type: application/json" -d '{}'

# Вариант B — публичный HTTPS приложения
curl -sS --max-time 10 -o /dev/null -w "%{http_code}\n" \
  -X POST "https://botato.ru/webhooks/telegram/test" \
  -H "Content-Type: application/json" -d '{}'
```

Быстрый код ответа (`403` / `404` / `405`) — путь живой.  
Timeout — этот вариант не использовать.

Предпочтительнее A: VPS→публичный РФ тоже может таймаутиться.

---

## 4. Установка Caddy на VPS

В обычном `apt` пакета `caddy` часто нет — ставьте из официального репозитория ([docs](https://caddyserver.com/docs/install)):

```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
  | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
  | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo chmod o+r /usr/share/keyrings/caddy-stable-archive-keyring.gpg
sudo chmod o+r /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install -y caddy
caddy version
sudo systemctl status caddy
```

Не используйте `snap install caddy` для этого сценария: неудобнее с `/etc/caddy/Caddyfile` и systemd.

После установки сервис `caddy` уже может быть запущен с дефолтным конфигом — дальше замените `/etc/caddy/Caddyfile` (раздел 5) и сделайте `reload`.

---

## 5. Caddyfile

Создайте `/etc/caddy/Caddyfile`.

### Вариант A — через WireGuard

```caddy
{
	email admin@botato.ru
}

tg-hook.botato.ru {
	handle /webhooks/telegram/* {
		reverse_proxy 10.8.0.10:80
	}

	handle {
		respond "Not found" 404
	}
}
```

При необходимости замените `10.8.0.10:80` на фактический адрес/порт HTTP на app в сети WG.

### Вариант B — на публичный `https://botato.ru`

```caddy
{
	email admin@botato.ru
}

tg-hook.botato.ru {
	handle /webhooks/telegram/* {
		reverse_proxy https://botato.ru {
			header_up Host botato.ru
			header_up X-Forwarded-Proto https
		}
	}

	handle {
		respond "Not found" 404
	}
}
```

Можно взять за основу репозиторный файл `telegram-webhook-relay.Caddyfile` и подставить домен/бэкенд.

### Вариант C — порт 8443, если 443 занят

Типичная ошибка при `systemctl reload caddy`:

```text
listening on :443: listen tcp :443: bind: address already in use
```

Проверка:

```bash
sudo ss -lntp | grep -E ':80|:443'
```

Частый случай на VPS с Amnezia: **443 занят `amnezia-xray`** (`docker-proxy`).  
Контейнер VPN **не останавливайте** — иначе сломается Xray/Reality.

Telegram Bot API принимает webhook на портах **443, 80, 88, 8443**.  
Поднимите Caddy на **8443** и укажите порт в `TELEGRAM_WEBHOOK_BASE_URL`.

```caddy
{
	email admin@botato.ru
	auto_https disable_redirects
}

tg-hook.botato.ru:8443 {
	handle /webhooks/telegram/* {
		# A или B — какой бэкенд отвечает с VPS:
		reverse_proxy 10.8.0.10:80
		# reverse_proxy https://botato.ru {
		# 	header_up Host botato.ru
		# 	header_up X-Forwarded-Proto https
		# }
	}

	handle {
		respond "Not found" 404
	}
}
```

`auto_https disable_redirects` — чтобы Caddy не пытался занять 443 под HTTP→HTTPS редирект.  
Сертификат Let's Encrypt обычно берётся через **HTTP-01 на :80** (Caddy должен слушать 80).

В botato:

```env
TELEGRAM_WEBHOOK_BASE_URL=https://tg-hook.botato.ru:8443
```

После смены порта — redeploy PHP и **«Обновить токен»** в кабинете (тот же `setWebhook` с новым URL).

---

## 6. Firewall и запуск

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# если используете вариант C (8443):
sudo ufw allow 8443/tcp

sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl enable --now caddy
# при смене Caddyfile надёжнее restart, если reload падал из‑за занятого 443:
sudo systemctl restart caddy
sudo systemctl status caddy
sudo ss -lntp | grep -E ':80|:443|:8443'
```

Сертификат Let's Encrypt Caddy получит сам при первом запросе на `tg-hook.botato.ru` (DNS уже должен указывать на VPS).

При варианте C ожидайте: Caddy на **80** и **8443**, на **443** может оставаться `docker-proxy` (xray) — это нормально.

Логи при ошибках:

```bash
sudo journalctl -u caddy -n 100 --no-pager
```

---

## 7. Проверка relay «снаружи»

Обычный HTTPS (варианты A/B, порт 443):

```bash
curl -sS --max-time 15 -o /dev/null -w "%{http_code}\n" \
  -X POST "https://tg-hook.botato.ru/webhooks/telegram/test" \
  -H "Content-Type: application/json" -d '{}'
```

Вариант C (порт 8443):

```bash
curl -sS --max-time 15 -o /dev/null -w "%{http_code}\n" \
  -X POST "https://tg-hook.botato.ru:8443/webhooks/telegram/test" \
  -H "Content-Type: application/json" -d '{}'
```

Ожидается быстрый HTTP-код (часто `403` без secret), не timeout.  
SSL в браузере/`curl -v` — валидный сертификат на `tg-hook.botato.ru`.

---

## 8. Связка с botato

1. В `docker/prod/.env` или `override.env`:

   ```env
   APP_PUBLIC_URL=https://botato.ru
   # обычный 443:
   TELEGRAM_WEBHOOK_BASE_URL=https://tg-hook.botato.ru
   # или вариант C, если 443 занят (Amnezia Xray и т.п.):
   # TELEGRAM_WEBHOOK_BASE_URL=https://tg-hook.botato.ru:8443
   TELEGRAM_PROXY=socks5h://tgproxy:PASSWORD@147.45.230.203:1080
   ```

2. Задеплоить образ с поддержкой `TELEGRAM_WEBHOOK_BASE_URL` и пересоздать PHP-сервис (stack deploy), чтобы env попал в контейнеры.

3. **Переподключить бота** в кабинете («Обновить токен» с тем же или новым токеном) — иначе в Telegram останется старый webhook на `https://botato.ru/...`.

4. Проверка:

   ```bash
   curl -sS --max-time 20 -x "$TELEGRAM_PROXY" \
     "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
   ```

   Ожидание: `url` начинается с `https://tg-hook.botato.ru` (при варианте C — с `:8443`), нет свежего `Connection timed out`.

5. Написать боту в личку — должен прийти ответ-заглушка (если подписка активна).

---

## Чеклист

- [ ] DNS `tg-hook.botato.ru` → IP VPS  
- [ ] С VPS бэкенд (A или B) отвечает без timeout  
- [ ] Caddy установлен, `Caddyfile` валиден, сервис активен  
- [ ] Порты открыты: `80` + (`443` **или** `8443` при занятом 443)  
- [ ] `curl` на webhook URL relay — быстрый ответ  
- [ ] В env задан `TELEGRAM_WEBHOOK_BASE_URL` (с `:8443`, если вариант C)  
- [ ] PHP-сервис пересоздан, бот переподключён («Обновить токен»)  
- [ ] `getWebhookInfo` без timeout, бот отвечает  

---

## Безопасность

- Наружу на VPS для relay: `80` и `443` **или** `8443`; SOCKS (`1080`) — только с IP app-сервера.  
- Не останавливайте `amnezia-xray` ради освобождения 443 — используйте порт 8443.  
- Не светите токены бота и пароли прокси в чатах/репозитории.  
- Relay отдаёт наружу только `/webhooks/telegram/*`, остальное — `404`.
