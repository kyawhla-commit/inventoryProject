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
