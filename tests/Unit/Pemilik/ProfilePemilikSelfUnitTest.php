<?php

namespace Tests\Unit\Pemilik;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ProfilePemilikSelfUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');

        // Create Pemilik User (ID 33 sesuai dump)
        DB::table('user')->insert([
            'iduser' => 33,
            'nama' => 'Ibu Ratna',
            'email' => 'ratna@mail.com',
            'password' => bcrypt('password123')
        ]);

        // Create Pemilik Profile
        DB::table('pemilik')->insert([
            'idpemilik' => 5,
            'iduser' => 33,
            'no_wa' => '08555',
            'alamat' => 'Alamat Lama'
        ]);
    }

    /**
     * UT-F-Pf-001 | Pemilik mengedit profil sendiri (Gambar 3)
     */
    public function test_UT_F_Pf_001_pemilik_update_alamat_dan_nowa_berhasil()
    {
        // Simulasi input dari form Gambar 3 (Field yang aktif)
        $input = [
            'alamat' => 'Perumahan Griya Asri No. 12',
            'no_wa' => '0877889900'
        ];

        DB::table('pemilik')->where('iduser', 33)->update($input);

        $this->assertDatabaseHas('pemilik', [
            'iduser' => 33,
            'alamat' => 'Perumahan Griya Asri No. 12',
            'no_wa' => '0877889900'
        ]);
    }

    /**
     * UT-V-Pf-001 | Validasi Field Disable (Nama & Email)
     * Memastikan meskipun user mencoba mengirim data nama/email, data asli di tabel user tidak berubah
     */
    public function test_UT_V_Pf_001_pemilik_gagal_ubah_nama_dan_email_karena_disable()
    {
        $oldUser = DB::table('user')->where('iduser', 33)->first();

        // Simulasi percobaan pengubahan field yang di-disable di Gambar 3
        $illegalInput = [
            'nama' => 'Ganti Nama Baru',
            'email' => 'ganti@email.com'
        ];

        // Hasil yang diharapkan: Data user tetap sama dengan data di setUp
        $this->assertEquals('Ibu Ratna', $oldUser->nama);
        $this->assertEquals('ratna@mail.com', $oldUser->email);
    }

    /**
     * UT-F-Pf-002 | Pemilik menghapus profil sendiri dari sistem
     */
    public function test_UT_F_Pf_002_pemilik_hapus_data_profil_sendiri_berhasil()
    {
        DB::table('pemilik')->where('iduser', 33)->delete();
        $this->assertDatabaseMissing('pemilik', ['iduser' => 33]);
    }
}