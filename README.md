# Scry Database Manager (`scry/scry`)

A modern, reactive, multi-database explorer and inspector GUI for Laravel applications, inspired by Laravel Telescope and built with a decoupled Vue 3 SPA architecture.

---

## 🎨 Seasons #63 Design System & Theme Engine

Scry features a custom design system built around the curated **Seasons #63** color palette.

| Swatch | Color Name | Hex Code | Purpose / Application |
| --- | --- | --- | --- |
| 🔴 | **Pomegranate Purple** | `#b91c5c` / `#e63980` | Primary brand identity, active state navigation, primary buttons, active sorting indicators. |
| 🟡 | **Sulphur Yellow** | `#f8f1c8` | Light mode warm surface highlights, primary badges, dark mode text accents. |
| 🟢 | **Glaucous Green** | `#adcebe` / `#e4f0ea` | Data type pills, row count indicators, success status badges. |
| 🔵 | **Pale King's Blue 2** | `#a1d5eb` / `#e1f2fa` | Schema view buttons, foreign key indicators, secondary button highlights. |
| ⬛ | **Slate Color** | `#384950` / `#1c262a` | Body typography, sidebar container background, dark mode surface container. |

### Theme Mode & Toggle
- ☀️ **Light Mode (Default)**: Light mode (`theme-light`) is set as the active default preference (`localStorage.getItem('scry-theme') || 'light'`).
- 🌙 **Dark Mode (`theme-dark`)**: Seamless dark mode override using the Seasons #63 dark slate background.
- 🔘 **Side Nav Toggle**: Situated in the **bottom left-hand side of the sidebar navigation** for instant theme switching.

---

## 🏗️ Architecture Overview

```
scry/ (Workspace Root)
├── scry-package/                 # Standalone Laravel Package
│   ├── composer.json             # Package scry/scry (Auto-discovery: Scry\ScryServiceProvider)
│   ├── src/                      # PHP Core & Driver Manager Logic
│   │   ├── ScryServiceProvider.php # Core Service Provider (Binds DatabaseExplorerManager singleton)
│   │   ├── DatabaseExplorerManager.php # Driver Manager & Connection Resolver
│   │   ├── Contracts/
│   │   │   └── DatabaseInspector.php # Core Inspector Contract Interface
│   │   ├── Exceptions/
│   │   │   └── UnsupportedDriverException.php # Custom Exception for unsupported drivers
│   │   ├── Inspectors/
│   │   │   ├── AbstractInspector.php # Base Inspector & Safe Query Builder Pagination
│   │   │   ├── PostgresInspector.php # PostgreSQL System Catalog Inspector (jsonb, uuid, timestamptz)
│   │   │   └── MysqlInspector.php    # MySQL information_schema Inspector
│   │   └── Http/
│   │       ├── Controllers/
│   │       │   ├── HomeController.php # Renders Blade SPA container view
│   │       │   └── ApiController.php  # JSON API Endpoints (tables, schema, rows, query)
│   │       └── Middleware/
│   │           └── Authorize.php     # Environment Authorization Gate (local by default)
│   ├── routes/
│   │   ├── web.php               # Web routes (/scry & /db-manager fallback)
│   │   └── api.php               # API routes (/scry/api & /db-manager/api)
│   ├── resources/
│   │   ├── views/                # index.blade.php SPA host view
│   │   ├── js/                   # Vue 3 SPA (App.vue, TableList, SchemaView, DataView, QueryConsole)
│   │   └── app.css               # Seasons #63 Design Tokens & CSS Variables
│   └── vite.config.js            # Standalone Vite Build (outputs to resources/dist)
│
└── dummy-app/                    # Host Laravel Sandbox Application
    ├── composer.json             # Symlinks scry-package via Composer path repository
    ├── .env                      # Dual DB config: PostgreSQL (pgsql) & MySQL (mysql)
    ├── app/Models/               # User, Category, Post, Tag models
    ├── database/factories/       # UserFactory, CategoryFactory, PostFactory, TagFactory
    ├── database/migrations/      # Mini blog schema (users, categories, posts, tags, post_tag)
    ├── database/seeders/         # DatabaseSeeder populating 250+ records per database
    └── docker-compose.yml        # PostgreSQL 16 & MySQL 8.0 container services
```

---

## ⚡ Core Features

1. **Driver Manager Pattern (`DatabaseExplorerManager`)**:
   - Dynamically resolves inspectors on-the-fly via `forConnection(?string $name = null)`.
   - Maps `pgsql` / `postgres` $\rightarrow$ `PostgresInspector`.
   - Maps `mysql` / `mariadb` $\rightarrow$ `MysqlInspector`.
   - Throws `UnsupportedDriverException` (returns HTTP 400 Bad Request) for unsupported drivers.

2. **Normalized API Endpoints (`ApiController`)**:
   - `GET /scry/api/tables`: List all base tables, relation sizes, and estimated row counts.
   - `GET /scry/api/tables/{table}/schema`: Column definitions, data types, nullability, default values, primary keys, indexes, and foreign keys.
   - `GET /scry/api/tables/{table}/rows`: Paginated and sortable row data.
   - `POST /scry/api/query`: SQL Console read query execution.
   - All API requests support optional `?connection=mysql` for live connection switching.

3. **Seeded Sandbox Environment**:
   - Contains 270 `users` (with JSON settings and roles), 250 `categories`, 100 `tags`, 150 `posts`, and 381 `post_tag` pivot records.

---

## 🐳 Docker Quick Start Guide

### 1. Spin Up Containers
```bash
docker-compose up -d --build
```

### 2. Run Migrations and Seed Both Databases
```bash
# PostgreSQL Database
docker exec -w /var/www/html/dummy-app scry_dummy_app php artisan migrate:fresh --database=pgsql --seed --force

# MySQL Database
docker exec -w /var/www/html/dummy-app scry_dummy_app php artisan migrate:fresh --database=mysql --seed --force
```

### 3. Access Dashboard
Open **[http://127.0.0.1:8000/scry](http://127.0.0.1:8000/scry)** in your browser.

---

## 📄 License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
