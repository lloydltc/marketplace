# Salma Tech Automotive Marketplace - Deployment Guide

## Overview

This guide provides step-by-step instructions for:

1. Local development environment setup
2. Docker containerization
3. PostgreSQL and Redis setup
4. Laravel optimization
5. Nginx configuration
6. SSL/TLS setup
7. DigitalOcean Droplet deployment
8. DigitalOcean Spaces configuration
9. Backup and recovery procedures
10. Monitoring and maintenance

---

## Part 1: Local Development Setup

### Prerequisites

- **PHP 8.2+** (with extensions: pdo_pgsql, redis, gd, mbstring, fileinfo)
- **Composer** (latest version)
- **Docker & Docker Compose** (recommended)
- **Node.js 18+** (for Tailwind compilation)
- **Git** (for version control)
- **PostgreSQL Client** (psql command-line tool)

### Initial Setup (Without Docker)

#### 1.1 Clone Repository

```bash
git clone https://github.com/salmatech/marketplace.git
cd marketplace
git checkout main
```

#### 1.2 Install Dependencies

```bash
# PHP dependencies
composer install

# Node.js dependencies (TailwindCSS, Alpine.js)
npm install
```

#### 1.3 Environment Configuration

```bash
# Copy example env file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure database connection
# Edit .env:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=salma_marketplace_dev
DB_USERNAME=marketplace_user
DB_PASSWORD=secure_password

# Configure caching & queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Payment gateway
PESEPAY_API_KEY=test_key_xxx
PESEPAY_MERCHANT_ID=test_merchant_xxx
PESEPAY_ENVIRONMENT=sandbox

# Mail driver (for local testing)
MAIL_DRIVER=log
MAIL_FROM_ADDRESS=noreply@salmatech.local
```

#### 1.4 Database Setup

```bash
# Create PostgreSQL database
createdb salma_marketplace_dev

# Create user
createuser -P marketplace_user
# Enter password: secure_password

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed --class=DevelopmentSeeder
```

#### 1.5 Start Development Server

```bash
# Laravel development server (port 8000)
php artisan serve

# In another terminal, run queue worker
php artisan queue:work

# In third terminal, run Tailwind watcher
npm run watch
```

Access application at `http://localhost:8000`

---

## Part 2: Docker Setup (Recommended)

### 2.1 Docker Prerequisites

```bash
# Verify installations
docker --version      # Docker 24+
docker-compose --version  # Docker Compose 2+
```

### 2.2 Docker Files

**Dockerfile**

```dockerfile
FROM php:8.2-fpm-alpine

WORKDIR /app

# System dependencies
RUN apk add --no-cache \
    postgresql-client \
    git \
    curl \
    libpq-dev \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    bcmath

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Application code
COPY . /app

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create storage directories
RUN mkdir -p storage/logs storage/app/public storage/framework/{cache,sessions,views} \
    && chown -R www-data:www-data storage bootstrap/cache

# Laravel optimization
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 9000

CMD ["php-fpm"]

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s \
  CMD php -r 'file_exists(".env") || exit(1);' || exit 1
```

**docker-compose.yml**

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: marketplace_app
    working_dir: /app
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - DB_HOST=postgres
      - DB_DATABASE=salma_marketplace
      - DB_USERNAME=marketplace
      - DB_PASSWORD=postgres
      - REDIS_HOST=redis
      - CACHE_DRIVER=redis
      - QUEUE_CONNECTION=redis
      - PESEPAY_ENVIRONMENT=sandbox
    ports:
      - "9000:9000"
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    volumes:
      - .:/app
      - app_storage:/app/storage
    networks:
      - marketplace

  nginx:
    image: nginx:alpine
    container_name: marketplace_nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/app
      - ./docker/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./docker/ssl:/etc/nginx/ssl:ro
    depends_on:
      - app
    networks:
      - marketplace

  postgres:
    image: postgres:15-alpine
    container_name: marketplace_postgres
    environment:
      - POSTGRES_DB=salma_marketplace
      - POSTGRES_USER=marketplace
      - POSTGRES_PASSWORD=postgres
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U marketplace"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - marketplace

  redis:
    image: redis:7-alpine
    container_name: marketplace_redis
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - marketplace

  queue-worker:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: marketplace_queue
    working_dir: /app
    command: php artisan queue:work --tries=3 --delay=3
    environment:
      - APP_ENV=local
      - DB_HOST=postgres
      - REDIS_HOST=redis
    depends_on:
      - postgres
      - redis
    volumes:
      - .:/app
      - app_storage:/app/storage
    networks:
      - marketplace

