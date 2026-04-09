<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ClientManager extends Component
{
    public string $nama_client = '';
    public string $nama_pic = '';
    public string $no_contact = '';
    public string $alamat = '';
    public string $tahun_audit = '';
    public bool $showModal = false;
    public bool $showDeleteConfirm = false;
    public ?int $editId = null;

    protected function rules()
    {
        return [
            'nama_client' => 'required|min:3|max:255',
            'nama_pic' => 'required|min:3|max:255',
            'no_contact' => 'required|min:8|max:20',
            'alamat' => 'nullable|max:500',
            'tahun_audit' => 'required|date',
        ];
    }

    public function openModal()
    {
        $kap = $this->getKapProfile();
        if (!$kap) {
            return redirect()->route('kap-profile');
        }

        $this->reset(['nama_client', 'nama_pic', 'no_contact', 'alamat', 'tahun_audit', 'editId']);
        $this->tahun_audit = date('Y') . '-12-31';
        $this->showModal = true;
    }

    public function editClient(int $id)
    {
        $kap = $this->getKapProfile();
        if (!$kap) {
            return redirect()->route('kap-profile');
        }

        $client = $kap->clients()->findOrFail($id);
        $this->editId = $client->id;
        $this->nama_client = $client->nama_client;
        $this->nama_pic = $client->nama_pic;
        $this->no_contact = $client->no_contact;
        $this->alamat = $client->alamat ?? '';
        $this->tahun_audit = $client->tahun_audit?->format('Y-m-d') ?? '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();
        $kap = $this->getKapProfile();
        if (!$kap) {
            return redirect()->route('kap-profile');
        }

        if ($this->editId) {
            $client = $kap->clients()->findOrFail($this->editId);
            $client->update([
                'nama_client' => $this->nama_client,
                'nama_pic' => $this->nama_pic,
                'no_contact' => $this->no_contact,
                'alamat' => $this->alamat,
                'tahun_audit' => $this->tahun_audit,
            ]);
            session()->flash('success', 'Klien berhasil diperbarui!');
        } else {
            $kap->clients()->create([
                'nama_client' => $this->nama_client,
                'nama_pic' => $this->nama_pic,
                'no_contact' => $this->no_contact,
                'alamat' => $this->alamat,
                'tahun_audit' => $this->tahun_audit,
            ]);
            session()->flash('success', 'Klien berhasil ditambahkan!');
        }

        $this->showModal = false;
    }

    public function deleteClient(int $id)
    {
        $kap = $this->getKapProfile();
        if (!$kap) {
            return redirect()->route('kap-profile');
        }

        $kap->clients()->findOrFail($id)->delete();
        session()->flash('success', 'Klien berhasil dihapus!');
    }

    public function deleteAll()
    {
        $kap = $this->getKapProfile();
        if (!$kap) {
            return redirect()->route('kap-profile');
        }

        $kap->clients()->delete();
        $this->showDeleteConfirm = false;
        session()->flash('success', 'Semua klien berhasil dihapus!');
    }

    private function getKapProfile(): ?KapProfile
    {
        /** @var User|null $user */
        $user = Auth::user();
        $kap = $user?->kapProfile;
        if (!$kap) {
            session()->flash('error', 'Silakan isi Profil KAP terlebih dahulu.');
            return null;
        }

        return $kap;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $kap = $user?->kapProfile;
        $clients = $kap ? $kap->clients()->latest()->get() : collect();

        return view('livewire.client-manager', [
            'clients' => $clients,
            'title' => 'Manajemen Klien',
        ]);
    }
}
