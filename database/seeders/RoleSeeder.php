<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * The roles available in the application.
     * Run with: php artisan db:seed --class=RoleSeeder
     *
     * @var list<string>
     */
    protected array $roles = [
        'admin',
        'manager',
        'user',
    ];

    /**
     * Create the base roles (idempotent — safe to run multiple times).
     */
    public function run(): void
    {
        foreach ($this->roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->command->info('Roles created: '.implode(', ', $this->roles));
    }
}
