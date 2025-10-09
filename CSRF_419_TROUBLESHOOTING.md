# 419 Page Expired Error - Troubleshooting Guide

## What is the 419 Error?
The "419 Page Expired" error occurs when Laravel's CSRF (Cross-Site Request Forgery) token expires or becomes invalid. This is a security feature to protect against malicious requests.

## Quick Fix
Run the automated fix command:
```bash
./docker-manage.sh fix-csrf
```

Or use the standalone script:
```bash
./fix-csrf.sh
```

## Manual Steps to Fix

### 1. Clear Laravel Caches
```bash
docker exec laravel-inventory php artisan config:clear
docker exec laravel-inventory php artisan cache:clear
docker exec laravel-inventory php artisan view:clear
docker exec laravel-inventory php artisan route:clear
```

### 2. Regenerate Application Key
```bash
docker exec laravel-inventory php artisan key:generate --force
```

### 3. Restart Container
```bash
docker restart laravel-inventory
```

### 4. Clear Browser Data
- Clear browser cache and cookies for `localhost:8080`
- Or use an incognito/private browsing window
- Ensure you're accessing `http://localhost:8080` (not https)

## Common Causes

### 1. Session Configuration Issues
- Incorrect `APP_URL` in .env file
- Session driver misconfiguration
- Cookie domain/path issues

### 2. Application Key Issues
- Missing or invalid `APP_KEY`
- Key changed after sessions were created

### 3. Browser/Network Issues
- Cached CSRF tokens in browser
- Mixed HTTP/HTTPS requests
- Proxy or firewall interference

## Prevention

### 1. Proper Environment Configuration
Ensure your `.env` file has correct settings:
```env
APP_URL=http://localhost:8080
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### 2. Regular Cache Clearing
After configuration changes, always clear caches:
```bash
php artisan config:clear
php artisan cache:clear
```

### 3. Consistent URL Usage
Always use the same URL format (http vs https, with/without www)

## Advanced Debugging

### Check Session Configuration
```bash
docker exec laravel-inventory php artisan config:show session
```

### Check Application Key
```bash
docker exec laravel-inventory php artisan tinker --execute="echo config('app.key');"
```

### View Laravel Logs
```bash
docker exec laravel-inventory tail -f /var/www/html/storage/logs/laravel.log
```

### Check Session Table
```bash
docker exec laravel-inventory php artisan tinker --execute="echo 'Sessions: ' . DB::table('sessions')->count();"
```

## If Problems Persist

1. **Rebuild the container completely:**
   ```bash
   ./docker-manage.sh rebuild
   ```

2. **Check for JavaScript errors** in browser console

3. **Verify form has CSRF token:**
   - View page source and look for `<input name="_token">`
   - Check if `@csrf` directive is present in forms

4. **Test with different browser** or device

## Contact Support
If none of these solutions work, provide:
- Browser and version
- Exact error message
- Steps to reproduce
- Laravel logs from the time of error