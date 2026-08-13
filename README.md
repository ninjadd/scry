# Scry Database Manager (`scry/scry`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![Latest Tag](https://img.shields.io/github/v/tag/ninjadd/scry?label=tag&style=flat-square)](https://github.com/ninjadd/scry/tags)
[![Total Downloads](https://img.shields.io/packagist/dt/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![License](https://img.shields.io/github/license/ninjadd/scry?style=flat-square)](LICENSE)
[![Laravel Support](https://img.shields.io/badge/Laravel-10_%7C_11_%7C_12_%7C_13%2B-red.svg?style=flat-square)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2_%7C_8.3_%7C_8.4_%7C_8.5-blue.svg?style=flat-square)](https://php.net)

A modern, reactive, multi-database explorer and management suite for Laravel applications, inspired by Laravel Telescope and built with a decoupled Vue 3 SPA architecture. Out-of-the-box support for **PostgreSQL**, **MySQL**, **MariaDB**, **SQLite**, and **SQL Server**.

---

## ⚡ Key Features

- 🕸️ **Interactive ERD Schema Visualizer**: Entity-relationship diagram visualizer powered by Mermaid.js. Includes interactive canvas **Zoom In / Zoom Out**, **Mouse Wheel Zoom**, **Canvas Panning**, **Draggable Table Cards**, and **Sticky Foreign Key Arrows** that dynamically stretch as nodes move. Export diagrams as Mermaid code, SVG, or high-DPI PNG.
- 🔌 **Smart Connection Switcher**: Probes TCP ports and SQLite files so the connection selector displays only active, reachable database connections on the dashboard and automatically falls back to live databases.
- 📊 **Table Views & Pagination**: Complete pagination controls (`page`, `per_page`, record counts) rendered across all table data views.
- 🛠️ **Query-by-Example (QBE) Builder**: Visually compose complex SQL queries with joins, WHERE filters, aggregations (`COUNT`, `SUM`, `AVG`), grouping, and sorting.
- 💻 **Raw SQL Console**: SQL query editor with snippet library, bookmarking, execution timing, and formatted JSON output.
- 📦 **Import & Export**: High-performance streaming exports (`CSV`, `SQL`, `XML`, `JSON`) and quote-aware transaction imports.
- ⚡ **Server Tuning & Slow Query Diagnostics**: Real-time process monitor (`PROCESSLIST`), active session killer, and database configuration health advisor.
- 🔍 **Global Database Search**: Cross-table `LIKE` search across all text columns and tables.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require scry/scry
```

Publish package frontend assets:

```bash
php artisan vendor:publish --tag=scry-assets
```

Visit the dashboard in your browser:
👉 `http://your-app.test/scry`

---

## 🎨 Seasons #63 Design System

Scry features a custom design system built around the curated **Seasons #63** color palette and clean typography.

| Color Name | Hex Code | Purpose / Application |
| --- | --- | --- |
| **Pomegranate Purple** | `#b91c5c` / `#e63980` | Primary brand identity, active state navigation, primary buttons. |
| **Sulphur Yellow** | `#f8f1c8` | Light mode surface highlights, primary badges, dark mode text accents. |
| **Glaucous Green** | `#adcebe` / `#e4f0ea` | Data type pills, row count indicators, success status badges. |
| **Pale King's Blue 2** | `#a1d5eb` / `#e1f2fa` | Schema view buttons, foreign key indicators, secondary button highlights. |
| **Slate Color** | `#384950` / `#1c262a` | Body typography, container background, dark mode surface container. |

### Theme Modes
- **Light Mode (Default)**: Active default preference stored in `localStorage`.
- **Dark Mode**: Seamless dark mode using Seasons #63 dark slate styling.
- **Toggle**: Located in the bottom-left sidebar navigation.

---

## 🧪 Testing & Development Stack

### Automated Unit & Feature Tests
```bash
composer test
# or
./vendor/bin/phpunit
```

### Multi-Database Seeding & Docker Stack
Scry includes a Docker Compose environment for testing PostgreSQL, MySQL, MariaDB, and SQL Server simultaneously:

```bash
docker compose up -d
php artisan scry:seed-all --fresh
```

---

## 📁 Repository Structure

```
scry/
├── composer.json                 # Package Configuration (scry/scry)
├── LICENSE                       # MIT Open Source License
├── README.md                     # Documentation
├── Dockerfile                    # Container image for dev environment
├── docker-compose.yml            # PostgreSQL, MySQL, MariaDB, SQL Server stack
├── phpunit.xml                   # PHPUnit Test Suite Configuration
├── tests/                        # Unit & Feature Test Suite
├── src/                          # PHP Source Code & Driver Inspectors
│   ├── ScryServiceProvider.php    # Core Package Service Provider
│   ├── DatabaseExplorerManager.php# Connection Prober & Driver Resolver
│   ├── Contracts/                # DatabaseInspector Interface
│   ├── Services/                 # SqlRunner, ExportService, ImportService, GlobalSearch, TuningAdvisor
│   ├── Http/Controllers/         # ApiController
│   └── Inspectors/               # Postgres, Mysql, Mariadb, Sqlite, Sqlsrv Inspectors
├── routes/                       # Web & API Routes (/scry & /scry/api)
├── resources/                    # Blade views & Vue 3 SPA frontend
└── dummy-app/                    # Host Testing Application
```

---

## 📄 License

This package is open-sourced software licensed under the **[MIT License](LICENSE)**.

Copyright (c) 2026 **Daniel Dickson**.
