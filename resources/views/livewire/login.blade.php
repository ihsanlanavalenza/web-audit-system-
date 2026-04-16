<div>
    <h2 class="text-xl font-bold mb-1 text-slate-900">Masuk</h2>
    <p class="text-sm text-slate-500 mb-6">Login ke akun WebAudit Anda</p>

    @if (session('error'))
        <div class="mb-4 text-sm text-red-600 bg-red-100 p-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6">
        @if ($googleLoginEnabled ?? false)
            <a href="{{ route('google.login') }}"
                class="w-full flex justify-center items-center py-2 px-4 border border-slate-300 rounded-md shadow-sm bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                    <path fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                Masuk dengan Google
            </a>
        @else
            <div class="w-full flex justify-center items-center py-2 px-4 border border-slate-200 rounded-md bg-slate-100 text-sm font-medium text-slate-400 cursor-not-allowed"
                aria-disabled="true">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                    <path fill="#9CA3AF"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#9CA3AF"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#9CA3AF"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#9CA3AF"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                Login Google belum tersedia
            </div>
        @endif
    </div>

    <div class="flex items-center mb-6">
        <hr class="w-full border-slate-200">
        <span class="px-3 text-slate-400 text-xs font-semibold tracking-wide uppercase">ATAU</span>
        <hr class="w-full border-slate-200">
    </div>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label class="form-label">Email</label>
            <input wire:model="email" type="email" class="form-input" placeholder="email@contoh.com" id="login-email">
            @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label">Password</label>
            <input wire:model="password" type="password" class="form-input" placeholder="Masukkan password"
                id="login-password">
            @error('password')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input wire:model="remember" type="checkbox"
                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" id="login-remember">
            <label for="login-remember" class="text-sm text-slate-500">Ingat saya</label>
        </div>

        <button type="submit" class="btn-auditor w-full text-center" id="login-submit">
            <span wire:loading.remove>Masuk</span>
            <span wire:loading>Memproses...</span>
        </button>
    </form>

    <p class="text-center text-sm text-slate-400 mt-6">
        Belum punya akun?
        <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-medium">Daftar</a>
    </p>
</div>
