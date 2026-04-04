<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Client Assistance Schedule</h1>
            <p class="text-sm text-slate-500 mt-1">Jadwal permintaan dan tracking data audit</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Client Selector (Auditor Only) --}}
            @if (auth()->user()->isAuditor() && count($clients) > 0)
                <select wire:model.live="clientId" class="form-input w-auto text-sm" id="client-selector">
                    <option value="">-- Pilih Klien --</option>
                    @foreach ($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->nama_client }}</option>
                    @endforeach
                </select>
            @endif

            @if ($clientId && auth()->user()->isAuditor())
                <button wire:click="openAddModal" class="btn-auditor text-sm" id="btn-add-request">
                    + Tambah Request
                </button>
            @endif
        </div>
    </div>

    @if (!$clientId)
        <div class="glass-card p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125" />
                </svg>
            </div>
            <p class="text-slate-400 text-sm">Pilih klien terlebih dahulu untuk melihat schedule.</p>
        </div>
    @elseif($requests->count() === 0)
        <div class="glass-card p-12 text-center">
            <p class="text-slate-400 text-sm">Belum ada data request untuk klien ini.</p>
        </div>
    @else
        {{-- Data Request Table --}}
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-24">Section / No.</th>
                            <th>Account / Process</th>
                            <th>Description</th>
                            <th>Request Date</th>
                            <th>Expected Received</th>
                            <th>Input File</th>
                            <th>Status</th>
                            <th>Last Update</th>
                            <th>Date Input</th>
                            <th>Comment (Client)</th>
                            <th>Comment (Auditor)</th>
                            @if (auth()->user()->isAuditor())
                                <th>Opsi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Pre-calculate rowspans for section_code, section, and account_process grouping
                            $items = $requests->values();
                            $sectionCodeSpans = [];
                            $sectionNoSpans = [];
                            $accountSpans = [];

                            $i = 0;
                            while ($i < $items->count()) {
                                // 1. Group by section_code (e.g. "A")
                                $codeStart = $i;
                                $currentCode = $items[$i]->section_code;
                                $codeCount = 0;
                                while ($i < $items->count() && $items[$i]->section_code === $currentCode) {
                                    $codeCount++;
                                    $i++;
                                }
                                $sectionCodeSpans[$codeStart] = $codeCount;

                                // 2. Within section_code, group by section (e.g. "A.1") AND account_process
                                $j = $codeStart;
                                while ($j < $codeStart + $codeCount) {
                                    $noStart = $j;
                                    $currentNo = $items[$j]->section;
                                    $currentAccount = $items[$j]->account_process;
                                    $noCount = 0;

                                    while (
                                        $j < $codeStart + $codeCount &&
                                        $items[$j]->section === $currentNo &&
                                        $items[$j]->account_process === $currentAccount
                                    ) {
                                        $noCount++;
                                        $j++;
                                    }
                                    $sectionNoSpans[$noStart] = $noCount;
                                    $accountSpans[$noStart] = $noCount; // Account merges identically with Section No
                                }
                            }
                        @endphp

                        @foreach ($items as $idx => $req)
                            <tr wire:key="req-{{ $req->id }}">
                                {{-- Section Code column (e.g. "A") --}}
                                @if (isset($sectionCodeSpans[$idx]))
                                    <td class="font-bold text-slate-900 text-center align-middle bg-slate-50"
                                        rowspan="{{ $sectionCodeSpans[$idx] }}">
                                        {{ $req->section_code ?: '-' }}
                                    </td>
                                @endif

                                {{-- Section No per row (e.g. "A.1") --}}
                                @if (isset($sectionNoSpans[$idx]))
                                    <td class="font-mono font-semibold text-slate-700 whitespace-nowrap align-top text-center"
                                        rowspan="{{ $sectionNoSpans[$idx] }}">
                                        {{ $req->section ?: '-' }}
                                    </td>
                                @endif

                                {{-- Account / Process --}}
                                @if (isset($accountSpans[$idx]))
                                    <td class="text-slate-600 align-top" rowspan="{{ $accountSpans[$idx] }}">
                                        {{ $req->account_process ?? '-' }}
                                    </td>
                                @endif

                                {{-- Description --}}
                                <td class="max-w-50 text-slate-600">
                                    {{ $req->description ?? '-' }}
                                </td>

                                {{-- Request Date --}}
                                <td class="whitespace-nowrap text-slate-600">
                                    {{ $req->request_date?->format('d/m/Y') ?? '-' }}</td>

                                {{-- Expected Received --}}
                                <td class="whitespace-nowrap text-slate-600">
                                    {{ $req->expected_received?->format('d/m/Y') ?? '-' }}</td>

                                {{-- Input File --}}
                                <td>
                                    <div class="flex items-center gap-2">
                                        <button wire:click="toggleFileDetail({{ $req->id }})"
                                            class="flex items-center justify-center w-7 h-7 rounded-lg transition-all duration-200 hover:scale-110 {{ is_array($req->input_file) && count($req->input_file) > 0 ? 'bg-emerald-100 hover:bg-emerald-200' : 'bg-red-100 hover:bg-red-200' }}"
                                            title="{{ is_array($req->input_file) && count($req->input_file) > 0 ? 'File sudah diupload — klik untuk detail' : 'File belum diupload — klik untuk detail' }}">
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ $expandedFileRow === $req->id ? 'rotate-90' : '' }} {{ is_array($req->input_file) && count($req->input_file) > 0 ? 'text-emerald-600' : 'text-red-500' }}"
                                                fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </button>

                                        @if (is_array($req->input_file) && count($req->input_file) > 0)
                                            <span
                                                class="text-xs text-emerald-600 font-medium">{{ count($req->input_file) }}
                                                File</span>
                                        @else
                                            <span class="text-xs text-red-500 font-medium">Belum</span>
                                        @endif
                                    </div>

                                    {{-- Expanded File Detail --}}
                                    @if ($expandedFileRow === $req->id)
                                        <div
                                            class="mt-2 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs space-y-2">
                                            @if (is_array($req->input_file) && count($req->input_file) > 0)
                                                <div class="flex items-center gap-2 mb-2">
                                                    <svg class="w-4 h-4 text-emerald-500" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="text-slate-700 font-medium">File telah diupload:</span>
                                                </div>
                                                <div class="space-y-1">
                                                    @foreach ($req->input_file as $idx => $path)
                                                        <div
                                                            class="flex items-center justify-between bg-white p-2 rounded border border-slate-100">
                                                            <span class="text-slate-500 truncate max-w-37.5"
                                                                title="{{ basename($path) }}">{{ basename($path) }}</span>
                                                            <a href="{{ asset('storage/' . $path) }}" target="_blank"
                                                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition font-medium">
                                                                <svg class="w-3 h-3" fill="none"
                                                                    stroke="currentColor" stroke-width="2"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                </svg>
                                                                Unduh
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                {{-- Allow adding more files --}}
                                                <div x-data="{ uploading: false }"
                                                    class="mt-3 pt-3 border-t border-slate-200">
                                                    <input type="file" wire:model="uploadFiles" multiple
                                                        x-on:livewire-upload-start="uploading = true"
                                                        x-on:livewire-upload-finish="uploading = false" class="hidden"
                                                        id="file-add-{{ $req->id }}"
                                                        x-on:change="$wire.uploadFilesForRow({{ $req->id }})">
                                                    <label for="file-add-{{ $req->id }}"
                                                        class="cursor-pointer inline-flex flex-wrap justify-center items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition font-medium w-full">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 4.5v15m7.5-7.5h-15" />
                                                        </svg>
                                                        Tambah File Lagi
                                                    </label>
                                                    <div x-show="uploading" class="text-amber-600 mt-1 text-center">
                                                        Uploading...</div>
                                                </div>
                                            @else
                                                <div x-data="{ uploading: false }" class="space-y-2">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <svg class="w-5 h-5 text-red-400" fill="none"
                                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                        </svg>
                                                        <span class="text-slate-600 text-center">Belum ada file yang
                                                            diupload</span>
                                                    </div>
                                                    <input type="file" wire:model="uploadFiles" multiple
                                                        x-on:livewire-upload-start="uploading = true"
                                                        x-on:livewire-upload-finish="uploading = false" class="hidden"
                                                        id="file-{{ $req->id }}"
                                                        x-on:change="$wire.uploadFilesForRow({{ $req->id }})">
                                                    <label for="file-{{ $req->id }}"
                                                        class="cursor-pointer flex justify-center items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium w-full">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                                        </svg>
                                                        Upload File (Multiple)
                                                    </label>
                                                    <div x-show="uploading" class="text-amber-600 text-center">
                                                        Uploading...</div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if (auth()->user()->isAuditor())
                                        <select wire:change="updateStatus({{ $req->id }}, $event.target.value)"
                                            class="text-xs rounded-full px-2 py-1 border cursor-pointer badge-{{ str_replace('_', '-', $req->status) }}">
                                            @foreach ($statuses as $key => $label)
                                                <option value="{{ $key }}"
                                                    {{ $req->status === $key ? 'selected' : '' }}>{{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="badge-{{ str_replace('_', '-', $req->status) }}">
                                            {{ $statuses[$req->status] ?? $req->status }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Last Update --}}
                                <td class="whitespace-nowrap text-xs text-slate-400">
                                    {{ $req->last_update?->format('d/m/Y H:i') ?? '-' }}</td>

                                {{-- Date Input --}}
                                <td class="whitespace-nowrap text-slate-600">
                                    @if ($req->date_input)
                                        <div class="text-xs">
                                            <div class="font-medium text-slate-700">
                                                {{ $req->date_input->format('d/m/Y') }}</div>
                                            <div class="text-slate-400">{{ $req->date_input->format('H:i') }}</div>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-300 italic">Auto-fill saat upload</span>
                                    @endif
                                </td>

                                {{-- Comment Client --}}
                                <td>
                                    @if ($commentRowId === $req->id && auth()->user()->isAuditi())
                                        <div class="flex gap-1">
                                            <input wire:model="newComment" type="text"
                                                class="form-input text-xs py-1 px-2" style="min-width:100px">
                                            <button wire:click="saveComment({{ $req->id }})"
                                                class="text-emerald-600 text-xs font-bold">✓</button>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs truncate max-w-30 text-slate-600"
                                                title="{{ $req->comment_client }}">{{ $req->comment_client ?: '-' }}</span>
                                            @if (auth()->user()->isAuditi())
                                                <button wire:click="openComment({{ $req->id }})"
                                                    class="text-blue-600 text-xs hover:text-blue-700"
                                                    title="Edit komentar">✎</button>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Comment Auditor --}}
                                <td>
                                    @if ($commentRowId === $req->id && auth()->user()->isAuditor())
                                        <div class="flex gap-1">
                                            <input wire:model="newComment" type="text"
                                                class="form-input text-xs py-1 px-2" style="min-width:100px">
                                            <button wire:click="saveComment({{ $req->id }})"
                                                class="text-emerald-600 text-xs font-bold">✓</button>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs truncate max-w-30 text-slate-600"
                                                title="{{ $req->comment_auditor }}">{{ $req->comment_auditor ?: '-' }}</span>
                                            @if (auth()->user()->isAuditor())
                                                <button wire:click="openComment({{ $req->id }})"
                                                    class="text-blue-600 text-xs hover:text-blue-700"
                                                    title="Edit komentar">✎</button>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Opsi (Auditor Only) --}}
                                @if (auth()->user()->isAuditor())
                                    <td>
                                        <div class="flex gap-2">
                                            <button wire:click="editRow({{ $req->id }})"
                                                class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button>
                                            <button wire:click="deleteRow({{ $req->id }})"
                                                wire:confirm="Yakin hapus baris ini?"
                                                class="text-red-600 hover:text-red-700 text-xs font-medium">Hapus</button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Add/Edit Modal (Auditor Only) --}}
    @if ($showModal)
        <div class="modal-overlay" wire:click.self="$set('showModal', false)">
            <div class="modal-content p-8" style="max-width: 48rem;">
                <h3 class="text-lg font-bold mb-6 text-slate-900">
                    {{ $editId ? 'Edit Data Request' : 'Tambah Data Request' }}</h3>
                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">No</label>
                            <input wire:model="no" type="number" class="form-input" min="1">
                            @error('no')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label">Section Code</label>
                            <input wire:model="section_code" type="text" class="form-input"
                                placeholder="Contoh: A, B, C">
                            @error('section_code')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label">Section No</label>
                            <input wire:model="section_no_input" type="text" class="form-input"
                                placeholder="Contoh: A.1, B.2, C.3">
                            @error('section_no_input')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Account / Process</label>
                        <input wire:model="account_process" type="text" class="form-input"
                            placeholder="Nama akun atau proses">
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea wire:model="description" class="form-input" rows="2" placeholder="Deskripsi data yang diminta"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Request Date</label>
                            <input wire:model="request_date" type="date" class="form-input">
                            @error('request_date')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label">Expected Received</label>
                            <input wire:model="expected_received" type="date" class="form-input">
                            @error('expected_received')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Status</label>
                            <select wire:model="status" class="form-input">
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-slate-400">Date Input</label>
                            <div class="form-input bg-slate-50 text-slate-400 cursor-not-allowed text-sm">
                                <em>Otomatis terisi saat upload file</em>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Comment (Client)</label>
                            <input wire:model="comment_client" type="text" class="form-input"
                                placeholder="Komentar klien">
                        </div>
                        <div>
                            <label class="form-label">Comment (Auditor)</label>
                            <input wire:model="comment_auditor" type="text" class="form-input"
                                placeholder="Komentar auditor">
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn-auditor">{{ $editId ? 'Perbarui' : 'Simpan' }}</button>
                        <button type="button" wire:click="$set('showModal', false)" class="btn-ghost">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
