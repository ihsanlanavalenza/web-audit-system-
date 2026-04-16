<?php

namespace Tests\Feature;

use App\Livewire\DataRequestTable;
use App\Models\Client;
use App\Models\DataRequest;
use App\Models\KapProfile;
use App\Models\User;
use App\Notifications\DataRequestFileUploadedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DataRequestUploadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_can_upload_multiple_files_in_one_action(): void
    {
        Storage::fake('public');
        Notification::fake();

        $auditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'auditor@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $auditor->id,
            'nama_kap' => 'KAP Integritas',
            'nama_pic' => 'Auditor PIC',
            'alamat' => 'Jl. Integritas 1',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Klien A',
            'nama_pic' => 'PIC Klien A',
            'no_contact' => '081234567890',
            'alamat' => 'Jl. Klien A',
            'tahun_audit' => now()->toDateString(),
        ]);

        $request = DataRequest::create([
            'client_id' => $client->id,
            'kap_id' => $kap->id,
            'no' => 1,
            'section_code' => 'A',
            'section_no' => '1',
            'account_process' => 'Kas',
            'status' => DataRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($auditor)
            ->test(DataRequestTable::class, ['clientId' => $client->id])
            ->set('uploadFiles', [
                UploadedFile::fake()->image('img-1.jpg')->size(200),
                UploadedFile::fake()->image('img-2.png')->size(150),
            ])
            ->call('uploadFilesForRow', $request->id)
            ->assertHasNoErrors();

        $request->refresh();

        $this->assertSame(DataRequest::STATUS_ON_REVIEW, $request->status);
        $this->assertNotNull($request->date_input);
        $this->assertIsArray($request->input_file);
        $this->assertCount(1, $request->input_file);

        $version = $request->input_file[0];
        $this->assertSame(1, $version['version']);
        $this->assertCount(2, $version['files']);

        foreach ($version['files'] as $path) {
            $this->assertTrue(Storage::disk('public')->exists($path));
        }

        Notification::assertSentTo($auditor, DataRequestFileUploadedNotification::class);
    }

    public function test_upload_adds_new_version_when_previous_version_exists(): void
    {
        Storage::fake('public');
        Notification::fake();

        $auditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'auditor2@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $auditor->id,
            'nama_kap' => 'KAP Akuntabel',
            'nama_pic' => 'Auditor PIC 2',
            'alamat' => 'Jl. Akuntabel 2',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Klien B',
            'nama_pic' => 'PIC Klien B',
            'no_contact' => '081298765432',
            'alamat' => 'Jl. Klien B',
            'tahun_audit' => now()->toDateString(),
        ]);

        $request = DataRequest::create([
            'client_id' => $client->id,
            'kap_id' => $kap->id,
            'no' => 2,
            'section_code' => 'B',
            'section_no' => '2',
            'account_process' => 'Piutang',
            'status' => DataRequest::STATUS_PENDING,
            'input_file' => [
                [
                    'version' => 1,
                    'files' => ['uploads/dummy/existing.jpg'],
                    'uploaded_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'uploaded_by' => 'Seeder',
                ],
            ],
        ]);

        Livewire::actingAs($auditor)
            ->test(DataRequestTable::class, ['clientId' => $client->id])
            ->set('uploadFiles', [
                UploadedFile::fake()->image('img-next.webp')->size(120),
            ])
            ->call('uploadFilesForRow', $request->id)
            ->assertHasNoErrors();

        $request->refresh();

        $this->assertCount(2, $request->input_file);
        $newVersion = $request->input_file[1];

        $this->assertSame(2, $newVersion['version']);
        $this->assertCount(1, $newVersion['files']);
        $this->assertTrue(Storage::disk('public')->exists($newVersion['files'][0]));

        Notification::assertSentTo($auditor, DataRequestFileUploadedNotification::class);
    }
}
