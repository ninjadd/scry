<?php

namespace Scry\Cli;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Routing\ResponseFactory;
use Scry\DatabaseExplorerManager;
use Scry\Http\Controllers\ApiController;
use Scry\Services\ExportService;
use Scry\Services\GlobalSearchService;
use Scry\Services\ImportService;
use Scry\Services\ServerTuningAdvisor;
use Scry\Services\SqlRunner;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class StandaloneKernel
{
    protected Capsule $capsule;
    protected ApiController $controller;
    protected DatabaseExplorerManager $manager;
    protected array $connections = [];

    public function __construct(array $connections = [])
    {
        $this->connections = $connections;
        $this->bootstrap();
    }

    /**
     * Bootstrap the standalone capsule container, services, and controller.
     */
    protected function bootstrap(): void
    {
        $this->capsule = new Capsule();

        $container = $this->capsule->getContainer();
        \Illuminate\Container\Container::setInstance($container);

        $defaultConn = array_key_first($this->connections) ?? 'default';
        $container['config']->set('database.default', $defaultConn);

        foreach ($this->connections as $name => $config) {
            $this->capsule->addConnection($config, $name);
            $container['config']->set("database.connections.{$name}", $config);

            $driver = $config['driver'] ?? null;
            if ($driver && !isset($this->connections[$driver])) {
                $this->capsule->addConnection($config, $driver);
                $container['config']->set("database.connections.{$driver}", $config);
            }
        }

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $dbManager = $this->capsule->getDatabaseManager();
        $container->instance(LaravelDatabaseManager::class, $dbManager);
        $container->instance('db', $dbManager);

        // Bind ResponseFactory if needed
        if (class_exists(ResponseFactory::class)) {
            $viewFactory = new class implements \Illuminate\Contracts\View\Factory {
                public function exists($view) { return false; }
                public function file($path, $data = [], $mergeData = []) {}
                public function make($view, $data = [], $mergeData = []) {}
                public function share($key, $value = null) {}
                public function composer($views, $callback) {}
                public function creator($views, $callback) {}
                public function addNamespace($namespace, $hints) {}
                public function replaceNamespace($namespace, $hints) {}
            };
            $rf = new ResponseFactory($viewFactory, new \Illuminate\Routing\Redirector(new \Illuminate\Routing\UrlGenerator(new \Illuminate\Routing\RouteCollection(), Request::capture())));
            $container->instance(ResponseFactoryContract::class, $rf);
            $container->instance('response', $rf);
        }

        // Bind validator instance for Request::validate() compatibility
        $validatorFactory = new class implements \Illuminate\Contracts\Validation\Factory {
            public function make(array $data, array $rules, array $messages = [], array $customAttributes = []): \Illuminate\Contracts\Validation\Validator {
                return new class($data, $rules) implements \Illuminate\Contracts\Validation\Validator {
                    public function __construct(protected array $data, protected array $rules) {}
                    public function validate(): array {
                        foreach ($this->rules as $field => $rule) {
                            $rulesList = is_string($rule) ? explode('|', $rule) : (array) $rule;
                            if (in_array('required', $rulesList)) {
                                $val = $this->data[$field] ?? null;
                                if ($val === null || $val === '') {
                                    throw new \Illuminate\Validation\ValidationException($this);
                                }
                            }
                        }
                        return $this->data;
                    }
                    public function validated(): array { return $this->validate(); }
                    public function fails(): bool { return false; }
                    public function failed(): array { return []; }
                    public function sometimes($attribute, $rules, callable $callback) { return $this; }
                    public function after($callback) { return $this; }
                    public function errors(): \Illuminate\Support\MessageBag { return new \Illuminate\Support\MessageBag(); }
                    public function getMessageBag(): \Illuminate\Support\MessageBag { return new \Illuminate\Support\MessageBag(); }
                };
            }
            public function extend($rule, $extension, $message = null) {}
            public function extendImplicit($rule, $extension, $message = null) {}
            public function extendDependent($rule, $extension, $message = null) {}
            public function replacer($rule, $replacer) {}
        };
        $container->instance(\Illuminate\Contracts\Validation\Factory::class, $validatorFactory);
        $container->instance('validator', $validatorFactory);

        // Register Request validation macro
        if (!Request::hasMacro('validate')) {
            Request::macro('validate', function (array $rules) {
                foreach ($rules as $field => $rule) {
                    $rulesList = is_string($rule) ? explode('|', $rule) : (array) $rule;
                    if (in_array('required', $rulesList)) {
                        $val = $this->input($field);
                        if ($val === null || $val === '') {
                            throw new \InvalidArgumentException("Field '{$field}' is required.");
                        }
                    }
                }
                return $this->all();
            });
        }

        $this->manager = new DatabaseExplorerManager($container);
        $sqlRunner = new SqlRunner($this->manager, $dbManager);
        $exportService = new ExportService();
        $importService = new ImportService($this->manager, $dbManager);
        $searchService = new GlobalSearchService($this->manager, $dbManager);
        $tuningAdvisor = new ServerTuningAdvisor($this->manager, $dbManager);

        $this->controller = new ApiController(
            $this->manager,
            $sqlRunner,
            $exportService,
            $importService,
            $searchService,
            $tuningAdvisor
        );
    }

    /**
     * Add a dynamic database connection to the running kernel.
     */
    public function addConnection(string $name, array $config): void
    {
        $this->connections[$name] = $config;
        $this->capsule->addConnection($config, $name);
        $container = $this->capsule->getContainer();
        $container['config']->set("database.connections.{$name}", $config);
    }

    /**
     * Get list of configured connections.
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Handle incoming HTTP request and return a Symfony/Laravel Response.
     */
    public function handle(Request $request): SymfonyResponse
    {
        $method = strtoupper($request->method());
        $uri = parse_url($request->server('REQUEST_URI', '/'), PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        // Strip /scry prefix if present
        if (str_starts_with($uri, '/scry')) {
            $uri = substr($uri, 5);
            if (empty($uri)) {
                $uri = '/';
            }
        }

        // Handle static assets
        if ($this->isStaticAsset($uri)) {
            return $this->serveStaticAsset($uri);
        }

        // Handle API routes
        if (str_starts_with($uri, '/api')) {
            $apiPath = substr($uri, 4);
            if (empty($apiPath)) {
                $apiPath = '/';
            }

            return $this->dispatchApi($method, $apiPath, $request);
        }

        // SPA HTML fallback
        return $this->serveSpaHtml();
    }

    /**
     * Dispatch an API route to ApiController.
     */
    protected function dispatchApi(string $method, string $path, Request $request): SymfonyResponse
    {
        try {
            // Dynamic connection endpoints for CLI tool
            if ($path === '/connections' && $method === 'GET') {
                return new JsonResponse([
                    'connections' => array_keys($this->connections),
                    'default' => array_key_first($this->connections) ?? 'default',
                    'available_connections' => $this->manager->getAvailableConnections(),
                ]);
            }

            if ($path === '/connections' && $method === 'POST') {
                $name = $request->input('name', 'conn_' . uniqid());
                $dsn = $request->input('dsn');
                if (!empty($dsn)) {
                    $config = ConnectionConfig::fromDsn($dsn);
                } else {
                    $config = ConnectionConfig::fromFlags($request->all());
                }
                $this->addConnection($name, $config);
                return new JsonResponse([
                    'success' => true,
                    'message' => "Connection '{$name}' added successfully.",
                    'connection' => $name,
                    'available_connections' => $this->manager->getAvailableConnections(),
                ]);
            }

            // Database Operations
            if ($path === '/databases') {
                return match ($method) {
                    'GET' => $this->controller->databases($request),
                    'POST' => $this->controller->createDatabase($request),
                    'DELETE' => $this->controller->dropDatabase($request),
                    default => $this->methodNotAllowed(),
                };
            }

            // Server Stats, Tuning, Processes, Health
            if ($path === '/server/stats' && $method === 'GET') return $this->controller->serverStats($request);
            if ($path === '/server/tuning' && $method === 'GET') return $this->controller->tuningSuggestions($request);
            if ($path === '/server/slow-queries' && $method === 'GET') return $this->controller->slowQueries($request);
            if ($path === '/server/processes' && $method === 'GET') return $this->controller->processes($request);
            if ($path === '/server/kill-process' && $method === 'POST') return $this->controller->killProcess($request);
            if (preg_match('#^/server/processes/([^/]+)$#', $path, $m) && $method === 'DELETE') return $this->controller->killProcessById($m[1], $request);
            if ($path === '/server/health' && $method === 'GET') return $this->controller->healthCheck($request);

            // Routines, Triggers, Views, Procedures
            if ($path === '/views' && $method === 'GET') return $this->controller->views($request);
            if ($path === '/triggers' && $method === 'GET') return $this->controller->triggers($request);
            if ($path === '/triggers' && $method === 'POST') return $this->controller->createTrigger($request);
            if ($path === '/procedures' && $method === 'GET') return $this->controller->procedures($request);
            if ($path === '/routines' && $method === 'POST') return $this->controller->createRoutine($request);

            // Users & Privileges
            if ($path === '/users' && $method === 'GET') return $this->controller->users($request);
            if ($path === '/users' && $method === 'POST') return $this->controller->createUser($request);
            if ($path === '/users/privileges' && $method === 'POST') return $this->controller->manageUserPrivileges($request);

            // Global Search, Import, Export
            if (($path === '/search' || $path === '/search/global') && ($method === 'GET' || $method === 'POST')) return $this->controller->globalSearch($request);
            if ($path === '/import' && $method === 'POST') return $this->controller->importFile($request);
            if (preg_match('#^/export/([^/]+)$#', $path, $m) && $method === 'GET') return $this->controller->exportTable($m[1], $request);

            // Tables & DDL
            if ($path === '/tables' && $method === 'GET') return $this->controller->tables($request);
            if (($path === '/tables' || $path === '/schema/tables') && $method === 'POST') return $this->controller->createTable($request);
            if ($path === '/tables/copy' && $method === 'POST') return $this->controller->copyTable($request);
            if ($path === '/schema/full' && $method === 'GET') return $this->controller->fullSchema($request);
            if (($path === '/schema/relationships' || $path === '/erd') && $method === 'GET') return $this->controller->schemaRelationships($request);

            // Parameterized Table Routes
            if (preg_match('#^/schema/tables/([^/]+)$#', $path, $m) && $method === 'PUT') return $this->controller->alterTable($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/alter$#', $path, $m) && $method === 'PUT') return $this->controller->alterTable($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/rename$#', $path, $m) && $method === 'PUT') return $this->controller->renameTable($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/truncate$#', $path, $m) && $method === 'POST') return $this->controller->truncateTable($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/optimize$#', $path, $m) && $method === 'POST') return $this->controller->optimizeTable($m[1], $request);

            // Index & Foreign Key routes
            if (preg_match('#^/tables/([^/]+)/indexes/([^/]+)$#', $path, $m) && $method === 'DELETE') return $this->controller->dropIndex($m[1], $m[2], $request);
            if (preg_match('#^/tables/([^/]+)/indexes$#', $path, $m) && $method === 'POST') return $this->controller->createIndex($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/foreign-keys/([^/]+)$#', $path, $m) && $method === 'DELETE') return $this->controller->dropForeignKey($m[1], $m[2], $request);
            if (preg_match('#^/tables/([^/]+)/foreign-keys$#', $path, $m) && $method === 'POST') return $this->controller->createForeignKey($m[1], $request);

            // Schema & Rows
            if (preg_match('#^/tables/([^/]+)/schema$#', $path, $m) && $method === 'GET') return $this->controller->schema($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/(rows|data)$#', $path, $m) && $method === 'GET') return $this->controller->rows($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/rows$#', $path, $m) && $method === 'POST') return $this->controller->insertRow($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/rows$#', $path, $m) && $method === 'PUT') return $this->controller->updateRow($m[1], $request);
            if (preg_match('#^/tables/([^/]+)/rows$#', $path, $m) && $method === 'DELETE') return $this->controller->deleteRow($m[1], $request);

            // Drop Table
            if (preg_match('#^/tables/([^/]+)$#', $path, $m) && $method === 'DELETE') return $this->controller->dropTable($m[1], $request);

            // SQL Execution
            if (($path === '/sql/execute' || $path === '/query/run') && $method === 'POST') return $this->controller->executeSql($request);
            if ($path === '/query' && $method === 'POST') return $this->controller->query($request);

            return new JsonResponse(['error' => "API route not found: [{$method}] {$path}"], 404);
        } catch (Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    protected function methodNotAllowed(): JsonResponse
    {
        return new JsonResponse(['error' => 'Method Not Allowed'], 405);
    }

    protected function isStaticAsset(string $uri): bool
    {
        $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        return in_array($ext, ['js', 'css', 'ttf', 'woff', 'woff2', 'svg', 'png', 'jpg', 'ico', 'map']);
    }

    protected function serveStaticAsset(string $uri): SymfonyResponse
    {
        $distPath = dirname(__DIR__, 2) . '/resources/dist';
        $cleanedUri = ltrim($uri, '/');
        // Handle paths like /vendor/scry/app.js or /app.js
        if (str_starts_with($cleanedUri, 'vendor/scry/')) {
            $cleanedUri = substr($cleanedUri, 12);
        }

        // Remove query parameters if present (e.g. ?v=123)
        if (($qPos = strpos($cleanedUri, '?')) !== false) {
            $cleanedUri = substr($cleanedUri, 0, $qPos);
        }

        $filePath = realpath($distPath . '/' . $cleanedUri);

        if ($filePath && str_starts_with($filePath, $distPath) && file_exists($filePath)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'js' => 'application/javascript',
                'css' => 'text/css',
                'ttf' => 'font/ttf',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'ico' => 'image/x-icon',
                'map' => 'application/json',
                default => 'application/octet-stream',
            };

            return new SymfonyResponse(file_get_contents($filePath), 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }

        return new SymfonyResponse('Asset not found', 404);
    }

    protected function serveSpaHtml(): SymfonyResponse
    {
        $defaultConn = $this->manager->resolveConnectionName();
        $driver = $this->manager->getDriverForConnection($defaultConn);
        $availableConnections = $this->manager->getAvailableConnections();

        $scryConfigJson = json_encode([
            'basePath' => '/',
            'baseApiUrl' => '/api',
            'activeConnection' => $defaultConn,
            'driver' => $driver,
            'availableConnections' => $availableConnections,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $distPath = dirname(__DIR__, 2) . '/resources/dist';
        $appJsPath = $distPath . '/app.js';
        $appCssPath = $distPath . '/app.css';
        $appJsVersion = file_exists($appJsPath) ? filemtime($appJsPath) : time();
        $appCssVersion = file_exists($appCssPath) ? filemtime($appCssPath) : time();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scry Database Manager (CLI Standalone)</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        window.ScryConfig = {$scryConfigJson};
    </script>

    <script type="module" src="/app.js?v={$appJsVersion}"></script>
    <link rel="stylesheet" href="/app.css?v={$appCssVersion}">
</head>
<body class="h-full font-sans antialiased overflow-hidden">
    <div id="app"></div>
</body>
</html>
HTML;

        return new SymfonyResponse($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
