<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PackageSeeder::class);

        // Admin user for local development
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@empower.test',
            'role' => UserRole::Admin,
        ]);

        // Sample client user for local development
        User::factory()->create([
            'name' => 'Test Client',
            'email' => 'client@empower.test',
            'role' => UserRole::Client,
        ]);
    }
}
