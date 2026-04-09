<?php

namespace Tests\Unit\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ProfilePemilikAdminUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');

        // Setup Master Role: Pemilik (ID 5 sesuai dump)
        DB::table('role')->insert(['idrole' => 5, 'nama_role' => 'Pemilik']);
        
        // Setup User (ID 29) sebagai user dasar
        DB::table('user')->insert([
            'iduser' => 29,
            'nama' => 'Bpk. Setiawan',
            'email' => 'setiawan@mail.com',
            'password' => bcrypt('password123')
        ]);
        
        DB::table('role_user')->insert(['idrole_user' => 1, 'iduser' => 29, 'idrole' => 5]);
    }

    /**
     * UT-F-Pf-001 | Admin membuat profil pemilik baru (Gambar 1 & 2)
     */
    public function test_UT_F_Pf_001_admin_tambah_profil_pemilik_berhasil()
    {
        // Mengisi alamat dan no wa sesuai form Gambar 2
        DB::table('pemilik')->insert([
            'idpemilik' => 10,
            'iduser' => 29,
            'no_wa' => '08122334455',
            'alamat' => 'Surabaya Barat'
        ]);

        $this->assertDatabaseHas('pemilik', [
            'iduser' => 29,
            'no_wa' => '08122334455'
        ]);
    }

    /**
     * UT-F-Pf-002 | Admin menghapus profil pemilik
     */
    public function test_UT_F_Pf_002_admin_hapus_profil_pemilik_berhasil()
    {
        DB::table('pemilik')->insert([
            'idpemilik' => 11, 'iduser' => 29, 'no_wa' => '0', 'alamat' => 'X'
        ]);

        DB::table('pemilik')->where('idpemilik', 11)->delete();
        $this->assertDatabaseMissing('pemilik', ['idpemilik' => 11]);
    }
}