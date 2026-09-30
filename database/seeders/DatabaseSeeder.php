<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rental.com'],
            [
                'name' => 'Admin Rental',
                'no_tlpn' => '0899999999',
                'role' => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'budi@example.com'],
            [
                'name' => 'Budi Pemilik',
                'no_tlpn' => '08123456781',
                'role' => 'pemilik',
                'password' => Hash::make('password123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'siti@example.com'],
            [
                'name' => 'Siti Penyewa',
                'no_tlpn' => '08123456782',
                'role' => 'penyewa',
                'password' => Hash::make('password123'),
            ]
        );
    }
}