volumes:
  postgres_data:
  redis_data:
  app_storage:

networks:
  marketplace:
    driver: bridge
```

### 2.3 Running with Docker

```bash
# Build images
docker-compose build

# Start all services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed --class=DevelopmentSeeder

# View logs
docker-compose logs -f app

# Stop all services
docker-compose down
```

Access application at `http://localhost`

---

## Part 3: PostgreSQL Configuration

### 3.1 PostgreSQL Best Practices

```sql
-- Create role with limited permissions
CREATE ROLE marketplace_app WITH LOGIN PASSWORD 'app_password';

-- Grant schema permissions
GRANT CREATE ON DATABASE salma_marketplace TO marketplace_app;
GRANT ALL PRIVILEGES ON SCHEMA public TO marketplace_app;

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Set timezone
ALTER DATABASE salma_marketplace SET timezone = 'UTC';
```

### 3.2 Backup Configuration

```bash
# Automated daily backup script
#!/bin/bash
BACKUP_DIR="/backups/marketplace"
DB_NAME="salma_marketplace"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

mkdir -p $BACKUP_DIR

# Full backup
pg_dump -U marketplace -h localhost $DB_NAME | gzip > $BACKUP_DIR/full_$TIMESTAMP.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "full_*.sql.gz" -mtime +30 -delete

# Verify backup
if gunzip -t $BACKUP_DIR/full_$TIMESTAMP.sql.gz 2>/dev/null; then
    echo "✅ Backup successful: full_$TIMESTAMP.sql.gz"
else
    echo "❌ Backup failed: full_$TIMESTAMP.sql.gz"
    exit 1
fi
```

### 3.3 Connection Pooling

For production, use PgBouncer:

```ini
; /etc/pgbouncer/pgbouncer.ini
[databases]
salma_marketplace = host=localhost port=5432 dbname=salma_marketplace user=marketplace

[pgbouncer]
pool_mode = transaction
max_client_conn = 500
default_pool_size = 10
reserve_pool_size = 5
reserve_pool_timeout = 3
min_db_connections = 5
max_db_connections = 20
```

---

## Part 4: Redis Configuration

### 4.1 Redis Setup

```bash
# Install Redis server (Ubuntu/Debian)
sudo apt-get install redis-server

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Verify
redis-cli ping  # Should return PONG
```

### 4.2 Redis Configuration (Production)

```conf
# /etc/redis/redis.conf

# Memory management
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000
appendonly yes
appendfsync everysec

# Security
requirepass strong_redis_password
```

### 4.3 Redis Monitoring

```bash
# Monitor Redis memory usage
redis-cli info memory

# Monitor active operations
redis-cli monitor

# Flush cache safely (when needed)
redis-cli FLUSHDB
```

---

## Part 5: Laravel Optimization

### 5.1 Production Configuration

```bash
# Set environment
APP_ENV=production
APP_DEBUG=false

# Enable caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Cache event discovery
php artisan event:cache
```

### 5.2 Laravel Opcache

```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'lock_connection' => 'default',
    ],
],

// Enable PHP opcache
extension=opcache.so
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=2
```

### 5.3 Database Connection Pooling (Laravel)

```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', 5432),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
    'options' => [
        \PDO::ATTR_PERSISTENT => true,
    ],
],
```

---

## Part 6: Nginx Configuration

### 6.1 Basic Nginx Configuration

