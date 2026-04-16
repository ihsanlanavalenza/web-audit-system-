<?php

namespace App\Livewire;

use App\Models\DataRequest;
use App\Models\Client;
use App\Models\User;
use App\Models\Invitation;
use App\Notifications\DataRequestFileUploadedNotification;
use App\Notifications\DataRequestRevisionNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class DataRequestTable extends Component
{
    use WithFileUploads;

    public ?int $clientId = null;
    public bool $showModal = false;
    public ?int $editId = null;

    // PIC (User Audit) List
    public $availablePics = [];
    public ?int $pic_id = null;

    // Form fields
    public int $no = 0;
    public string $section_code = '';
    public string $section_no_input = '';
    public string $account_process = '';
    public string $description = '';
    public ?string $request_date = null;
    public ?string $expected_received = null;
    public string $status = 'pending';
    public string $comment_client = '';
    public string $comment_auditor = '';

    // File & comments
    public $uploadFiles = [];
    public ?string $uploadError = null;
    public ?int $commentRowId = null;
    public string $newComment = '';

    // File detail expansion
    public ?int $expandedFileRow = null;

    // Header filters (Excel-like)
    public string $filterSectionNo = '';
    public string $filterAccountProcess = '';
    public array $filterStatuses = [];
    public ?string $filterRequestDateFrom = null;
    public ?string $filterRequestDateTo = null;
    public ?string $filterExpectedReceivedFrom = null;
    public ?string $filterExpectedReceivedTo = null;
    public ?string $filterDateInputFrom = null;
    public ?string $filterDateInputTo = null;
    public string $filterInputFileState = '';

    private const MAX_UPLOAD_FILE_KB = 10240;
    private const MAX_UPLOAD_FILES_PER_REQUEST = 10;
    private const MAX_UPLOAD_TOTAL_BYTES = 52428800; // 50 MB

    public function mount(?int $clientId = null)
    {
        $this->clientId = $clientId;

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // If auditi, find their client_id from invitation
        if ($user->isAuditi() && !$this->clientId) {
            $invitation = Invitation::where('email', $user->email)
                ->where('role', 'auditi')
                ->whereNotNull('accepted_at')
                ->where(function (Builder $q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest('accepted_at')
                ->first();
            $this->clientId = $invitation?->client_id;
        }

        $this->authorizeClientAccess();
    }

    private function loadPics()
    {
        if ($this->clientId) {
            $invitations = Invitation::where('client_id', $this->clientId)
                ->where('role', 'auditi')
                ->whereNotNull('accepted_at')
                ->where(function (Builder $q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('email');

            $this->availablePics = User::where('role', 'auditi')
                ->whereIn('email', $invitations)
                ->get(['id', 'name', 'email']);

            return;
        }

        $this->availablePics = [];
    }

    public function updatedClientId(): void
    {
        $this->authorizeClientAccess();
        $this->uploadError = null;
        $this->resetFilters();
        $this->loadPics();
    }

    public function resetFilters(): void
    {
        $this->filterSectionNo = '';
        $this->filterAccountProcess = '';
        $this->filterStatuses = [];
        $this->filterRequestDateFrom = null;
        $this->filterRequestDateTo = null;
        $this->filterExpectedReceivedFrom = null;
        $this->filterExpectedReceivedTo = null;
        $this->filterDateInputFrom = null;
        $this->filterDateInputTo = null;
        $this->filterInputFileState = '';
    }

    public function openAddModal()
    {
        $this->authorizeClientAccess();
        $this->loadPics();

        $this->reset(['no', 'pic_id', 'section_code', 'section_no_input', 'account_process', 'description', 'request_date', 'expected_received', 'status', 'comment_client', 'comment_auditor', 'editId']);
        $this->status = 'pending';

        // Auto-increment no
        $lastNo = $this->getQuery()->max('no');
        $this->no = ($lastNo ?? 0) + 1;
        $this->request_date = date('Y-m-d');

        $this->showModal = true;
    }

    public function editRow(int $id)
    {
        $this->authorizeClientAccess();
        $this->loadPics();

        $row = $this->getQuery()->findOrFail($id);
        $this->editId = $row->id;
        $this->pic_id = $row->pic_id;
        $this->no = $row->no;
        $this->section_code = $row->section_code ?? '';
        $this->section_no_input = $row->section_no !== null ? (string) $row->section_no : '';
        $this->account_process = $row->account_process ?? '';
        $this->description = $row->description ?? '';
        $this->request_date = $row->request_date ? date('Y-m-d', strtotime((string) $row->request_date)) : null;
        $this->expected_received = $row->expected_received ? date('Y-m-d', strtotime((string) $row->expected_received)) : null;
        $this->status = $row->status;
        $this->comment_client = $row->comment_client ?? '';
        $this->comment_auditor = $row->comment_auditor ?? '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->authorizeClientAccess();

        $this->validate([
            'no' => 'required|integer|min:1',
            'pic_id' => 'nullable|exists:users,id',
            'section_code' => 'nullable|max:10',
            'section_no_input' => 'nullable|string|max:20',
            'account_process' => 'nullable|max:255',
            'description' => 'nullable',
            'request_date' => 'nullable|date',
            'expected_received' => 'nullable|date',
            'status' => 'required|in:partially_received,on_review,received,not_applicable,pending',
        ]);

        $kap = $this->getKapId();

        $data = [
            'client_id' => $this->clientId,
            'kap_id' => $kap,
            'pic_id' => $this->pic_id ?: null,
            'no' => $this->no,
            'section_code' => $this->section_code ?: null,
            'section_no' => $this->section_no_input !== '' ? $this->section_no_input : null,
            'account_process' => $this->account_process,
            'description' => $this->description,
            'request_date' => $this->request_date,
            'expected_received' => $this->expected_received,
            'status' => $this->status,
            'comment_client' => $this->comment_client,
            'comment_auditor' => $this->comment_auditor,
        ];

        if ($this->editId) {
            $this->getQuery()->findOrFail($this->editId)->update($data);
            session()->flash('success', 'Data Request berhasil diperbarui!');
        } else {
            DataRequest::create($data);
            session()->flash('success', 'Data Request berhasil ditambahkan!');
        }

        $this->showModal = false;
    }

    public function deleteRow(int $id)
    {
        $this->authorizeClientAccess();
        $this->uploadError = null;
        $this->getQuery()->findOrFail($id)->delete();
        session()->flash('success', 'Data Request berhasil dihapus!');
    }

    public function toggleFileDetail(int $id)
    {
        $this->expandedFileRow = $this->expandedFileRow === $id ? null : $id;
    }

    public function uploadFilesForRow(int $id)
    {
        $this->authorizeClientAccess();
        $this->uploadError = null;

        $this->validate([
            'uploadFiles' => 'required|array|min:1|max:' . self::MAX_UPLOAD_FILES_PER_REQUEST,
            'uploadFiles.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_UPLOAD_FILE_KB,
        ], [
            'uploadFiles.required' => 'Pilih minimal satu file untuk diunggah.',
            'uploadFiles.array' => 'Format upload file tidak valid.',
            'uploadFiles.min' => 'Pilih minimal satu file untuk diunggah.',
            'uploadFiles.max' => 'Maksimal ' . self::MAX_UPLOAD_FILES_PER_REQUEST . ' file per upload.',
            'uploadFiles.*.required' => 'Ada file yang tidak valid. Pilih ulang file Anda.',
            'uploadFiles.*.file' => 'File yang dipilih tidak valid.',
            'uploadFiles.*.mimes' => 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau WEBP.',
            'uploadFiles.*.max' => 'Ukuran file terlalu besar. Maksimal 10MB per file.',
        ]);

        $files = is_array($this->uploadFiles) ? $this->uploadFiles : [$this->uploadFiles];
        $files = array_values(array_filter($files));

        if (count($files) === 0) {
            $this->uploadError = 'Tidak ada file yang dipilih untuk diunggah.';
            session()->flash('error', 'Tidak ada file yang dipilih untuk diunggah.');
            return;
        }

        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += (int) $file->getSize();
        }

        if ($totalSize > self::MAX_UPLOAD_TOTAL_BYTES) {
            $this->uploadError = 'Total ukuran file melebihi 50MB. Kurangi jumlah file lalu coba lagi.';
            session()->flash('error', $this->uploadError);
            return;
        }

        try {
            $row = $this->getQuery()->findOrFail($id);

            if (!$this->clientId || (int) $row->client_id !== (int) $this->clientId) {
                abort(403);
            }

            $currentInputFiles = $row->input_file ?? [];
            if (!is_array($currentInputFiles)) {
                $currentInputFiles = [];
            }

            // Format detect: if array elements aren't associative with "version", convert them first.
            $needsMigration = false;
            if (count($currentInputFiles) > 0 && !isset($currentInputFiles[0]['version'])) {
                $needsMigration = true;
            }

            if ($needsMigration) {
                $oldFiles = $currentInputFiles;
                $currentInputFiles = [
                    [
                        'version' => 1,
                        'files' => $oldFiles,
                        'uploaded_at' => $row->date_input ? $row->date_input->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                        'uploaded_by' => 'Auto-migrated', // unknown (old logic)
                    ]
                ];
            }

            $nextVersionNumber = count($currentInputFiles) + 1;

            $newPaths = [];
            foreach ($files as $file) {
                $newPaths[] = $file->store("uploads/{$this->clientId}", 'public');
            }

            $currentInputFiles[] = [
                'version' => $nextVersionNumber,
                'files' => $newPaths,
                'uploaded_at' => now()->format('Y-m-d H:i:s'),
                'uploaded_by' => Auth::user()?->name ?? 'Unknown',
            ];

            $row->update([
                'input_file' => $currentInputFiles, // always an array of versions now
                'status' => DataRequest::STATUS_ON_REVIEW,
                'date_input' => now(), // track latest action time
            ]);

            $row->refresh();

            // Notify all auditors that currently have access to this client.
            $auditors = User::query()
                ->where('role', 'auditor')
                ->whereHas('clients', function (Builder $q) use ($row) {
                    $q->where('clients.id', $row->client_id);
                })
                ->get();

            if ($auditors->isNotEmpty()) {
                Notification::send($auditors, new DataRequestFileUploadedNotification($row));
            }

            $this->expandedFileRow = null;
            session()->flash('success', count($newPaths) . ' file berhasil diupload sebagai versi ' . $nextVersionNumber . '! Status berubah ke On Review.');
        } catch (ValidationException $e) {
            $message = (string) ($e->validator->errors()->first() ?? 'Validasi upload gagal.');
            $this->uploadError = $message;
            session()->flash('error', $message);
        } catch (\Throwable $e) {
            report($e);

            $this->uploadError = 'Upload gagal diproses di server. Coba lagi atau hubungi admin.';
            session()->flash('error', $this->uploadError);
        } finally {
            $this->uploadFiles = [];
        }
    }

    public function updateStatus(int $id, string $status)
    {
        $this->authorizeClientAccess();
        $row = $this->getQuery()->findOrFail($id);
        $row->update(['status' => $status]);
        session()->flash('success', 'Status berhasil diperbarui!');
    }

    public function requestRevision(int $id)
    {
        $this->authorizeClientAccess();
        $row = $this->getQuery()->findOrFail($id);

        if (empty(trim($row->comment_auditor))) {
            session()->flash('error', 'Komentar Auditor wajib diisi sebelum meminta revisi!');
            return;
        }

        $row->update([
            'status' => DataRequest::STATUS_PARTIALLY_RECEIVED,
        ]);

        // Trigger Notification to PIC or to all related auditis
        $auditis = collect();
        if ($row->pic_id) {
            $user = User::find($row->pic_id);
            if ($user && $user->isAuditi()) {
                $auditis->push($user);
            }
        } else {
            $invitationsEmails = Invitation::where('client_id', $this->clientId)
                ->where('role', 'auditi')
                ->whereNotNull('accepted_at')
                ->where(function (Builder $q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('email');

            /** @var \Illuminate\Database\Eloquent\Collection<int, User> $auditis */
            $auditis = User::where('role', 'auditi')
                ->whereIn('email', $invitationsEmails)
                ->get();
        }

        Notification::send($auditis, new DataRequestRevisionNotification($row));

        session()->flash('success', 'Permintaan revisi dikirim. Status berubah menjadi Partially Received.');
    }

    public function saveComment(int $id)
    {
        $this->authorizeClientAccess();
        $row = $this->getQuery()->findOrFail($id);
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $field = $user->isAuditor() ? 'comment_auditor' : 'comment_client';
        $row->update([$field => $this->newComment]);
        $this->commentRowId = null;
        $this->newComment = '';
    }

    public function openComment(int $id)
    {
        $this->authorizeClientAccess();
        $row = $this->getQuery()->findOrFail($id);
        $this->commentRowId = $id;
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $this->newComment = $user->isAuditor()
            ? ($row->comment_auditor ?? '')
            : ($row->comment_client ?? '');
    }

    private function getQuery()
    {
        $query = DataRequest::query()->where('client_id', $this->clientId);

        $sectionNo = trim($this->filterSectionNo);
        if ($sectionNo !== '') {
            $query->where(function (Builder $q) use ($sectionNo) {
                $q->where('section_code', 'like', '%' . $sectionNo . '%')
                    ->orWhere('section_no', 'like', '%' . $sectionNo . '%');
            });
        }

        $accountProcess = trim($this->filterAccountProcess);
        if ($accountProcess !== '') {
            $query->where('account_process', 'like', '%' . $accountProcess . '%');
        }

        $allowedStatuses = array_keys(DataRequest::STATUSES);
        $statusFilters = array_values(array_intersect($this->filterStatuses, $allowedStatuses));
        if (!empty($statusFilters)) {
            $query->whereIn('status', $statusFilters);
        }

        if ($this->filterRequestDateFrom) {
            $query->whereDate('request_date', '>=', $this->filterRequestDateFrom);
        }

        if ($this->filterRequestDateTo) {
            $query->whereDate('request_date', '<=', $this->filterRequestDateTo);
        }

        if ($this->filterExpectedReceivedFrom) {
            $query->whereDate('expected_received', '>=', $this->filterExpectedReceivedFrom);
        }

        if ($this->filterExpectedReceivedTo) {
            $query->whereDate('expected_received', '<=', $this->filterExpectedReceivedTo);
        }

        if ($this->filterDateInputFrom) {
            $query->whereDate('date_input', '>=', $this->filterDateInputFrom);
        }

        if ($this->filterDateInputTo) {
            $query->whereDate('date_input', '<=', $this->filterDateInputTo);
        }

        if ($this->filterInputFileState === 'uploaded') {
            $query->whereNotNull('input_file')
                ->where('input_file', '!=', '[]')
                ->where('input_file', '!=', '')
                ->where('input_file', '!=', 'null');
        } elseif ($this->filterInputFileState === 'not_uploaded') {
            $query->where(function (Builder $q) {
                $q->whereNull('input_file')
                    ->orWhere('input_file', '[]')
                    ->orWhere('input_file', '')
                    ->orWhere('input_file', 'null');
            });
        }

        return $query;
    }

    private function authorizeClientAccess(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$this->clientId) {
            return;
        }

        if ($user->isAuditor()) {
            if (!$user->hasClientAccess($this->clientId)) {
                abort(403);
            }

            return;
        }

        if ($user->isAuditi()) {
            $allowed = Invitation::where('email', $user->email)
                ->where('role', 'auditi')
                ->whereNotNull('accepted_at')
                ->where(function (Builder $q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->where('client_id', $this->clientId)
                ->exists();

            if (!$allowed) {
                abort(403);
            }
        }
    }

    private function getKapId()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if ($user->isAuditor()) {
            if ($this->clientId) {
                return Client::where('id', $this->clientId)->value('kap_id');
            }

            return $user->resolveKapId();
        }
        $invitation = Invitation::where('email', $user->email)
            ->where('role', 'auditi')
            ->whereNotNull('accepted_at')
            ->latest('accepted_at')
            ->first();
        return $invitation?->kap_id;
    }

    private function hasActiveFilters(): bool
    {
        return trim($this->filterSectionNo) !== ''
            || trim($this->filterAccountProcess) !== ''
            || !empty($this->filterStatuses)
            || !empty($this->filterRequestDateFrom)
            || !empty($this->filterRequestDateTo)
            || !empty($this->filterExpectedReceivedFrom)
            || !empty($this->filterExpectedReceivedTo)
            || !empty($this->filterDateInputFrom)
            || !empty($this->filterDateInputTo)
            || trim($this->filterInputFileState) !== '';
    }

    public function exportCsv()
    {
        $this->authorizeClientAccess();
        $requests = $this->getQuery()
            ->orderBy('section_code')
            ->orderBy('section_no')
            ->orderBy('no')
            ->get();

        $filename = 'data-requests-client-' . $this->clientId . '-' . date('YmdHis') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        /* @var \Illuminate\Support\Collection $requests */
        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No', 'Section', 'Account/Process', 'Description', 'Request Date', 'Expected Received', 'Status', 'Date Input', 'Comment Client', 'Comment Auditor']);

            foreach ($requests as $row) {
                fputcsv($file, [
                    $row->no,
                    $row->section,
                    $row->account_process,
                    strip_tags($row->description),
                    $row->request_date ? $row->request_date->format('Y-m-d') : '',
                    $row->expected_received ? $row->expected_received->format('Y-m-d') : '',
                    $row->status,
                    $row->date_input ? $row->date_input->format('Y-m-d H:i') : '',
                    $row->comment_client,
                    $row->comment_auditor
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $this->authorizeClientAccess();

        $hasBaseRequests = false;
        $requests = $this->clientId
            ? $this->getQuery()
            ->orderBy('section_code')
            ->orderBy('section_no')
            ->orderBy('no')
            ->get()
            : collect();

        if ($this->clientId) {
            $hasBaseRequests = DataRequest::query()
                ->where('client_id', $this->clientId)
                ->exists();
        }

        $clients = [];
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->isAuditor()) {
            $clients = $user->clients()->orderBy('nama_client')->get();
        }

        return view('livewire.data-request-table', [
            'requests' => $requests,
            'clients' => $clients,
            'statuses' => DataRequest::STATUSES,
            'hasBaseRequests' => $hasBaseRequests,
            'isFiltering' => $this->hasActiveFilters(),
        ]);
    }
}
