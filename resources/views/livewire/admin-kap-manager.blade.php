<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kelola KAP</h1>
            <p class="text-sm text-slate-500 mt-1">Manajemen profil KAP dan ownership auditor</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" class="form-input text-sm sm:w-72"
                placeholder="Cari nama KAP / PIC / auditor...">
            <button wire:click="openModal" class="btn-superadmin text-sm whitespace-nowrap">+ Tambah KAP</button>
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama KAP</th>
                        <th>Nama PIC</th>
                        <th>Owner Auditor</th>
                        <th>Total Klien</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kaps as $index => $kap)
                        <tr>
                            <td class="font-mono text-slate-500">{{ $kaps->firstItem() + $index }}</td>
                            <td class="font-medium text-slate-900">{{ $kap->nama_kap }}</td>
                            <td class="text-slate-600">{{ $kap->nama_pic }}</td>
                            <td>
                                <div class="text-slate-700 text-sm">{{ $kap->user?->name ?? '-' }}</div>
                                <div class="text-slate-400 text-xs">{{ $kap->user?->email ?? '-' }}</div>
                            </td>
                            <td class="text-slate-600">{{ $kap->clients_count }}</td>
                            <td class="text-slate-600 max-w-60 truncate" title="{{ $kap->alamat }}">{{ $kap->alamat }}
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button wire:click="editKap({{ $kap->id }})"
                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button>
                                    <button wire:click="confirmDelete({{ $kap->id }})"
                                        class="text-red-600 hover:text-red-700 text-xs font-medium">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">Belum ada profil KAP.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($kaps->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $kaps->links() }}
            </div>
        @endif
    </div>

    @if ($showModal)
        <div class="modal-overlay" wire:click.self="$set('showModal', false)">
            <div class="modal-content p-6 sm:p-8">
                <h3 class="text-lg font-bold mb-6 text-slate-900">{{ $editId ? 'Edit KAP' : 'Tambah KAP Baru' }}</h3>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="form-label">Nama KAP</label>
                        <input wire:model="nama_kap" type="text" class="form-input" placeholder="KAP Contoh & Rekan">
                        @error('nama_kap')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Nama PIC</label>
                        <input wire:model="nama_pic" type="text" class="form-input" placeholder="Nama PIC KAP">
                        @error('nama_pic')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Owner Auditor</label>
                        <select wire:model="user_id" class="form-input">
                            <option value="">-- Pilih Auditor --</option>
                            @foreach ($auditors as $auditor)
                                <option value="{{ $auditor->id }}">{{ $auditor->name }} ({{ $auditor->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Alamat</label>
                        <textarea wire:model="alamat" class="form-input" rows="3" placeholder="Alamat lengkap KAP"></textarea>
                        @error('alamat')
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

    @if ($showDeleteConfirm)
        <div class="modal-overlay" wire:click.self="$set('showDeleteConfirm', false)">
            <div class="modal-content p-8 max-w-sm text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold mb-2 text-red-700">Hapus KAP?</h3>
                <p class="text-sm text-slate-500 mb-6">Semua klien, data request, dan undangan terkait KAP ini akan ikut
                    terhapus.</p>
                <div class="flex gap-3 justify-center">
                    <button wire:click="deleteKap" class="btn-auditi text-sm">Ya, Hapus</button>
                    <button wire:click="$set('showDeleteConfirm', false)" class="btn-ghost text-sm">Batal</button>
                </div>
            </div>
        </div>
    @endif
</div>
