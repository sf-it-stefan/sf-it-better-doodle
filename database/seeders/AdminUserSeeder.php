<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() === 0) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@sf-it.at',
                'password' => bcrypt('admin1234'),
            ]);

            $this->command?->info('Admin user created (admin@sf-it.at / admin1234). Change the password immediately after first login.');
        }
    }
}
