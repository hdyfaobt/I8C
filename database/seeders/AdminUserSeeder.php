<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the default admin user.
     * Run with: php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        // Make sure the base roles exist — this seeder can be run standalone
        // (php artisan db:seed --class=AdminUserSeeder), so we can't assume
        // RoleSeeder already ran.
        $this->call(RoleSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@be'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Give the default account full admin rights (requires RoleSeeder to have run first)
        $admin->syncRoles(['admin']);

        $this->command->info('Admin user created: admin@be / password (role: admin)');
    }
}
