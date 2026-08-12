# Scry Database Manager Package (`scry/scry`)

Scry is an open-source, enterprise-grade database manager package for Laravel applications. It provides a modern, reactive Vue 3 GUI to inspect multi-database engines (PostgreSQL, MySQL, MariaDB) in real time.

---

## 🎨 Seasons #63 Design Tokens & Theme System

The Scry package UI is styled using custom design tokens derived from the **Seasons #63** color palette.

| Token | Swatch | Color Name | Hex Code | Purpose / Application |
| --- | --- | --- | --- | --- |
| `--pomegranate` | 🔴 | **Pomegranate Purple** | `#b91c5c` / `#e63980` | Primary brand accent, active navigation, primary action buttons, sorting indicators. |
| `--sulphur` | 🟡 | **Sulphur Yellow** | `#f8f1c8` | Light mode warm surface highlights, primary badges, dark mode text accents. |
| `--glaucous` | 🟢 | **Glaucous Green** | `#adcebe` / `#e4f0ea` | Data type pills, row count indicators, success status badges. |
| `--pale-blue` | 🔵 | **Pale King's Blue 2** | `#a1d5eb` / `#e1f2fa` | Schema view buttons, foreign key indicators, secondary button highlights. |
| `--slate-color` | ⬛ | **Slate Color** | `#384950` / `#1c262a` | Body typography, sidebar background, dark mode surface container. |

### Theme Mode & Preferences
- **Default Theme**: **Light Mode** (`theme-light`) is set as the active default preference (`localStorage.getItem('scry-theme') || 'light'`).
- **Dark Mode**: Toggleable via `.theme-dark` class.
- **Side Nav Theme Toggle**: Located in the **bottom left-hand side of the sidebar navigation** (`App.vue` footer).

---

## 🏗️ Architecture & Component Hierarchy

```
src/
├── ScryServiceProvider.php         # Package Service Provider & singleton bindings
├── DatabaseExplorerManager.php        # Extends Illuminate\Support\Manager
├── Contracts/
│   └── DatabaseInspector.php          # Inspector interface contract
├── Exceptions/
│   └── UnsupportedDriverException.php # Custom driver resolution exception
├── Inspectors/
│   ├── AbstractInspector.php          # Base inspector & safe query builder pagination
│   ├── PostgresInspector.php          # PostgreSQL catalog inspector (jsonb, uuid, timestamptz)
│   └── MysqlInspector.php             # MySQL information_schema inspector
└── Http/
    ├── Controllers/
    │   ├── HomeController.php          # Serves Blade SPA view container
    │   └── ApiController.php          # JSON API endpoints
    └── Middleware/
        └── Authorize.php              # Local environment security gate

routes/
├── web.php                            # Web routes (/scry & /db-manager)
└── api.php                            # API routes (/scry/api & /db-manager/api)

resources/
├── js/
│   ├── App.vue                        # SPA Shell & Side Nav Theme Toggle
│   ├── app.css                        # Seasons #63 CSS Custom Properties
│   └── components/
│       ├── TableList.vue              # Table grid & storage statistics
│       ├── SchemaView.vue             # Column types, indexes, and foreign keys
│       ├── DataView.vue               # Paginated and sortable row data grid
│       └── QueryConsole.vue           # SQL console read query executor
└── views/
    └── index.blade.php                # Host Blade container view
```

---

## 🧪 Driver Manager Contract Interface

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
    public function executeQuery(string $query): array;
}
```

---

## 📄 License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
