# Scry Database Manager (`scry/scry`)

A modern, reactive, multi-database explorer and management suite for Laravel applications, inspired by Laravel Telescope and built with a decoupled Vue 3 SPA architecture.

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

## Architecture Overview

```
scry/ (Workspace Root)
├── scry-package/                 # Standalone Laravel Package
│   ├── composer.json             # Package scry/scry (Auto-discovery & dompdf/dompdf dependency)
│   ├── README.md                 # Package README documenting Tokens & Architecture
│   ├── src/                      # PHP Core & Driver Manager Logic
│   │   ├── ScryServiceProvider.php # Core Service Provider
│   │   ├── DatabaseExplorerManager.php # Driver Manager & Resolver
│   │   ├── Contracts/DatabaseInspector.php # Core Inspector Interface
│   │   ├── Exceptions/UnsupportedDriverException.php # Custom Driver Exception
│   │   ├── Services/
│   │   │   ├── SqlRunner.php             # SqlRunner Service
│   │   │   ├── ExportService.php         # ExportService (CSV, SQL, XML, PDF, Word .doc, ODT, JSON, LaTeX)
│   │   │   ├── ImportService.php         # ImportService (SQL script & CSV streaming batch parser)
│   │   │   ├── GlobalSearchService.php   # Global Search Service across text columns & tables
│   │   │   └── ServerTuningAdvisor.php   # MySQL Status Variables & Buffer Pool Tuning Advisor
│   │   ├── Http/Controllers/ApiController.php # API Controller (Databases, DDL, Views, Routines, Users, Tuning, Search, Export/Import)
│   │   └── Inspectors/                   # PostgresInspector & MysqlInspector
│   ├── routes/                           # Web & API routes (/scry & /scry/api)
│   ├── resources/                        # Blade views (scry::index) & Vue 3 SPA frontend
│   │   ├── js/App.vue                    # Seasons #63 Light/Dark Theme Switcher & Categorized Sidebar Nav
│   │   ├── js/app.js                     # Vue 3 App Entry point initializing Pinia and Vue Router
│   │   ├── js/router.js                  # Vue Router mapping 12 views
│   │   ├── js/stores/
│   │   │   └── useConnectionStore.js     # Pinia Store with global scryFetch connection interceptor
│   │   ├── js/views/
│   │   │   ├── DashboardView.vue         # Real-time server performance metrics and stats
│   │   │   ├── TableBrowserView.vue      # Table browser with Create Table, Copy Table, Rename Table modals
│   │   │   ├── DataGridView.vue          # Paginated data table grid with CSV/SQL exports
│   │   │   ├── QueryRunnerView.vue       # Raw SQL console with Bookmarks & Query History Drawer
│   │   │   ├── QueryBuilderQBEView.vue   # Visual QBE query builder (edit in console or execute directly)
│   │   │   ├── SchemaVisualizerERDView.vue # ERD diagram visualizer (Export Mermaid, SVG, PNG)
│   │   │   ├── ServerTuningView.vue      # Database server optimization recommendations
│   │   │   ├── GlobalSearchView.vue      # Cross-table string pattern search
│   │   │   ├── UserManagementView.vue    # MySQL user accounts & Create User / Privilege Matrix modals
│   │   │   ├── RoutinesView.vue          # Stored Procedures, Functions, & Triggers manager with creation modals
│   │   │   └── ImportExportView.vue      # Multi-format import and export tool studio (.doc & .odt added)
│   │   ├── js/components/
│   │   │   └── BlobTransformComponent.vue # BLOB image preview, hex view, and binary download
│   │   ├── js/app.css                    # Seasons #63 Design Tokens & CSS Variables
│   │   └── dist/                         # Compiled Vite Assets (published to dummy-app)
│   ├── vite.config.js                    # Standalone Vite compilation output to resources/dist
│   └── package.json
│
└── dummy-app/                    # Host Laravel Application
    ├── composer.json             # Symlinks scry-package via Composer path repository
    ├── .env                      # Dual DB config: PostgreSQL (pgsql) & MySQL (mysql)
    ├── app/Models/               # User, Category, Post, Tag models
    ├── database/factories/       # UserFactory, CategoryFactory, PostFactory, TagFactory
    ├── database/migrations/      # Mini blog schema (users, categories, posts, tags, post_tag)
    ├── database/seeders/         # DatabaseSeeder populating 100+ rows per table
    └── docker-compose.yml        # PostgreSQL 16 & MySQL 8.0 container services
```

