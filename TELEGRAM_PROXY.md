# Прокси для Telegram Bot API (сервер в РФ)

Исходящие запросы к `api.telegram.org` с серверов в РФ часто блокируются (timeout на порт 443).  
Приложение ходит в Bot API через переменную окружения `TELEGRAM_PROXY` (SOCKS5 или HTTP).

Через прокси уходит **только** трафик Telegram API (`getMe`, `setWebhook`, `sendMessage`).  
Остальной трафик сервера (сайт, БД, Redis, почта) идёт напрямую.

## Схема

```
[PHP / Docker в РФ]
   └─ TELEGRAM_PROXY ──► SOCKS5 на VPS вне РФ
                              └─► api.telegram.org
```

WireGuard сам по себе приложению не нужен: PHP понимает только URL вида `socks5h://...` или `http://...`.

Рекомендуемый вариант для Docker: SOCKS5 на публичном IP VPS + firewall только с IP сервера приложения.  
WireGuard можно оставить для личного доступа к VPS.

---

## Часть A. SOCKS5 на VPS

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
# подставьте публичный IP хоста, где крутится botato
sudo ufw allow from APP_SERVER_PUBLIC_IP to any port 1080 proto tcp
```

Не открывайте `1080/tcp` всему интернету без ограничения по IP.

### 4. Проверка с сервера приложения

```bash
curl -x socks5h://tgproxy:CHANGE_ME_PASSWORD@VPS_PUBLIC_IP:1080 -I https://api.telegram.org
```

Ожидается HTTP-ответ, не timeout.

`socks5h://` предпочтительнее `socks5://`: DNS резолвится на стороне прокси (важно при блокировках DNS).

---

## Часть B. Настройка botato

В `docker/prod/override.env` (или соответствующий prod env):

```env
TELEGRAM_PROXY=socks5h://tgproxy:CHANGE_ME_PASSWORD@VPS_PUBLIC_IP:1080
```

Примеры форматов:

```env
# SOCKS5, DNS через прокси (рекомендуется)
TELEGRAM_PROXY=socks5h://user:pass@host:1080

# SOCKS5 без DNS через прокси
TELEGRAM_PROXY=socks5://user:pass@host:1080

# HTTP-прокси
TELEGRAM_PROXY=http://user:pass@host:8080
```

Перезапустите PHP-контейнеры, чтобы подхватить переменную окружения.

После этого подключение бота в кабинете должно проходить проверку токена через Telegram API.

---

## Опционально: SOCKS только внутри WireGuard

Если не хотите публиковать порт 1080 наружу:

1. На VPS SOCKS слушает только адрес WG, например `10.8.0.1:1080`.
2. На хосте приложения поднимаете WireGuard-клиент.
3. В env: `TELEGRAM_PROXY=socks5h://tgproxy:PASS@10.8.0.1:1080`.

### Важно: не full tunnel

На сервере приложения **нельзя** использовать:

```ini
AllowedIPs = 0.0.0.0/0, ::/0
```

Это уводит весь трафик хоста в VPN. Нужен split tunnel — только сеть WireGuard:

```ini
[Interface]
PrivateKey = <client_private_key>
Address = 10.8.0.10/24

[Peer]
PublicKey = <server_public_key>
PresharedKey = <preshared_key>
AllowedIPs = 10.8.0.0/24
Endpoint = VPS_PUBLIC_IP:51820
PersistentKeepalive = 25
```

Так через туннель доступен только SOCKS в сети `10.8.0.0/24`. Telegram «выезжает» уже с VPS через SOCKS.

### Docker и адрес `10.8.0.1`

Контейнеры часто не видят WireGuard-интерфейс хоста. Если с хоста `curl` к прокси работает, а из контейнера — нет, проще использовать **часть A** (публичный SOCKS + firewall по IP).

---

## Что куда идёт

| Трафик | Маршрут |
|--------|---------|
| Telegram Bot API | через `TELEGRAM_PROXY` → VPS → интернет |
| Сайт, БД, Redis, почта | напрямую с сервера приложения |
| Личный WireGuard на ноутбуке | отдельно, на приложение не влияет |

---

## Чеклист

1. На VPS: microsocks + пароль + firewall по IP app-сервера.
2. С app-сервера: `curl -x socks5h://... https://api.telegram.org` — успех.
3. В `override.env`: `TELEGRAM_PROXY=...`.
4. Рестарт PHP-контейнеров.
5. Подключить бота в кабинете.

---

## Безопасность

- Не коммитьте `override.env` с паролем прокси.
- Не открывайте SOCKS без пароля и без ограничения по IP.
- Ключи WireGuard и пароли прокси не публикуйте в чатах и репозитории.
- `AllowedIPs = 0.0.0.0/0` допустим на личном устройстве для полного VPN; на сервере botato — нет.
