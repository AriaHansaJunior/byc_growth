<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial administrator accounts.
     */
    public function run(): void
    {
        // Admin Account 1: admin_byc@gmail.com
        User::updateOrCreate(
            ['email' => 'admin_byc@gmail.com'],
            [
                'name' => 'Admin BYC',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Admin Account 2: jojo_ganteng@gmail.com
        User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );
    }
}
