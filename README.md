Rifat Bd Calling, [8/29/2025 9:04 AM]
# Fixing Reverb WebSocket Timeouts (Nginx + Laravel Reverb + Vite): Step‑by‑Step Guide

This guide documents how the SSL connection timeout on the Reverb WebSocket endpoint was diagnosed and fixed for reverb.lorimovers.com.

---

## TL;DR ✅
- Root causes:
    1) Frontend connecting directly to wss://reverb.lorimovers.com:8083 instead of going through Nginx on 443.
    2) Nginx had no WebSocket proxy for the required /app and /apps paths.
    3) Frontend assets were built with hardcoded `:8083`.
- Fixes applied:
    - Added Nginx WebSocket proxy for /app and /apps, with SSL termination and proper Upgrade headers.
    - Corrected Vite env vars so the browser uses standard HTTPS port (443).
    - Rebuilt assets to remove old host/port.
    - Set REVERB_SERVER_PORT=8083 (Reverb listens locally; Nginx proxies from 443).

---

## Architecture (After Fix)
Browser (wss) → https://reverb.lorimovers.com/app/{key} :443
│  (TLS/HTTP2; Nginx SSL termination)
▼
Nginx reverse proxy
│  (Upgrade: websocket)
▼
http://127.0.0.1:8083  (Reverb server)

---

## 1) Prerequisites & Assumptions
- A running Laravel app using Broadcasting = Reverb.
- Reverb server running on the same host as Nginx.
- Valid TLS certificate installed on Nginx for reverb.lorimovers.com.
- Firewall allows 443/tcp (public) and blocks 8083 from the public internet (bind Reverb to 127.0.0.1).

---

## 2) Fix Nginx: Add a WebSocket‑aware Proxy

Create or edit the server block for reverb.lorimovers.com.

> Files (typical paths):
> - /etc/nginx/sites-available/reverb.lorimovers.com.conf (symlink to sites-enabled/)

Add a map for connection upgrades (place in http context, e.g., /etc/nginx/nginx.conf or the top of your site file if allowed by your distro):

map $http_upgrade $connection_upgrade {
default upgrade;
''      close;
}

Server block (essential bits):

server {
listen 443 ssl http2;
server_name reverb.lorimovers.com;

# TLS certs
ssl_certificate     /etc/letsencrypt/live/reverb.lorimovers.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/reverb.lorimovers.com/privkey.pem;

# --- WebSocket proxy for Pusher/Reverb paths ---
# Handshake & client traffic
location ~ ^/(app|apps)/ {
proxy_pass http://127.0.0.1:8083;
proxy_http_version 1.1;

    proxy_set_header Host               $host;
    proxy_set_header X-Real-IP          $remote_addr;
    proxy_set_header X-Forwarded-For    $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto  $scheme;

    # WebSocket magic
    proxy_set_header Upgrade    $http_upgrade;
    proxy_set_header Connection $connection_upgrade;

    # Optional timeouts (tune as needed)
    proxy_read_timeout  600s;
    proxy_send_timeout  600s;
}

# (Optional) Serve a health check
location = /healthz { return 200 'ok'; add_header Content-Type text/plain; }
}

# Optionally, redirect HTTP→HTTPS
server {
listen 80;
server_name reverb.lorimovers.com;
return 301 https://$host$request_uri;
}

Reload Nginx safely:

sudo nginx -t && sudo systemctl reload nginx

Why this matters: Without the Upgrade/Connection headers and HTTP/1.1 proxying, Nginx will not switch protocols and the browser handshake stalls → timeout.

---

## 3) Configure Reverb Server (Backend)
Ensure the Reverb server listens only on localhost and a distinct port (e.g., 8083):

.env (backend)
BROADCAST_DRIVER=reverb

REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret

# External URL seen by browsers
REVERB_HOST=reverb.lorimovers.com
REVERB_SCHEME=https
# Leave REVERB_PORT empty (browser uses 443 by default) or set to 443
REVERB_PORT=

# Internal server binding (what Nginx proxies to)
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8083

Run Reverb bound to localhost (examples; pick your supervisor):
# One-off (dev)
php artisan reverb:start --host=127.0.0.1 --port=8083

# or configure Supervisor/systemd for production

---

Rifat Bd Calling, [8/29/2025 9:04 AM]
## 4) Fix Frontend (Vite/Laravel Echo) Environment
Set Vite variables so the browser uses the public hostname and standard HTTPS port.

.env / .env.production (frontend build):
VITE_REVERB_APP_KEY=your_app_key
VITE_REVERB_HOST=reverb.lorimovers.com
VITE_REVERB_SCHEME=https
VITE_REVERB_PORT=   # keep empty for 443

Echo/Reverb init (`resources/js/bootstrap.js` example):
import Echo from 'laravel-echo';

window.Pusher = undefined; // Reverb is Pusher-compatible

