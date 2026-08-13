<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Database Manager - Scry</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script type="module" src="{{ asset('vendor/database-manager/app.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/database-manager/app.css') }}">
</head>
<body class="h-full font-sans antialiased overflow-hidden selection:bg-indigo-500 selection:text-white">
    <div id="app" data-base-path="/{{ config('database-manager.path', 'db-manager') }}"></div>
</body>
</html>
