<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder untuk testing ST-002 User Login
 * 
 * Membuat test users yang dapat digunakan untuk manual testing
 * atau setup test environment
 */
class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Test User 1: Dokter (Verified Email)
        User::create([
            'nama' => 'Dr. Budi Santoso',
            'email' => 'dokter@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Test User 2: Admin (Verified Email)
        User::create([
            'nama' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('securepass123'),
            'email_verified_at' => now(),
        ]);

        // Test User 3: Regular User (Not Verified)
        User::create([
            'nama' => 'John Doe',
            'email' => 'user@example.com',
            'password' => Hash::make('pass123'),
            'email_verified_at' => null, // Not verified
        ]);

        // Test User 4: Perawat (Verified Email)
        User::create([
            'nama' => 'Perawat Siti',
            'email' => 'perawat@example.com',
            'password' => Hash::make('perawatpass'),
            'email_verified_at' => now(),
        ]);

        // Test User 5: Pemilik Klinik (Verified Email)
        User::create([
            'nama' => 'Pemilik Klinik',
            'email' => 'pemilik@example.com',
            'password' => Hash::make('pemilikpass'),
            'email_verified_at' => now(),
        ]);

        // Test User 6: Soft Deleted User (for testing soft delete scenario)
        $deletedUser = User::create([
            'nama' => 'Deleted User',
            'email' => 'deleted@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        $deletedUser->delete(); // Soft delete

        $this->command->info('Test users created successfully!');
        $this->command->table(
            ['Email', 'Password', 'Status'],
            [
                ['dokter@example.com', 'password123', 'Verified'],
                ['admin@example.com', 'securepass123', 'Verified'],
                ['user@example.com', 'pass123', 'Not Verified'],
                ['perawat@example.com', 'perawatpass', 'Verified'],
                ['pemilik@example.com', 'pemilikpass', 'Verified'],
                ['deleted@example.com', 'password123', 'Soft Deleted'],
            ]
        );
    }
}
