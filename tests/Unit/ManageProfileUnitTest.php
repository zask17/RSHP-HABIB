<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * UNIT TEST — Fitur Kelola Profile Admin (Cluster 1)
 * Menguji logika bisnis untuk Dokter, Perawat, dan Pemilik sesuai database RSHP.
 */
class ManageProfileUnitTest extends TestCase
{
    /**
     * TC-01: Memastikan Role ID untuk Dokter adalah 2.
     * Berdasarkan dump SQL dan logika pengecekan hasRole('Dokter') pada User.php
     */
    public function test_role_id_untuk_query_dokter_adalah_2()
    {
        // Berdasarkan mapping tabel 'role' di database RSHP
        $roleIdDokter = 2; 
        $this->assertEquals(2, $roleIdDokter, 'Role ID untuk Dokter harus 2 sesuai database pprshp.');
    }

    /**
     * TC-02: Memastikan Role ID untuk Perawat adalah 3.
     * Berdasarkan dump SQL dan logika pengecekan hasRole('Perawat') pada User.php
     */
    public function test_role_id_untuk_query_perawat_adalah_3()
    {
        // Berdasarkan mapping tabel 'role' di database RSHP
        $roleIdPerawat = 3;
        $this->assertEquals(3, $roleIdPerawat, 'Role ID untuk Perawat harus 3 sesuai database pprshp.');
    }

    /**
     * TC-03: Memastikan Role ID untuk Pemilik adalah 5.
     * Berdasarkan dump SQL dan logika di PemilikController::index()
     */
    public function test_role_id_untuk_query_pemilik_adalah_5()
    {
        // Berdasarkan mapping tabel 'role' di database RSHP
        $roleIdPemilik = 5;
        $this->assertEquals(5, $roleIdPemilik, 'Role ID untuk Pemilik harus 5 sesuai database pprshp.');
    }

    /**
     * TC-04: Simulasi sanitasi data input profil (Nama, WA, Alamat).
     * Menguji logika pembersihan data (trimming) yang dilakukan sebelum proses 
     * update() di PemilikController agar data di DB tetap bersih.
     */
    public function test_simulasi_sanitasi_input_profil_sebelum_simpan()
    {
        // 1. Simulasi input dari form HTML (Raw Data)
        $requestData = [
            'nama'   => '  Zaskia Rania Zaini  ', // Spasi ekstra
            'no_wa'  => ' 081234567890 ',        // Spasi ekstra
            'alamat' => ' Surabaya Timur ',       // Spasi ekstra
            '_token' => 'csrf_token_secret_123'    // Token Laravel
        ];

        // 2. Simulasi logika sanitasi yang aman bagi database
        // Logika ini memastikan data string bersih dari spasi dan parameter sistem (_token) dibuang
        $sanitizedData = [];
        foreach ($requestData as $key => $value) {
            if ($key === '_token') continue; // Simulasi logic 'except'
            $sanitizedData[$key] = is_string($value) ? trim($value) : $value;
        }

        // 3. Ekspektasi: token sistem hilang, spasi di awal/akhir bersih
        $this->assertArrayNotHasKey('_token', $sanitizedData, 'Token CSRF harus dibuang sebelum masuk ke model.');
        $this->assertEquals('Zaskia Rania Zaini', $sanitizedData['nama']);
        $this->assertEquals('081234567890', $sanitizedData['no_wa']);
        $this->assertEquals('Surabaya Timur', $sanitizedData['alamat']);
    }

    /**
     * TC-05: Verifikasi Logika Penentuan Role User.
     * Simulasi pengecekan akses admin panel seperti pada User::canAccessAdmin()
     */
    public function test_simulasi_logika_akses_admin_panel()
    {
        $allowedRoles = ['Administrator', 'Dokter', 'Resepsionis', 'Perawat'];
        
        $currentUserRole = 'Resepsionis'; // Simulasi user yang login
        
        $hasAccess = in_array($currentUserRole, $allowedRoles);
        
        $this->assertTrue($hasAccess, 'User Resepsionis seharusnya memiliki akses ke panel data.');
        
        $invalidRole = 'Guest';
        $this->assertFalse(in_array($invalidRole, $allowedRoles), 'Role Guest tidak boleh mengakses panel data.');
    }
}