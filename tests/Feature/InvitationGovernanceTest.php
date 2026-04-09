<?php

namespace Tests\Feature;

use App\Livewire\Login;
use App\Livewire\UserManager;
use App\Models\ActivityLog;
use App\Models\Invitation;
use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_login_accepts_pending_invitation_and_logs_changes(): void
    {
        $inviter = User::factory()->create([
            'role' => 'auditor',
            'email' => 'inviter@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $inviter->id,
            'nama_kap' => 'KAP Integritas',
            'nama_pic' => 'Auditor PIC',
            'alamat' => 'Jl. Integritas 1',
        ]);

        $user = User::factory()->create([
            'role' => 'auditi',
            'kap_id' => null,
            'email' => 'target@example.com',
            'password' => Hash::make('password123'),
        ]);

        $invitation = Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => null,
            'email' => strtoupper($user->email),
            'role' => 'auditor',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(3),
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $invitation->refresh();

        $this->assertEquals('auditor', $user->role);
        $this->assertEquals($kap->id, $user->kap_id);
        $this->assertNotNull($invitation->accepted_at);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Invitation::class,
            'model_id' => $invitation->id,
            'action' => 'invitation_accepted',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'action' => 'role_changed_by_invitation',
        ]);
    }

    public function test_admin_can_change_role_and_cancel_conflicting_pending_invitations(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin@example.com',
        ]);

        $target = User::factory()->create([
            'role' => 'auditi',
            'email' => 'member@example.com',
        ]);

        $inviter = User::factory()->create([
            'role' => 'auditor',
            'email' => 'owner@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $inviter->id,
            'nama_kap' => 'KAP Owner',
            'nama_pic' => 'Owner PIC',
            'alamat' => 'Jl. Owner 2',
        ]);

        $conflictingInvitation = Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => null,
            'email' => $target->email,
            'role' => 'auditi',
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(3),
        ]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('changeRole', $target->id, 'auditor')
            ->assertSet('showRoleConflictModal', true)
            ->call('confirmRoleChange', true)
            ->assertSet('showRoleConflictModal', false);

        $target->refresh();

        $this->assertEquals('auditor', $target->role);
        $this->assertDatabaseMissing('invitations', [
            'id' => $conflictingInvitation->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => User::class,
            'model_id' => $target->id,
            'action' => 'role_changed_by_admin',
            'user_id' => $admin->id,
        ]);

        $latestLog = ActivityLog::query()
            ->where('model_type', User::class)
            ->where('model_id', $target->id)
            ->where('action', 'role_changed_by_admin')
            ->latest('id')
            ->first();

        $this->assertNotNull($latestLog);
        $this->assertContains($conflictingInvitation->id, $latestLog->new_payload['cancelled_invitation_ids'] ?? []);
    }
}