```nginx
# /etc/nginx/sites-available/marketplace

upstream php_backend {
    server app:9000;
}

server {
    listen 80;
    listen [::]:80;
    server_name marketplace.local;

    root /app/public;
    index index.php index.html;

    # Logging
    access_log /var/log/nginx/marketplace_access.log combined buffer=32k flush=5s;
    error_log /var/log/nginx/marketplace_error.log warn;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css text/xml text/javascript 
               application/x-javascript application/xml+rss 
               application/json image/svg+xml;

    # Client limits
    client_max_body_size 50M;

    # PHP handling
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass php_backend;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeout configuration
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\.env {
        deny all;
    }

    location ~ /storage {
        deny all;
    }
}
```

### 6.2 SSL/TLS Configuration

```nginx
# /etc/nginx/sites-available/marketplace

server {
    listen 80;
    listen [::]:80;
    server_name marketplace.local;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name marketplace.local;

    # SSL certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/marketplace.local/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/marketplace.local/privkey.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # ... rest of configuration above
}
```

### 6.3 Enable Nginx Configuration

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/marketplace /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## Part 7: DigitalOcean Droplet Deployment

### 7.1 Droplet Creation

```bash
# Prerequisites
- Droplet type: General Purpose (2GB RAM, 2vCPU)
- Image: Ubuntu 22.04 LTS
- Region: Choose closest to customers (e.g., Johannesburg)
- Enable IPv6
- Add SSH keys (not password authentication)
```

### 7.2 Initial Server Setup

```bash
# SSH into droplet
ssh root@your_droplet_ip

# Update system
apt-get update && apt-get upgrade -y

# Create non-root user
adduser deploy
usermod -aG sudo deploy

# Add SSH key to deploy user
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Disable root login
sed -i 's/^#PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
systemctl restart sshd
```

### 7.3 Install Docker

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Add deploy user to docker group
usermod -aG docker deploy

# Install Docker Compose
curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Verify
docker --version
docker-compose --version
```

### 7.4 Deploy Application

```bash
# Switch to deploy user
su - deploy

# Clone repository
git clone https://github.com/salmatech/marketplace.git
cd marketplace

# Configure environment
cp .env.example .env

# Edit .env for production
nano .env

# Important variables:
APP_ENV=production
APP_DEBUG=false
DB_HOST=db.internal
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
PESEPAY_ENVIRONMENT=production
PESEPAY_API_KEY=prod_key_xxx
```

### 7.5 Start Application

```bash
# Build images
docker-compose -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Generate sitemap
docker-compose exec app php artisan sitemap:generate

# Verify
docker-compose ps
docker-compose logs -f app
```

### 7.6 Firewall Configuration

```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow PostgreSQL (internal only)
sudo ufw allow from 10.0.0.0/8 to any port 5432

# Check status
sudo ufw status
```

---

## Part 8: DigitalOcean Spaces Configuration

### 8.1 Create Spaces Bucket

```bash
# Via DigitalOcean API
curl -X POST \
  "https://api.digitalocean.com/v2/spaces" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $DO_TOKEN" \
  -d '{
    "name": "salma-marketplace-assets",
    "region": "jnb"
  }'
```

### 8.2 Laravel Spaces Configuration

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('DO_SPACES_KEY'),
        'secret' => env('DO_SPACES_SECRET'),
        'region' => env('DO_SPACES_REGION', 'jnb'),
        'bucket' => env('DO_SPACES_BUCKET'),
        'endpoint' => env('DO_SPACES_ENDPOINT'),
        'use_path_style_endpoint' => true,
    ],
],

// .env
DO_SPACES_KEY=xxx
DO_SPACES_SECRET=xxx
DO_SPACES_REGION=jnb
DO_SPACES_BUCKET=salma-marketplace-assets
DO_SPACES_ENDPOINT=https://jnb.digitaloceanspaces.com
FILESYSTEM_DISK=s3
```

### 8.3 CDN Configuration

```bash
# Create CDN endpoint in DigitalOcean dashboard
# CDN URL: https://cdn.salmatech.local
# Origin: https://jnb.digitaloceanspaces.com/salma-marketplace-assets

# Update Laravel config
ASSET_CDN_URL=https://cdn.salmatech.local

# Use in views
<img src="{{ asset('images/product.jpg') }}" />
<!-- Renders: https://cdn.salmatech.local/images/product.jpg -->
```

---

