<?php

namespace App\Livewire;

use App\Models\Invitation;
use App\Models\User;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            /** @var User|null $user */
            $user = Auth::user();
            $acceptedInvitation = $user ? Invitation::acceptForUser($user) : null;

            if ($user) {
                Auth::setUser($user->fresh());
            }

            if ($acceptedInvitation) {
                session()->flash('success', 'Undangan Anda berhasil diaktifkan. Akses akun telah diperbarui.');
            }

            return redirect()->intended(route('dashboard'));
        }

        $this->addError('email', 'Email atau password salah.');
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.login', [
            'googleLoginEnabled' => $this->hasGoogleOauthConfig(),
        ]);
    }

    private function hasGoogleOauthConfig(): bool
    {
        $clientId = (string) config('services.google.client_id');
        $clientSecret = (string) config('services.google.client_secret');
        $redirect = (string) config('services.google.redirect');

        if (!filled($clientId) || !filled($clientSecret) || !filled($redirect)) {
            return false;
        }

        if ((string) config('app.env') === 'production' && str_contains($redirect, 'localhost')) {
            return false;
        }

        return true;
    }
}
