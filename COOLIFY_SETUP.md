# Coolify Deployment Guide for PSP LMS

## ✅ Coolify Readiness Checklist

Your application is **Coolify-ready** with the following features:

- ✅ Environment-aware local setup (Windows-only, skipped in production)
- ✅ Domain-based multi-tenancy
- ✅ PostgreSQL database support
- ✅ Single database strategy with landlord connection
- ✅ Queue and session drivers compatible with production
- ✅ Proper error handling and logging

---

## Required Environment Variables for Coolify

### 1. Core Application Settings

```env
APP_NAME="PSP LMS"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://psp-lms.yourdomain.com
```

**🔑 Generate APP_KEY:**

```bash
php artisan key:generate --show
```

### 2. Database Configuration (PostgreSQL)

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=psp_lms
DB_USERNAME=postgres
DB_PASSWORD=YOUR_SECURE_PASSWORD
```

**Important:** Coolify uses PostgreSQL by default. The `landlord` connection in `config/database.php` will automatically use these same credentials unless you set separate `LANDLORD_*` variables.

### 3. Multi-Tenancy Settings

```env
MULTITENANCY_DATABASE_STRATEGY=single
```

This is already configured in `config/multitenancy.php` with a default value, so it's **optional** to add to .env.

### 4. Session & Cache (Production-ready)

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=database
CACHE_PREFIX=psp_lms
```

**Why database?** Database-backed sessions/cache work across multiple servers (if you scale).

### 5. Queue Configuration

```env
QUEUE_CONNECTION=database
```

**Optional:** Use Redis for better performance:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### 6. Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 7. Logging

```env
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error
```

---

## Setting up Tenant Domains in Coolify

### Option 1: Individual Domains (Recommended for Production)

1. Go to your application in Coolify
2. Navigate to **Settings** → **Domains**
3. Add each tenant domain:
    - `psp-lms.yourdomain.com` (main app)
    - `citygeneral.yourdomain.com`
    - `metropolitan.yourdomain.com`
    - etc.
4. Coolify will automatically:
    - Generate SSL certificates (Let's Encrypt)
    - Configure Nginx routing
    - Handle DNS (if using Coolify DNS)

### Option 2: Wildcard Subdomain

1. Add `*.yourdomain.com` to domains
2. Configure wildcard DNS record in your DNS provider:
    ```
    *.yourdomain.com  →  A  →  YOUR_SERVER_IP
    ```
3. Coolify will generate a wildcard SSL certificate

---

## Database Migration & Seeding

### First Deployment

```bash
# Run migrations for both landlord and default database
php artisan migrate --force

# Seed initial data (roles, tenants, etc.)
php artisan db:seed --force
```

### Adding New Tenants (Production)

**Method 1: Via Artisan Command**

```bash
php artisan tenant:create "Hospital Name" "hospital.yourdomain.com"
```

**Method 2: Via Admin UI**

1. Login as system admin
2. Go to `/admin/tenants`
3. Click "Create Tenant"
4. Fill in:
    - Name: Hospital Name
    - Domain: hospital.yourdomain.com
    - Database: hospital (optional)
5. Add the domain to Coolify
6. Done!

---

## Post-Deployment Checklist

### 1. Run Migrations

```bash
php artisan migrate --force
```

### 2. Seed Database (First time only)

```bash
php artisan db:seed --force
```

### 3. Create System Admin

```bash
php artisan tinker
> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);
> $user = User::first();
> $user->assignRole('system admin');
```

Or use the seeder (already includes system admin):

```bash
php artisan db:seed AdminSeeder --force
```

### 4. Clear Caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Run Queue Worker (if using queues)

In Coolify, add a **Worker** service:

```bash
php artisan queue:work --tries=3 --timeout=90
```

---

## Differences Between Local & Production

| Feature          | Local (Windows/Laragon) | Production (Coolify/Linux)  |
| ---------------- | ----------------------- | --------------------------- |
| Hosts file setup | ✅ Automatic            | ❌ Not needed (DNS)         |
| Virtual host     | ✅ Apache `.conf` files | ✅ Nginx (Coolify)          |
| SSL              | ❌ HTTP only            | ✅ Auto SSL (Let's Encrypt) |
| Wildcard domains | ⚠️ Manual hosts file    | ✅ Wildcard SSL             |
| Local setup UI   | ✅ Shown in admin panel | ❌ Hidden automatically     |

**The code automatically detects the environment:**

```php
if (config('app.env') === 'local' && PHP_OS_FAMILY === 'Windows') {
    // Local Windows setup (hosts file, virtual host)
} else {
    // Production - skip local setup
}
```

---

## Troubleshooting

### Issue: Migrations fail with "relation does not exist"

**Solution:** Run landlord migrations first:

```bash
php artisan migrate --path=database/migrations/landlord --force
php artisan migrate --force
```

### Issue: Tenant domain not working

**Checklist:**

1. ✅ Domain added to Coolify?
2. ✅ DNS pointing to Coolify server?
3. ✅ SSL certificate generated?
4. ✅ Tenant exists in `tenants` table?

**Verify tenant:**

```bash
php artisan tinker
> Tenant::whereDomain('domain.yourdomain.com')->first()
```

### Issue: "Class 'Redis' not found"

**Solution:** If not using Redis, ensure:

```env
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

---

## Security Recommendations

1. **Strong APP_KEY:** Use `php artisan key:generate`
2. **Secure DB_PASSWORD:** Use a strong, random password
3. **APP_DEBUG=false:** Never enable debug in production
4. **HTTPS Only:** Coolify handles this automatically
5. **Environment Variables:** Never commit `.env` to git

---

## Quick Reference

### System Admin Login (from seeder)

- **Email:** `admin@example.com`
- **Password:** `password`

### Hospital Admin Login (from seeder)

- **Email:** `hospital.admin@example.com`
- **Password:** `password`

### Resident Login (from seeder)

- **Email:** Random (check database)
- **Password:** `password`

**⚠️ Change these passwords immediately in production!**

---

## Need Help?

- Check logs: `storage/logs/laravel.log`
- Run `php artisan tinker` to debug
- Check Coolify logs for deployment issues
- Ensure all environment variables are set correctly
