<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_seeder_creates_default_account(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@webaudit.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue(Hash::check('admin123', (string) $admin->password));
    }

    public function test_super_admin_seeder_upgrades_existing_user_with_same_email(): void
    {
        User::factory()->create([
            'email' => 'admin@webaudit.com',
            'role' => 'auditi',
            'password' => Hash::make('old-password'),
        ]);

        $this->seed(SuperAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@webaudit.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue(Hash::check('admin123', (string) $admin->password));
    }
}
