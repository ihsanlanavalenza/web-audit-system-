<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Klien</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola daftar klien audit Anda</p>
        </div>
        <div class="flex gap-2">
            @if ($clients->count() > 0)
                <button wire:click="$set('showDeleteConfirm', true)"
                    class="btn-ghost text-sm text-red-600 border-red-200 hover:bg-red-50" id="btn-delete-all">
                    Hapus Semua
                </button>
            @endif
            <button wire:click="openModal" class="btn-auditor text-sm" id="btn-add-client">
                + Tambah Klien
            </button>
        </div>
    </div>

    {{-- Client Table --}}
    @if ($clients->count() > 0)
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Client</th>
                            <th>PIC</th>
                            <th>No. Contact</th>
                            <th>Alamat</th>
                            <th>Tahun Audit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $index => $client)
                            <tr>
                                <td class="text-slate-500">{{ $index + 1 }}</td>
                                <td class="font-medium text-slate-900">{{ $client->nama_client }}</td>
                                <td class="text-slate-600">{{ $client->nama_pic }}</td>
                                <td class="text-slate-600">{{ $client->no_contact }}</td>
                                <td class="text-slate-600 max-w-37.5 truncate" title="{{ $client->alamat }}">
                                    {{ $client->alamat ?? '-' }}</td>
                                <td class="text-slate-600">{{ $client->tahun_audit?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        <button wire:click="editClient({{ $client->id }})"
                                            class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button>
                                        <a href="{{ route('schedule.show', $client->id) }}"
                                            class="text-emerald-600 hover:text-emerald-700 text-xs font-medium">Schedule</a>
                                        <button wire:click="deleteClient({{ $client->id }})"
                                            wire:confirm="Yakin hapus klien ini?"
                                            class="text-red-600 hover:text-red-700 text-xs font-medium">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="glass-card p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <p class="text-slate-400 text-sm">Belum ada klien. Klik "Tambah Klien" untuk memulai.</p>
        </div>
    @endif

    {{-- Add/Edit Modal --}}
    @if ($showModal)
        <div class="modal-overlay" wire:click.self="$set('showModal', false)">
            <div class="modal-content p-8">
                <h3 class="text-lg font-bold mb-6 text-slate-900">{{ $editId ? 'Edit Klien' : 'Tambah Klien Baru' }}
                </h3>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="form-label">Nama Client</label>
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
                        <label class="form-label">Alamat</label>
                        <input wire:model="alamat" type="text" class="form-input" placeholder="Alamat klien">
                        @error('alamat')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Tahun Audit</label>
                        <input wire:model="tahun_audit" type="date" class="form-input">
                        @error('tahun_audit')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn-auditor">{{ $editId ? 'Perbarui' : 'Simpan' }}</button>
                        <button type="button" wire:click="$set('showModal', false)" class="btn-ghost">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete All Confirm --}}
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
                <h3 class="text-lg font-bold mb-2 text-red-700">Hapus Semua Klien?</h3>
                <p class="text-sm text-slate-500 mb-6">Tindakan ini tidak dapat dibatalkan. Semua data klien dan request
                    terkait akan dihapus.</p>
                <div class="flex gap-3 justify-center">
                    <button wire:click="deleteAll" class="btn-auditi text-sm">Ya, Hapus Semua</button>
                    <button wire:click="$set('showDeleteConfirm', false)" class="btn-ghost text-sm">Batal</button>
                </div>
            </div>
        </div>
    @endif
</div>
