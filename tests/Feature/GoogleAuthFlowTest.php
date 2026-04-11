<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoogleAuthFlowTest extends TestCase
{
    public function test_google_callback_handles_access_denied_gracefully(): void
    {
        $response = $this->get('/auth/google/callback?error=access_denied');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Login Google dibatalkan atau akun belum diizinkan pada OAuth Consent Screen.');
    }

    public function test_google_redirect_requires_complete_oauth_config(): void
    {
        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);
        config()->set('services.google.redirect', null);

        $response = $this->get(route('google.login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Konfigurasi Google Login belum lengkap. Hubungi admin sistem.');
    }

    public function test_google_redirect_requires_non_localhost_redirect_in_production(): void
    {
        config()->set('app.env', 'production');
        config()->set('services.google.client_id', 'dummy-id');
        config()->set('services.google.client_secret', 'dummy-secret');
        config()->set('services.google.redirect', 'http://localhost:8000/auth/google/callback');

        $response = $this->get(route('google.login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Konfigurasi Google Login belum lengkap. Hubungi admin sistem.');
    }
}
