<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * One demo account per role, same password.
     * Run with: php artisan db:seed --class=UserSeeder
     */
    protected array $accounts = [
        'admin' => ['name' => 'Admin', 'email' => 'admin@be'],
        'manager' => ['name' => 'Manager', 'email' => 'manager@be'],
        'receptionist' => ['name' => 'Receptionist', 'email' => 'rec@be'],
        'orderpicker' => ['name' => 'Orderpicker', 'email' => 'order@be'],
    ];

    public function run(): void
    {
        // Can run standalone, roles may not exist yet
        $this->call(RoleSeeder::class);

        foreach ($this->accounts as $role => $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$role]);
        }

        $this->command->info('Users created: admin@be, manager@be, rec@be, order@be (password: password)');
    }
}
