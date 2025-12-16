<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
  <h1 align="center">Inventory Management System</h1>
  <p align="center">A comprehensive Laravel-based inventory management solution</p>
  
  <p align="center">
    <a href="#features">Features</a> •
    <a href="#prerequisites">Prerequisites</a> •
    <a href="#installation">Installation</a> •
    <a href="#usage">Usage</a>
  </p>
</p>

## Features

- **Multi-User Role System** (Admin, Manager, Staff)
- **Product Management** - Track inventory items with categories
- **Customer Management** - Maintain customer records and order history
- **Supplier Management** - Manage suppliers and their products
- **Purchase Orders** - Record and track inventory purchases
- **Sales Management** - Process and track customer sales
- **Order Management** - Manage customer orders and order statuses
- **Dashboard** - Visual overview of key metrics and reports
- **Low Stock Alerts** - Get notified when inventory runs low

## Prerequisites

- PHP >= 8.1
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 14+ & NPM
- Web server (Apache/Nginx) or PHP's built-in server

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/inventory-management-system.git
   cd inventory-management-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Create environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Configure database**
   Update `.env` file with your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=inventory
   DB_USERNAME=root
   DB_PASSWORD=
   ```

7. **Run migrations and seed the database**
   ```bash
   php artisan migrate --seed
   ```
   This will create the database tables and seed them with sample data.

8. **Link storage**
   ```bash
   php artisan storage:link
   
   ```

9. **Compile assets**
   ```bash
   npm run build
   ```

10. **Start the development server**
    ```bash
    php artisan serve
    ```

## Usage

1. Access the application at `http://localhost:8000`
2. Login using the default credentials:
   - **Admin**
     - Email: admin@example.com
     - Password: password
   - **Manager**
     - Email: manager@example.com
     - Password: password
   - **Staff**
     - Email: staff@example.com
     - Password: password

# How to Use This Inventory Project

This inventory management system helps you track products, manage stock, and monitor business operations efficiently. Below are detailed instructions on how to use the system:

## 1. Installation

- Clone the repository:
  ```bash
  git clone https://github.com/kyawhla-commit/inventoryProject.git
  cd inventoryProject
  ```
- Install dependencies:
  ```bash
  npm install
  ```
- Start the development server:
  ```bash
  npm run dev
  ```
  The app will be available at `http://localhost:3000`.

## 2. User Authentication
- Register a new account or log in with your credentials.
- User roles (admin, manager, staff) may have different permissions.

## 3. Dashboard Overview
- After login, you will see a dashboard summarizing inventory status, product counts, and recent activities.

## 4. Managing Products
- Navigate to the **Products** section.
- Add new products by providing details such as name, SKU, category, price, and quantity.
- Edit or delete existing products as needed.

## 5. Inventory Tracking
- Go to the **Inventory** section.
- View current stock levels for all products.
- Update stock quantities when new items arrive or are sold.
- Set low-stock alerts to avoid running out of products.

## 6. Expense Management
- Access the **Expenses** section to log and categorize business expenses.
- View expense history and generate reports for financial analysis.

## 7. User Management
- In the **Users** section, admins can add, edit, or remove users.
- Assign roles and permissions to control access to different features.

## 8. Settings
- Customize system preferences, such as currency, language, and notification settings.
- Update your profile information.

## 9. Reports & Analytics
- Generate inventory, sales, and expense reports for business insights.
- Export data as CSV or PDF for external use.

## 10. Support
- For help or troubleshooting, refer to the documentation or contact the project maintainer.

---

This guide covers the main features and usage of the inventory management system. For more details, explore each section in the app or check the source code for advanced customization.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security Vulnerabilities

If you discover a security vulnerability, please send an email to your-email@example.com. All security vulnerabilities will be promptly addressed.

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).

## About Laravel

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

---

*This project was developed using Laravel. For more information about the framework, visit the [official Laravel documentation](https://laravel.com/docs).*

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Docker Daily Usage

This project includes a convenient `docker-manage.sh` script for easy Docker management.

### Quick Start

```bash
# First time setup - build and start
./docker-manage.sh build
./docker-manage.sh start
```

Access your app at **http://localhost:8080**

### Daily Commands

| Command | Description |
|---------|-------------|
| `./docker-manage.sh start` | Start the application |
| `./docker-manage.sh stop` | Stop the application |
| `./docker-manage.sh restart` | Restart the application |
| `./docker-manage.sh logs` | Watch live logs (Ctrl+C to exit) |
| `./docker-manage.sh status` | Check if container is running |

### Troubleshooting Commands

| Command | When to use |
|---------|-------------|
| `./docker-manage.sh fix-csrf` | Getting 419 errors on forms |
| `./docker-manage.sh fix-db` | Database readonly errors |
| `./docker-manage.sh fix-sqlite` | SQLite compatibility issues |
| `./docker-manage.sh shell` | Run artisan commands or debug inside container |
| `./docker-manage.sh db` | Open SQLite database directly |

### After Code Changes

```bash
# If you changed PHP code only - just restart
./docker-manage.sh restart

# If you changed Dockerfile or dependencies - rebuild
./docker-manage.sh rebuild
```

### Using Docker Compose (Alternative)

```bash
docker-compose up -d      # Start in background
docker-compose down       # Stop
docker-compose logs -f    # View logs
```

---

## Offline & Portable Deployment

You can ship this application to a customer who has **no internet connection**.

### Option 1 – Portable Windows Bundle (SQLite)
1. Set `.env` to SQLite:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   ```
2. Create the DB and migrate:
   ```bash
   mkdir -p database && type nul > database\database.sqlite
   php artisan migrate --seed
   ```
3. Copy a portable PHP runtime (PHP 8.x zip) into `runtime\php`.
4. Add a launcher `start.bat`:
   ```bat
   @echo off
   runtime\php\php.exe -S localhost:8000 -t public > server.log 2>&1 &
   start "" http://localhost:8000
   pause
   ```

### Option 2 – Docker Appliance (cross-platform)
1. Provide this `docker-compose.yml`:
   ```yaml
   version: "3.9"
   services:
     app:
       image: php:8.2-cli
       working_dir: /var/www
       volumes:
         - ./:/var/www
       command: php artisan serve --host=0.0.0.0 --port=8000
       depends_on: [db]
     db:
       image: mariadb:10.9
       environment:
         MYSQL_ROOT_PASSWORD: secret
         MYSQL_DATABASE: inventory
   volumes:
     dbdata:
   ```
2. Ship `start.bat` / `start.sh` that runs `docker compose up --build` and opens the browser.

---

