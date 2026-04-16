<?php

namespace Tests\Feature;

use App\Livewire\DataRequestTable;
use App\Models\Client;
use App\Models\DataRequest;
use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DataRequestUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_rejects_unsupported_file_type(): void
    {
        Storage::fake('public');
        Notification::fake();

        ['auditor' => $auditor, 'client' => $client, 'request' => $request] = $this->makeUploadContext();

        Livewire::actingAs($auditor)
            ->test(DataRequestTable::class, ['clientId' => $client->id])
            ->set('uploadFiles', [
                UploadedFile::fake()->create('evidence.pdf', 500, 'application/pdf'),
            ])
            ->call('uploadFilesForRow', $request->id)
            ->assertHasErrors(['uploadFiles.0' => 'mimes']);
    }

    public function test_upload_rejects_oversized_image_file(): void
    {
        Storage::fake('public');
        Notification::fake();

        ['auditor' => $auditor, 'client' => $client, 'request' => $request] = $this->makeUploadContext();

        Livewire::actingAs($auditor)
            ->test(DataRequestTable::class, ['clientId' => $client->id])
            ->set('uploadFiles', [
                UploadedFile::fake()->image('huge-photo.jpg')->size(12000),
            ])
            ->call('uploadFilesForRow', $request->id)
            ->assertHasErrors(['uploadFiles.0' => 'max']);
    }

    public function test_upload_accepts_multiple_valid_images(): void
    {
        Storage::fake('public');
        Notification::fake();

        ['auditor' => $auditor, 'client' => $client, 'request' => $request] = $this->makeUploadContext();

        Livewire::actingAs($auditor)
            ->test(DataRequestTable::class, ['clientId' => $client->id])
            ->set('uploadFiles', [
                UploadedFile::fake()->image('photo-1.jpg')->size(400),
                UploadedFile::fake()->image('photo-2.png')->size(700),
            ])
            ->call('uploadFilesForRow', $request->id)
            ->assertHasNoErrors();

        $request->refresh();

        $this->assertSame(DataRequest::STATUS_ON_REVIEW, $request->status);
        $this->assertNotNull($request->date_input);
        $this->assertIsArray($request->input_file);
        $this->assertCount(1, $request->input_file);
        $this->assertCount(2, $request->input_file[0]['files']);
    }

    /**
     * @return array{auditor: User, client: Client, request: DataRequest}
     */
    private function makeUploadContext(): array
    {
        $auditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'upload-auditor@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $auditor->id,
            'nama_kap' => 'KAP Upload Test',
            'nama_pic' => 'PIC Upload Test',
            'alamat' => 'Jl. Upload Test 1',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Upload Test',
            'nama_pic' => 'PIC Client Upload',
            'no_contact' => '081234560123',
            'alamat' => 'Jl. Client Upload',
            'tahun_audit' => now()->toDateString(),
        ]);

        $auditor->clients()->syncWithoutDetaching([$client->id]);

        $request = DataRequest::create([
            'client_id' => $client->id,
            'kap_id' => $kap->id,
            'no' => 1,
            'section_code' => 'A',
            'section_no' => '1',
            'account_process' => 'Kas',
            'status' => DataRequest::STATUS_PENDING,
            'input_file' => null,
        ]);

        return [
            'auditor' => $auditor,
            'client' => $client,
            'request' => $request,
        ];
    }
}
