# Scry Database Manager (`scry/scry`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![Latest Tag](https://img.shields.io/github/v/tag/ninjadd/scry?label=tag&style=flat-square)](https://github.com/ninjadd/scry/tags)
[![Total Downloads](https://img.shields.io/packagist/dt/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![Tests Passing](https://img.shields.io/badge/Tests-47%20Passing-emerald.svg?style=flat-square)](https://github.com/ninjadd/scry)
[![License](https://img.shields.io/github/license/ninjadd/scry?style=flat-square)](LICENSE)
[![Laravel Support](https://img.shields.io/badge/Laravel-10_%7C_11_%7C_12_%7C_13%2B-red.svg?style=flat-square)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2_%7C_8.3_%7C_8.4_%7C_8.5-blue.svg?style=flat-square)](https://php.net)

---

## 📖 Introduction

**Scry** is a high-performance, reactive, Vue 3-powered database manager and visual workbench for Laravel applications. It brings desktop-class database administration tools—such as visual DDL designers, Monaco SQL console, Query-by-Example (QBE) builders, and interactive Mermaid.js ERD diagrams—directly into your web browser.

Designed as an embedded developer tool for Laravel, Scry automatically discovers all connections defined in `config/database.php` across **PostgreSQL**, **MySQL**, **MariaDB**, **SQLite**, and **SQL Server (`sqlsrv`)**, giving developers an instant, friction-free interface to inspect schemas, execute queries, manage foreign keys, and monitor server health.

---

## 📑 Table of Contents

- [Features & Workbench Capabilities](#-features--workbench-capabilities)
  - [1. Monaco SQL Console & Execution Metrics](#1-monaco-sql-console--execution-metrics)
  - [2. Visual Query-by-Example (QBE) Builder](#2-visual-query-by-example-qbe-builder)
  - [3. Interactive Mermaid.js ERD Visualizer](#3-interactive-mermaidjs-erd-visualizer)
  - [4. Global Cross-Table Search](#4-global-cross-table-search)
  - [5. Server Process Monitor & Health Advisor](#5-server-process-monitor--health-advisor)
  - [6. Streaming Data Import & Export](#6-streaming-data-import--export)
  - [7. Visual Table Designer & DDL Blueprint Manager](#7-visual-table-designer--ddl-blueprint-manager)
- [Supported Database Drivers](#-supported-database-drivers)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Security & Gate Authorization](#-security--gate-authorization)
- [Quickstart & Local Verification](#-quickstart--local-verification)
- [Multi-Driver Docker Compose Environment](#-multi-driver-docker-compose-environment)
- [Running Automated Tests](#-running-automated-tests)
- [License](#-license)

---

## ⚡ Features & Workbench Capabilities

### 1. Monaco SQL Console & Execution Metrics
- **Full Monaco SQL Editor:** Syntax highlighting, code folding, bracket matching, autocomplete, and shortcut execution (`Cmd+Enter` / `Ctrl+Enter`).
- **Execution Timing:** Real-time query execution duration badge measured in milliseconds (`ms`) alongside affected row counts.
- **Persistent History & Bookmarks:** Local execution history drawer with search, timestamp tracking, and saved query bookmarks.
- **Dual Result Viewer:** Toggle between an interactive sortable tabular data grid (with column sorting and instant CSV export) and a formatted JSON viewer.

### 2. Visual Query-by-Example (QBE) Builder
- **Multi-Table Visual Joins:** Configure `INNER JOIN`, `LEFT JOIN`, `RIGHT JOIN`, and `FULL OUTER JOIN` without writing manual SQL.
- **Filtering & Aggregations:** Drag-and-drop WHERE criteria with comparison operators (`=`, `!=`, `LIKE`, `IN`, `BETWEEN`, `IS NULL`) and aggregate functions (`COUNT`, `SUM`, `AVG`, `MIN`, `MAX`).
- **Dialect-Aware Live SQL:** Real-time statement preview that automatically adapts quote wrapping (backticks for MySQL/MariaDB, double quotes for Postgres/SQLite, square brackets for SQL Server) and paging limits.
- **One-Click Hand-Off:** Execute queries directly in place or transfer them seamlessly into the Monaco SQL Console for further refinement.

### 3. Interactive Mermaid.js ERD Visualizer
- **Automated Schema Relationship Discovery:** Inspects primary keys, unique constraints, and foreign key relationships across all configured database engines.
- **Dynamic Canvas Controls:** Interactive zoom in/out/reset, mouse wheel zooming, drag/pan canvas, and instant table name search filter.
- **Export Utilities:** One-click downloads to **SVG** and high-resolution **PNG**, or copy raw Mermaid.js syntax to the clipboard.
- **Schema Cards Mode:** Alternate card view displaying row counts, column types, primary keys, and foreign key references.

### 4. Global Cross-Table Search
- **Driver-Wide Column Discovery:** Automatically scans `VARCHAR`, `TEXT`, `JSON`, `UUID`, and string columns across all tables simultaneously.
- **Memory-Safe Batch Querying:** Prevents memory exhaustion by executing bounded batch queries across large databases.
- **Match Highlighting & Scoping:** Grouped table results with per-table match counters and keyword highlighting. Scope search to specific tables via the table filter dropdown.

### 5. Server Process Monitor & Health Advisor
- **Live Process Listing:** Inspect active threads and long-running queries across all engines (`SHOW FULL PROCESSLIST`, `pg_stat_activity`, `sys.dm_exec_requests`).
- **Safe Query Termination:** Cancel hung threads (`KILL {pid}`, `pg_terminate_backend({pid})`) with a typed confirmation modal.
- **Connection Health & Auto-Polling:** Live connection latency badge (`Online (0.42ms)`) with auto-refresh intervals (3s, 5s, 10s, 30s).
- **Tuning Diagnostics:** Automated optimization suggestions for indexing, query performance, and server configuration.

### 6. Streaming Data Import & Export
- **Multi-Format Streaming Export:** Export tables to **CSV**, **SQL Dump** (with optional `DROP TABLE IF EXISTS`), **XML**, and **JSON** without memory bottlenecks.
- **Transactional Imports:** Multi-statement `.sql` and `.csv` imports executed inside isolated database transactions (`DB::beginTransaction()` / `DB::rollBack()`). Catches syntax and constraint violations with statement failure reporting.

### 7. Visual Table Designer & DDL Blueprint Manager
- **Visual Column Designer:** Configure column names, data types, precision, nullability, auto-increments, and default values.
- **Column Presets:** One-click presets for standard columns (`+ id (PK)`, `+ timestamps`, `+ uuid`).
- **Index & Foreign Key Managers:** Dedicated modals to create and drop secondary indexes, unique constraints, and foreign key constraints.
- **Safe Drop & Truncate:** Danger confirmation modals requiring typing the exact table name to eliminate accidental data loss.

---

## 🗄️ Supported Database Drivers

| Database Driver | Schema Introspection | Monaco SQL Console | QBE Builder | Mermaid ERD | Process Monitor | DDL / Alter Table |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **MySQL (8.0+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **MariaDB (10.4+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **PostgreSQL (12+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **SQLite (3.8+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **SQL Server (2019+)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 📦 Installation

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

## ⚙️ Configuration

In `config/scry.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Scry URI Path
    |--------------------------------------------------------------------------
    | The base URI path where Scry's web interface is accessible.
    */
    'path' => env('SCRY_PATH', 'scry'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    | Environments where Scry is accessible without custom authorization.
    */
    'allowed_environments' => [
        'local',
        'testing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web',
    ],
];
```

---

## 🔒 Security & Gate Authorization

> [!CAUTION]
> **STRICT SECURITY WARNING**
> Scry provides administrative database access (raw query execution, DDL alteration, process cancellation). By default, Scry is locked strictly to `local` and `testing` environments. Do not expose Scry in production environments without proper authorization gates.

To authorize users in non-local environments, define an authorization gate using `Scry::auth()` inside your `AppServiceProvider` or `AuthServiceProvider`:

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

## 🚀 Quickstart & Local Verification

1. Start your local development server:

```bash
php artisan serve
```

2. Open your browser and navigate to:

```
http://localhost:8000/scry
```

3. Toggle between configured database connections in the top-left dropdown to inspect schemas, visualize ERDs, and run queries.

---

## 🐳 Multi-Driver Docker Compose Environment

A multi-driver Docker Compose setup is bundled for integration testing across all database engines:

```bash
# Start PostgreSQL, MySQL, MariaDB, and SQL Server containers
docker compose up -d

# Run migrations and seed rich relational test data on each engine:
php artisan migrate:fresh --seed --database=pgsql
php artisan migrate:fresh --seed --database=mysql
php artisan migrate:fresh --seed --database=mariadb
php artisan migrate:fresh --seed --database=sqlsrv
php artisan migrate:fresh --seed --database=sqlite
```

---

## 🧪 Running Automated Tests

Run the full PHPUnit test suite:

```bash
composer test
# or
./vendor/bin/phpunit
```

All 47 tests (157 assertions) cover:
- Schema introspection across MySQL, MariaDB, PostgreSQL, SQLite, and SQL Server.
- SQL Runner security, dialect generation, and execution metrics.
- Global cross-table search with batch limits.
- Process listing and health monitoring.
- Transactional imports and streaming exports.
- DDL table creation, column altering, and index/foreign key management.

---

## 📄 License

This package is open-sourced software licensed under the **[MIT License](LICENSE)**.
