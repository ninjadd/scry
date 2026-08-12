# Scry Database Manager (`scry/database-manager`)

A modern, reactive local database manager and explorer GUI for Laravel applications, inspired by Laravel Telescope.

## Features

- ⚡ **Driver Manager Architecture**: Supports PostgreSQL and MySQL via Laravel's native connection manager (`DatabaseExplorerManager`).
- 🎨 **Standalone Vue 3 SPA GUI**: Built with Vue 3, Tailwind CSS, and Vite, fully compiled and decoupled from host application assets.
- 🛡️ **Environment Security Gate**: Restricts GUI access to `local` development environments by default.
- 🔍 **Schema & Table Inspector**: Inspect table list, row counts, table size, column definitions, data types, nullability, and index configurations.
- 📊 **Interactive Data Grid**: Browse table rows with interactive sorting and pagination.
- 💻 **SQL Query Console**: Execute custom queries against active database connections.
- 🐳 **Docker Testing Environment**: Pre-configured multi-database Docker environment (PHP 8.3, PostgreSQL 16, MySQL 8.0).

---

## Architecture Overview

```
src/
├── DatabaseManagerServiceProvider.php  # Service provider, asset publishing & routes
├── DatabaseExplorerManager.php        # Extends Illuminate\Support\Manager
├── Contracts/
│   └── DatabaseInspector.php          # Driver interface contract
├── Inspectors/
│   ├── AbstractInspector.php          # Base driver logic & pagination helpers
│   ├── PostgresInspector.php          # PostgreSQL driver implementation
│   └── MysqlInspector.php             # MySQL driver implementation
└── Http/
    ├── Controllers/
    │   ├── HomeController.php          # Serves Blade SPA container
    │   └── DatabaseController.php      # API endpoints
    └── Middleware/
        └── Authorize.php              # Environment authorization gate
```

---

## Docker Local Development & Driver Testing Step-by-Step Guide

### 1. Build and Start Docker Containers
Spin up the isolated PHP 8.3 application container alongside PostgreSQL 16 and MySQL 8.0:

```bash
docker-compose up -d --build
```

This starts:
- `scry_app`: PHP 8.3 CLI/Server container listening on `http://localhost:8000`
- `scry_postgres`: PostgreSQL 16 container listening on port `5432`
- `scry_mysql`: MySQL 8.0 container listening on port `3306`

### 2. Install Dependencies & Compile Vue SPA Assets
Run npm build inside or outside the container to generate static assets in `public/`:

```bash
npm install
npm run build
```

### 3. Driver Resolution Testing
The `DatabaseExplorerManager` inspects your application's `DB_CONNECTION` setting:
- When `DB_CONNECTION=pgsql`, the `PostgresInspector` queries `information_schema` & `pg_class`.
- When `DB_CONNECTION=mysql`, the `MysqlInspector` queries `information_schema.TABLES`, `COLUMNS`, & `STATISTICS`.

---

## License

The MIT License (MIT). See License File for more information.
