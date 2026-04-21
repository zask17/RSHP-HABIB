<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Role;
use App\Models\TemuDokter;
use App\Models\RekamMedis;
use App\Models\KodeTindakanTerapi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DuplikasiRekamMedisTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $dokter;
    private $perawat;
    private $pet;
    private $appointment;
    private $dokterRoleUser;

    /**
     * SETUP: Inisialisasi Role, User, dan Data Pasien
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role
        $adminRole = Role::create(['nama_role' => 'Administrator']);
        $dokterRole = Role::create(['nama_role' => 'Dokter']);
        $perawatRole = Role::create(['nama_role' => 'Perawat']);

        // 2. Setup Pengguna & Mapping Role
        $this->admin = User::factory()->create(['nama' => 'Admin RSHP']);
        $this->admin->roles()->attach($adminRole->idrole, ['status' => 1]);

        $this->dokter = User::factory()->create(['nama' => 'Dokter Pemeriksa']);
        $this->dokter->roles()->attach($dokterRole->idrole, ['status' => 1]);
        $this->dokterRoleUser = DB::table('role_user')
            ->where('iduser', $this->dokter->iduser)
            ->where('idrole', $dokterRole->idrole)
            ->first();

        $this->perawat = User::factory()->create(['nama' => 'Perawat Pendamping']);
        $this->perawat->roles()->attach($perawatRole->idrole, ['status' => 1]);

        // 3. Setup Pemilik dan Pet
        DB::table('pemilik')->insert([
            'idpemilik' => 1,
            'iduser' => $this->admin->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Surabaya'
        ]);

        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Anjing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Beagle', 'idjenis_hewan' => 1]);

        $this->pet = Pet::create([
            'nama' => 'Snoopy',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'L'
        ]);

        // 4. Buat Janji Temu (Temu Dokter)
        $this->appointment = TemuDokter::create([
            'idpet' => $this->pet->idpet,
            'idrole_user' => $this->dokterRoleUser->idrole_user,
            'waktu_daftar' => now()->toDateTimeString(),
            'no_urut' => 1,
            'status' => '0' // Menunggu
        ]);
    }

    /**
     * TEST : Skenario Negatif - Otorisasi Multi-Role (Cross-Role Violation)
     * Memastikan bahwa Perawat tidak dapat menyimpan "Detail Tindakan Terapi". 
     * Sesuai aturan bisnis, Detail Tindakan hanya boleh diakses & disimpan oleh Role Dokter.
     */
    public function test_proses_sistem_harus_menolak_perawat_menyimpan_detail_tindakan_terapi_dokter()
    {
        $this->withoutExceptionHandling(); // Supaya error authorization terlihat jelas saat testing
        
        // Langkah 1: Buat Rekam Medis (Data Utama) sebagai prasyarat
        $rekamMedis = RekamMedis::create([
            'idreservasi_dokter' => $this->appointment->idreservasi_dokter,
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUser->idrole_user,
            'anamnesa' => 'Anjing batuk',
            'temuan_klinis' => 'Tenggorokan merah',
            'diagnosa' => 'Radang tenggorokan'
        ]);

        // Setup Master Data Tindakan
        $idKat = DB::table('kategori')->insertGetId(['nama_kategori' => 'Umum']);
        $idKatKlinis = DB::table('kategori_klinis')->insertGetId(['nama_kategori_klinis' => 'Terapi']);
        DB::table('kode_tindakan_terapi')->insert([
            'idkode_tindakan_terapi' => 1, 'kode' => 'TRP-01', 'idkategori' => $idKat, 'idkategori_klinis' => $idKatKlinis
        ]);

        $payloadTindakan = [
            'detail_tindakan' => [
                ['idkode_tindakan_terapi' => 1, 'detail' => 'Pemberian obat batuk cair']
            ]
        ];

        // Langkah 2: Simulasi Perawat mencoba akses Update Detail Tindakan Dokter (Harusnya Forbidden/Ditolak)
        // Mengharapkan error AccessDeniedHttpException (403) dari Middleware CheckRole atau Policy
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->actingAs($this->perawat)
            ->put(route('data.rekam-medis.update-detail', $rekamMedis->idrekam_medis), $payloadTindakan);
    }
}
