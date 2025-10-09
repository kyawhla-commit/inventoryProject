# Database Summary - Laravel Inventory System

## 🗄️ Database Overview
- **Type**: SQLite
- **Location**: `/var/www/html/database/database.sqlite`
- **Status**: ✅ Fully populated with sample data

## 📊 Data Summary

### Users (2 records)
- **Admin User**: admin@example.com / password (Role: admin)
- **Staff User**: staff@example.com / password (Role: staff)

### Core Inventory Data
- **Products**: 50 items with prices, quantities, and categories
- **Categories**: 5 categories (Electronics, Books, Clothing, Home & Kitchen, Toys & Games)
- **Suppliers**: 10 supplier companies
- **Customers**: 20 customer records

### Transaction Data
- **Sales**: 50 completed sales transactions
- **Purchases**: 20 purchase orders
- **Orders**: Various order records

### Production Data
- **Raw Materials**: Materials for production
- **Recipes**: 3 sample recipes with ingredients
- **Staff**: 5 staff members for production tracking

## 🔍 How to View Your Database

### Quick Commands
```bash
# View record counts
./view-database.sh count

# View specific data
./view-database.sh users
./view-database.sh products
./view-database.sh categories
./view-database.sh sales

# Open interactive SQLite shell
./docker-manage.sh db
```

### Database Viewer Script
```bash
./view-database.sh {tables|users|products|categories|sales|count|shell|backup}
```

### Direct Laravel Commands
```bash
# Using Tinker (Laravel's REPL)
docker exec -it laravel-inventory php artisan tinker

# Example queries in Tinker:
User::all()
Product::with('category')->limit(5)->get()
Sale::with('customer')->latest()->limit(10)->get()
```

## 🔑 Login Credentials

### Admin Access
- **Email**: admin@example.com
- **Password**: password
- **Role**: admin (full access)

### Staff Access
- **Email**: staff@example.com
- **Password**: password
- **Role**: staff (limited access)

## 📋 Available Tables

### Core Tables
- `users` - System users (admin, staff)
- `products` - Inventory products
- `categories` - Product categories
- `suppliers` - Supplier information
- `customers` - Customer records

### Transaction Tables
- `sales` - Sales transactions
- `sale_items` - Individual sale items
- `purchases` - Purchase orders
- `purchase_items` - Purchase order items
- `orders` - General orders
- `order_items` - Order line items

### Production Tables
- `raw_materials` - Production materials
- `recipes` - Product recipes
- `recipe_items` - Recipe ingredients
- `production_plans` - Production planning
- `staff` - Production staff

### System Tables
- `sessions` - User sessions
- `cache` - Application cache
- `notifications` - System notifications
- `personal_access_tokens` - API tokens

## 🛠️ Database Management

### Backup Database
```bash
./view-database.sh backup
```

### Reset and Reseed
```bash
docker exec laravel-inventory php artisan migrate:fresh --seed
```

### Fix Permissions
```bash
./docker-manage.sh fix-db
```

## 🌐 Web Interface
Access your inventory system at: **http://localhost:8080**

The web interface provides:
- Dashboard with sales/inventory overview
- Product management
- Sales and purchase tracking
- Customer and supplier management
- Production planning
- Reports and analytics

Your database is now fully populated and ready for use!