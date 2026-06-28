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
        User::updateOrCreate(
            ['email' => 'admin@be'],
            [
                'name'              => 'Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created: admin@be / password');
    }
}
