<div>
    <h2 class="text-xl font-bold mb-1 text-slate-900">Daftar Akun</h2>
    <p class="text-sm text-slate-500 mb-6">Buat akun baru untuk mulai menggunakan WebAudit</p>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label class="form-label">Nama Lengkap</label>
            <input wire:model="name" type="text" class="form-input" placeholder="Masukkan nama lengkap"
                id="register-name">
            @error('name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label">Email</label>
            <input wire:model="email" type="email" class="form-input" placeholder="email@contoh.com"
                id="register-email" {{ $invitation_token ? 'readonly' : '' }}>
            @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        @if ($invitation_token)
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Role dari undangan</p>
                <p class="text-sm text-blue-900 mt-1">Role akun akan mengikuti role yang ditentukan pada undangan.</p>
            </div>
        @else
            <div>
                <label class="form-label">Daftar Sebagai</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="block cursor-pointer">
                        <input type="radio" wire:model="role" value="auditor" class="sr-only peer">
                        <div
                            class="rounded-xl border border-slate-200 p-3 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:ring-2 peer-checked:ring-blue-200">
                            <p class="font-semibold text-slate-900 text-sm">Auditor</p>
                            <p class="text-xs text-slate-500 mt-1">Kelola KAP, klien, dan proses audit.</p>
                        </div>
                    </label>

                    <label class="block cursor-pointer">
                        <input type="radio" wire:model="role" value="auditi" class="sr-only peer">
                        <div
                            class="rounded-xl border border-slate-200 p-3 transition-all peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:ring-2 peer-checked:ring-red-200">
                            <p class="font-semibold text-slate-900 text-sm">Auditi</p>
                            <p class="text-xs text-slate-500 mt-1">Upload dokumen dan tindak lanjut permintaan audit.</p>
                        </div>
                    </label>
                </div>
                @error('role')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div>
            <label class="form-label">Password</label>
            <input wire:model="password" type="password" class="form-input" placeholder="Minimal 8 karakter"
                id="register-password">
            @error('password')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label">Konfirmasi Password</label>
            <input wire:model="password_confirmation" type="password" class="form-input" placeholder="Ulangi password"
                id="register-password-confirm">
        </div>

        <button type="submit" class="btn-auditor w-full text-center" id="register-submit">
            <span wire:loading.remove>Daftar</span>
            <span wire:loading>Memproses...</span>
        </button>
    </form>

    <p class="text-center text-sm text-slate-400 mt-6">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">Masuk</a>
    </p>
</div>
