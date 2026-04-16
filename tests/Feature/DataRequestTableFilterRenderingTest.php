<?php

namespace Tests\Feature;

use App\Livewire\DataRequestTable;
use App\Models\Client;
use App\Models\DataRequest;
use App\Models\KapProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DataRequestTableFilterRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_header_stays_visible_when_filter_returns_no_rows(): void
    {
        ['auditor' => $auditor, 'client' => $client] = $this->makeFilterContext();

        Livewire::actingAs($auditor)
            ->test(DataRequestTable::class, ['clientId' => $client->id])
            ->set('filterAccountProcess', 'tidak-ada-data-cocok')
            ->assertSee('Section / No.')
            ->assertSee('Data tidak ditemukan.');
    }

    /**
     * @return array{auditor: User, client: Client}
     */
    private function makeFilterContext(): array
    {
        $auditor = User::factory()->create([
            'role' => 'auditor',
            'email' => 'filter-auditor@example.com',
        ]);

        $kap = KapProfile::create([
            'user_id' => $auditor->id,
            'nama_kap' => 'KAP Filter Test',
            'nama_pic' => 'PIC Filter Test',
            'alamat' => 'Jl. Filter Test',
        ]);

        $client = Client::create([
            'kap_id' => $kap->id,
            'nama_client' => 'PT Filter Test',
            'nama_pic' => 'PIC Filter Client',
            'no_contact' => '081234560321',
            'alamat' => 'Jl. Filter Client',
            'tahun_audit' => now()->toDateString(),
        ]);

        $auditor->clients()->syncWithoutDetaching([$client->id]);

        DataRequest::create([
            'client_id' => $client->id,
            'kap_id' => $kap->id,
            'no' => 1,
            'section_code' => 'A',
            'section_no' => '1',
            'account_process' => 'Kas',
            'status' => DataRequest::STATUS_PENDING,
        ]);

        return [
            'auditor' => $auditor,
            'client' => $client,
        ];
    }
}
