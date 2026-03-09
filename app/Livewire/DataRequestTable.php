<?php

namespace App\Livewire;

use App\Models\DataRequest;
use App\Models\Client;
use Livewire\Component;
use Livewire\WithFileUploads;

class DataRequestTable extends Component
{
    use WithFileUploads;

    public ?int $clientId = null;
    public bool $showModal = false;
    public ?int $editId = null;

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
    public $uploadFile = null;
    public ?int $commentRowId = null;
    public string $newComment = '';

    // File detail expansion
    public ?int $expandedFileRow = null;

    public function mount(?int $clientId = null)
    {
        $this->clientId = $clientId;

        // If auditi, find their client_id from invitation
        if (auth()->user()->isAuditi() && !$this->clientId) {
            $invitation = \App\Models\Invitation::where('email', auth()->user()->email)
                ->whereNotNull('accepted_at')
                ->first();
            $this->clientId = $invitation?->client_id;
        }
    }

    public function openAddModal()
    {
        $this->reset(['no', 'section_code', 'section_no_input', 'account_process', 'description', 'request_date', 'expected_received', 'status', 'comment_client', 'comment_auditor', 'editId']);
        $this->status = 'pending';

        // Auto-increment no
        $lastNo = DataRequest::where('client_id', $this->clientId)->max('no');
        $this->no = ($lastNo ?? 0) + 1;
        $this->request_date = date('Y-m-d');

        $this->showModal = true;
    }

    public function editRow(int $id)
    {
        $row = $this->getQuery()->findOrFail($id);
        $this->editId = $row->id;
        $this->no = $row->no;
        $this->section_code = $row->section_code ?? '';
        $this->section_no_input = $row->section_no !== null ? (string) $row->section_no : '';
        $this->account_process = $row->account_process ?? '';
        $this->description = $row->description ?? '';
        $this->request_date = $row->request_date?->format('Y-m-d');
        $this->expected_received = $row->expected_received?->format('Y-m-d');
        $this->status = $row->status;
        $this->comment_client = $row->comment_client ?? '';
        $this->comment_auditor = $row->comment_auditor ?? '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'no' => 'required|integer|min:1',
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
            DataRequest::findOrFail($this->editId)->update($data);
            session()->flash('success', 'Data Request berhasil diperbarui!');
        } else {
            DataRequest::create($data);
            session()->flash('success', 'Data Request berhasil ditambahkan!');
        }

        $this->showModal = false;
    }

    public function deleteRow(int $id)
    {
        $this->getQuery()->findOrFail($id)->delete();
        session()->flash('success', 'Data Request berhasil dihapus!');
    }

    public function toggleFileDetail(int $id)
    {
        $this->expandedFileRow = $this->expandedFileRow === $id ? null : $id;
    }

    public function uploadFileForRow(int $id)
    {
        $this->validate(['uploadFile' => 'required|file|max:10240']);

        $row = $this->getQuery()->findOrFail($id);
        $path = $this->uploadFile->store("uploads/{$this->clientId}", 'public');

        $row->update([
            'input_file' => $path,
            'status' => DataRequest::STATUS_ON_REVIEW,
            'date_input' => now(), // Auto-timestamp saat upload
        ]);

        $this->uploadFile = null;
        $this->expandedFileRow = null;
        session()->flash('success', 'File berhasil diupload! Status: On Review.');
    }

    public function updateStatus(int $id, string $status)
    {
        $row = $this->getQuery()->findOrFail($id);
        $row->update(['status' => $status]);
        session()->flash('success', 'Status berhasil diperbarui!');
    }

    public function saveComment(int $id)
    {
        $row = $this->getQuery()->findOrFail($id);
        $field = auth()->user()->isAuditor() ? 'comment_auditor' : 'comment_client';
        $row->update([$field => $this->newComment]);
        $this->commentRowId = null;
        $this->newComment = '';
    }

    public function openComment(int $id)
    {
        $row = $this->getQuery()->findOrFail($id);
        $this->commentRowId = $id;
        $this->newComment = auth()->user()->isAuditor()
            ? ($row->comment_auditor ?? '')
            : ($row->comment_client ?? '');
    }

    private function getQuery()
    {
        return DataRequest::where('client_id', $this->clientId);
    }

    private function getKapId()
    {
        if (auth()->user()->isAuditor()) {
            return auth()->user()->kapProfile?->id;
        }
        $invitation = \App\Models\Invitation::where('email', auth()->user()->email)
            ->whereNotNull('accepted_at')
            ->first();
        return $invitation?->kap_id;
    }

    public function render()
    {
        $requests = $this->clientId
            ? DataRequest::where('client_id', $this->clientId)
                ->orderBy('section_code')
                ->orderBy('section_no')
                ->orderBy('no')
                ->get()
            : collect();

        $clients = [];
        if (auth()->user()->isAuditor() && auth()->user()->kapProfile) {
            $clients = auth()->user()->kapProfile->clients;
        }

        return view('livewire.data-request-table', [
            'requests' => $requests,
            'clients' => $clients,
            'statuses' => DataRequest::STATUSES,
        ])->layout('layouts.app', ['title' => 'Client Assistance Schedule']);
    }
}
