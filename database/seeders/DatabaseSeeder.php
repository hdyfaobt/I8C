<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles first, then the default admin account (see AdminUserSeeder),
        // then a starter product catalog.
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            ProductSeeder::class,
        ]);

        // User::factory(10)->create();

        // There is no generic 'user' role anymore — every account needs a
        // real role (admin, manager, receptionist or orderpicker). The test
        // account defaults to 'receptionist' since that's the closest thing
        // to a standard staff account for trying out the app.
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $testUser->assignRole('receptionist');
    }
}
