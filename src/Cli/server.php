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

// Load connections from a private temp file (preferred, avoids putting
// credentials in the process environment) or, for backwards compatibility,
// a raw JSON env var.
$connectionsFile = getenv('SCRY_CONNECTIONS_FILE');
$connectionsJson = getenv('SCRY_CONNECTIONS_JSON');
$connections = [];

if (!empty($connectionsFile) && file_exists($connectionsFile)) {
    $connections = json_decode(file_get_contents($connectionsFile), true) ?: [];
} elseif (!empty($connectionsJson)) {
    $connections = json_decode($connectionsJson, true) ?: [];
}

if (empty($connections)) {
    $target = getenv('SCRY_TARGET') ?: null;
    $connections = ConnectionConfig::resolveConnections($target);
}

$token = getenv('SCRY_AUTH_TOKEN') ?: null;
$debug = filter_var(getenv('SCRY_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN);

$kernel = new StandaloneKernel($connections, $token, $debug);
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
