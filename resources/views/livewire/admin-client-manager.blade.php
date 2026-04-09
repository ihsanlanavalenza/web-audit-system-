<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kelola Klien</h1>
            <p class="text-sm text-slate-500 mt-1">CRUD klien lintas KAP untuk Super Admin</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" class="form-input text-sm sm:w-64"
                placeholder="Cari klien / PIC / KAP...">
            <select wire:model.live="kapFilter" class="form-input text-sm sm:w-56">
                <option value="">Semua KAP</option>
                @foreach ($kaps as $kap)
                    <option value="{{ $kap->id }}">{{ $kap->nama_kap }}</option>
                @endforeach
            </select>
            <button wire:click="openModal" class="btn-superadmin text-sm whitespace-nowrap">+ Tambah Klien</button>
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Klien</th>
                        <th>PIC</th>
                        <th>No. Contact</th>
                        <th>KAP</th>
                        <th>Tahun Audit</th>
                        <th>Data Request</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $index => $client)
                        <tr>
                            <td class="font-mono text-slate-500">{{ $clients->firstItem() + $index }}</td>
                            <td class="font-medium text-slate-900">{{ $client->nama_client }}</td>
                            <td class="text-slate-600">{{ $client->nama_pic }}</td>
                            <td class="text-slate-600">{{ $client->no_contact }}</td>
                            <td class="text-slate-700">{{ $client->kapProfile?->nama_kap ?? '-' }}</td>
                            <td class="text-slate-600">{{ $client->tahun_audit?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-slate-600">{{ $client->data_requests_count }}</td>
                            <td>
                                <div class="flex gap-2">
                                    <button wire:click="editClient({{ $client->id }})"
                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button>
                                    <button wire:click="confirmDelete({{ $client->id }})"
                                        class="text-red-600 hover:text-red-700 text-xs font-medium">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">Belum ada klien.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($clients->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $clients->links() }}
            </div>
        @endif
    </div>

    @if ($showModal)
        <div class="modal-overlay" wire:click.self="$set('showModal', false)">
            <div class="modal-content p-6 sm:p-8">
                <h3 class="text-lg font-bold mb-6 text-slate-900">{{ $editId ? 'Edit Klien' : 'Tambah Klien Baru' }}
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="form-label">Nama Klien</label>
                        <input wire:model="nama_client" type="text" class="form-input"
                            placeholder="PT Contoh Indonesia">
                        @error('nama_client')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Nama PIC</label>
                        <input wire:model="nama_pic" type="text" class="form-input" placeholder="Nama PIC">
                        @error('nama_pic')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">No. Contact</label>
                        <input wire:model="no_contact" type="text" class="form-input" placeholder="08xxxxxxxxxx">
                        @error('no_contact')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">KAP</label>
                        <select wire:model="kap_id" class="form-input">
                            <option value="">-- Pilih KAP --</option>
                            @foreach ($kaps as $kap)
                                <option value="{{ $kap->id }}">{{ $kap->nama_kap }}</option>
                            @endforeach
                        </select>
                        @error('kap_id')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tahun Audit</label>
                            <input wire:model="tahun_audit" type="date" class="form-input">
                            @error('tahun_audit')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">Alamat</label>
                            <input wire:model="alamat" type="text" class="form-input"
                                placeholder="Alamat klien (opsional)">
                            @error('alamat')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
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
                <h3 class="text-lg font-bold mb-2 text-red-700">Hapus Klien?</h3>
                <p class="text-sm text-slate-500 mb-6">Data request terkait klien ini akan ikut terhapus.</p>
                <div class="flex gap-3 justify-center">
                    <button wire:click="deleteClient" class="btn-auditi text-sm">Ya, Hapus</button>
                    <button wire:click="$set('showDeleteConfirm', false)" class="btn-ghost text-sm">Batal</button>
                </div>
            </div>
        </div>
    @endif
</div>
