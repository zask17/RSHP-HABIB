<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\TemuDokter;
use App\Models\RekamMedis;
use App\Models\Role;
use App\Http\Controllers\Admin\TemuDokterController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class JanjiTemuRekamMedisTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $dokter;
    private $pet;
    private $dokterRoleUser;

    /**
     * SETUP: Mengisi data awal sesuai alur RSHP
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Buat Role (Sesuai web.php middleware role)
        $adminRole = Role::create(['nama_role' => 'Administrator']);
        $dokterRole = Role::create(['nama_role' => 'Dokter']);

        // 2. Buat User Admin & Dokter
        $this->admin = User::factory()->create(['nama' => 'Admin Test']);
        $this->admin->roles()->attach($adminRole->idrole, ['status' => 1]);

        $this->dokter = User::factory()->create(['nama' => 'Dokter Test']);
        $this->dokter->roles()->attach($dokterRole->idrole, ['status' => 1]);
        
        // Ambil idrole_user untuk keperluan foreign key di temu_dokter
        $this->dokterRoleUser = DB::table('role_user')
            ->where('iduser', $this->dokter->iduser)
            ->where('idrole', $dokterRole->idrole)
            ->first();

        // 3. Buat Pemilik (ID Manual sesuai logic controller)
        $lastPemilik = DB::table('pemilik')->orderBy('idpemilik', 'desc')->first();
        $nextIdPemilik = $lastPemilik ? $lastPemilik->idpemilik + 1 : 1;

        DB::table('pemilik')->insert([
            'idpemilik' => $nextIdPemilik,
            'iduser' => $this->admin->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Surabaya'
        ]);

        $pemilik = Pemilik::where('idpemilik', $nextIdPemilik)->first();

        // 4. Mock Master Data Hewan (Foreign Key Constraints)
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Persia', 'idjenis_hewan' => 1]);

        $this->pet = Pet::create([
            'nama' => 'Buddy',
            'idpemilik' => $pemilik->idpemilik,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'L'
        ]);

        // 5. Mock Master Data Tindakan
        DB::table('kategori')->insert(['idkategori' => 1, 'nama_kategori' => 'Umum']);
        DB::table('kategori_klinis')->insert(['idkategori_klinis' => 1, 'nama_kategori_klinis' => 'Ringan']);
        DB::table('kode_tindakan_terapi')->insert([
            'idkode_tindakan_terapi' => 1,
            'kode' => 'T001',
            'deskripsi_tindakan_terapi' => 'Pemeriksaan Fisik',
            'idkategori' => 1,
            'idkategori_klinis' => 1
        ]);
    }

    /**
     * TEST: Alur Create Temu Dokter sampai Rekam Medis
     */
    public function test_alur_top_down_janji_temu_sampai_rekam_medis()
    {
        // --- STEP 1: Create Temu Dokter ---
        $appointmentData = [
            'idrole_user' => $this->dokterRoleUser->idrole_user,
            'waktu_daftar' => now()->format('Y-m-d H:i:s'),
            'no_urut' => 1
        ];

        // Menggunakan nama route dari web.php
        $response1 = $this->actingAs($this->admin)
            ->post(route('data.temu-dokter.store'), $appointmentData);

        $response1->assertStatus(302); 
        $this->assertDatabaseHas('temu_dokter', ['no_urut' => 1]);

        $appointment = TemuDokter::latest('idreservasi_dokter')->first();

        // --- STEP 2: Create Rekam Medis (Integrasi) --- 
        $rekamMedisData = [
            'idpet' => $this->pet->idpet,
            'anamnesa' => 'Kucing lemas tidak mau makan',
            'temuan_klinis' => 'Suhu 39C',
            'diagnosa' => 'Flu Ringan',
            'detail_tindakan' => [
                [
                    'idkode_tindakan_terapi' => 1,
                    'detail' => 'Pemberian vitamin'
                ]
            ]
        ];

        // MENGGUNAKAN NAMA ROUTE YANG TEPAT: data.temu-dokter.store-rekam-medis
        // Menggunakan postJson karena controller mengembalikan response()->json()
        $response2 = $this->actingAs($this->admin)
            ->postJson(route('data.temu-dokter.store-rekam-medis', ['id' => $appointment->idreservasi_dokter]), $rekamMedisData);

        // --- STEP 3: Verifikasi Hasil ---
        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);

        // Cek apakah data tersimpan di tabel rekam_medis
        $this->assertDatabaseHas('rekam_medis', [
            'idreservasi_dokter' => $appointment->idreservasi_dokter,
            'idpet' => $this->pet->idpet,
            'diagnosa' => 'Flu Ringan'
        ]);

        // Cek Detail Tindakan
        $this->assertDatabaseHas('detail_rekam_medis', [
            'detail' => 'Pemberian vitamin'
        ]);
    }
}