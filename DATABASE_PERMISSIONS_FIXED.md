# Database Permissions Issue - RESOLVED

## Problem

```
Illuminate\Database\QueryException
SQLSTATE[HY000]: General error: 8 attempt to write a readonly database
```

## Root Cause

The SQLite database directory (`/var/www/html/database/`) was owned by user ID 1000, but Apache runs as `www-data`. This prevented Laravel from writing to the database for caching and other operations.

## Solution Applied

### 1. Fixed Current Container

```bash
docker exec laravel-inventory chown -R www-data:www-data /var/www/html/database
docker exec laravel-inventory chmod -R 775 /var/www/html/database
```

### 2. Updated Dockerfile

-   Modified permissions setup to include entire database directory
-   Ensures proper ownership for future builds

### 3. Enhanced Startup Script

-   Added comprehensive database permissions check
-   Automatically fixes permissions on container start

### 4. Created Management Tools

#### Quick Fix Command

```bash
./docker-manage.sh fix-db
```

#### Comprehensive Fix Script

```bash
./fix-database-permissions.sh
```

## Prevention

The updated Docker configuration now automatically:

-   Sets proper ownership of database directory
-   Applies correct permissions (775 for directory, 664 for database file)
-   Runs permission checks on container startup

## Verification

Database write access confirmed:

```bash
docker exec laravel-inventory php artisan tinker --execute="DB::table('cache')->insert(['key' => 'test', 'value' => 'test', 'expiration' => time() + 3600]); echo 'SUCCESS';"
```

## Files Updated

-   `Dockerfile` - Enhanced permissions setup
-   `docker/startup.sh` - Added database permission checks
-   `docker-manage.sh` - Added `fix-db` command
-   `fix-database-permissions.sh` - New comprehensive fix script

## Status

✅ **RESOLVED** - Database is now fully writable and the readonly error is eliminated.

The Laravel application can now:

-   Write cache data to database
-   Store sessions in database
-   Perform all database operations without permission errors
