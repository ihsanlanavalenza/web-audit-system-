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
                <button wire:click="exportCsv" class="btn-ghost text-sm flex items-center gap-2 border border-slate-200"
                    id="btn-export">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export CSV
                </button>
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
    @elseif(!$hasBaseRequests)
        <div class="glass-card p-12 text-center">
            <p class="text-slate-400 text-sm">Belum ada data request untuk klien ini.</p>
        </div>
    @else
        {{-- Data Request Table --}}
        <div class="glass-card overflow-hidden" x-data="{ openFilter: null }">
            @if ($uploadError)
                <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                    {{ $uploadError }}
                </div>
            @endif

            <div class="overflow-x-auto schedule-table-wrap">
                <table class="data-table schedule-table">
                    <thead>
                        <tr>
                            <th class="w-40" colspan="2">
                                <div class="th-filter">
                                    <span>Section / No.</span>
                                    <button type="button"
                                        class="th-filter-trigger {{ $filterSectionNo !== '' ? 'is-active' : '' }}"
                                        @click="openFilter = openFilter === 'section_no' ? null : 'section_no'"
                                        :aria-expanded="openFilter === 'section_no'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 4.5h18m-15 6h12m-9 6h6" />
                                        </svg>
                                    </button>
                                    <div class="th-filter-menu" x-cloak x-show="openFilter === 'section_no'"
                                        x-transition.origin.top.right @click.outside="openFilter = null">
                                        <label class="th-filter-label">Cari Section / No.</label>
                                        <input wire:model.live.debounce.300ms="filterSectionNo" type="text"
                                            class="form-input text-xs" placeholder="Contoh: A / A.1">
                                        <div class="th-filter-actions">
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterSectionNo', ''); openFilter = null">Bersihkan</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="th-filter">
                                    <span>Account / Process</span>
                                    <button type="button"
                                        class="th-filter-trigger {{ $filterAccountProcess !== '' ? 'is-active' : '' }}"
                                        @click="openFilter = openFilter === 'account_process' ? null : 'account_process'"
                                        :aria-expanded="openFilter === 'account_process'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 4.5h18m-15 6h12m-9 6h6" />
                                        </svg>
                                    </button>
                                    <div class="th-filter-menu" x-cloak x-show="openFilter === 'account_process'"
                                        x-transition.origin.top.right @click.outside="openFilter = null">
                                        <label class="th-filter-label">Cari Account / Process</label>
                                        <input wire:model.live.debounce.300ms="filterAccountProcess" type="text"
                                            class="form-input text-xs" placeholder="Ketik kata kunci">
                                        <div class="th-filter-actions">
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterAccountProcess', ''); openFilter = null">Bersihkan</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th>Description</th>
                            <th>
                                <div class="th-filter">
                                    <span>Request Date</span>
                                    <button type="button"
                                        class="th-filter-trigger {{ $filterRequestDateFrom || $filterRequestDateTo ? 'is-active' : '' }}"
                                        @click="openFilter = openFilter === 'request_date' ? null : 'request_date'"
                                        :aria-expanded="openFilter === 'request_date'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <div class="th-filter-menu" x-cloak x-show="openFilter === 'request_date'"
                                        x-transition.origin.top.right @click.outside="openFilter = null">
                                        <label class="th-filter-label">From</label>
                                        <input wire:model.live="filterRequestDateFrom" type="date"
                                            class="form-input text-xs">
                                        <label class="th-filter-label mt-2">To</label>
                                        <input wire:model.live="filterRequestDateTo" type="date"
                                            class="form-input text-xs">
                                        <div class="th-filter-actions">
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterRequestDateFrom', null); $wire.set('filterRequestDateTo', null); openFilter = null">Bersihkan</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="th-filter">
                                    <span>Expected Received</span>
                                    <button type="button"
                                        class="th-filter-trigger {{ $filterExpectedReceivedFrom || $filterExpectedReceivedTo ? 'is-active' : '' }}"
                                        @click="openFilter = openFilter === 'expected_received' ? null : 'expected_received'"
                                        :aria-expanded="openFilter === 'expected_received'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <div class="th-filter-menu" x-cloak x-show="openFilter === 'expected_received'"
                                        x-transition.origin.top.right @click.outside="openFilter = null">
                                        <label class="th-filter-label">From</label>
                                        <input wire:model.live="filterExpectedReceivedFrom" type="date"
                                            class="form-input text-xs">
                                        <label class="th-filter-label mt-2">To</label>
                                        <input wire:model.live="filterExpectedReceivedTo" type="date"
                                            class="form-input text-xs">
                                        <div class="th-filter-actions">
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterExpectedReceivedFrom', null); $wire.set('filterExpectedReceivedTo', null); openFilter = null">Bersihkan</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="th-filter">
                                    <span>Input File</span>
                                    <button type="button"
                                        class="th-filter-trigger {{ $filterInputFileState !== '' ? 'is-active' : '' }}"
                                        @click="openFilter = openFilter === 'input_file' ? null : 'input_file'"
                                        :aria-expanded="openFilter === 'input_file'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 4.5h18m-15 6h12m-9 6h6" />
                                        </svg>
                                    </button>
                                    <div class="th-filter-menu" x-cloak x-show="openFilter === 'input_file'"
                                        x-transition.origin.top.right @click.outside="openFilter = null">
                                        <label class="th-filter-label">State Input File</label>
                                        <select wire:model.live="filterInputFileState" class="form-input text-xs">
                                            <option value="">Semua</option>
                                            <option value="uploaded">Sudah Upload</option>
                                            <option value="not_uploaded">Belum Upload</option>
                                        </select>
                                        <div class="th-filter-actions">
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterInputFileState', ''); openFilter = null">Bersihkan</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="th-filter">
                                    <span>Status</span>
                                    <button type="button"
                                        class="th-filter-trigger {{ !empty($filterStatuses) ? 'is-active' : '' }}"
                                        @click="openFilter = openFilter === 'status' ? null : 'status'"
                                        :aria-expanded="openFilter === 'status'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 4.5h18m-15 6h12m-9 6h6" />
                                        </svg>
                                    </button>
                                    <div class="th-filter-menu" x-cloak x-show="openFilter === 'status'"
                                        x-transition.origin.top.right @click.outside="openFilter = null">
                                        <label class="th-filter-label">Pilih Status</label>
                                        <div class="th-filter-checklist">
                                            @foreach ($statuses as $key => $label)
                                                <label class="th-filter-check">
                                                    <input type="checkbox" value="{{ $key }}"
                                                        wire:model.live="filterStatuses">
                                                    <span>{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="th-filter-actions">
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterStatuses', @js(array_keys($statuses))); openFilter = null">Pilih
                                                Semua</button>
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterStatuses', []); openFilter = null">Bersihkan</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th>Last Update</th>
                            <th>
                                <div class="th-filter">
                                    <span>Date Input</span>
                                    <button type="button"
                                        class="th-filter-trigger {{ $filterDateInputFrom || $filterDateInputTo ? 'is-active' : '' }}"
                                        @click="openFilter = openFilter === 'date_input' ? null : 'date_input'"
                                        :aria-expanded="openFilter === 'date_input'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <div class="th-filter-menu" x-cloak x-show="openFilter === 'date_input'"
                                        x-transition.origin.top.right @click.outside="openFilter = null">
                                        <label class="th-filter-label">From</label>
                                        <input wire:model.live="filterDateInputFrom" type="date"
                                            class="form-input text-xs">
                                        <label class="th-filter-label mt-2">To</label>
                                        <input wire:model.live="filterDateInputTo" type="date"
                                            class="form-input text-xs">
                                        <div class="th-filter-actions">
                                            <button type="button" class="th-filter-clear"
                                                @click="$wire.set('filterDateInputFrom', null); $wire.set('filterDateInputTo', null); openFilter = null">Bersihkan</button>
                                            <button type="button" class="th-filter-clear" wire:click="resetFilters"
                                                @click="openFilter = null">Reset Semua</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <th>Comment (Client)</th>
                            <th>Comment (Auditor)</th>
                            @if (auth()->user()->isAuditor())
                                <th>Opsi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $items =
                                $requests instanceof \Illuminate\Pagination\AbstractPaginator
                                    ? collect($requests->items())
                                    : collect($requests);
                            $columnCount = auth()->user()->isAuditor() ? 13 : 12;
                        @endphp

                        @if ($items->isEmpty())
                            <tr>
                                <td colspan="{{ $columnCount }}" class="px-4 py-12 text-center">
                                    <p class="text-sm text-slate-500">Data tidak ditemukan.</p>
                                    @if ($isFiltering)
                                        <p class="mt-1 text-xs text-slate-400">Coba ubah atau bersihkan filter untuk
                                            melihat data.</p>
                                    @endif
                                </td>
                            </tr>
                        @else
                            @php
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
                                                    class="text-xs text-emerald-600 font-medium">{{ isset($req->input_file[0]['version']) ? count($req->input_file) . ' Versi' : count($req->input_file) . ' File' }}</span>
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
                                                            stroke="currentColor" stroke-width="2"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="text-slate-700 font-medium">File telah
                                                            diupload:</span>
                                                    </div>
                                                    <div class="space-y-1">
                                                        @php
                                                            $versions =
                                                                isset($req->input_file[0]) &&
                                                                is_array($req->input_file[0])
                                                                    ? $req->input_file
                                                                    : [
                                                                        [
                                                                            'version' => 1,
                                                                            'files' => $req->input_file,
                                                                            'uploaded_at' => $req->date_input?->format(
                                                                                'Y-m-d H:i:s',
                                                                            ),
                                                                        ],
                                                                    ];
                                                        @endphp

                                                        @foreach (array_reverse($versions) as $verRow)
                                                            @if (isset($verRow['files']) && count($verRow['files']) > 0)
                                                                <div class="mb-3">
                                                                    <div
                                                                        class="text-[10px] font-bold text-slate-500 mb-1 uppercase">
                                                                        Versi {{ $verRow['version'] ?? 1 }} (<span
                                                                            class="font-normal">{{ \Carbon\Carbon::parse($verRow['uploaded_at'] ?? now())->format('d M Y H:i') }}</span>)
                                                                    </div>
                                                                    <div class="space-y-1">
                                                                        @foreach ($verRow['files'] as $path)
                                                                            <div
                                                                                class="flex items-center justify-between bg-white p-2 rounded border border-slate-100">
                                                                                <span
                                                                                    class="text-slate-500 truncate max-w-37.5"
                                                                                    title="{{ basename($path) }}">{{ basename($path) }}</span>
                                                                                <a href="{{ asset('storage/' . $path) }}"
                                                                                    target="_blank"
                                                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition font-medium">
                                                                                    <svg class="w-3 h-3"
                                                                                        fill="none"
                                                                                        stroke="currentColor"
                                                                                        stroke-width="2"
                                                                                        viewBox="0 0 24 24">
                                                                                        <path stroke-linecap="round"
                                                                                            stroke-linejoin="round"
                                                                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                                    </svg>
                                                                                    Unduh
                                                                                </a>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>

                                                    {{-- Allow adding more files --}}
                                                    <div x-data="{ uploading: false }"
                                                        class="mt-3 pt-3 border-t border-slate-200">
                                                        <input type="file" wire:model="uploadFiles" multiple
                                                            accept=".jpg,.jpeg,.png,.webp"
                                                            x-on:change="
                                                            const files = Array.from($event.target.files || []);
                                                            if (!files.length) return;

                                                            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                                                            const maxPerFile = 10 * 1024 * 1024;
                                                            const maxTotal = 50 * 1024 * 1024;
                                                            let totalSize = 0;

                                                            for (const file of files) {
                                                                totalSize += file.size;

                                                                if (!allowed.includes(file.type)) {
                                                                    $wire.set('uploadError', `Format file '${file.name}' tidak didukung. Gunakan JPG, JPEG, PNG, atau WEBP.`);
                                                                    $event
                                                                    return;
                                                                }

                                                                if (file.size > maxPerFile) {
                                                                    $wire.set('uploadError', `Ukuran file '${file.name}' terlalu besar. Maksimal 10MB per file.`);
                                                                    $event
                                                                    return;
                                                                }
                                                            }

                                                            if (totalSize > maxTotal) {
                                                                $wire.set('uploadError', 'Total ukuran file melebihi 50MB. Kurangi jumlah file lalu coba lagi.');
                                                                $event
                                                            }
                                                        "
                                                            x-on:livewire-upload-start="uploading = true; $wire.set('uploadError', null)"
                                                            x-on:livewire-upload-finish="uploading = false; $wire.uploadFilesForRow({{ $req->id }})"
                                                            x-on:livewire-upload-error="uploading = false; $wire.set('uploadError', 'Upload gagal. Pastikan format JPG/JPEG/PNG/WEBP dan ukuran maksimal 10MB per file.')"
                                                            class="hidden" id="file-add-{{ $req->id }}">
                                                        <label for="file-add-{{ $req->id }}"
                                                            class="cursor-pointer inline-flex flex-wrap justify-center items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition font-medium w-full">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                stroke-width="2" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                                            </svg>
                                                            Tambah File Lagi
                                                        </label>
                                                        <div x-show="uploading"
                                                            class="text-amber-600 mt-1 text-center">
                                                            Uploading...</div>
                                                    </div>
                                                @else
                                                    <div x-data="{ uploading: false }" class="space-y-2">
                                                        <div class="flex flex-col items-center gap-2">
                                                            <svg class="w-5 h-5 text-red-400" fill="none"
                                                                stroke="currentColor" stroke-width="2"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                            </svg>
                                                            <span class="text-slate-600 text-center">Belum ada file
                                                                yang
                                                                diupload</span>
                                                        </div>
                                                        <input type="file" wire:model="uploadFiles" multiple
                                                            accept=".jpg,.jpeg,.png,.webp"
                                                            x-on:change="
                                                            const files = Array.from($event.target.files || []);
                                                            if (!files.length) return;

                                                            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                                                            const maxPerFile = 10 * 1024 * 1024;
                                                            const maxTotal = 50 * 1024 * 1024;
                                                            let totalSize = 0;

                                                            for (const file of files) {
                                                                totalSize += file.size;

                                                                if (!allowed.includes(file.type)) {
                                                                    $wire.set('uploadError', `Format file '${file.name}' tidak didukung. Gunakan JPG, JPEG, PNG, atau WEBP.`);
                                                                    $event
                                                                    return;
                                                                }

                                                                if (file.size > maxPerFile) {
                                                                    $wire.set('uploadError', `Ukuran file '${file.name}' terlalu besar. Maksimal 10MB per file.`);
                                                                    $event
                                                                    return;
                                                                }
                                                            }

                                                            if (totalSize > maxTotal) {
                                                                $wire.set('uploadError', 'Total ukuran file melebihi 50MB. Kurangi jumlah file lalu coba lagi.');
                                                                $event
                                                            }
                                                        "
                                                            x-on:livewire-upload-start="uploading = true; $wire.set('uploadError', null)"
                                                            x-on:livewire-upload-finish="uploading = false; $wire.uploadFilesForRow({{ $req->id }})"
                                                            x-on:livewire-upload-error="uploading = false; $wire.set('uploadError', 'Upload gagal. Pastikan format JPG/JPEG/PNG/WEBP dan ukuran maksimal 10MB per file.')"
                                                            class="hidden" id="file-{{ $req->id }}">
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
                                            <select
                                                wire:change="updateStatus({{ $req->id }}, $event.target.value)"
                                                class="text-xs rounded-full px-2 py-1 border cursor-pointer badge-{{ str_replace('_', '-', $req->status) }}">
                                                @foreach ($statuses as $key => $label)
                                                    <option value="{{ $key }}"
                                                        {{ $req->status === $key ? 'selected' : '' }}>
                                                        {{ $label }}
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
                                                <div class="text-slate-400">{{ $req->date_input->format('H:i') }}
                                                </div>
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
                                            <div class="flex flex-col gap-1 w-full mt-2">
                                                @if ($req->status === 'on_review')
                                                    <button wire:click="requestRevision({{ $req->id }})"
                                                        title="Kirim notifikasi revisi ke Auditi (Wajib isi Komentar Auditor)"
                                                        class="w-full text-center bg-amber-100 hover:bg-amber-200 text-amber-700 text-[10px] font-bold py-1 rounded">Minta
                                                        Revisi</button>
                                                @endif

                                                <div
                                                    class="flex items-center justify-between w-full mt-1 border-t border-slate-100 pt-1">
                                                    <button wire:click="editRow({{ $req->id }})"
                                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button>
                                                    <button wire:click="deleteRow({{ $req->id }})"
                                                        wire:confirm="Yakin hapus Data Request '{{ $req->section ?: 'No.' . $req->no }}{{ $req->account_process ? ' — ' . Str::limit($req->account_process, 25) : '' }}'?"
                                                        class="text-red-600 hover:text-red-700 text-xs font-medium">Hapus</button>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            @if ($requests instanceof \Illuminate\Pagination\AbstractPaginator && $requests->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $requests->links() }}
                </div>
            @endif
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
                        <div>
                            <label class="form-label">PIC (Auditi)</label>
                            <select wire:model="pic_id" class="form-input text-sm">
                                <option value="">Semua PIC</option>
                                @foreach ($availablePics ?? [] as $apic)
                                    <option value="{{ $apic->id }}">{{ $apic->name }} ({{ $apic->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('pic_id')
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
