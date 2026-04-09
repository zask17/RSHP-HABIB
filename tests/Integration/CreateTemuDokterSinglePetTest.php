<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\TemuDokter;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CreateTemuDokterSinglePetTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $dokter;
    private $pet;
    private $dokterRoleUser;

    /**
     * SETUP: Inisialisasi data dasar sesuai alur fungsionalitas RSHP
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Inisialisasi Role sesuai middleware di web.php
        $adminRole = Role::create(['nama_role' => 'Administrator']);
        $dokterRole = Role::create(['nama_role' => 'Dokter']);

        // 2. Inisialisasi User
        $this->admin = User::factory()->create(['nama' => 'Admin RSHP']);
        $this->admin->roles()->attach($adminRole->idrole, ['status' => 1]);

        $this->dokter = User::factory()->create(['nama' => 'Dr. Budi Santoso']);
        $this->dokter->roles()->attach($dokterRole->idrole, ['status' => 1]);
        
        // Mengambil idrole_user untuk foreign key temu_dokter
        $this->dokterRoleUser = DB::table('role_user')
            ->where('iduser', $this->dokter->iduser)
            ->where('idrole', $dokterRole->idrole)
            ->first();

        // 3. Inisialisasi Pemilik dengan ID Manual
        $lastPemilik = DB::table('pemilik')->orderBy('idpemilik', 'desc')->first();
        $nextIdPemilik = $lastPemilik ? $lastPemilik->idpemilik + 1 : 1;

        DB::table('pemilik')->insert([
            'idpemilik' => $nextIdPemilik,
            'iduser' => $this->admin->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Jl. Merdeka No. 123'
        ]);

        $pemilik = Pemilik::where('idpemilik', $nextIdPemilik)->first();

        // 4. Inisialisasi Master Data Hewan
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Persia', 'idjenis_hewan' => 1]);

        $this->pet = Pet::create([
            'nama' => 'Whiskers',
            'idpemilik' => $pemilik->idpemilik,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'M',
            'warna_tanda' => 'Orange'
        ]);

        // 5. Inisialisasi Master Data Tindakan
        DB::table('kategori')->insert(['idkategori' => 1, 'nama_kategori' => 'Umum']);
        DB::table('kategori_klinis')->insert(['idkategori_klinis' => 1, 'nama_kategori_klinis' => 'Ringan']);
        DB::table('kode_tindakan_terapi')->insert([
            'idkode_tindakan_terapi' => 1,
            'kode' => 'T001',
            'deskripsi_tindakan_terapi' => 'Vaksinasi Rabies',
            'idkategori' => 1,
            'idkategori_klinis' => 1
        ]);
    }

    /**
     * TEST: Skenario Alur Create Temu Dokter sampai Rekam Medis (Single Pet) (Positif) 
     */
    public function test_proses_create_temu_dokter_dengan_single_pet()
    {
        // --- STEP 1: Create Temu Dokter  ---
        $appointmentData = [
            'idrole_user' => $this->dokterRoleUser->idrole_user,
            'waktu_daftar' => now()->format('Y-m-d H:i:s'),
            'no_urut' => 1
        ];

        $response1 = $this->actingAs($this->admin)
            ->post(route('data.temu-dokter.store'), $appointmentData);

        // Verifikasi Redirect sukses 
        $response1->assertStatus(302); 
        
        $appointment = TemuDokter::latest('idreservasi_dokter')->first();

        // --- STEP 2: Create Rekam Medis melalui Temu Dokter ---
        $rekamMedisData = [
            'idpet' => $this->pet->idpet,
            'anamnesa' => 'Pasien menunjukkan gejala demam tinggi.',
            'temuan_klinis' => 'Suhu tubuh 39.5C, mata berlendir',
            'diagnosa' => 'Rhinotracheitis Felina',
            'detail_tindakan' => [
                [
                    'idkode_tindakan_terapi' => 1,
                    'detail' => 'Vaksinasi rabies diberikan, hewan stabil.'
                ]
            ]
        ];

        // Memicu fungsi storeRekamMedis. Perhatikan penggunaan => untuk array
        $response2 = $this->actingAs($this->admin)
            ->postJson(route('data.temu-dokter.store-rekam-medis', ['id' => $appointment->idreservasi_dokter]), $rekamMedisData);

        // --- STEP 3: Verifikasi Hasil Akhir  ---
        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);

        // Verifikasi integrasi data 
        $this->assertDatabaseHas('rekam_medis', [
            'idreservasi_dokter' => $appointment->idreservasi_dokter,
            'idpet' => $this->pet->idpet,
            'diagnosa' => 'Rhinotracheitis Felina'
        ]);

        // Verifikasi detail rekam medis 
        $this->assertDatabaseHas('detail_rekam_medis', [
            'idkode_tindakan_terapi' => 1,
            'detail' => 'Vaksinasi rabies diberikan, hewan stabil.'
        ]);
    }
}