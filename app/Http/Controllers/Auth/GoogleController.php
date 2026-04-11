<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    public function redirect()
    {
        if (!$this->hasGoogleOauthConfig()) {
            return redirect()->route('login')
                ->with('error', 'Konfigurasi Google Login belum lengkap. Hubungi admin sistem.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            $error = strtolower((string) $request->query('error'));
            $message = $error === 'access_denied'
                ? 'Login Google dibatalkan atau akun belum diizinkan pada OAuth Consent Screen.'
                : 'Login Google gagal diproses. Coba lagi.';

            return redirect()->route('login')->with('error', $message);
        }

        if (!$this->hasGoogleOauthConfig()) {
            return redirect()->route('login')
                ->with('error', 'Konfigurasi Google Login belum lengkap. Hubungi admin sistem.');
        }

        try {
            /** @var GoogleProvider $googleProvider */
            $googleProvider = Socialite::driver('google');

            try {
                $googleUser = $googleProvider->user();
            } catch (InvalidStateException $e) {
                report($e);
                // Fallback for local/dev session state mismatch between callback and browser session.
                $googleUser = $googleProvider->stateless()->user();
            }

            $email = $googleUser->getEmail();

            if (!$email) {
                return redirect()->route('login')->with('error', 'Email Google tidak ditemukan. Gunakan akun Google yang memiliki email aktif.');
            }

            // 1. Cek apakah user sudah terdaftar
            $user = User::where('email', $email)->first();

            if ($user) {
                // Update google_id jika belum ada
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }

                $acceptedInvitation = Invitation::acceptForUser($user);

                Auth::login($user->fresh());
                session()->regenerate();

                if ($acceptedInvitation) {
                    return redirect()->route('dashboard')
                        ->with('success', 'Undangan Anda berhasil diaktifkan. Akses akun telah diperbarui.');
                }

                return redirect()->route('dashboard');
            }

            // 2. Jika belum terdaftar, cek invitation terbaru
            $invitation = Invitation::latestPendingForEmail($email);

            // 3. Auto register user baru
            $newUser = User::create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'password' => null,
                'role' => $invitation
                    ? (in_array($invitation->role, ['auditor', 'auditi'], true) ? $invitation->role : 'auditi')
                    : 'auditi',
                'kap_id' => $invitation?->kap_id,
            ]);

            if ($invitation) {
                Invitation::acceptForUser($newUser, $invitation);
            }

            Auth::login($newUser);
            session()->regenerate();

            return redirect()->route('dashboard')->with(
                'success',
                $invitation
                    ? 'Akun berhasil dibuat dan undangan Anda sudah diaktifkan.'
                    : 'Akun berhasil dibuat otomatis lewat Google.'
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', $this->resolveOauthErrorMessage($e));
        }
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

    private function resolveOauthErrorMessage(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'redirect_uri_mismatch')) {
            return 'Redirect URI Google tidak sesuai. Samakan URL callback di Google Cloud dengan konfigurasi aplikasi.';
        }

        if (str_contains($message, 'invalid_client')) {
            return 'Google Client ID/Secret tidak valid. Periksa konfigurasi OAuth aplikasi.';
        }

        if (str_contains($message, 'access_denied')) {
            return 'Akses Google ditolak. Pastikan akun Anda termasuk test user pada OAuth Consent Screen.';
        }

        return 'Terjadi kesalahan saat login menggunakan Google. Silakan coba lagi.';
    }
}
