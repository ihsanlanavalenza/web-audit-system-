<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kelola User</h1>
            <p class="text-sm text-slate-500 mt-1">Manajemen semua pengguna sistem</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" class="form-input text-sm sm:w-64"
                placeholder="🔍 Cari nama atau email..." id="search-user">
            <button wire:click="openModal" class="btn-superadmin text-sm whitespace-nowrap" id="btn-add-user">+ Tambah
                User</button>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $user)
                        <tr>
                            <td class="font-mono text-slate-500">{{ $users->firstItem() + $index }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 text-white
                                    {{ $user->role === 'super_admin' ? 'bg-linear-to-br from-purple-600 to-purple-400' : ($user->role === 'auditor' ? 'bg-linear-to-br from-blue-600 to-blue-400' : 'bg-linear-to-br from-red-600 to-red-400') }}">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <span
                                        class="font-medium truncate max-w-37.5 text-slate-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="text-slate-500 text-xs">{{ $user->email }}</td>
                            <td>
                                <select wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                    class="text-xs rounded-full px-2 py-1 border cursor-pointer font-semibold
                                {{ $user->role === 'super_admin' ? 'bg-purple-50 text-purple-700 border-purple-200' : ($user->role === 'auditor' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-red-50 text-red-700 border-red-200') }}"
                                    {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                    <option value="super_admin" {{ $user->role === 'super_admin' ? 'selected' : '' }}>
                                        Super Admin</option>
                                    <option value="auditor" {{ $user->role === 'auditor' ? 'selected' : '' }}>Auditor
                                    </option>
                                    <option value="auditi" {{ $user->role === 'auditi' ? 'selected' : '' }}>Auditi
                                    </option>
                                </select>
                            </td>
                            <td class="text-xs text-slate-400 whitespace-nowrap">
                                {{ $user->created_at?->format('d/m/Y') }}</td>
                            <td>
                                <div class="flex gap-2">
                                    <button wire:click="editUser({{ $user->id }})"
                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button>
                                    @if ($user->id !== auth()->id())
                                        <button wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="Yakin hapus user {{ $user->name }}?"
                                            class="text-red-600 hover:text-red-700 text-xs font-medium">Hapus</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400">Tidak ada user ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Add/Edit Modal --}}
    @if ($showModal)
        <div class="modal-overlay" wire:click.self="$set('showModal', false)">
            <div class="modal-content p-6 sm:p-8">
                <h3 class="text-lg font-bold mb-6 text-slate-900">{{ $editId ? 'Edit User' : 'Tambah User Baru' }}</h3>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="form-label">Nama Lengkap</label>
                        <input wire:model="name" type="text" class="form-input" placeholder="Nama lengkap">
                        @error('name')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input wire:model="email" type="email" class="form-input" placeholder="email@contoh.com">
                        @error('email')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Role</label>
                        <select wire:model="role" class="form-input">
                            <option value="super_admin">🛡️ Super Admin</option>
                            <option value="auditor">🔵 Auditor</option>
                            <option value="auditi">🔴 Auditi</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Password {{ $editId ? '(kosongkan jika tidak diubah)' : '' }}</label>
                        <input wire:model="password" type="password" class="form-input"
                            placeholder="{{ $editId ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}">
                        @error('password')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn-superadmin">{{ $editId ? 'Perbarui' : 'Simpan' }}</button>
                        <button type="button" wire:click="$set('showModal', false)" class="btn-ghost">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Role Conflict Modal --}}
    @if ($showRoleConflictModal)
        <div class="modal-overlay" wire:click.self="cancelRoleChange">
            <div class="modal-content p-6 sm:p-8 max-w-lg">
                <h3 class="text-lg font-bold mb-3 text-slate-900">Konflik Undangan Pending</h3>
                <p class="text-sm text-slate-600 mb-4">
                    User <strong>{{ $pendingRoleUserName }}</strong> memiliki {{ $pendingConflictCount }} undangan
                    pending dengan role berbeda.
                </p>
                <p class="text-sm text-slate-500 mb-6">
                    Pilih tindakan: lanjut ubah role dan biarkan undangan tetap aktif, atau batalkan undangan pending
                    agar role tidak berubah lagi saat undangan diterima.
                </p>

                <div class="flex flex-wrap gap-3">
                    <button type="button" wire:click="confirmRoleChange(false)" class="btn-superadmin text-sm">
                        Lanjut Tanpa Batalkan Undangan
                    </button>
                    <button type="button" wire:click="confirmRoleChange(true)" class="btn-auditi text-sm">
                        Lanjut & Batalkan Undangan Pending
                    </button>
                    <button type="button" wire:click="cancelRoleChange" class="btn-ghost text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
