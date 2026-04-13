<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'WebAudit' }} — Client Assistance Schedule</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="antialiased">

    <div class="min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-linear-to-br from-blue-600 to-blue-400 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
                    <span class="text-white font-bold text-xl">WA</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">WebAudit</h1>
                <p class="text-sm text-slate-500 mt-1">Client Assistance Schedule</p>
            </div>

            {{-- Content Card --}}
            <div class="glass-card p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