---

## Core Features & Functionality

1. **Driver Manager Pattern (`DatabaseExplorerManager`)**:
   - Dynamically resolves database inspectors via `forConnection(?string $name = null)`.
   - Maps `pgsql` / `postgres` -> `PostgresInspector`.
   - Maps `mysql` / `mariadb` -> `MysqlInspector`.
   - Multi-server administration via `?connection=` switcher parameter.

2. **Interactive DDL Table Operations**:
   - Create Table modal (field definitions, data types, nullability, primary key, auto-increment).
   - Copy Table modal (structure-only vs structure + data).
   - Rename Table modal.
   - Drop Table modal.

3. **Query-by-Example (QBE) Visual Builder**:
   - Visual query builder (table selection, column picking, filter conditions, sorting, limit).
   - Features dual execution actions: **Edit in SQL Console** or **Execute Directly**.

4. **ERD Database Schema Visualizer**:
   - Interactive Entity-Relationship Diagram mapping foreign keys and primary keys.
   - Exports diagrams as **Mermaid Code (`.mmd`)**, **SVG**, and **PNG**.

5. **SQL Console & Bookmarks Drawer**:
   - Execute raw queries, batch commands (`;` separated), timing metrics.
   - Local storage persistent **Bookmarks Drawer** with query history and snippet loading.

6. **Multi-Format Export & Import Studio**:
   - Imports: SQL script execution and CSV batch row streaming.
   - Exports: CSV, SQL Dumps, XML, PDF (via `dompdf`), Word (`.doc`), OpenDocument (`.odt`), JSON, and LaTeX.

7. **MySQL Server Performance & Tuning Advisor**:
   - Analyzes `innodb_buffer_pool_size`, slow query counts, and disk temp table ratios to output optimization suggestions.

8. **Global Database Search Engine**:
   - Searches text and JSON columns across all database tables for pattern matches.

9. **User Privileges & Accounts Manager**:
   - Interactive user account creation and `GRANT` / `REVOKE` privilege matrix.
   - Graceful permission check: Displays setup instructions if elevated privileges are missing, keeping all other features fully operational.

10. **Routines & Triggers Manager**:
    - Manage and inspect stored procedures, functions, and database triggers with creation modals.

11. **Custom Data Transformations**:
    - BLOB data rendering (inline image thumbnails, modal previews, and binary downloads).

---

## API Endpoints Reference

- `GET /scry/api/databases`: List all databases on server instance.
- `POST /scry/api/databases`: Create database.
- `DELETE /scry/api/databases`: Drop database.
- `GET /scry/api/tables`: List tables, storage sizes, and row counts.
- `POST /scry/api/tables`: Create table via DDL payload.
- `POST /scry/api/tables/copy`: Copy table structure and data.
- `PUT /scry/api/tables/{table}/rename`: Rename table.
- `DELETE /scry/api/tables/{table}`: Drop table.
- `GET /scry/api/tables/{table}/schema`: Column definitions, primary keys, indexes, foreign keys.
- `GET /scry/api/tables/{table}/rows`: Paginated and sortable row data.
- `POST /scry/api/tables/{table}/rows`: Insert row.
- `PUT /scry/api/tables/{table}/rows`: Update row by primary key.
- `DELETE /scry/api/tables/{table}/rows`: Delete row by primary key.
- `POST /scry/api/sql/execute`: Execute raw SQL queries.
- `GET /scry/api/server/stats`: Storage metrics and active connection count.
- `GET /scry/api/server/tuning`: Server performance tuning advisor recommendations.
- `GET /scry/api/search`: Global database search.
- `POST /scry/api/import`: File import (SQL script or CSV).
- `GET /scry/api/export/{table}`: Multi-format data exports (`format=csv|sql|xml|pdf|doc|odt|json|latex`).
- `GET /scry/api/users`: List MySQL users and check permissions.
- `POST /scry/api/users`: Create MySQL user account.
- `POST /scry/api/users/privileges`: Grant or revoke user privileges.
- `GET /scry/api/procedures`: List stored procedures and functions.
- `POST /scry/api/routines`: Create stored procedure or function.
- `GET /scry/api/triggers`: List database triggers.
- `POST /scry/api/triggers`: Create database trigger.

---

## Docker Quick Start Guide

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

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
