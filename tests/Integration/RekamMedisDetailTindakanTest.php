<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\TemuDokter;
use App\Models\RekamMedis;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class RekamMedisDetailTindakanTest extends TestCase
{
    use RefreshDatabase;

    private $perawat;
    private $dokter;
    private $pet;
    private $appointment;

    /**
     * SETUP: Inisialisasi data untuk simulasi Top-Down (Level 3)
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role & User (Perawat dan Dokter)
        $perawatRole = Role::create(['nama_role' => 'Perawat']);
        $dokterRole = Role::create(['nama_role' => 'Dokter']);

        $this->perawat = User::factory()->create(['nama' => 'Perawat Test']);
        $this->perawat->roles()->attach($perawatRole->idrole, ['status' => 1]);

        $this->dokter = User::factory()->create(['nama' => 'Dokter Test']);
        $this->dokter->roles()->attach($dokterRole->idrole, ['status' => 1]);
        
        $dokterRoleUser = DB::table('role_user')
            ->where('iduser', $this->dokter->iduser)
            ->first();

        // 2. Setup Pemilik & Pet (ID Manual sesuai logic RSHP) 
        DB::table('pemilik')->insert([
            'idpemilik' => 1,
            'iduser' => $this->perawat->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Surabaya'
        ]);

        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Anjing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Husky', 'idjenis_hewan' => 1]);

        $this->pet = Pet::create([
            'nama' => 'Max',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'L'
        ]);

        // 3. Setup Janji Temu & Kode Tindakan 
        $this->appointment = TemuDokter::create([
            'idrole_user' => $dokterRoleUser->idrole_user,
            'waktu_daftar' => now()->toDateTimeString(),
            'status' => '0',
            'no_urut' => 1
        ]);

        DB::table('kategori')->insert(['idkategori' => 1, 'nama_kategori' => 'Klinis']);
        DB::table('kategori_klinis')->insert(['idkategori_klinis' => 1, 'nama_kategori_klinis' => 'Internis']);
        DB::table('kode_tindakan_terapi')->insert([
            'idkode_tindakan_terapi' => 1,
            'kode' => 'K01',
            'deskripsi_tindakan_terapi' => 'Cek Darah',
            'idkategori' => 1,
            'idkategori_klinis' => 1
        ]);
    }

    /**
     * TEST: Proses input rekam medis sampai detail rekam medis (Positif)
     */
    public function test_proses_input_rekam_medis_sampai_detail_dengan_temuan_klinis()
    {
        // --- LANGKAH 1: Perawat Input Rekam Medis Utama (Level 1) ---
        $temuanKlinisStr = "Suhu tubuh 39.5°C, dehidrasi ringan, pernapasan cepat.";
        
        $payloadRM = [
            'idpet' => $this->pet->idpet,
            'anamnesa' => 'Anjing lemas tidak mau makan', 
            'temuan_klinis' => $temuanKlinisStr,
            'diagnosa' => 'Suspek Infeksi viral',
            'detail_tindakan' => []
        ];

        $response1 = $this->actingAs($this->perawat)
            ->postJson(route('data.temu-dokter.store-rekam-medis', ['id' => $this->appointment->idreservasi_dokter]), $payloadRM);

        $response1->assertStatus(200);
        
        $rekamMedis = RekamMedis::latest('idrekam_medis')->first();

        // --- LANGKAH 2: Dokter Input Detail Tindakan (Level 1 Integrasi)---
        $payloadDetail = [
            'idkode_tindakan_terapi' => 1,
            'detail' => 'Vaksinasi diberikan, kondisi stabil.'
        ];

        // Memanggil fungsi update detail pada rekam medis yang baru dibuat
        $response2 = $this->actingAs($this->dokter)
            ->postJson("/admin/rekam-medis/{$rekamMedis->idrekam_medis}/tindakan", $payloadDetail);

        // --- LANGKAH 3: Verifikasi Hasil (Level 3: Validasi & Relasi) ---
        $response2->assertStatus(200);

        // Verifikasi temuan klinis tersimpan dan panjangnya < 1000 karakter
        $this->assertDatabaseHas('rekam_medis', [
            'idrekam_medis' => $rekamMedis->idrekam_medis,
            'temuan_klinis' => $temuanKlinisStr
        ]);

        $this->assertLessThan(1000, strlen($rekamMedis->temuan_klinis));

        // Verifikasi detail tindakan terhubung dengan rekam medis yang benar
        $this->assertDatabaseHas('detail_rekam_medis', [
            'idrekam_medis' => $rekamMedis->idrekam_medis,
            'detail' => 'Vaksinasi diberikan, kondisi stabil.'
        ]);
    }
}
