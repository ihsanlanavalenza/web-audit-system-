<?php

namespace Tests\Feature;

use App\Livewire\InviteManager;
use App\Models\Client;
use App\Models\Invitation;
use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AuditorClientScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_cannot_open_schedule_for_unassigned_client(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'auditor',
            'email' => 'owner-http-scope@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $owner->id,
            'nama_kap' => 'KAP HTTP Scope',
            'nama_pic' => 'PIC HTTP Scope',
            'alamat' => 'Jl. HTTP Scope 1',
        ]);

        $allowedClient = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Allowed',
            'nama_pic' => 'PIC Allowed',
            'no_contact' => '081234560000',
            'alamat' => 'Jl. Allowed',
            'tahun_audit' => now()->toDateString(),
        ]);

        $blockedClient = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Blocked',
            'nama_pic' => 'PIC Blocked',
            'no_contact' => '081234560099',
            'alamat' => 'Jl. Blocked',
            'tahun_audit' => now()->toDateString(),
        ]);

        /** @var User $scopedAuditor */
        $scopedAuditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'scoped-http@example.com',
            'kap_id' => $kap->id,
        ]);
        $scopedAuditor->clients()->sync([$allowedClient->id]);

        $this->actingAs($scopedAuditor)
            ->get(route('schedule.show', ['clientId' => $blockedClient->id]))
            ->assertForbidden();

        Livewire::actingAs($scopedAuditor)
            ->test(\App\Livewire\DataRequestTable::class, ['clientId' => $allowedClient->id])
            ->assertSet('clientId', $allowedClient->id);
    }

    public function test_invite_manager_requires_client_scope_for_auditor_role(): void
    {
        Mail::fake();

        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'auditor',
            'email' => 'owner-scope@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $owner->id,
            'nama_kap' => 'KAP Scope',
            'nama_pic' => 'PIC Scope',
            'alamat' => 'Jl. Scope 1',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Scope Client',
            'nama_pic' => 'PIC Scope Client',
            'no_contact' => '081234560001',
            'alamat' => 'Jl. Scope Client',
            'tahun_audit' => now()->toDateString(),
        ]);

        Livewire::actingAs($owner)
            ->test(InviteManager::class)
            ->set('email', 'auditor-target@example.com')
            ->set('role', 'auditor')
            ->set('client_id', null)
            ->call('sendInvite')
            ->assertHasErrors(['client_id' => 'required']);

        Livewire::actingAs($owner)
            ->test(InviteManager::class)
            ->set('email', 'auditor-target@example.com')
            ->set('role', 'auditor')
            ->set('client_id', $client->id)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invitations', [
            'kap_id' => $kap->id,
            'client_id' => $client->id,
            'role' => 'auditor',
            'email' => 'auditor-target@example.com',
        ]);
    }

    public function test_accepting_auditor_invitation_with_client_adds_client_access(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'auditor',
            'email' => 'owner-access@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $owner->id,
            'nama_kap' => 'KAP Access',
            'nama_pic' => 'PIC Access',
            'alamat' => 'Jl. Access 1',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Access Client',
            'nama_pic' => 'PIC Access Client',
            'no_contact' => '081234560002',
            'alamat' => 'Jl. Access Client',
            'tahun_audit' => now()->toDateString(),
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'auditi',
            'email' => 'new-auditor@example.com',
        ]);

        $invitation = Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => $client->id,
            'email' => strtoupper($user->email),
            'role' => 'auditor',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(3),
        ]);

        Invitation::acceptForUser($user, $invitation);

        $user->refresh();

        $this->assertSame('auditor', $user->role);
        $this->assertDatabaseHas('client_user_access', [
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_accepting_legacy_auditor_invitation_without_client_grants_all_kap_clients(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'auditor',
            'email' => 'owner-legacy@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $owner->id,
            'nama_kap' => 'KAP Legacy',
            'nama_pic' => 'PIC Legacy',
            'alamat' => 'Jl. Legacy 1',
        ]);

        $clientA = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Legacy A',
            'nama_pic' => 'PIC Legacy A',
            'no_contact' => '081234560003',
            'alamat' => 'Jl. Legacy A',
            'tahun_audit' => now()->toDateString(),
        ]);

        $clientB = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Legacy B',
            'nama_pic' => 'PIC Legacy B',
            'no_contact' => '081234560004',
            'alamat' => 'Jl. Legacy B',
            'tahun_audit' => now()->toDateString(),
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'auditi',
            'email' => 'legacy-auditor@example.com',
        ]);

        $invitation = Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => null,
            'email' => $user->email,
            'role' => 'auditor',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(3),
        ]);

        Invitation::acceptForUser($user, $invitation);

        $this->assertDatabaseHas('client_user_access', [
            'user_id' => $user->id,
            'client_id' => $clientA->id,
        ]);

        $this->assertDatabaseHas('client_user_access', [
            'user_id' => $user->id,
            'client_id' => $clientB->id,
        ]);
    }
}
