<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserProfileTest extends TestCase
{


    /**
     * Skenario Positif: Membuat profil PEMILIK baru via Admin
     * Route: data.pemilik.store
     */
    public function test_create_pemilik_profile_success()
    {
        // Mencari user admin yang ada di database pplrshp (ID 6 atau 11)
        $admin = User::where('email', 'admin@mail.com')->first();
        
        $data = [
            'role' => 'PEMILIK',
            'email' => 'pemilikbaru@gmail.com', 
            'no_wa' => '0812345678',
            'alamat' => 'Alamat Pemilik Test'
        ];

        // Menggunakan route name: data.pemilik.store
        $response = $this->actingAs($admin)->post(route('data.pemilik.store'), $data);

        $response->assertStatus(302); 
        $this->assertDatabaseHas('pemilik', [
            'no_wa' => '0812345678',
            'alamat' => 'Alamat Pemilik Test'
        ]);
    }

    /**
     * Skenario Negatif: Gagal membuat profil jika input kosong (Validasi)
     */
    public function test_create_profile_fails_on_empty_input()
    {
        $admin = User::where('email', 'admin@mail.com')->first();

        $data = [
            'role' => 'PEMILIK',
            'email' => '', 
            'no_wa' => '', 
            'alamat' => '' 
        ];

        $response = $this->actingAs($admin)->post(route('data.pemilik.store'), $data);

        // Mengharapkan error validasi masuk ke session
        $response->assertSessionHasErrors(['email', 'no_wa', 'alamat']);
    }

    /**
     * Skenario Positif: Membuat profil DOKTER baru via Admin
     * Route: data.dokter.store
     */
    public function test_create_dokter_profile_success()
    {
        $admin = User::where('email', 'admin@mail.com')->first();
        
        $data = [
            'role' => 'DOKTER',
            'email' => 'dokterbaru@gmail.com',
            'jenis_kelamin' => 'P',
            'no_hp' => '081111111111',
            'bidang_dokter' => 'Dokter Umum',
            'alamat' => 'Alamat Dokter Test'
        ];

        $response = $this->actingAs($admin)->post(route('data.dokter.store'), $data);

        $this->assertDatabaseHas('dokter', [
            'no_hp' => '081111111111',
            'bidang_dokter' => 'Dokter Umum'
        ]);
    }

    /**
     * Skenario Negatif: Akses ditolak jika bukan Admin (Middleware Role)
     */
    public function test_non_admin_cannot_access_dokter_create()
    {
        // Mencari user dengan role Dokter (ID 8)
        $dokter = User::where('email', 'dokter@test.com')->first();

        // Mencoba akses route create dokter yang diproteksi middleware role:Administrator
        $response = $this->actingAs($dokter)->get(route('data.dokter.create'));

        // Ekspektasi: 403 Forbidden
        $response->assertStatus(403);
    }
}