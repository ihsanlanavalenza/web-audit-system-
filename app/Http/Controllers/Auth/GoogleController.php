<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // 1. Cek apakah user sudah terdaftar
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Update google_id jika belum ada
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }

                Auth::login($user);
                session()->regenerate();
                return redirect()->route('dashboard');
            }

            // 2. Cek apakah user diundang
            $invitation = Invitation::where('email', $googleUser->getEmail())->first();

            if ($invitation) {
                $newUser = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'role' => $invitation->role ?? 'auditi',
                    'kap_id' => $invitation->kap_id,
                ]);

                $invitation->update(['accepted_at' => now()]);

                Auth::login($newUser);
                session()->regenerate();
                return redirect()->route('dashboard');
            }

            return redirect()->route('login')->with('error', 'Maaf, email Anda belum terdaftar atau diundang.');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Terjadi kesalahan saat login menggunakan Google.');
        }
    }
}
