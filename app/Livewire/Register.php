<?php

namespace App\Livewire;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'auditi';
    public string $invitation_token = '';

    public function mount()
    {
        if (request()->has('token')) {
            $this->invitation_token = (string) request()->input('token');
            $invitation = Invitation::where('token', $this->invitation_token)
                ->whereNull('accepted_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();
            if ($invitation) {
                $this->email = $invitation->email;
                $this->role = $invitation->role;
            }
        }
    }

    public function register()
    {
        $invitation = null;
        if ($this->invitation_token) {
            $invitation = Invitation::where('token', $this->invitation_token)
                ->whereNull('accepted_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if (!$invitation) {
                $this->addError('invitation_token', 'Token undangan tidak valid atau sudah kadaluarsa.');
                return;
            }

            if (strcasecmp($invitation->email, $this->email) !== 0) {
                $this->addError('email', 'Email harus sama dengan email pada undangan.');
                return;
            }

            if (User::where('email', $this->email)->exists()) {
                return redirect()->route('login')
                    ->with('success', 'Email ini sudah terdaftar. Silakan login untuk mengaktifkan undangan Anda.');
            }
        }

        $this->validate([
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => ['required', Rule::in(['auditor', 'auditi'])],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $invitation?->role ?? 'auditi',
            'kap_id' => $invitation?->kap_id,
            'invitation_token' => $this->invitation_token ?: null,
        ]);

        if ($invitation) {
            Invitation::acceptForUser($user, $invitation);
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.register');
    }
}
