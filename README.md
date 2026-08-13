# Scry Database Manager (`scry/scry`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![Latest Tag](https://img.shields.io/github/v/tag/ninjadd/scry?label=tag&style=flat-square)](https://github.com/ninjadd/scry/tags)
[![Total Downloads](https://img.shields.io/packagist/dt/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![License](https://img.shields.io/github/license/ninjadd/scry?style=flat-square)](LICENSE)
[![Laravel Support](https://img.shields.io/badge/Laravel-10_%7C_11_%7C_12_%7C_13%2B-red.svg?style=flat-square)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2_%7C_8.3_%7C_8.4_%7C_8.5-blue.svg?style=flat-square)](https://php.net)

---

## Introduction

**Scry** is a fast, reactive, Vue-powered database manager for Laravel applications. It provides dynamic schema introspection, raw SQL execution, and interactive ERD diagrams directly inside your local Laravel environment.

Designed as a modern alternative to legacy tools like phpMyAdmin or heavy desktop clients, Scry runs as an embedded package inside your application. It automatically detects configured database connections, giving developers an instant, friction-free GUI to inspect tables, debug queries, and analyze database structures without leaving their browser.

---

## Features

- **Multi-Connection Support:** Seamlessly toggle between multiple database connections (PostgreSQL, MySQL, MariaDB, SQLite, and SQL Server) defined in your application.
- **Vue SPA Frontend:** Snappy, single-page application built with modern reactive components, dark/light theme support, and responsive typography.
- **Dynamic Schema Inspection:** Interactive Entity-Relationship Diagram (ERD) visualizer powered by Mermaid.js featuring canvas zooming, panning, draggable table nodes, dynamic foreign key arrows, and SVG/PNG exports.
- **Raw SQL Console:** Powerful SQL editor with execution timing, query history, bookmarking, and formatted JSON/tabular output.
- **Query-by-Example (QBE) Builder:** Visually compose complex SQL queries with joins, `WHERE` conditions, aggregations (`COUNT`, `SUM`, `AVG`), and custom sorting.
- **Streaming Data Import & Export:** High-performance data exports (`CSV`, `SQL`, `XML`, `JSON`) and quote-aware SQL script imports within database transactions.
- **Server Tuning & Process Monitor:** Live process listing (`PROCESSLIST`), active query cancellation, and automated health checks.
- **Global Cross-Table Search:** Search across all text fields and tables simultaneously.

---

## Installation

Install the package via Composer:

```bash
composer require scry/scry
```

---

## Configuration

Publish the pre-compiled Vue assets required for the single-page interface:

```bash
php artisan vendor:publish --tag=scry-assets
```

Optionally publish the package configuration file to `config/scry.php`:

```bash
php artisan vendor:publish --tag=scry-config
```

### Environment Restrictions

By default, Scry is locked down strictly to `local` and `testing` environments to prevent unauthorized access.

You can configure the URI path and allowed environments via environment variables or inside `config/scry.php`:

```env
SCRY_PATH=scry
```

In `config/scry.php`:

```php
'allowed_environments' => [
    'local',
    'testing',
],
```

---

## Usage

Once installed and assets are published, open your browser and navigate to:

```
http://your-app.test/scry
```

Use the top-navigation connection dropdown to toggle between database connections configured in your application's `config/database.php`. Scry automatically tests connection health and loads table schemas on demand.

---

## Security

> [!CAUTION]
> **STRICT SECURITY WARNING**
> Scry provides administrative access to read schema definitions and execute raw SQL queries on your database. **Do not expose Scry in production environments without proper gate authorization.**

To authorize users in non-local environments, define a custom authorization callback using `Scry::auth()` inside your `AppServiceProvider` or `AuthServiceProvider`:

```php
use Illuminate\Http\Request;
use Scry\Scry;

public function boot(): void
{
    Scry::auth(function (Request $request) {
        return app()->environment('local') ||
               ($request->user() && $request->user()->is_admin);
    });
}
```

---

## Contributing

We welcome community contributions! While Scry includes built-in inspectors for PostgreSQL and MySQL, community support and Pull Requests are actively encouraged to refine and expand drivers for **SQLite** and **SQL Server** (`sqlsrv`).

If you are interested in contributing schema introspection queries, extending driver interfaces, or improving the Vue frontend, please refer to our [CONTRIBUTING.md](CONTRIBUTING.md) guide.

---

## License

This package is open-sourced software licensed under the **[MIT License](LICENSE)**.
