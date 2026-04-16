<?php

namespace Tests\Feature;

use App\Mail\FollowupDataRequestMail;
use App\Models\Client;
use App\Models\DataRequest;
use App\Models\Invitation;
use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FollowupReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_followup_command_sends_milestone_reminders_at_7_and_15_days_only_once_per_level(): void
    {
        Mail::fake();

        $ownerAuditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'owner-followup@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $ownerAuditor->id,
            'nama_kap' => 'KAP Followup',
            'nama_pic' => 'PIC Followup',
            'alamat' => 'Jl. Followup 1',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Followup Client',
            'nama_pic' => 'PIC Followup Client',
            'no_contact' => '081234560010',
            'alamat' => 'Jl. Followup Client',
            'tahun_audit' => now()->toDateString(),
        ]);

        $invitedAuditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'invited-followup@example.com',
            'kap_id' => $kap->id,
        ]);
        $invitedAuditor->clients()->syncWithoutDetaching([$client->id]);

        $auditi = User::factory()->create([
            'role' => 'auditi',
            'email' => 'auditi-followup@example.com',
        ]);

        Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => $client->id,
            'email' => $auditi->email,
            'role' => 'auditi',
            'token' => Invitation::generateToken(),
            'accepted_at' => now()->subDay(),
            'expires_at' => now()->addDays(7),
        ]);

        $request = DataRequest::create([
            'client_id' => $client->id,
            'kap_id' => $kap->id,
            'no' => 1,
            'section_code' => 'A',
            'section_no' => '1',
            'account_process' => 'Kas',
            'status' => DataRequest::STATUS_PENDING,
            'expected_received' => now()->subDays(6)->toDateString(),
            'input_file' => null,
            'followup_sent_at' => null,
        ]);

        // Not yet 7 days overdue -> no email
        $this->assertSame(0, Artisan::call('audit:send-followup'));
        Mail::assertNothingSent();

        // Reach 7 days overdue -> first follow-up (once)
        $request->update(['expected_received' => now()->subDays(7)->toDateString()]);

        $this->assertSame(0, Artisan::call('audit:send-followup'));

        Mail::assertSent(FollowupDataRequestMail::class, 3);
        Mail::assertSent(FollowupDataRequestMail::class, fn(FollowupDataRequestMail $mail) => $mail->followupLevel === 1);
        Mail::assertSent(FollowupDataRequestMail::class, fn(FollowupDataRequestMail $mail) => $mail->hasTo($auditi->email));
        Mail::assertSent(FollowupDataRequestMail::class, fn(FollowupDataRequestMail $mail) => $mail->hasTo($ownerAuditor->email));
        Mail::assertSent(FollowupDataRequestMail::class, fn(FollowupDataRequestMail $mail) => $mail->hasTo($invitedAuditor->email));

        $request->refresh();
        $this->assertNotNull($request->followup_sent_at);
        $this->assertNotNull($request->followup_7day_sent_at);
        $this->assertNull($request->followup_15day_sent_at);

        // Same level should not resend.
        $this->assertSame(0, Artisan::call('audit:send-followup'));
        Mail::assertSent(FollowupDataRequestMail::class, 3);

        // Reach 15 days overdue -> second follow-up (once)
        $request->update(['expected_received' => now()->subDays(15)->toDateString()]);

        $this->assertSame(0, Artisan::call('audit:send-followup'));
        Mail::assertSent(FollowupDataRequestMail::class, 6);
        Mail::assertSent(FollowupDataRequestMail::class, fn(FollowupDataRequestMail $mail) => $mail->followupLevel === 2);

        $request->refresh();
        $this->assertNotNull($request->followup_15day_sent_at);

        // Second level should not resend again.
        $this->assertSame(0, Artisan::call('audit:send-followup'));
        Mail::assertSent(FollowupDataRequestMail::class, 6);

        // Once file is uploaded, command should skip this request.
        $request->update(['input_file' => ['uploads/demo/proof.jpg']]);
        $this->assertSame(0, Artisan::call('audit:send-followup'));
        Mail::assertSent(FollowupDataRequestMail::class, 6);
    }

    public function test_followup_command_sends_only_second_level_when_schedule_recovers_after_day_15(): void
    {
        Mail::fake();

        $ownerAuditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'owner-after15@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $ownerAuditor->id,
            'nama_kap' => 'KAP After15',
            'nama_pic' => 'PIC After15',
            'alamat' => 'Jl. After15 1',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT After15 Client',
            'nama_pic' => 'PIC After15 Client',
            'no_contact' => '081234560099',
            'alamat' => 'Jl. After15 Client',
            'tahun_audit' => now()->toDateString(),
        ]);

        $auditi = User::factory()->create([
            'role' => 'auditi',
            'email' => 'auditi-after15@example.com',
        ]);

        Invitation::create([
            'kap_id' => $kap->id,
            'client_id' => $client->id,
            'email' => $auditi->email,
            'role' => 'auditi',
            'token' => Invitation::generateToken(),
            'accepted_at' => now()->subDay(),
            'expires_at' => now()->addDays(7),
        ]);

        $request = DataRequest::create([
            'client_id' => $client->id,
            'kap_id' => $kap->id,
            'no' => 7,
            'section_code' => 'B',
            'section_no' => '2',
            'account_process' => 'Piutang',
            'status' => DataRequest::STATUS_PENDING,
            'expected_received' => now()->subDays(16)->toDateString(),
            'input_file' => null,
            'followup_7day_sent_at' => null,
            'followup_15day_sent_at' => null,
        ]);

        $this->assertSame(0, Artisan::call('audit:send-followup'));

        Mail::assertSent(FollowupDataRequestMail::class, 2);
        Mail::assertSent(FollowupDataRequestMail::class, fn(FollowupDataRequestMail $mail) => $mail->followupLevel === 2);

        $request->refresh();
        $this->assertNull($request->followup_7day_sent_at);
        $this->assertNotNull($request->followup_15day_sent_at);
    }
}