## Part 9: Backup & Disaster Recovery

### 9.1 Automated Daily Backups

```bash
# Create backup script
cat > /home/deploy/scripts/backup.sh << 'EOF'
#!/bin/bash

BACKUP_DIR="/home/deploy/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME="salma_marketplace"

mkdir -p $BACKUP_DIR

# Database backup
docker-compose exec -T postgres pg_dump -U marketplace $DB_NAME | gzip > $BACKUP_DIR/db_$TIMESTAMP.sql.gz

# Application files backup
tar -czf $BACKUP_DIR/app_$TIMESTAMP.tar.gz /home/deploy/marketplace/app /home/deploy/marketplace/database

# Upload to Spaces
s3cmd sync $BACKUP_DIR/ s3://salma-marketplace-backups/daily/ --delete-removed

# Keep local backups for 7 days
find $BACKUP_DIR -mtime +7 -delete

echo "✅ Backup completed: $TIMESTAMP"
EOF

chmod +x /home/deploy/scripts/backup.sh

# Schedule via cron
crontab -e

# Add: 0 2 * * * /home/deploy/scripts/backup.sh
```

### 9.2 Restore from Backup

```bash
# Download backup from Spaces
s3cmd get s3://salma-marketplace-backups/daily/db_YYYYMMDD_HHMMSS.sql.gz

# Restore database
gunzip < db_YYYYMMDD_HHMMSS.sql.gz | docker-compose exec -T postgres psql -U marketplace salma_marketplace

# Verify
docker-compose exec postgres pg_dump -U marketplace salma_marketplace | wc -l
```

### 9.3 Rollback Procedure

```bash
# Tag release
git tag -a v1.0.1 -m "Release 1.0.1"
git push origin v1.0.1

# On production, rollback to previous image
docker-compose down
git checkout v1.0.0
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d

# Verify
docker-compose logs -f app
```

---

## Part 10: Monitoring & Logging

### 10.1 Application Monitoring

```php
// config/app.php
'providers' => [
    // ...
    Sentry\Laravel\Integration::class,
],

// Install Sentry
composer require sentry/sentry-laravel

// Configure
SENTRY_DSN=https://xxx@xxx.ingest.sentry.io/xxx
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### 10.2 Server Monitoring

```bash
# Install monitoring tools
apt-get install -y node-exporter
apt-get install -y prometheus-node-exporter

# Monitor disk usage
df -h

# Monitor memory usage
free -h

# Monitor CPU usage
top -b -n 1 | head -20

# Monitor Docker
docker stats
```

### 10.3 Log Aggregation

```bash
# Configure Log Rotation
cat > /etc/logrotate.d/marketplace << 'EOF'
/home/deploy/marketplace/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 deploy deploy
}
EOF

# Test rotation
logrotate -f /etc/logrotate.d/marketplace
```

---

## Part 11: SSL/TLS Management

### 11.1 Let's Encrypt Certificate

```bash
# Install Certbot
apt-get install -y certbot python3-certbot-nginx

# Obtain certificate
certbot certonly --nginx -d marketplace.salmatech.local

# Auto-renewal (already enabled)
systemctl enable certbot.timer
systemctl start certbot.timer

# Verify renewal
certbot renew --dry-run
```

### 11.2 Certificate Renewal

```bash
# Manual renewal (if needed)
certbot renew

# Post-renewal hook
nano /etc/letsencrypt/renewal-hooks/post/marketplace.sh

#!/bin/bash
systemctl reload nginx
docker-compose exec app php artisan optimize
```

---

## Part 12: CI/CD Pipeline (GitHub Actions)

### 12.1 GitHub Actions Workflow

```yaml
# .github/workflows/deploy.yml

name: Deploy to Production

