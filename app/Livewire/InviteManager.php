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
    public ?string $message = null;
    public ?string $messageType = null; // 'success' or 'error'

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
            $this->message = 'Silakan isi Profil KAP terlebih dahulu.';
            $this->messageType = 'error';
            return;
        }

        $this->validate([
            'email' => 'required|email',
            'role' => 'required|in:auditor,auditi',
            'client_id' => [
                'required',
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
            ->where('client_id', $this->client_id)
            ->pending()
            ->active()
            ->exists();

        if ($duplicatePendingInvite) {
            $this->message = 'Undangan aktif untuk email dan scope yang sama sudah ada.';
            $this->messageType = 'error';
            return;
        }

        try {
            $invitation = Invitation::create([
                'kap_id' => $kap->id,
                'client_id' => $this->client_id,
                'email' => $normalizedEmail,
                'role' => $this->role,
                'token' => Invitation::generateToken(),
                'expires_at' => now()->addDays(7),
            ]);

            try {
                Mail::to($invitation->email)->send(new InvitationMail($invitation));
                $this->message = 'Undangan berhasil dibuat dan email telah dikirim.';
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim email undangan: ' . $e->getMessage());
                $this->message = 'Undangan berhasil dibuat, tapi email gagal dikirim. Anda dapat membagikan link secara manual.';
            }

            $this->messageType = 'success';
            $this->showModal = false;
            $this->reset(['email', 'role', 'client_id']);
        } catch (\Exception $e) {
            $this->message = 'Gagal membuat undangan: ' . $e->getMessage();
            $this->messageType = 'error';
        }
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
        $this->message = 'Undangan berhasil dihapus!';
        $this->messageType = 'success';
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
