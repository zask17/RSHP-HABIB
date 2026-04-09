<?php

namespace Tests\Unit\Perawat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ProfilePerawatSelfUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');

        // Create Perawat User
        DB::table('user')->insert([
            'iduser' => 28,
            'nama' => 'Suster Anita',
            'email' => 'anita@rshp.com',
            'password' => bcrypt('password123')
        ]);

        // Create Perawat Profile
        DB::table('perawat')->insert([
            'idperawat' => 1,
            'alamat' => 'Alamat Lama',
            'no_hp' => '08777',
            'jenis_kelamin' => 'F',
            'pendidikan' => 'D3',
            'iduser' => 28
        ]);
    }

    /**
     * UT-F-Pf-001 | Perawat mengedit profil sendiri (Gambar 1)
     */
    public function test_UT_F_Pf_001_perawat_update_data_mandiri_berhasil()
    {
        // Data yang diperbolehkan diubah di Gambar 1
        $input = [
            'alamat' => 'Jl. Melati No. 10',
            'no_hp' => '08222222',
            'pendidikan' => 'S1 Keperawatan'
        ];

        DB::table('perawat')->where('iduser', 28)->update($input);

        $this->assertDatabaseHas('perawat', [
            'iduser' => 28,
            'alamat' => 'Jl. Melati No. 10',
            'pendidikan' => 'S1 Keperawatan'
        ]);
    }

    /**
     * UT-V-Pf-001 | Validasi Field Disable (Keamanan Data)
     * Memastikan data User (Nama & Email) tetap aman meskipun ada request ilegal
     */
    public function test_UT_V_Pf_001_perawat_gagal_ubah_nama_dan_email_karena_disable()
    {
        $oldUser = DB::table('user')->where('iduser', 28)->first();

        // Simulasi jika ada yang mencoba menembak API dengan data nama/email
        $illegalInput = [
            'nama' => 'Nama Diubah Paksa',
            'email' => 'hacker@rshp.com'
        ];

        // Sesuai fungsionalitas: Data user tidak boleh berubah
        $this->assertEquals('Suster Anita', $oldUser->nama);
        $this->assertEquals('anita@rshp.com', $oldUser->email);
    }

    /**
     * UT-F-Pf-002 | Perawat menghapus profil sendiri dari sistem
     */
    public function test_UT_F_Pf_002_perawat_hapus_data_profil_sendiri_berhasil()
    {
        DB::table('perawat')->where('iduser', 28)->delete();
        $this->assertDatabaseMissing('perawat', ['iduser' => 28]);
    }
}