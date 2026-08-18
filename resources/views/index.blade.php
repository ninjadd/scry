<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scry Database Manager</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @php
        $explorer = app(\Scry\DatabaseExplorerManager::class);
        $defaultConn = $explorer->resolveConnectionName();
        $driver = $explorer->getDriverForConnection($defaultConn);
        $availableConns = $explorer->getAvailableConnections();
    @endphp
    <script>
        window.ScryConfig = {
            basePath: "/{{ config('scry.path', 'scry') }}",
            baseApiUrl: "/{{ config('scry.path', 'scry') }}/api",
            activeConnection: "{{ $defaultConn }}",
            driver: "{{ $driver }}",
            availableConnections: @json($availableConns),
        };
    </script>

    <script type="module" src="{{ asset('vendor/scry/app.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/scry/app.css') }}">
</head>
<body class="h-full font-sans antialiased overflow-hidden">
    <div id="app"></div>
</body>
</html>