on:
  push:
    branches:
      - main
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_pgsql, redis
      
      - name: Run tests
        run: |
          composer install
          php artisan test --coverage
      
      - name: Deploy to DigitalOcean
        env:
          DO_PRIVATE_KEY: ${{ secrets.DO_PRIVATE_KEY }}
          DO_HOST: ${{ secrets.DO_HOST }}
        run: |
          mkdir -p ~/.ssh
          echo "$DO_PRIVATE_KEY" > ~/.ssh/id_rsa
          chmod 600 ~/.ssh/id_rsa
          ssh -o StrictHostKeyChecking=no deploy@$DO_HOST << 'ENDSSH'
          cd marketplace
          git pull origin main
          docker-compose -f docker-compose.prod.yml build
          docker-compose -f docker-compose.prod.yml up -d
          docker-compose exec -T app php artisan migrate --force
          docker-compose exec -T app php artisan optimize
          ENDSSH
```

---

## Part 13: Maintenance Procedures

### 13.1 Database Maintenance

```bash
# Run VACUUM to reclaim space
docker-compose exec postgres vacuumdb -U marketplace salma_marketplace

# Analyze query performance
docker-compose exec postgres analyzedb -U marketplace salma_marketplace

# Monitor slow queries
docker-compose exec postgres \
  psql -U marketplace -d salma_marketplace \
  -c "SELECT * FROM pg_stat_statements ORDER BY mean_exec_time DESC LIMIT 10;"
```

### 13.2 Redis Maintenance

```bash
# Monitor memory usage
docker-compose exec redis redis-cli info memory

# Clear expired keys
docker-compose exec redis redis-cli FLUSHDB ASYNC

# Monitor command statistics
docker-compose exec redis redis-cli info commandstats
```

### 13.3 Application Updates

```bash
# Update PHP dependencies
docker-compose exec app composer update

# Update Node dependencies
docker-compose exec app npm update

# Run tests
docker-compose exec app php artisan test

# Deploy updates
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d
```

---

## Part 14: Performance Tuning

### 14.1 Database Query Optimization

```php
// Use eager loading
$products = Product::with('images', 'vendor', 'category')
    ->where('status', 'active')
    ->paginate(20);

// Use select to limit columns
$vendors = Vendor::select('id', 'business_name', 'rating')
    ->where('status', 'approved')
    ->get();

// Use chunking for large datasets
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process
    }
});
```

### 14.2 Caching Strategy

```php
// Cache expensive queries
$topProducts = Cache::remember('products.top.30days', 3600, function () {
    return Product::select('id', 'title', 'price_zwl')
        ->where('status', 'active')
        ->orderBy('sales', 'desc')
        ->limit(10)
        ->get();
});

// Cache product listings
$category = Category::with([
    'products' => function ($query) {
        $query->select('id', 'title', 'price_zwl', 'vendor_id')
            ->limit(20);
    }
])->find($id);

Cache::put("category.{$id}", $category, 300);
```

### 14.3 Load Testing

```bash
# Install Apache Bench
apt-get install apache2-utils

# Simple load test
ab -n 1000 -c 10 https://marketplace.local/

# Test API endpoint
ab -n 5000 -c 50 -H "Content-Type: application/json" \
  https://marketplace.local/api/v1/products
```

---

## Checklist: Pre-Production Deployment

- [ ] All tests passing (coverage >80%)
- [ ] Environment variables configured
- [ ] Database migrations tested
- [ ] SSL certificate installed
- [ ] Backup scripts tested
- [ ] Monitoring configured
- [ ] Firewall rules applied
- [ ] Database optimized (indexes, VACUUM)
- [ ] Cache warming scripts tested
- [ ] Load testing passed (acceptable performance)
- [ ] Security audit completed
- [ ] Documentation updated
- [ ] Team trained on deployment procedures
- [ ] Rollback plan documented
- [ ] Incident response plan in place

---

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| **502 Bad Gateway** | Check PHP-FPM: `docker-compose logs app` |
| **Database connection refused** | Verify DB_HOST, DB_PASSWORD in .env |
| **Redis connection failed** | Check Redis service: `docker-compose exec redis redis-cli ping` |
| **File upload fails** | Verify storage permissions and disk space |
| **Queue jobs not processing** | Check queue worker: `docker-compose logs queue-worker` |
| **Email not sending** | Verify MAIL_FROM, SMTP settings; check logs |
| **High memory usage** | Check for memory leaks: `docker stats` |

---

*Document Version: 1.0*  
*Last Updated: 2026*  
*Status: Approved*
