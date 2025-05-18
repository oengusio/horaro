# Horaro Local development guide

## Prerequisites
1. Mariadb installation
2. php 8.3+
3. composer (https://getcomposer.org/)
4. symfony cli (https://symfony.com/download)

## Installation for local development
1. Clone the repository
2. Copy `config/parameters.dist.yml` to `config/parameters.yml` (turn debug on if you don't want to configure optimus)
3. Configure `database.url` following this format: `mysql://USERNAME:PASSWORD@HOST:PORT/DBNAME?serverVersion=10.11.2-MariaDB&charset=utf8mb4`
4. Run `resources/schema.sql` and `resources/seed-data.sql`
5. Run `composer install`
6. Run `symfony server:start` to start the development server
7. Visit `http://localhost:8000` in your browser

You should now have a working local instance of Horaro. You will be able to log in with "operator" as username and password.