window.Echo = new Echo({
broadcaster: 'reverb',
key: import.meta.env.VITE_REVERB_APP_KEY,
wsHost: import.meta.env.VITE_REVERB_HOST,
wsPort: import.meta.env.VITE_REVERB_PORT || 443,
wssPort: import.meta.env.VITE_REVERB_PORT || 443,
forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
enabledTransports: ['ws', 'wss'],
});

Rebuild assets so old :8083 URLs are eliminated:
npm ci            # or npm install
npm run build
# For dev: npm run dev

✅ Verification:
grep -R "8083" public/build || echo "OK: no hardcoded :8083 in built assets"

---

## 5) Validate End‑to‑End

### A. TLS & Nginx
# Check TLS and ALPN
openssl s_client -connect reverb.lorimovers.com:443 -servername reverb.lorimovers.com -alpn h2,http/1.1 </dev/null | grep -E "issuer=|subject=|ALPN"

# Quick HTTP check
curl -I https://reverb.lorimovers.com/healthz

### B. WebSocket Handshake
Using wscat (node), simulate a client:
npx wscat -c "wss://reverb.lorimovers.com/app/your_app_key?protocol=7&client=js&version=8.0.0"
You should see a successful connection (and later ping/pong).

### C. Browser Console
- Open DevTools → Network → filter by WS → select the /app/... request.
- Confirm Status 101 (Switching Protocols) and frames flowing.

---

## 6) Common Errors → Fixes
| Symptom | Likely Cause | Fix |
|---|---|---|
| Timeout / stalled handshake | Missing Upgrade/Connection headers or proxying via HTTP/1.0 | Use proxy_http_version 1.1 and headers shown above |
| 400 Bad Request on /app/... | Host mismatch | Set proxy_set_header Host $host; |
| Mixed Content warnings | Using ws:// on HTTPS site | Ensure VITE_REVERB_SCHEME=https and forceTLS: true |
| 403 / subscription error | Auth endpoint misconfigured | Check broadcasting.php, private channel auth routes, and session/cookies/CORS |
| Still hitting :8083 | Old assets cached | npm run build and clear CDN/browser cache |
| Public access to 8083 | Reverb bound to 0.0.0.0 | Bind 127.0.0.1 and block 8083 in firewall |

---

## 7) Security & Hardening
- Bind internally: REVERB_SERVER_HOST=127.0.0.1 so Reverb isn’t exposed.
- TLS only: Terminate TLS at Nginx (HTTP/2 enabled) and redirect HTTP→HTTPS.
- CORS / Origins: Limit allowed origins to your app domains if you expose auth endpoints.
- Rate limits: Consider basic rate limiting on /apps/ if you post server events over HTTP.
- Logs: Monitor error.log for upstream timeouts and access.log for 101 handshakes.

---

## 8) Operational Checklist (Copy/Paste)
- [ ] Add map $http_upgrade in Nginx http{} context.
- [ ] Add /^(app|apps)/ location with headers and proxy_pass to 127.0.0.1:8083.
- [ ] nginx -t && systemctl reload nginx.
- [ ] Set backend .env: REVERB_HOST, REVERB_SCHEME=https, REVERB_PORT= (empty), REVERB_SERVER_HOST=127.0.0.1, REVERB_SERVER_PORT=8083.
- [ ] Start Reverb bound to 127.0.0.1:8083 (Supervisor/systemd).
- [ ] Set frontend .env(.production) VITE_REVERB_* as above.
- [ ] npm run build; confirm built files don’t contain :8083.
- [ ] Browser DevTools: verify WebSocket 101 Switching Protocols.

---

## 9) Appendix: Sample systemd Unit for Reverb
# /etc/systemd/system/reverb.service
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
WorkingDirectory=/var/www/your-app
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8083
Restart=always
User=www-data
Group=www-data
Environment=APP_ENV=production

[Install]
WantedBy=multi-user.target
sudo systemctl daemon-reload
sudo systemctl enable --now reverb
sudo systemctl status reverb

---

Rifat Bd Calling, [8/29/2025 9:04 AM]
## 10) What Changed (Before → After)
- Before: Browser attempted wss://reverb.lorimovers.com:8083 directly → no TLS termination via Nginx → handshake failed/timeouts.
- After: Browser uses wss://reverb.lorimovers.com/app/... on 443, Nginx handles TLS + websocket upgrade → proxies to 127.0.0.1:8083.

---

### Done 🎉
If new console errors appear, they’re likely auth/subscription issues (app logic). Use the Network panel to inspect /broadcasting/auth (for private/presence channels) and verify session/cookie/CORS setup.






APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:9ujicG/j6BaYs1SOsWJn4fYZSq13op6fbzzFdduOGhI=
APP_DEBUG=true
APP_URL=https://reverb.lorimovers.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reverb
DB_USERNAME=reverb
DB_PASSWORD=o7QgZLb6GrCmnVK4CnA3

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"


