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

class RekamMedisKosongTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $pet;
    private $appointment;
    private $dokterRoleUser;

    /**
     * SETUP: Inisialisasi data dasar (Level 3)
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role & Pengguna
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

        // 2. Setup Pemilik (ID Manual sesuai logic RSHP)
        $lastPemilik = DB::table('pemilik')->orderBy('idpemilik', 'desc')->first();
        $nextIdPemilik = $lastPemilik ? $lastPemilik->idpemilik + 1 : 1;

        DB::table('pemilik')->insert([
            'idpemilik' => $nextIdPemilik,
            'iduser' => $this->admin->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Surabaya'
        ]);

        // 3. Setup Master Data Hewan
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Anjing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Bulldog', 'idjenis_hewan' => 1]);

        $this->pet = Pet::create([
            'nama' => 'Bruno',
            'idpemilik' => $nextIdPemilik,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'L'
        ]);

        // 4. Buat Janji Temu 
        $this->appointment = TemuDokter::create([
            'idrole_user' => $this->dokterRoleUser->idrole_user,
            'waktu_daftar' => now()->toDateTimeString(),
            'no_urut' => 1,
            'status' => '0'
        ]);
    }

    /**
     * TEST: Skenario Negatif - Input rekam medis dengan semua field kosong harus ditolak
     */
    public function test_alur_input_rekam_medis_dengan_field_kosong_harus_ditolak()
    {
        // Hitung jumlah data awal di database
        $jumlahRMAwal = DB::table('rekam_medis')->count();
        $jumlahDetailAwal = DB::table('detail_rekam_medis')->count();

        // --- LANGKAH 1: Simulasi Input Form Kosong oleh Perawat/Admin ---
        $payloadKosong = [
            'idpet' => $this->pet->idpet,
            'anamnesa' => null,      // Kosong
            'temuan_klinis' => null, // Kosong
            'diagnosa' => null,      // Kosong
            'detail_tindakan' => [
                [
                    'idkode_tindakan_terapi' => null,
                    'detail' => null // Kosong
                ]
            ]
        ];

        // Hit ke storeRekamMedis 
        $response = $this->actingAs($this->admin)
            ->postJson(route('data.temu-dokter.store-rekam-medis', ['id' => $this->appointment->idreservasi_dokter]), $payloadKosong);

        // --- LANGKAH 2: Verifikasi Penolakan (Harapan QA: 422) ---
        // Sistem harus memberikan error validasi (422 Unprocessable Entity)
        $response->assertStatus(422);
        
        // Memastikan ada pesan error untuk field yang wajib diisi
        $response->assertJsonValidationErrors(['anamnesa', 'temuan_klinis', 'diagnosa']);

        // --- LANGKAH 3: Verifikasi Integritas Database ---
        // Data tidak boleh tersimpan di database jika validasi gagal
        $this->assertEquals($jumlahRMAwal, DB::table('rekam_medis')->count(), 'Data rekam medis tersimpan padahal input kosong!');
        $this->assertEquals($jumlahDetailAwal, DB::table('detail_rekam_medis')->count(), 'Detail rekam medis tersimpan padahal input kosong!');
    }
}