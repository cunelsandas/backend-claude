<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Bluemocha ERP') }}</title>

    {{-- Bluemocha Console typography stack --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Google Sans (body) — variable-axis 400..700, italic supported --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap"
        rel="stylesheet"
    />
    {{-- Fraunces (display) + JetBrains Mono (data) — from Bunny --}}
    <link
        href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700,800|jetbrains-mono:400,500,600&display=swap"
        rel="stylesheet"
    />

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
