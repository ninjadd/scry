# Scry Database Manager (`scry/scry`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![Total Downloads](https://img.shields.io/packagist/dt/scry/scry.svg?style=flat-square)](https://packagist.org/packages/scry/scry)
[![License](https://img.shields.io/packagist/l/scry/scry.svg?style=flat-square)](LICENSE)
[![Laravel Support](https://img.shields.io/badge/Laravel-10_%7C_11_%7C_12_%7C_13%2B-red.svg?style=flat-square)](https://laravel.com)

A modern, reactive, multi-database explorer and management suite for Laravel applications, inspired by Laravel Telescope and built with a decoupled Vue 3 SPA architecture. Out-of-the-box support for **PostgreSQL**, **MySQL**, **MariaDB**, **SQLite**, and **SQL Server**.

---

## Seasons #63 Design System & Theme Engine

Scry features a custom design system built around the curated **Seasons #63** color palette and clean typography with zero emojis (strictly SVG icon system).

| Color Name | Hex Code | Purpose / Application |
| --- | --- | --- |
| **Pomegranate Purple** | `#b91c5c` / `#e63980` | Primary brand identity, active state navigation, primary buttons, active sorting indicators. |
| **Sulphur Yellow** | `#f8f1c8` | Light mode warm surface highlights, primary badges, dark mode text accents. |
| **Glaucous Green** | `#adcebe` / `#e4f0ea` | Data type pills, row count indicators, success status badges. |
| **Pale King's Blue 2** | `#a1d5eb` / `#e1f2fa` | Schema view buttons, foreign key indicators, secondary button highlights. |
| **Slate Color** | `#384950` / `#1c262a` | Body typography, sidebar container background, dark mode surface container. |

### Theme Mode & Toggle
- **Light Mode (Default)**: Light mode (`theme-light`) is set as the active default preference (`localStorage.getItem('scry-theme') || 'light'`).
- **Dark Mode (`theme-dark`)**: Seamless dark mode override using the Seasons #63 dark slate background.
- **Side Nav Toggle**: Situated in the **bottom left-hand side of the sidebar navigation** for instant theme switching.

---

## Automated Test Suite & Multi-DB Seeding

Scry includes a 100% automated PHPUnit / Orchestra Testbench test suite covering driver manager resolution, catalog inspectors, services, API endpoints, DDL, and row CRUD.

### Running Package Unit & Feature Tests
```bash
cd scry-package
composer test
# or
./vendor/bin/phpunit
```

### Multi-Database Seeding Command
To seed all 5 database engines (`pgsql`, `mysql`, `mariadb`, `sqlite`, `sqlsrv`) simultaneously:
```bash
php artisan scry:seed-all --fresh
```

---

## Architecture Overview

```
scry/ (Workspace Root)
├── scry-package/                 # Standalone Laravel Package
│   ├── composer.json             # Package scry/scry
│   ├── phpunit.xml               # PHPUnit Test Runner Configuration
│   ├── tests/                    # Automated Test Suite (Unit & Feature)
│   ├── src/                      # PHP Core & Driver Manager Logic
│   │   ├── ScryServiceProvider.php # Core Service Provider
│   │   ├── DatabaseExplorerManager.php # Driver Manager & Resolver
│   │   ├── Contracts/DatabaseInspector.php # Core Inspector Interface
│   │   ├── Services/
│   │   │   ├── SqlRunner.php             # SqlRunner Service
│   │   │   ├── ExportService.php         # ExportService (CSV, SQL, XML, PDF, Word .doc, ODT, JSON, LaTeX)
│   │   │   ├── ImportService.php         # ImportService (Quote-aware SQL parser & CSV batch stream)
│   │   │   ├── GlobalSearchService.php   # Database-level LIKE search across text columns
│   │   │   └── ServerTuningAdvisor.php   # Status Variables & Process List Tuning Advisor
│   │   ├── Http/Controllers/ApiController.php # API Controller
│   │   └── Inspectors/                   # Postgres, Mysql, Mariadb, Sqlite, Sqlsrv Inspectors
│   ├── routes/                           # Web & API routes (/scry & /scry/api)
│   └── resources/                        # Blade views & Vue 3 SPA frontend
│
└── dummy-app/                    # Host Laravel Application
    ├── app/Console/Commands/     # SeedAllDatabasesCommand (php artisan scry:seed-all)
    ├── config/database.php       # All 5 database engine connections
    └── docker-compose.yml        # PostgreSQL, MySQL, MariaDB, SQL Server services
```

---

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
