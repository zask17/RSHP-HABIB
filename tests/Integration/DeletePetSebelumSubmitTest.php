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

class DeletePetSebelumSubmitTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $pet;
    private $appointment;
    private $dokterRoleUser;

    /**
     * SETUP: Inisialisasi data awal (Level 3: Detail Fungsi Spesifik)
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

        // 2. Setup Pemilik (ID Manual sesuai prosedur RSHP) 
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
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Golden', 'idjenis_hewan' => 1]);

        $this->pet = Pet::create([
            'nama' => 'Buddy',
            'idpemilik' => $nextIdPemilik,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'L'
        ]);

        // 4. Buat Janji Temu Awal
        $this->appointment = TemuDokter::create([
            'idrole_user' => $this->dokterRoleUser->idrole_user,
            'waktu_daftar' => now()->toDateTimeString(),
            'no_urut' => 1,
            'status' => '0'
        ]);
    }

    /**
     * TEST: Skenario Negatif IT-RM-DRM-005 
     * Verifikasi sistem menolak rekam medis jika hewan dihapus sebelum submit
     */
    public function test_proses_input_rekam_medis_saat_pet_dihapus_sebelum_submit()
    {
        // --- LANGKAH 1: Simulasi Hapus Hewan (Soft Delete) ---
        $this->pet->update([
            'deleted_at' => now(),
            'deleted_by' => $this->admin->iduser
        ]);

        $jumlahRMAwal = DB::table('rekam_medis')->count();

        // --- LANGKAH 2: Mencoba Simpan Rekam Medis (Level 1) ---
        $rekamMedisData = [
            'idpet' => $this->pet->idpet,
            'anamnesa' => 'Hewan terlihat sangat lemas',
            'temuan_klinis' => 'Suhu tubuh 40C',
            'diagnosa' => 'Infeksi Virus',
            'detail_tindakan' => []
        ];

        // Kirim request ke storeRekamMedis
        $response = $this->actingAs($this->admin)
            ->postJson(route('data.temu-dokter.store-rekam-medis', ['id' => $this->appointment->idreservasi_dokter]), $rekamMedisData);

        // --- LANGKAH 3: Verifikasi Penolakan (Harapan hasil: 422)  ---
        $response->assertStatus(422);

        // Pastikan database tidak bertambah 
        $this->assertEquals($jumlahRMAwal, DB::table('rekam_medis')->count(), 'Data rekam medis seharusnya TIDAK tersimpan jika hewan sudah dihapus!');
        
        // Memastikan tidak ada detail tindakan yang masuk
        $this->assertDatabaseMissing('detail_rekam_medis', [
            'idrekam_medis' => ($jumlahRMAwal + 1)
        ]);
    }
}