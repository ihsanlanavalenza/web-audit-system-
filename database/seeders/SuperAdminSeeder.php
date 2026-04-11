<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SUPER_ADMIN_EMAIL', 'admin@webaudit.com');
        $password = (string) env('SUPER_ADMIN_PASSWORD', 'admin123');
        $name = (string) env('SUPER_ADMIN_NAME', 'Super Admin');

        if (!filled($email) || !filled($password)) {
            $this->command?->warn('Super admin seeding skipped: email/password not configured.');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => filled($name) ? $name : 'Super Admin',
                'password' => Hash::make($password),
                'role' => 'super_admin',
            ]
        );

        $this->command?->info("Super admin synchronized for {$email}.");
    }
}
