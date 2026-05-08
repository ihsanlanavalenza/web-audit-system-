<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'WebAudit' }} — Client Assistance Schedule</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        * {
            font-family: var(--font-ui, "Space Grotesk", sans-serif);
        }
    </style>
</head>

<body class="antialiased">

    <div class="min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="brand-mark brand-mark--lg mx-auto mb-4" aria-hidden="true">
                    <svg class="brand-icon" viewBox="0 0 36 36">
                        <rect x="6" y="14" width="6" height="16" rx="3" />
                        <rect x="15" y="9" width="6" height="21" rx="3" />
                        <rect x="24" y="17" width="6" height="13" rx="3" />
                    </svg>
                </div>
                <h1 class="brand-title text-2xl">WebAudit</h1>
                <p class="brand-subtitle mt-2">Client Assistance Schedule</p>
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
