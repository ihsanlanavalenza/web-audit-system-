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
        return view('livewire.login');
    }
}
