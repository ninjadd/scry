# Scry Database Manager Package (`scry/scry`)

Scry is an open-source, enterprise-grade database manager package for Laravel applications. It provides a modern, reactive Vue 3 GUI to inspect and manage multi-database engines (PostgreSQL, MySQL, MariaDB, SQLite, SQL Server) in real time.

---

## Seasons #63 Design Tokens & Theme System

The Scry package UI is styled using custom design tokens derived from the **Seasons #63** color palette and clean typography with zero emojis (strictly SVG icon system).

| Token | Color Name | Hex Code | Purpose / Application |
| --- | --- | --- | --- |
| `--pomegranate` | **Pomegranate Purple** | `#b91c5c` / `#e63980` | Primary brand accent, active navigation, primary action buttons, sorting indicators. |
| `--sulphur` | **Sulphur Yellow** | `#f8f1c8` | Light mode warm surface highlights, primary badges, dark mode text accents. |
| `--glaucous` | **Glaucous Green** | `#adcebe` / `#e4f0ea` | Data type pills, row count indicators, success status badges. |
| `--pale-blue` | **Pale King's Blue 2** | `#a1d5eb` / `#e1f2fa` | Schema view buttons, foreign key indicators, secondary button highlights. |
| `--slate-color` | **Slate Color** | `#384950` / `#1c262a` | Body typography, sidebar background, dark mode surface container. |

### Theme Mode & Preferences
- **Default Theme**: **Light Mode** (`theme-light`) is set as the active default preference (`localStorage.getItem('scry-theme') || 'light'`).
- **Dark Mode**: Toggleable via `.theme-dark` class.
- **Side Nav Theme Toggle**: Located in the **bottom left-hand side of the sidebar navigation** (`App.vue` footer).

---

## Automated Testing Suite & QA Architecture

Scry features an automated PHPUnit / Orchestra Testbench test suite covering unit, integration, and feature tests across all 5 database engine drivers.

### Running Automated Tests
```bash
# Run unit & feature tests via composer alias
composer test

# Direct PHPUnit execution
./vendor/bin/phpunit
```

### Test Suite Structure
```
tests/
├── TestCase.php                               # Orchestra Testbench base setup & mock connections
├── Unit/
│   ├── DatabaseExplorerManagerTest.php         # Driver manager resolution & factory methods
│   ├── SqliteInspectorTest.php                 # SQLite inspector DDL, catalog & CRUD operations
│   ├── InspectorsUnitTest.php                  # Inspector instantiations across 5 DB engines
│   ├── SqlRunnerTest.php                      # Raw SQL execution, timing & error payloads
│   ├── ExportServiceTest.php                  # ExportService (CSV, SQL, JSON, XML, PDF)
│   ├── ImportServiceTest.php                  # Quote-aware SQL statement parser & CSV import
│   ├── GlobalSearchServiceTest.php            # Dynamic SQL database-level pattern searching
│   └── ServerTuningAdvisorTest.php            # Status variable analysis & process list routines
└── Feature/
    └── ApiControllerTest.php                  # End-to-end API HTTP integration endpoints
```

---

## Architecture & Component Hierarchy

```
src/
├── ScryServiceProvider.php         # Package Service Provider & singleton bindings
├── DatabaseExplorerManager.php        # Extends Illuminate\Support\Manager
├── Contracts/
│   └── DatabaseInspector.php          # Inspector interface contract
├── Exceptions/
│   └── UnsupportedDriverException.php # Custom driver resolution exception
├── Services/
│   ├── SqlRunner.php                 # Query type detection & SQL execution
│   ├── ExportService.php             # ExportService (CSV, SQL, XML, PDF, Word .doc, ODT, JSON, LaTeX)
│   ├── ImportService.php             # ImportService (Quote-aware SQL parser & CSV batch stream)
│   ├── GlobalSearchService.php       # Database-level LIKE search across text columns
│   └── ServerTuningAdvisor.php       # Status Variables & Process List Tuning Advisor
├── Inspectors/
│   ├── AbstractInspector.php          # Base inspector & safe query builder pagination
│   ├── PostgresInspector.php          # PostgreSQL catalog inspector
│   ├── MysqlInspector.php             # MySQL information_schema inspector
│   ├── MariadbInspector.php           # MariaDB catalog inspector
│   ├── SqliteInspector.php            # SQLite master & PRAGMA inspector
│   └── SqlsrvInspector.php            # SQL Server sys views inspector
└── Http/
    ├── Controllers/
    │   ├── HomeController.php          # Serves Blade SPA view container
    │   └── ApiController.php          # JSON API endpoints
    └── Middleware/
        └── Authorize.php              # Local/testing environment security gate
```

---

## Driver Manager Contract Interface

All database inspectors implement `Scry\Contracts\DatabaseInspector`:

```php
namespace Scry\Contracts;

interface DatabaseInspector
{
    public function getTables(): array;
    public function getTableSchema(string $table): array;
    public function getTableIndexes(string $table): array;
    public function getTableForeignKeys(string $table): array;
    public function getPaginatedRows(string $table, int $page = 1, int $perPage = 25, ?string $sortBy = null, string $sortDir = 'asc'): array;
    public function insertRow(string $table, array $data): bool;
    public function updateRow(string $table, array $primaryKey, array $data): bool;
    public function deleteRow(string $table, array $primaryKey): bool;
    public function getServerStats(): array;
    public function getDatabases(): array;
    public function createDatabase(string $name, ?string $charset = null, ?string $collation = null): bool;
    public function dropDatabase(string $name): bool;
    public function dropTable(string $table): bool;
    public function renameTable(string $table, string $newName): bool;
    public function copyTable(string $sourceTable, string $targetTable, bool $copyData = true): bool;
    public function getViews(): array;
    public function getTriggers(): array;
    public function getProcedures(): array;
    public function hasUserManagementPrivileges(): bool;
    public function getUsers(): array;
    public function executeQuery(string $query): array;
}
```

---

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
