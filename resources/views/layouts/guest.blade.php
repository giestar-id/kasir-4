<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Masuk - POS Kuliner' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="glass-canvas min-h-screen text-[#1E2029] antialiased selection:bg-[#5E63E8]/20 selection:text-[#5E63E8] flex items-center justify-center p-4">

    <!-- Soft Ambient Mesh Blobs -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-32 -left-32 w-[500px] h-[500px] bg-[#5E63E8]/18 rounded-full blur-[110px]"></div>
        <div class="absolute -bottom-32 -right-32 w-[520px] h-[520px] bg-[#F4A261]/16 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-[#64CCF3]/14 rounded-full blur-[130px]"></div>
    </div>

    <div class="relative z-10 w-full flex items-center justify-center">
        {{ $slot }}
    </div>

    <!-- Toast Layer -->
    <x-ui.toast />

    @livewireScripts
</body>
</html>

