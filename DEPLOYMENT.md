# 部署文档

## 环境要求

| 依赖 | 版本 |
|------|------|
| Ubuntu | 22.04 LTS |
| PHP | 8.4 |
| Nginx | latest |
| MySQL | 8.0+ |
| Node.js | 20+ |
| Composer | 2.x |
| Supervisor | latest |

> **注意**：本项目包含 `channels:listen` 常驻守护进程，且运行时会为每个通道 spawn 独立子进程，**必须使用 VPS 或独立服务器**，不支持无服务器平台（Vercel、Lambda、Shared Hosting 等）。

---

## 一、服务器初始化

### 1.1 安装 PHP 8.4

```bash
sudo apt update && sudo apt upgrade -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-intl php8.4-bcmath php8.4-pcntl
```

### 1.2 安装 Nginx、MySQL、Node.js

```bash
sudo apt install -y nginx mysql-server

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Supervisor
sudo apt install -y supervisor
```

---

## 二、数据库

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE clawbot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'clawbot'@'localhost' IDENTIFIED BY '你的强密码';
GRANT ALL PRIVILEGES ON clawbot.* TO 'clawbot'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 三、代码部署

### 3.1 拉取代码

```bash
sudo mkdir -p /var/www/clawbot
sudo chown $USER:$USER /var/www/clawbot
git clone https://your-repo-url.git /var/www/clawbot
cd /var/www/clawbot
```

### 3.2 安装依赖

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 3.3 配置环境变量

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env`，修改以下关键项：

```env
APP_NAME="ClawBot"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clawbot
DB_USERNAME=clawbot
DB_PASSWORD=你的强密码

QUEUE_CONNECTION=database

APP_LOCALE=zh_CN
APP_FALLBACK_LOCALE=zh_CN
APP_FAKER_LOCALE=zh_CN
APP_TIMEZONE=Asia/Shanghai

GATEWAY_URL=https://ilinkai.weixin.qq.com
```

### 3.4 初始化应用

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 设置目录权限
sudo chown -R www-data:www-data /var/www/clawbot
sudo chmod -R 755 /var/www/clawbot
sudo chmod -R 775 /var/www/clawbot/storage
sudo chmod -R 775 /var/www/clawbot/bootstrap/cache
```

---

## 四、Nginx 配置

```bash
sudo nano /etc/nginx/sites-available/clawbot
```

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /var/www/clawbot/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    charset utf-8;
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/clawbot /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### SSL 证书（Let's Encrypt）

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## 五、Supervisor 守护进程

本项目需要两个常驻进程：队列 worker 和消息监听器。

```bash
sudo nano /etc/supervisor/conf.d/clawbot.conf
```

```ini
[program:clawbot-queue]
command=php /var/www/clawbot/artisan queue:listen --sleep=3 --tries=3
directory=/var/www/clawbot
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/clawbot/storage/logs/queue.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3

[program:clawbot-listen]
command=php /var/www/clawbot/artisan channels:listen
directory=/var/www/clawbot
autostart=true
autorestart=true
user=www-data
; 必须为 true，确保重启时同时终止所有通道子进程，避免孤立进程
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/www/clawbot/storage/logs/listen.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
sudo supervisorctl status
```

正常输出：

```
clawbot-listen    RUNNING   pid 12345, uptime 0:00:05
clawbot-queue     RUNNING   pid 12346, uptime 0:00:05
```

---

## 六、验证部署

```bash
# 检查进程状态
sudo supervisorctl status

# 查看监听日志（应看到通道 worker 启动信息）
tail -f /var/www/clawbot/storage/logs/listen.log

# 查看应用日志
tail -f /var/www/clawbot/storage/logs/laravel.log
```

访问 `https://yourdomain.com`，注册账号，创建通道，验证消息收发正常。

---

## 七、代码更新流程

每次发布新版本执行：

```bash
cd /var/www/clawbot
git pull

composer install --no-dev --optimize-autoloader
npm install && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 重启守护进程（channels:listen 子进程会随之重启）
sudo supervisorctl restart clawbot-queue
sudo supervisorctl restart clawbot-listen
```

---

## 常见问题

**Q：重启 `clawbot-listen` 后通道多久恢复监听？**
主进程每 5 秒扫描一次活跃通道，重启后 5 秒内所有通道自动恢复轮询。

**Q：为什么 Supervisor 配置里要写 `stopasgroup=true`？**
`channels:listen` 是主进程，运行时会为每个通道 spawn 子进程。不加这两个参数，重启主进程时子进程会变成孤立进程，继续占用连接和内存。

**Q：日志文件太大怎么处理？**
Supervisor 配置里已设置 `stdout_logfile_maxbytes=10MB` 和 `backups=3` 自动轮转。Laravel 日志通过 `config/logging.php` 中的 `daily` driver 控制，默认保留 14 天。
