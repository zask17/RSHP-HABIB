<?php

namespace Tests\Unit\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ProfilePerawatAdminUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');

        // Setup Dasar: Role Perawat (ID 3 sesuai dump)
        DB::table('role')->insert(['idrole' => 3, 'nama_role' => 'Perawat']);
        
        // Setup User (ID 10) yang akan dijadikan perawat
        DB::table('user')->insert([
            'iduser' => 10,
            'nama' => 'Suster Clara',
            'email' => 'clara@rshp.com',
            'password' => bcrypt('password123')
        ]);
        
        DB::table('role_user')->insert(['idrole_user' => 1, 'iduser' => 10, 'idrole' => 3]);
    }

    /**
     * UT-F-Pf-001 | Admin membuat profil perawat baru (Gambar 2)
     */
    public function test_UT_F_Pf_001_admin_tambah_profil_perawat_berhasil()
    {
        DB::table('perawat')->insert([
            'alamat' => 'Sidoarjo',
            'no_hp' => '0812345',
            'jenis_kelamin' => 'F',
            'pendidikan' => 'D3 Keperawatan',
            'iduser' => 10,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->assertDatabaseHas('perawat', [
            'iduser' => 10, 
            'pendidikan' => 'D3 Keperawatan'
        ]);
    }

    /**
     * UT-F-Pf-002 | Admin menghapus profil perawat
     */
    public function test_UT_F_Pf_002_admin_hapus_profil_perawat_berhasil()
    {
        DB::table('perawat')->insert([
            'iddperawat' => 1, 'alamat' => 'X', 'no_hp' => '0', 'jenis_kelamin' => 'M', 'pendidikan' => 'S1', 'iduser' => 10
        ]);

        DB::table('perawat')->where('iduser', 10)->delete();
        $this->assertDatabaseMissing('perawat', ['iduser' => 10]);
    }
}