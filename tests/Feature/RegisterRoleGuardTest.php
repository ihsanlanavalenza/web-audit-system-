<?php

namespace Tests\Feature;

use App\Livewire\Register;
use App\Models\Invitation;
use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterRoleGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_defaults_to_auditi_role(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Public User')
            ->set('email', 'public@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'public@example.com',
            'role' => 'auditi',
        ]);
    }

    public function test_invitation_registration_applies_invited_role(): void
    {
        $inviter = User::factory()->create([
            'role' => 'auditor',
            'email' => 'inviter@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $inviter->id,
            'nama_kap' => 'KAP Test',
            'nama_pic' => 'PIC Test',
            'alamat' => 'Jl. Test 123',
        ]);

        $invitation = Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => null,
            'email' => 'invitee@example.com',
            'role' => 'auditor',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(2),
        ]);

        Livewire::test(Register::class)
            ->set('name', 'Invited User')
            ->set('email', 'invitee@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('invitation_token', $invitation->token)
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $registeredUser = User::query()->where('email', 'invitee@example.com')->first();

        $this->assertNotNull($registeredUser);
        $this->assertSame('auditor', $registeredUser->role);
        $this->assertSame($kap->id, $registeredUser->kap_id);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }
}
