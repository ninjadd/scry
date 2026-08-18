<?php

// Autoloader discovery
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../../autoload.php',
];

$loaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    fwrite(STDERR, "Could not locate composer autoloader.\n");
    exit(1);
}

use Illuminate\Http\Request;
use Scry\Cli\ConnectionConfig;
use Scry\Cli\StandaloneKernel;

// Load connections from environment / serialized state if passed
$connectionsJson = getenv('SCRY_CONNECTIONS_JSON');
$connections = [];

if (!empty($connectionsJson)) {
    $connections = json_decode($connectionsJson, true) ?: [];
}

if (empty($connections)) {
    $target = getenv('SCRY_TARGET') ?: null;
    $connections = ConnectionConfig::resolveConnections($target);
}

$kernel = new StandaloneKernel($connections);
$request = Request::capture();
$response = $kernel->handle($request);

// Send HTTP status code
http_response_code($response->getStatusCode());

// Send HTTP response headers
foreach ($response->headers->all() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}", false);
    }
}

// Send response body
echo $response->getContent();
