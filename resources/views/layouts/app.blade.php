<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WebAudit - Client Assistance Schedule">
    <title>{{ $title ?? 'WebAudit' }} — Client Assistance Schedule</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="antialiased">

    @auth
        <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">
            {{-- Mobile Sidebar Overlay --}}
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity duration-200"
                x-transition:leave="transition-opacity duration-200" x-cloak class="sidebar-overlay lg:hidden"
                @click="sidebarOpen = false"></div>

            {{-- Sidebar --}}
            <aside
                class="sidebar w-64 flex flex-col py-6 px-4 fixed h-full z-40
            max-lg:transform max-lg:-translate-x-full lg:translate-x-0"
                :class="{ 'max-lg:translate-x-0': sidebarOpen }" @click.away="sidebarOpen = false">
                {{-- Logo --}}
                <div class="mb-8 px-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-linear-to-br from-blue-600 to-blue-400 flex items-center justify-center font-bold text-sm text-white shadow-md">
                            WA
                        </div>
                        <div>
                            <h1 class="text-base font-bold tracking-tight text-slate-900">WebAudit</h1>
                            <p class="text-xs text-slate-400">Client Assistance</p>
                        </div>
                    </div>
                </div>

                {{-- Role Badge --}}
                <div class="mb-6 px-2">
                    @if (auth()->user()->isSuperAdmin())
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                            🛡️ Super Admin
                        </span>
                    @elseif(auth()->user()->isAuditor())
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Auditor
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Auditi
                        </span>
                    @endif
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        @click="sidebarOpen = false">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        Dashboard
                    </a>

                    {{-- Super Admin Navigation --}}
                    @if (auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.users') }}"
                            class="sidebar-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
                            @click="sidebarOpen = false">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Kelola User
                        </a>
                        <a href="{{ route('admin.kaps') }}"
                            class="sidebar-link {{ request()->routeIs('admin.kaps') ? 'active' : '' }}"
                            @click="sidebarOpen = false">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                            Kelola KAP
                        </a>
                        <a href="{{ route('admin.clients') }}"
                            class="sidebar-link {{ request()->routeIs('admin.clients') ? 'active' : '' }}"
                            @click="sidebarOpen = false">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" />
                            </svg>
                            Kelola Klien
                        </a>
                        <div class="px-3 pt-3 pb-1">
                            <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Monitoring</span>
                        </div>
                    @endif

                    {{-- Auditor Navigation --}}
                    @if (auth()->user()->isAuditor())
                        <a href="{{ route('kap-profile') }}"
                            class="sidebar-link {{ request()->routeIs('kap-profile') ? 'active' : '' }}"
                            @click="sidebarOpen = false">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                            Profil KAP
                        </a>
                        <a href="{{ route('clients.index') }}"
                            class="sidebar-link {{ request()->routeIs('clients.*') ? 'active' : '' }}"
                            @click="sidebarOpen = false">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Klien
                        </a>
                        <a href="{{ route('invitations.index') }}"
                            class="sidebar-link {{ request()->routeIs('invitations.*') ? 'active' : '' }}"
                            @click="sidebarOpen = false">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                            Undangan
                        </a>
                    @endif

                    {{-- Shared: Schedule --}}
                    @if (!auth()->user()->isSuperAdmin())
                        <a href="{{ route('schedule.index') }}"
                            class="sidebar-link {{ request()->routeIs('schedule.*') ? 'active' : '' }}"
                            @click="sidebarOpen = false">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            Schedule
                        </a>
                    @endif
                </nav>

                {{-- User Info --}}
                <div class="mt-auto pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-3 px-2">
                        <div
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 text-white
                        {{ auth()->user()->isSuperAdmin() ? 'bg-linear-to-br from-purple-600 to-purple-400' : (auth()->user()->isAuditor() ? 'bg-linear-to-br from-blue-600 to-blue-400' : 'bg-linear-to-br from-red-600 to-red-400') }}">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3 px-2">
                        @csrf
                        <button type="submit" class="btn-ghost w-full text-sm text-center">Logout</button>
                    </form>
                </div>
            </aside>

            {{-- Mobile Header --}}
            <div
                class="lg:hidden fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-100 py-3 px-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="hamburger-btn" aria-label="Toggle menu">
                        <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <svg x-show="sidebarOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-7 h-7 rounded-lg bg-linear-to-br from-blue-600 to-blue-400 flex items-center justify-center font-bold text-xs text-white">
                            WA</div>
                        <span class="font-semibold text-sm text-slate-900">WebAudit</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <livewire:notification-bell />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-ghost text-xs py-1.5 px-3">Logout</button>
                    </form>
                </div>
            </div>

            {{-- Main Content --}}
            <main class="flex-1 lg:ml-64">
                {{-- Topbar Desktop --}}
                <div
                    class="hidden lg:flex items-center justify-end px-8 py-3 bg-white/50 backdrop-blur-md border-b border-slate-100/50 sticky top-0 z-20">
                    <div class="flex items-center gap-4">
                        <livewire:notification-bell />
                        <div class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</div>
                    </div>
                </div>
                <div class="p-4 sm:p-6 lg:p-8 max-lg:pt-20">
                    {{-- Flash Messages --}}
                    @if (session('success'))
                        <div
                            class="mb-4 sm:mb-6 bg-emerald-50 border border-emerald-200 rounded-xl p-3 sm:p-4 border-l-4 border-l-emerald-500 flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-emerald-800">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div
                            class="mb-4 sm:mb-6 bg-red-50 border border-red-200 rounded-xl p-3 sm:p-4 border-l-4 border-l-red-500 flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-red-800">{{ session('error') }}</span>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    @else
        <div class="relative">
            {{ $slot }}
        </div>
    @endauth

    @livewireScripts
</body>

</html>
