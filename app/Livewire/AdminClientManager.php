<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\KapProfile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class AdminClientManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;
    public ?int $editId = null;

    public string $nama_client = '';
    public string $nama_pic = '';
    public string $no_contact = '';
    public string $alamat = '';
    public string $tahun_audit = '';
    public ?int $kap_id = null;

    public string $search = '';
    public string $kapFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedKapFilter(): void
    {
        $this->resetPage();
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->tahun_audit = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function editClient(int $id): void
    {
        $client = Client::findOrFail($id);

        $this->editId = $client->id;
        $this->nama_client = $client->nama_client;
        $this->nama_pic = $client->nama_pic;
        $this->no_contact = $client->no_contact;
        $this->alamat = (string) ($client->alamat ?? '');
        $this->tahun_audit = $client->tahun_audit?->format('Y-m-d') ?? '';
        $this->kap_id = $client->kap_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'nama_client' => 'required|min:3|max:255',
            'nama_pic' => 'required|min:3|max:255',
            'no_contact' => 'required|min:8|max:20',
            'alamat' => 'nullable|max:500',
            'tahun_audit' => 'required|date',
            'kap_id' => ['required', Rule::exists('kap_profiles', 'id')],
        ]);

        $payload = [
            'nama_client' => $this->nama_client,
            'nama_pic' => $this->nama_pic,
            'no_contact' => $this->no_contact,
            'alamat' => $this->alamat,
            'tahun_audit' => $this->tahun_audit,
            'kap_id' => $this->kap_id,
        ];

        if ($this->editId) {
            Client::findOrFail($this->editId)->update($payload);
            session()->flash('success', 'Klien berhasil diperbarui.');
        } else {
            Client::create($payload);
            session()->flash('success', 'Klien berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteClient(): void
    {
        if (!$this->deleteId) {
            return;
        }

        Client::findOrFail($this->deleteId)->delete();

        $this->showDeleteConfirm = false;
        $this->deleteId = null;

        session()->flash('success', 'Klien berhasil dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'nama_client', 'nama_pic', 'no_contact', 'alamat', 'tahun_audit', 'kap_id']);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $clients = Client::query()
            ->with('kapProfile:id,nama_kap')
            ->withCount('dataRequests')
            ->when($this->kapFilter !== '', function ($q) {
                $q->where('kap_id', (int) $this->kapFilter);
            })
            ->when($this->search, function ($q) {
                $search = "%{$this->search}%";
                $q->where(function ($inner) use ($search) {
                    $inner->where('nama_client', 'like', $search)
                        ->orWhere('nama_pic', 'like', $search)
                        ->orWhere('no_contact', 'like', $search)
                        ->orWhereHas('kapProfile', function ($kapQuery) use ($search) {
                            $kapQuery->where('nama_kap', 'like', $search);
                        });
                });
            })
            ->latest()
            ->paginate(15);

        $kaps = KapProfile::query()
            ->orderBy('nama_kap')
            ->get(['id', 'nama_kap']);

        return view('livewire.admin-client-manager', [
            'clients' => $clients,
            'kaps' => $kaps,
            'title' => 'Kelola Klien',
        ]);
    }
}
