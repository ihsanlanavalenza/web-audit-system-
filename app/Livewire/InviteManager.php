<?php

namespace App\Livewire;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class InviteManager extends Component
{
    public string $email = '';
    public string $role = 'auditi';
    public ?int $client_id = null;
    public bool $showModal = false;

    public function openModal()
    {
        $this->reset(['email', 'role', 'client_id']);
        $this->role = 'auditi';
        $this->showModal = true;
    }

    public function sendInvite()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $kap = $user?->kapProfile;
        if (!$kap) {
            session()->flash('error', 'Silakan isi Profil KAP terlebih dahulu.');
            return redirect()->route('kap-profile');
        }

        $this->validate([
            'email' => 'required|email',
            'role' => 'required|in:auditor,auditi',
            'client_id' => [
                'nullable',
                'required_if:role,auditi',
                Rule::exists('clients', 'id')->where(function ($q) use ($kap) {
                    $q->where('kap_id', $kap->id);
                }),
            ],
        ]);

        $normalizedEmail = strtolower(trim($this->email));

        $duplicatePendingInvite = Invitation::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->where('kap_id', $kap->id)
            ->where('role', $this->role)
            ->when(
                $this->role === 'auditi',
                fn($q) => $q->where('client_id', $this->client_id),
                fn($q) => $q->whereNull('client_id')
            )
            ->pending()
            ->active()
            ->exists();

        if ($duplicatePendingInvite) {
            session()->flash('error', 'Undangan aktif untuk email dan scope yang sama sudah ada.');
            return;
        }

        $invitation = Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => $this->role === 'auditi' ? $this->client_id : null,
            'email' => $normalizedEmail,
            'role' => $this->role,
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        $this->showModal = false;
        session()->flash('success', 'Undangan berhasil dibuat dan email telah dikirim.');
    }

    public function deleteInvitation(int $id)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $kap = $user?->kapProfile;
        if (!$kap) {
            abort(403);
        }

        $kap->invitations()->findOrFail($id)->delete();
        session()->flash('success', 'Undangan berhasil dihapus!');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $kap = $user?->kapProfile;
        $invitations = $kap ? $kap->invitations()->with('client')->latest()->get() : collect();
        $clients = $kap ? $kap->clients()->get() : collect();

        return view('livewire.invite-manager', compact('invitations', 'clients'));
    }
}
