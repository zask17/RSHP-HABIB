<?php

namespace Tests\Unit\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ProfileDokterAdminUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');

        // Setup Dasar: Role Dokter
        DB::table('role')->insert(['idrole' => 2, 'nama_role' => 'Dokter']);
        
        // Setup User (ID 8) yang akan dijadikan dokter
        DB::table('user')->insert([
            'iduser' => 8,
            'nama' => 'Dr. Budi Utomo',
            'email' => 'budi@rshp.com',
            'password' => bcrypt('password')
        ]);
        
        DB::table('role_user')->insert(['idrole_user' => 1, 'iduser' => 8, 'idrole' => 2]);
    }

    /**
     * UT-F-Pf-001 | Admin membuat profil dokter baru (Gambar 1)
     */
    public function test_UT_F_Pf_001_admin_tambah_profil_dokter_berhasil()
    {
        DB::table('dokter')->insert([
            'alamat' => 'Surabaya',
            'no_hp' => '08123',
            'bidang_dokter' => 'Bedah Hewan',
            'jenis_kelamin' => 'M',
            'iduser' => 8
        ]);

        $this->assertDatabaseHas('dokter', ['iduser' => 8, 'bidang_dokter' => 'Bedah Hewan']);
    }

    /**
     * UT-F-Pf-002 | Admin menghapus profil dokter
     */
    public function test_UT_F_Pf_002_admin_hapus_profil_dokter_berhasil()
    {
        DB::table('dokter')->insert([
            'iddokter' => 10, 'alamat' => 'A', 'no_hp' => '1', 'bidang_dokter' => 'B', 'jenis_kelamin' => 'M', 'iduser' => 8
        ]);

        DB::table('dokter')->where('iddokter', 10)->delete();
        $this->assertDatabaseMissing('dokter', ['iddokter' => 10]);
    }
}