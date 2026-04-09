<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\TemuDokter;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class RekamMedisMultiplePetsTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $petPertama; // Mimi
    private $petKedua;   // Bobby
    private $dokterRoleUser;

    /**
     * SETUP: Inisialisasi data untuk pemilik dengan banyak hewan (Level 3)
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role & User Admin
        $adminRole = Role::create(['nama_role' => 'Administrator']);
        $dokterRole = Role::create(['nama_role' => 'Dokter']);

        $this->admin = User::factory()->create(['nama' => 'Admin RSHP']);
        $this->admin->roles()->attach($adminRole->idrole, ['status' => 1]);

        $dokter = User::factory()->create(['nama' => 'Dokter Pemeriksa']);
        $dokter->roles()->attach($dokterRole->idrole, ['status' => 1]);
        
        $this->dokterRoleUser = DB::table('role_user')
            ->where('iduser', $dokter->iduser)
            ->where('idrole', $dokterRole->idrole)
            ->first();

        // 2. Setup Pemilik (ID Manual)
        DB::table('pemilik')->insert([
            'idpemilik' => 1,
            'iduser' => $this->admin->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Surabaya'
        ]);

        // 3. Setup Master Data Hewan
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Persia', 'idjenis_hewan' => 1]);

        // 4. Buat 2 Pet untuk Pemilik yang sama 
        $this->petPertama = Pet::create([
            'nama' => 'Mimi',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'F'
        ]);

        $this->petKedua = Pet::create([
            'nama' => 'Bobby',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'M'
        ]);
    }

    /**
     * TEST: Skenario Positif - Proses input rekam medis menggunakan salah satu pet dari user dengan multiple pets.
     */
    public function test_proses_input_rekam_medis_menggunakan_pet_dari_user_dengan_multiple_pets()
    {
        // --- LANGKAH 1: Buat Reservasi untuk Pet Kedua (Bobby) ---
        $reservasiBobby = TemuDokter::create([
            'idpet' => $this->petKedua->idpet,
            'idrole_user' => $this->dokterRoleUser->idrole_user,
            'waktu_daftar' => now()->toDateTimeString(),
            'status' => '0', // Menunggu
            'no_urut' => 1
        ]);

        // --- LANGKAH 2: Simpan Rekam Medis untuk Reservasi Bobby ---
        $payload = [
            'idpet' => $this->petKedua->idpet,
            'anamnesa' => 'Nafsu makan menurun selama 2 hari.',
            'temuan_klinis' => 'Suhu tubuh meningkat ringan.',
            'diagnosa' => 'Infeksi saluran pencernaan ringan.',
            'detail_tindakan' => []
        ];

        // Kirim request ke storeRekamMedis
        $response = $this->actingAs($this->admin)
            ->postJson(route('data.temu-dokter.store-rekam-medis', ['id' => $reservasiBobby->idreservasi_dokter]), $payload);

        // --- LANGKAH 3: Verifikasi Hasil (Level 3) ---
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verifikasi rekam medis tersimpan untuk Bobby
        $this->assertDatabaseHas('rekam_medis', [
            'idreservasi_dokter' => $reservasiBobby->idreservasi_dokter,
            'idpet' => $this->petKedua->idpet,
            'diagnosa' => 'Infeksi saluran pencernaan ringan.'
        ]);

        // Verifikasi bahwa TIDAK ADA rekam medis yang masuk untuk Mimi (Pet Pertama) 
        $this->assertDatabaseMissing('rekam_medis', [
            'idpet' => $this->petPertama->idpet
        ]);

        // Verifikasi melalui relasi (Top-Down)
        $rekamMedis = DB::table('rekam_medis')->where('idreservasi_dokter', $reservasiBobby->idreservasi_dokter)->first();
        $this->assertEquals($this->petKedua->idpet, $rekamMedis->idpet, 'ID Pet pada rekam medis harus sesuai dengan Bobby');
    }
}