REVERB_APP_ID=474271
REVERB_APP_KEY=h8j1bvm0d1zyoxybcesv
REVERB_APP_SECRET=mal4mdpt1fpm531jo70b
REVERB_HOST=reverb.lorimovers.com
REVERB_PORT=443
REVERB_SCHEME=https


VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
# Frontend-specific Reverb configuration
VITE_REVERB_HOST=reverb.lorimovers.com
VITE_REVERB_PORT=
VITE_REVERB_SCHEME=https
REVERB_SERVER_PORT=8083









##  ------------------------------------------------ bangla v-----------------------------------------


# Laravel Reverb WebSocket Timeout Fix (Bangla Explanation + English Commands)

এখানে আমরা Laravel Reverb এর WebSocket timeout সমস্যা সমাধানের জন্য ধাপে ধাপে নির্দেশনা দিব।  
সব কমান্ড এবং কনফিগারেশন ইংরেজিতে থাকবে, কিন্তু ব্যাখ্যা বাংলায়**।

---

## 1. Laravel Project .env Configuration

`.env` ফাইলে নিচের কনফিগারেশন দিনঃ

```env
REVERB_APP_ID=474271
REVERB_APP_KEY=h8j1bvm0d1zyoxybcesv
REVERB_APP_SECRET=your-secret-key
REVERB_HOST=reverb.yourdomain.com
REVERB_PORT=8083
REVERB_SCHEME=https
```

➡️ **বাংলায় ব্যাখ্যা:  
.env ফাইলে আপনার Reverb server এর তথ্য দিতে হবে। HOST, PORT, KEY, SECRET সব ঠিকমত দিলে Laravel Reverb server এর সাথে যুক্ত হতে পারবে।

---

## 2. Run Reverb Server with Artisan

Reverb সার্ভার চালু করতে এই কমান্ড ব্যবহার করুনঃ

php artisan reverb:start

➡️ বাংলায় ব্যাখ্যা:  
এই কমান্ড Reverb সার্ভার চালাবে। টেস্ট করার জন্য manual চালাতে পারেন। Production এর জন্য Supervisor দিয়ে auto চালু রাখতে হবে।

---

## 3. Supervisor Setup (Auto Run)

Linux সার্ভারে Supervisor ব্যবহার করে Reverb সার্ভার সবসময় চালু রাখতে হবে।  
কনফিগ ফাইল তৈরি করুনঃ

sudo nano /etc/supervisor/conf.d/reverb.conf

তারপর লিখুনঃ

[program:reverb]
process_name=%(program_name)s_%(process_num)02d
command=php /home/youruser/htdocs/yourproject/artisan reverb:start
autostart=true
autorestart=true
user=youruser
numprocs=1
redirect_stderr=true
stdout_logfile=/home/youruser/htdocs/yourproject/storage/logs/reverb.log

Supervisor রিলোড করুনঃ

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb

➡️ বাংলায় ব্যাখ্যা:  
Supervisor সার্ভার Reverb কে সবসময় চালু রাখবে। সার্ভার রিস্টার্ট হলেও Reverb আবার চালু হয়ে যাবে।

---

## 4. Nginx Reverse Proxy Setup

Reverb সার্ভারের জন্য Nginx কনফিগার করুনঃ

Rifat Bd Calling, [8/29/2025 9:04 AM]
server {
server_name reverb.yourdomain.com;

    location / {
        proxy_pass http://127.0.0.1:8083;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }
}

➡️ বাংলায় ব্যাখ্যা:  
Nginx proxy এর মাধ্যমে reverb.yourdomain.com থেকে 8083 পোর্টে Reverb সার্ভারে কানেকশন যাবে। Upgrade এবং Connection হেডার WebSocket এর জন্য জরুরি।

---

## 5. Test Reverb Connection

Reverb ঠিকভাবে কাজ করছে কিনা টেস্ট করতে curl ব্যবহার করুনঃ

curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket"     -H "Host: reverb.yourdomain.com"     -H "Origin: http://reverb.yourdomain.com"     http://127.0.0.1:8083

➡️ বাংলায় ব্যাখ্যা:  
এই কমান্ড চালালে যদি WebSocket সঠিকভাবে কাজ করে তবে সাড়া (response) পাবেন।

---

## 6. Common Issues

- Timeout সমস্যা হলে proxy_read_timeout এবং proxy_send_timeout Nginx এ বাড়ান।
- Permission সমস্যা হলে Supervisor config এ user=youruser ঠিক করে দিন।
- SSL সমস্যা হলে Reverb host অবশ্যই https দিয়ে দিন এবং Nginx এ SSL সার্টিফিকেট সেট করুন।

---

## ✅ Final Note

এভাবে step-by-step করলে Laravel Reverb এর timeout সমস্যা সমাধান হবে।  
Production server এ সবসময় Supervisor + Nginx reverse proxy ব্যবহার করা উচিত।
