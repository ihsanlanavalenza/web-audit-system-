<?php

namespace App\Livewire;

use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class AdminKapManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;
    public ?int $editId = null;
    public ?int $currentOwnerId = null;

    public string $nama_kap = '';
    public string $nama_pic = '';
    public string $alamat = '';
    public ?int $user_id = null;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editKap(int $id): void
    {
        $kap = KapProfile::findOrFail($id);

        $this->editId = $kap->id;
        $this->currentOwnerId = $kap->user_id;
        $this->nama_kap = $kap->nama_kap;
        $this->nama_pic = $kap->nama_pic;
        $this->alamat = $kap->alamat;
        $this->user_id = $kap->user_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'nama_kap' => 'required|min:3|max:255',
            'nama_pic' => 'required|min:3|max:255',
            'alamat' => 'required|min:10',
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'auditor')),
            ],
        ]);

        $ownerHasOtherKap = KapProfile::query()
            ->where('user_id', $this->user_id)
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();

        if ($ownerHasOtherKap) {
            $this->addError('user_id', 'Auditor ini sudah memiliki KAP lain.');
            return;
        }

        $payload = [
            'nama_kap' => $this->nama_kap,
            'nama_pic' => $this->nama_pic,
            'alamat' => $this->alamat,
            'user_id' => $this->user_id,
        ];

        if ($this->editId) {
            $kap = KapProfile::findOrFail($this->editId);
            $oldOwnerId = $kap->user_id;

            $kap->update($payload);
            $this->syncOwnerKapId($oldOwnerId, $this->user_id, $kap->id);

            session()->flash('success', 'Profil KAP berhasil diperbarui.');
        } else {
            $kap = KapProfile::create($payload);
            $this->syncOwnerKapId(null, $this->user_id, $kap->id);

            session()->flash('success', 'Profil KAP berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteKap(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $kap = KapProfile::findOrFail($this->deleteId);
        $ownerId = $kap->user_id;

        $kap->delete();

        if ($ownerId && !KapProfile::where('user_id', $ownerId)->exists()) {
            User::whereKey($ownerId)->update(['kap_id' => null]);
        }

        $this->showDeleteConfirm = false;
        $this->deleteId = null;

        session()->flash('success', 'Profil KAP berhasil dihapus.');
    }

    private function syncOwnerKapId(?int $oldOwnerId, ?int $newOwnerId, int $kapId): void
    {
        if ($oldOwnerId && $oldOwnerId !== $newOwnerId && !KapProfile::where('user_id', $oldOwnerId)->where('id', '!=', $kapId)->exists()) {
            User::whereKey($oldOwnerId)->update(['kap_id' => null]);
        }

        if ($newOwnerId) {
            User::whereKey($newOwnerId)->update(['kap_id' => $kapId]);
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editId', 'currentOwnerId', 'nama_kap', 'nama_pic', 'alamat', 'user_id']);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $kaps = KapProfile::query()
            ->with('user:id,name,email')
            ->withCount('clients')
            ->when($this->search, function ($q) {
                $search = "%{$this->search}%";
                $q->where(function ($inner) use ($search) {
                    $inner->where('nama_kap', 'like', $search)
                        ->orWhere('nama_pic', 'like', $search)
                        ->orWhere('alamat', 'like', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->latest()
            ->paginate(15);

        $auditors = User::query()
            ->where('role', 'auditor')
            ->where(function ($q) {
                $q->whereDoesntHave('kapProfile');

                if ($this->currentOwnerId) {
                    $q->orWhere('id', $this->currentOwnerId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.admin-kap-manager', [
            'kaps' => $kaps,
            'auditors' => $auditors,
            'title' => 'Kelola KAP',
        ]);
    }
}
