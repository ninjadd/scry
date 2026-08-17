# Scry Database Manager (`scry/scry`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![Latest Tag](https://img.shields.io/github/v/tag/ninjadd/scry?label=tag&style=flat-square)](https://github.com/ninjadd/scry/tags)
[![Total Downloads](https://img.shields.io/packagist/dt/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![License](https://img.shields.io/github/license/ninjadd/scry?style=flat-square)](LICENSE)
[![Laravel Support](https://img.shields.io/badge/Laravel-10_%7C_11_%7C_12_%7C_13%2B-red.svg?style=flat-square)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2_%7C_8.3_%7C_8.4_%7C_8.5-blue.svg?style=flat-square)](https://php.net)

---

## Introduction

**Scry** is a high-performance, reactive, Vue 3-powered database manager and visual workbench for Laravel applications. It brings full-featured MySQL Workbench and desktop-class database administration tools directly inside your browser.

Scry runs seamlessly as an embedded Laravel package. It dynamically inspects connections defined in `config/database.php` across **MySQL**, **MariaDB**, **PostgreSQL**, **SQLite**, and **SQL Server (`sqlsrv`)**, giving developers an immediate, zero-friction interface to inspect schemas, execute queries, design DDL blueprints, manage foreign keys, and monitor server health.

---

## Features & Workbench Capabilities

### 1. Monaco SQL Console & Query Runner
- **Monaco SQL Editor:** Syntax highlighting, code folding, auto-indentation, and shortcut execution (`Cmd+Enter` / `Ctrl+Enter`).
- **Execution Timing & Metrics:** Real-time query duration measurement badge in milliseconds (`ms`) and affected row counts.
- **Query History & Bookmarks:** Persistent execution history stack with timestamp search and reusable query bookmark drawers.
- **Dual Result Viewer:** Toggle between an interactive sortable tabular data grid (with instant CSV export) and syntax-highlighted formatted JSON viewer.

### 2. Visual Query-by-Example (QBE) Builder
- **Visual Join Configurator:** Build multi-table queries with `INNER JOIN`, `LEFT JOIN`, `RIGHT JOIN`, and `FULL OUTER JOIN`.
- **Filtering & Aggregations:** Drag-and-drop WHERE criteria with comparison operators (`=`, `!=`, `LIKE`, `IN`, `BETWEEN`, `IS NULL`) and aggregate functions (`COUNT`, `SUM`, `AVG`, `MIN`, `MAX`).
- **Dialect-Aware Live SQL:** Real-time query generator with automatic identifier quoting (backticks for MySQL/MariaDB, double quotes for Postgres/SQLite, square brackets for SQL Server).
- **Direct Navigation:** Execute queries directly in place or transfer them seamlessly to the SQL Console.

### 3. Interactive Mermaid.js ERD Visualizer
- **Automated Schema Relationship Map:** Driver-level foreign key and primary key extraction across all tables.
- **Dynamic Canvas Controls:** Interactive zoom in/out, pan/drag canvas, table search filter, and draggable node positioning.
- **Export Utilities:** Download diagrams directly as **SVG** or high-resolution **PNG**, or copy raw Mermaid.js syntax.

### 4. Global Cross-Table Search
- **Driver-Wide Column Scanning:** Automatically identifies `VARCHAR`, `TEXT`, `JSON`, `UUID`, and string columns across all tables.
- **Memory-Safe Batch Querying:** Prevents memory exhaustion when scanning large database instances.
- **Match Highlighting & Pagination:** Grouped table matches with per-table row counts and keyword highlighting.

### 5. Server Process Monitor & Health Advisor
- **Live Process Listing:** Inspect active threads and queries across all engines (`SHOW FULL PROCESSLIST`, `pg_stat_activity`, `sys.dm_exec_requests`).
- **Safe Query Termination:** Cancel long-running threads (`KILL {pid}`, `pg_terminate_backend({pid})`) via confirmation modal.
- **Connection Health & Polling:** Automated background health checks with response latency monitoring.
- **Tuning Diagnostics:** Automated optimization suggestions for storage, indexing, and configuration.

### 6. Streaming Data Import & Export
- **Multi-Format Export:** High-performance streaming exports in `CSV`, `SQL Dump`, `XML`, and `JSON` formats.
- **Transactional Imports:** Multi-statement `.sql` and `.csv` imports executed inside isolated database transactions (`DB::beginTransaction()` / `DB::rollBack()`) with precise statement failure reporting.

### 7. Visual Table Designer & DDL Blueprint Manager
- **Visual Column Designer:** Configure column names, data types, precision, nullability, auto-increments, and defaults.
- **Index & Foreign Key Manager:** Create and drop secondary indexes, unique constraints, and cascading foreign keys.
- **Safe Drop & Truncate:** Danger confirmation modals requiring explicit typed table confirmation to eliminate accidental data loss.

---

## Supported Database Drivers

| Database Driver | Schema Introspection | Monaco SQL Console | QBE Builder | Mermaid ERD | Process Monitor | DDL / Alter Table |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **MySQL (8.0+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **MariaDB (10.4+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **PostgreSQL (12+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **SQLite (3.8+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **SQL Server (2019+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Installation

Install the package via Composer:

```bash
composer require scry/scry
```

Publish the pre-compiled Vue single-page application assets:

```bash
php artisan vendor:publish --tag=scry-assets --force
```

Optionally publish the package configuration file to `config/scry.php`:

```bash
php artisan vendor:publish --tag=scry-config
```

---

## Quickstart & Local Verification

1. Start your local development server:

```bash
php artisan serve
```

2. Open your browser and navigate to:

```
http://localhost:8000/scry
```

3. Toggle between configured database connections in the top-right dropdown to inspect schemas, visualize ERDs, and run queries.

---

## Docker Compose Test Environment

A multi-driver Docker Compose environment is bundled for integration testing across PostgreSQL, MySQL, MariaDB, and SQL Server:

```bash
# Start all database engines
docker compose up -d

# Run migrations and seed rich relational test data
php artisan migrate:fresh --seed
```

---

## Security & Gate Authorization

> [!CAUTION]
> **STRICT SECURITY WARNING**
> Scry provides administrative database access. By default, Scry is locked strictly to `local` and `testing` environments. Do not expose Scry in production environments without authorization gates.

To authorize users in non-local environments, define an authorization gate using `Scry::auth()` in your `AppServiceProvider`:

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

## Running Tests

Run the automated test suite with PHPUnit:

```bash
composer test
# or
./vendor/bin/phpunit
```

---

## License

This package is open-sourced software licensed under the **[MIT License](LICENSE)**.
